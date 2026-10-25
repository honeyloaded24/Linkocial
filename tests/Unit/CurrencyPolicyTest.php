<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CurrencyPolicyTest extends TestCase
{
    /**
     * Test currency helper formats consistently in NGN.
     */
    public function test_currency_helper_formats_in_naira(): void
    {
        require_once __DIR__ . '/../../app/Helpers/CurrencyHelper.php';

        $formatted = money(1500);
        $this->assertStringContainsString('₦', $formatted);
        $this->assertStringContainsString('1,500.00', $formatted);

        $noSymbol = money(2500.5, false);
        $this->assertEquals('2500.50', $noSymbol);
    }

    /**
     * Test money helper ignores legacy forceCurrency parameter and enforces NGN symbol.
     */
    public function test_money_helper_ignores_legacy_usd_parameter(): void
    {
        require_once __DIR__ . '/../../app/Helpers/CurrencyHelper.php';

        $formatted = money(100, true, 'USD');
        $this->assertStringContainsString('₦', $formatted);
        $this->assertStringNotContainsString('$', $formatted);
    }

    /**
     * Test currency symbol and code getters.
     */
    public function test_currency_symbol_and_code(): void
    {
        require_once __DIR__ . '/../../app/Helpers/CurrencyHelper.php';

        $this->assertEquals('₦', get_currency_symbol());
        $this->assertEquals('NGN', get_currency_code());
    }
}
