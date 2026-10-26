<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CurrencyConversionService;
use App\Models\Setting;
use InvalidArgumentException;

class CurrencyConversionServiceTest extends TestCase
{
    protected CurrencyConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyConversionService();
        // Set standard rate for testing: 1 USD = ₦1,500 NGN
        Setting::set('usd_to_ngn_rate', '1500');
        Setting::set('ngn_to_usd_rate', '0.0006666667');
    }

    /**
     * Test USD to NGN conversion at rate 1500.
     */
    public function test_usd_to_ngn_conversions(): void
    {
        $this->assertEquals(1500.00, $this->service->usdToNgn(1));
        $this->assertEquals(7500.00, $this->service->usdToNgn(5));
        $this->assertEquals(15000.00, $this->service->usdToNgn(10));
        $this->assertEquals(70500.00, $this->service->usdToNgn(47));
        $this->assertEquals(150000.00, $this->service->usdToNgn(100));
        $this->assertEquals(1500000.00, $this->service->usdToNgn(1000));
    }

    /**
     * Test reverse NGN to USD conversion at rate 1500.
     */
    public function test_ngn_to_usd_conversions(): void
    {
        $this->assertEquals(1.0000, $this->service->ngnToUsd(1500));
        $this->assertEquals(5.0000, $this->service->ngnToUsd(7500));
        $this->assertEquals(10.0000, $this->service->ngnToUsd(15000));
        $this->assertEquals(47.0000, $this->service->ngnToUsd(70500));
        $this->assertEquals(100.0000, $this->service->ngnToUsd(150000));
    }

    /**
     * Test zero and boundary amounts.
     */
    public function test_zero_amount(): void
    {
        $this->assertEquals(0.0, $this->service->usdToNgn(0));
        $this->assertEquals(0.0, $this->service->ngnToUsd(0));
    }

    /**
     * Test negative amount throws exception.
     */
    public function test_negative_amount_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->usdToNgn(-10);
    }

    /**
     * Test reverse rate calculation is mathematically consistent.
     */
    public function test_reverse_rate_consistency(): void
    {
        $rate = $this->service->getUsdToNgnRate();
        $reverse = $this->service->getNgnToUsdRate();

        $this->assertEquals(1500.0, $rate);
        $product = $rate * $reverse;
        $this->assertEqualsWithDelta(1.0, $product, 0.0001);
    }
}
