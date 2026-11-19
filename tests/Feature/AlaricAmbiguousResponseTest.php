<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\AlaricApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlaricAmbiguousResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_exception_is_reported_as_ambiguous_not_definitive_failure(): void
    {
        Setting::set('alaric_api_key', 'test_key_123');
        Http::fake(function ($request) {
            throw new ConnectionException('Connection reset after request submission');
        });

        $result = app(AlaricApiService::class)->createOrder('test-product', 1, 5.00, 'test-idempotency-key');

        $this->assertFalse($result['success']);
        $this->assertFalse($result['timed_out']);
        $this->assertTrue($result['ambiguous']);
        $this->assertStringContainsString('outcome is unknown', $result['error_message']);
    }
}
