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
