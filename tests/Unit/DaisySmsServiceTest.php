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

    public function test_flat_usa_catalogue_exposes_only_the_four_routed_services(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response([
                'whatsapp' => ['cost' => 1.60, 'count' => 830, 'multi' => 0],
                'whatsapp_canada' => ['cost' => 0.30, 'count' => 658, 'multi' => 1],
                'telegram' => ['cost' => 0.92, 'count' => 223, 'multi' => 0],
                'telegram_canada' => ['cost' => 0.42, 'count' => 540, 'multi' => 0],
                'facebook' => ['cost' => 0.90, 'count' => 320, 'multi' => 0],
            ], 200),
        ]);

        $rows = app(DaisySmsService::class)->getPricesForCountry('187');

        $this->assertCount(4, $rows);
        $this->assertSame(['whatsapp', 'telegram', 'whatsapp_canada', 'telegram_canada'], collect($rows)->pluck('service')->sort()->values()->all());
        $this->assertSame(1.60, $rows[0]['price']);
        $this->assertSame(830, $rows[0]['stock']);
    }

    public function test_non_usa_catalogue_is_not_requested_from_daisy_sms(): void
    {
        Http::fake();

        $this->assertSame([], app(DaisySmsService::class)->getPricesForCountry('1'));
        Http::assertNothingSent();
    }

    public function test_purchase_sends_the_service_code_and_maximum_price_and_reads_actual_price(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_NUMBER:act-123:+12025550123', 200, ['X-Price' => '1.60']),
        ]);

        $result = app(DaisySmsService::class)->buyNumber('187', 'whatsapp', 1.75);

        $this->assertSame('act-123', $result['order_id']);
        $this->assertSame('+12025550123', $result['phonenumber']);
        $this->assertSame(1.60, $result['cost']);

        Http::assertSent(function ($request) {
            return $request['action'] === 'getNumber'
                && $request['service'] === 'whatsapp'
                && $request['max_price'] === '1.75'
                && $request['api_key'] === 'test-daisy-key';
        });
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

    public function test_cancellation_requires_provider_confirmation(): void
    {
        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_CANCEL'),
        ]);

        $this->assertSame(
            ['success' => true, 'status' => 'canceled'],
            app(DaisySmsService::class)->cancelOrder('act-123')
        );

        Http::fake([
            'daisysms.io/*' => Http::response('ACCESS_READY'),
        ]);

        try {
            app(DaisySmsService::class)->cancelOrder('act-123');
            $this->fail('Cancellation without provider confirmation must throw.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('No refund was issued', $exception->getMessage());
        }
    }
}
