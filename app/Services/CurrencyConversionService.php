<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CurrencyConversionService
{
    /**
     * Default fallback rate if setting has not yet been configured in DB.
     */
    public const DEFAULT_USD_TO_NGN_RATE = 1500.0;

    /**
     * Get the authoritative USD to NGN exchange rate (1 USD = X NGN).
     *
     * @return float
     */
    public function getUsdToNgnRate(): float
    {
        $raw = Setting::get('usd_to_ngn_rate');

        if ($raw !== null && is_numeric($raw) && (float) $raw > 0) {
            return (float) $raw;
        }

        // Fallback to provider_usd_rate if configured, else default to 1500.0
        $providerRate = Setting::get('provider_usd_rate');
        if ($providerRate !== null && is_numeric($providerRate) && (float) $providerRate > 0) {
            return (float) $providerRate;
        }

        return self::DEFAULT_USD_TO_NGN_RATE;
    }

    /**
     * Get the reverse NGN to USD exchange rate (1 NGN = Y USD).
     *
     * Calculated as: 1 / USD_TO_NGN_RATE
     *
     * @return float
     */
    public function getNgnToUsdRate(): float
    {
        $usdRate = $this->getUsdToNgnRate();
        if ($usdRate <= 0) {
            return 0.0;
        }

        return round(1.0 / $usdRate, 10);
    }

    /**
     * Convert an amount from USD to NGN.
     *
     * Formula: NGN = USD * USD_TO_NGN_RATE
     *
     * @param float|int|string|null $usdAmount
     * @param int $decimals
     * @return float
     * @throws InvalidArgumentException
     */
    public function usdToNgn($usdAmount, int $decimals = 2): float
    {
        if ($usdAmount === null || !is_numeric($usdAmount)) {
            throw new InvalidArgumentException("Invalid USD amount provided for conversion.");
        }

        $amount = (float) $usdAmount;
        if ($amount < 0) {
            throw new InvalidArgumentException("Monetary conversion does not support negative amounts.");
        }

        if ($amount == 0.0) {
            return 0.0;
        }

        $rate = $this->getUsdToNgnRate();
        $converted = $amount * $rate;

        return round($converted, $decimals);
    }

    /**
     * Convert an amount from NGN to USD.
     *
     * Formula: USD = NGN / USD_TO_NGN_RATE
     *
     * @param float|int|string|null $ngnAmount
     * @param int $decimals
     * @return float
     * @throws InvalidArgumentException
     */
    public function ngnToUsd($ngnAmount, int $decimals = 4): float
    {
        if ($ngnAmount === null || !is_numeric($ngnAmount)) {
            throw new InvalidArgumentException("Invalid NGN amount provided for conversion.");
        }

        $amount = (float) $ngnAmount;
        if ($amount < 0) {
            throw new InvalidArgumentException("Monetary conversion does not support negative amounts.");
        }

        if ($amount == 0.0) {
            return 0.0;
        }

        $rate = $this->getUsdToNgnRate();
        if ($rate <= 0) {
            throw new InvalidArgumentException("Active USD to NGN exchange rate is invalid or zero.");
        }

        $converted = $amount / $rate;

        return round($converted, $decimals);
    }

    /**
     * Update the authoritative exchange rate in database and log the change.
     *
     * NOTE: Updating this rate does NOT alter existing NGN wallet balances!
     * It only applies to future conversion operations.
     *
     * @param float $usdToNgnRate
     * @param string|null $updatedBy Username or identifier of admin
     * @return void
     * @throws InvalidArgumentException
     */
    public function updateRates(float $usdToNgnRate, ?string $updatedBy = null): void
    {
        if ($usdToNgnRate <= 0) {
            throw new InvalidArgumentException("Exchange rate must be greater than zero.");
        }

        $previousRate = $this->getUsdToNgnRate();
        $reverseRate = round(1.0 / $usdToNgnRate, 10);
        $timestamp = now()->toDateTimeString();

        Setting::set('usd_to_ngn_rate', (string) $usdToNgnRate);
        Setting::set('ngn_to_usd_rate', (string) $reverseRate);
        Setting::set('provider_usd_rate', (string) $usdToNgnRate); // Keep provider wholesale sync rate aligned
        Setting::set('exchange_rate_updated_at', $timestamp);

        if ($updatedBy) {
            Setting::set('exchange_rate_updated_by', $updatedBy);
        }

        Setting::clearCache();

        ActivityLog::log("Updated Currency Exchange Rate", auth()->user(), [
            'previous_rate' => $previousRate,
            'new_rate' => $usdToNgnRate,
            'reverse_rate' => $reverseRate,
            'updated_by' => $updatedBy,
            'timestamp' => $timestamp,
        ]);

        Log::info("Currency Exchange Rate updated to 1 USD = {$usdToNgnRate} NGN by " . ($updatedBy ?? 'system'));
    }
}
