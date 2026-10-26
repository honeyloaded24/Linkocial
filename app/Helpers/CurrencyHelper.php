<?php

if (!function_exists('money')) {
    /**
     * Format an amount in Nigerian Naira (NGN).
     *
     * In the Linkocial single-currency policy, all amounts are denominated in NGN.
     *
     * @param float|int|string|null $amount
     * @param bool $showSymbol
     * @param string|null $forceCurrency (deprecated; preserved for contract compatibility)
     * @return string
     */
    function money($amount, $showSymbol = true, $forceCurrency = null): string
    {
        $num = (float) ($amount ?? 0);
        $formatted = number_format($num, 2);

        if (!$showSymbol) {
            return number_format($num, 2, '.', '');
        }

        $symbol = config('app.currency_symbol', '₦');
        return $symbol . $formatted;
    }
}

if (!function_exists('get_currency_symbol')) {
    /**
     * Get the authoritative system currency symbol (₦).
     */
    function get_currency_symbol(): string
    {
        return config('app.currency_symbol', '₦');
    }
}

if (!function_exists('get_currency_code')) {
    /**
     * Get the authoritative system currency code (NGN).
     */
    function get_currency_code(): string
    {
        return config('app.currency', 'NGN');
    }
}

if (!function_exists('usd_to_ngn')) {
    /**
     * Convert USD amount to NGN using the configured exchange rate.
     */
    function usd_to_ngn($usdAmount, int $decimals = 2): float
    {
        return app(\App\Services\CurrencyConversionService::class)->usdToNgn($usdAmount, $decimals);
    }
}

if (!function_exists('ngn_to_usd')) {
    /**
     * Convert NGN amount to USD using the configured exchange rate.
     */
    function ngn_to_usd($ngnAmount, int $decimals = 4): float
    {
        return app(\App\Services\CurrencyConversionService::class)->ngnToUsd($ngnAmount, $decimals);
    }
}

if (!function_exists('get_usd_to_ngn_rate')) {
    /**
     * Get current configured USD to NGN exchange rate (e.g. 1500).
     */
    function get_usd_to_ngn_rate(): float
    {
        return app(\App\Services\CurrencyConversionService::class)->getUsdToNgnRate();
    }
}

if (!function_exists('get_ngn_to_usd_rate')) {
    /**
     * Get reverse NGN to USD rate.
     */
    function get_ngn_to_usd_rate(): float
    {
        return app(\App\Services\CurrencyConversionService::class)->getNgnToUsdRate();
    }
}
