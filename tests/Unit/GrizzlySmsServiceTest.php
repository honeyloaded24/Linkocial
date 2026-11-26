<?php

namespace Tests\Unit;

use App\Exceptions\ProviderTimeoutException;
use App\Models\Setting;
use App\Services\GrizzlySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GrizzlySmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::clearCache();
        Setting::set('grizzly_api_key', 'test-grizzly-key');
    }

    public function test_country_catalogue_parses_live_country_records(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response([
                '187' => ['id' => 187, 'eng' => 'United States', 'prefix' => '1'],
                '73' => ['id' => 73, 'eng' => 'Nigeria', 'prefix' => '234'],
            ], 200),
        ]);

        $countries = app(GrizzlySmsService::class)->getCountries();

        $this->assertSame(['187', '73'], array_column($countries, 'ID'));
        $this->assertSame('United States', $countries[0]['name']);
        $this->assertSame('234', $countries[1]['cc']);
    }

    public function test_country_catalogue_does_not_replace_a_failed_live_response_with_a_static_catalogue(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('temporary error', 502),
        ]);

        $this->expectException(\RuntimeException::class);
        app(GrizzlySmsService::class)->getCountries();
    }

    public function test_price_catalogue_excludes_zero_stock_and_zero_price_offers(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response([
                '187' => [
                    'wa' => ['cost' => 0.80, 'count' => 5],
                    'tg' => ['cost' => 0.60, 'count' => 0],
                    'ig' => ['cost' => 0, 'count' => 9],
                ],
            ], 200),
        ]);

        $offers = app(GrizzlySmsService::class)->getPricesForCountry('187');

        $this->assertCount(1, $offers);
        $this->assertSame('wa', $offers[0]['service']);
        $this->assertSame(0.80, $offers[0]['price']);
        $this->assertSame(5, $offers[0]['count']);
    }

    public function test_purchase_sends_the_safe_maximum_price_and_parses_activation(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response([
                'activationId' => 123456,
                'phoneNumber' => '66846426435',
                'activationCost' => 1.10,
                'countryCode' => '2',
            ], 200),
        ]);

        $result = app(GrizzlySmsService::class)->buyNumber('187', 'wa', 1.25);

        $this->assertSame('123456', $result['order_id']);
        $this->assertSame('66846426435', $result['phonenumber']);
        $this->assertSame(1.10, $result['cost']);

        Http::assertSent(fn ($request) =>
            $request['action'] === 'getNumberV2'
            && $request['country'] === '187'
            && $request['service'] === 'wa'
            && $request['maxPrice'] === '1.25'
            && $request['api_key'] === 'test-grizzly-key'
        );
    }

    public function test_successful_activation_without_provider_cost_is_left_for_live_quote_fallback(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response([
                'activationId' => 123457,
                'phoneNumber' => '66846426436',
            ], 200),
        ]);

        $result = app(GrizzlySmsService::class)->buyNumber('187', 'wa', 1.25);

        $this->assertNull($result['cost']);
        $this->assertSame('123457', $result['order_id']);
    }

    public function test_explicit_no_numbers_response_is_a_definite_rejection(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('NO_NUMBERS', 200),
        ]);

        try {
            app(GrizzlySmsService::class)->buyNumber('187', 'wa', 1.25);
            $this->fail('NO_NUMBERS must be rejected as a definite no-purchase response.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('No numbers currently available', $exception->getMessage());
        }
    }

    public function test_unknown_provider_response_is_ambiguous_and_not_a_refundable_rejection(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('TEMPORARY_ERROR', 200),
        ]);

        $this->expectException(ProviderTimeoutException::class);
        app(GrizzlySmsService::class)->buyNumber('187', 'wa', 1.25);
    }

    public function test_http_gateway_failure_is_ambiguous_and_not_a_refundable_rejection(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('Gateway error', 502),
        ]);

        $this->expectException(ProviderTimeoutException::class);
        app(GrizzlySmsService::class)->buyNumber('187', 'wa', 1.25);
    }

    public function test_connection_interruption_is_ambiguous(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->expectException(ProviderTimeoutException::class);
        app(GrizzlySmsService::class)->buyNumber('187', 'wa', 1.25);
    }
    public function test_cancellation_wait_message_uses_provider_supplied_seconds(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('EARLY_CANCEL_DENIED: Please wait 50 seconds', 200),
        ]);

        try {
            app(GrizzlySmsService::class)->cancelOrder('activation-50');
            $this->fail('An early cancellation must be rejected until the provider permits it.');
        } catch (\\RuntimeException $exception) {
            $this->assertSame('Please wait 50 seconds before cancelling this number.', $exception->getMessage());
        }
    }

    public function test_bare_early_cancel_denied_does_not_invent_a_fixed_two_minute_wait(): void
    {
        Http::fake([
            'api.grizzlysms.com/*' => Http::response('EARLY_CANCEL_DENIED', 200),
        ]);

        try {
            app(GrizzlySmsService::class)->cancelOrder('activation-early');
            $this->fail('An early cancellation must be rejected.');
        } catch (\\RuntimeException $exception) {
            $this->assertStringContainsString('Please wait a little longer', $exception->getMessage());
            $this->assertStringNotContainsString('2-minute', $exception->getMessage());
        }
    }

}
