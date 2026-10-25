<?php

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

if (!function_exists('money')) {
    /**
     * Convert USD to the current currency and format it.
     */
    function money($amount, $showSymbol = true, $forceCurrency = null)
    {
        $currencyCode = $forceCurrency ?? Session::get('currency_code', auth()->user()->currency_code ?? 'NGN');
        
        $currencyData = Cache::remember("currency_data_{$currencyCode}", 3600, function () use ($currencyCode) {
            if ($currencyCode === 'USD') {
                return [
                    'symbol' => '$',
                    'exchange_rate' => 1.0
                ];
            }

            $curr = Currency::where('code', $currencyCode)->first() 
                ?? Currency::where('is_default', true)->first();
            
            return $curr ? [
                'symbol' => $curr->symbol,
                'exchange_rate' => (float)$curr->exchange_rate
            ] : [
                'symbol' => '₦',
                'exchange_rate' => 1450.0
            ];
        });

        // If we are forcing USD (or the master currency is USD), we don't multiply by the rate 
        // if the input $amount is already in USD master.
        // Actually, the money helper assumes input is USD and converts TO target.
        // So if $forceCurrency is USD, rate will be 1.0. This is perfect.
        $converted = $amount * $currencyData['exchange_rate'];
        
        if (!$showSymbol) {
            return number_format($converted, 2, '.', '');
        }

        $sep = strlen($currencyData['symbol']) > 2 ? ' ' : '';
        return $currencyData['symbol'] . $sep . number_format($converted, 2);
    }
}

if (!function_exists('get_currency_symbol')) {
    /**
     * Get the current currency symbol.
     */
    function get_currency_symbol()
    {
        $currencyCode = Session::get('currency_code', auth()->user()->currency_code ?? 'NGN');
        $currency = Currency::where('code', $currencyCode)->first() ?? Currency::where('is_default', true)->first();
        return $currency ? $currency->symbol : '₦';
    }
}
