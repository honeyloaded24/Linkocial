<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\DaisySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DaisySmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::clearCache();
        Setting::set('daisysms_api_key', 'test-daisy-key');
    }

    public function test_flat_usa_catalogue_exposes_only_the_four_confirmed_routed_services_and_live_offer_fields(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response([
                'whatsapp' => ['cost' => 1.60, 'count' => 279, 'multi' => 0],
                'whatsapp_canada' => ['cost' => 0.60, 'count' => 821, 'multi' => 1],
                'telegram' => ['cost' => 0.92, 'count' => 893, 'multi' => 1],
                'telegram_canada' => ['cost' => 0.42, 'count' => 471, 'multi' => 1],
                'facebook' => ['cost' => 0.90, 'count' => 320, 'multi' => 0],
            ], 200),
        ]);

        $rows = collect(app(DaisySmsService::class)->getPricesForCountry('187'))->keyBy('service');

        $this->assertCount(4, $rows);
        $this->assertSame(1.60, $rows['whatsapp']['price']);
        $this->assertSame(279, $rows['whatsapp']['stock']);
        $this->assertSame(0.60, $rows['whatsapp_canada']['price']);
        $this->assertSame(821, $rows['whatsapp_canada']['stock']);
        $this->assertSame(0.92, $rows['telegram']['price']);
        $this->assertSame(893, $rows['telegram']['stock']);
        $this->assertSame(0.42, $rows['telegram_canada']['price']);
        $this->assertSame(471, $rows['telegram_canada']['stock']);
    }

    public function test_non_usa_catalogue_is_not_requested_from_daisy_sms(): void
    {
        Http::fake();

        $this->assertSame([], app(DaisySmsService::class)->getPricesForCountry('1'));
        Http::assertNothingSent();
    }

    public function test_purchase_omits_optional_maximum_price_and_reads_actual_price(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_NUMBER:act-123:+12025550123', 200, ['X-Price' => '1.60']),
        ]);

        $result = app(DaisySmsService::class)->buyNumber('187', 'whatsapp');

        $this->assertSame('act-123', $result['order_id']);
        $this->assertSame('+12025550123', $result['phonenumber']);
        $this->assertSame(1.60, $result['cost']);

        Http::assertSent(function ($request) {
            return $request['action'] === 'getNumber'
                && $request['service'] === 'whatsapp'
                && !isset($request['max_price'])
                && $request['api_key'] === 'test-daisy-key';
        });
    }

    public function test_purchase_can_still_send_an_explicit_maximum_price_when_requested(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_NUMBER:act-124:+12025550124', 200, ['X-Price' => '1.60']),
        ]);

        app(DaisySmsService::class)->buyNumber('187', 'telegram', 1.75);

        Http::assertSent(fn ($request) =>
            $request['action'] === 'getNumber'
            && $request['service'] === 'telegram'
            && $request['max_price'] === '1.75'
        );
    }

    public function test_successful_activation_without_price_header_is_preserved_for_reconciliation(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_NUMBER:act-456:+12025550124', 200),
        ]);

        $result = app(DaisySmsService::class)->buyNumber('187', 'telegram', 1.25);

        $this->assertSame('act-456', $result['order_id']);
        $this->assertSame('+12025550124', $result['phonenumber']);
        $this->assertTrue($result['price_unverified']);
        $this->assertSame(0.0, $result['cost']);
        $this->assertSame(1.25, $result['provider_quote_usd']);
    }

    public function test_confirmed_activation_uses_fresh_catalogue_quote_when_price_header_is_missing(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_NUMBER:act-457:+15822719990', 200),
        ]);

        $result = app(DaisySmsService::class)->buyNumber('187', 'whatsapp_canada', null, 0.60);

        $this->assertSame('act-457', $result['order_id']);
        $this->assertSame('+15822719990', $result['phonenumber']);
        $this->assertSame(0.60, $result['cost']);
        $this->assertSame(0.60, $result['provider_cost']);
        $this->assertFalse($result['price_unverified']);
        $this->assertSame(0.60, $result['provider_quote_usd']);

        Http::assertSent(fn ($request) =>
            $request['action'] === 'getNumber'
            && $request['service'] === 'whatsapp_canada'
            && !isset($request['max_price'])
        );
    }

    public function test_status_response_maps_sms_code_and_full_message(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('STATUS_OK:654321', 200, ['X-Text' => 'Your code is 654321']),
        ]);

        $status = app(DaisySmsService::class)->checkOrder('act-123');

        $this->assertSame(3, $status['status']);
        $this->assertSame('654321', $status['sms_code']);
        $this->assertSame('Your code is 654321', $status['full_sms']);
    }

    public function test_status_waiting_is_treated_as_waiting_for_extra_activation(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('STATUS_WAITING', 200),
        ]);

        $status = app(DaisySmsService::class)->checkOrder('act-next');

        $this->assertSame(['status' => 1], $status);
    }

    public function test_extra_activation_can_be_requested_immediately(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_NUMBER:act-next:+12025550123', 200),
        ]);

        $result = app(DaisySmsService::class)->requestExtraActivation('act-previous');

        $this->assertSame([
            'status' => 'ready',
            'activation_id' => 'act-next',
            'phone_number' => '+12025550123',
            'ready_at' => null,
        ], $result);

        Http::assertSent(fn ($request) =>
            $request['action'] === 'getExtraActivation'
            && $request['activationId'] === 'act-previous'
            && $request['api_key'] === 'test-daisy-key'
        );
    }

    public function test_extra_activation_can_return_a_wait_until_timestamp(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ASLEEP:act-next:+12025550123:1795640400', 200),
        ]);

        $result = app(DaisySmsService::class)->requestExtraActivation('act-previous');

        $this->assertSame('waiting', $result['status']);
        $this->assertSame('act-next', $result['activation_id']);
        $this->assertSame('+12025550123', $result['phone_number']);
        $this->assertSame(1795640400, $result['ready_at']);
    }

    public function test_extra_activation_status_waiting_is_treated_as_ready_for_sms_delivery(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('STATUS_WAITING', 200),
        ]);

        $status = app(DaisySmsService::class)->checkOrder('act-next');

        $this->assertSame(1, $status['status']);
    }

    public function test_extra_activation_rejects_provider_bad_id(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('BAD_ID', 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot provide another activation');

        app(DaisySmsService::class)->requestExtraActivation('act-missing');
    }

    public function test_unknown_extra_activation_response_is_treated_as_ambiguous_not_rejected(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('TEMPORARY_ERROR', 200),
        ]);

        try {
            app(DaisySmsService::class)->requestExtraActivation('act-previous');
            $this->fail('An unknown provider response must remain ambiguous.');
        } catch (\App\Exceptions\ProviderTimeoutException $exception) {
            $this->assertStringContainsString('outcome is unknown', $exception->getMessage());
        }
    }

    public function test_extra_activation_http_server_error_is_treated_as_ambiguous(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('Gateway error', 502),
        ]);

        try {
            app(DaisySmsService::class)->requestExtraActivation('act-previous');
            $this->fail('A gateway error must not be treated as a confirmed rejection.');
        } catch (\App\Exceptions\ProviderTimeoutException $exception) {
            $this->assertStringContainsString('outcome could not be confirmed', $exception->getMessage());
        }
    }

    public function test_cancellation_requires_provider_confirmation(): void
    {
        $sequence = Http::sequence()
            ->push('ACCESS_CANCEL', 200)
            ->push('ACCESS_READY', 200);
        Http::fake(['daisysms.io/*' => $sequence]);

        $this->assertSame(
            ['success' => true, 'status' => 'canceled'],
            app(DaisySmsService::class)->cancelOrder('act-123')
        );

        try {
            app(DaisySmsService::class)->cancelOrder('act-123');
            $this->fail('Cancellation without provider confirmation must throw.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('No refund was issued', $exception->getMessage());
        }
    }
}
