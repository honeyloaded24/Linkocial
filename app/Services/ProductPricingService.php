<?php

namespace App\Services;

use App\Models\ProductPricingSetting;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProductPricingService
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Supported product keys
     */
    public const PRODUCT_VTU = 'vtu';
    public const PRODUCT_LOGS = 'logs';
    public const PRODUCT_VIRTUAL_NUMBERS = 'virtual_numbers';
    public const PRODUCT_BOOST = 'boost';

    /**
     * Get default baseline configuration for a product.
     */
    public function getDefaultSettings(string $productKey): array
    {
        $platformFxRate = $this->currencyService->getUsdToNgnRate();

        switch ($productKey) {
            case self::PRODUCT_VTU:
                $dataMarkup = (float) Setting::get('markup_vtu_data', 0.0);
                return [
                    'currency'           => 'NGN',
                    'fix_rate'           => $platformFxRate,
                    'pricing_mode'       => 'percentage',
                    'percentage_markup'  => $dataMarkup,
                    'fixed_markup'       => 0.0,
                    'min_selling_price'  => null,
                    'min_profit'         => null,
                    'rounding_rule'      => '2_decimal',
                ];

            case self::PRODUCT_LOGS:
                $logsMarkup = (float) Setting::get('markup_logs_market', 20.0);
                return [
                    'currency'           => 'NGN',
                    'fix_rate'           => $platformFxRate,
                    'pricing_mode'       => 'percentage',
                    'percentage_markup'  => $logsMarkup,
                    'fixed_markup'       => 0.0,
                    'min_selling_price'  => null,
                    'min_profit'         => null,
                    'rounding_rule'      => '2_decimal',
                ];

            case self::PRODUCT_VIRTUAL_NUMBERS:
                $vnRate = (float) Setting::get('provider_usd_rate', $platformFxRate);
                $vnMarkupNgn = (float) Setting::get('vn_markup_ngn', 1500.0);
                return [
                    'currency'           => 'USD',
                    'fix_rate'           => $vnRate,
                    'pricing_mode'       => 'fixed',
                    'percentage_markup'  => 0.0,
                    'fixed_markup'       => $vnMarkupNgn,
                    'min_selling_price'  => null,
                    'min_profit'         => null,
                    'rounding_rule'      => '2_decimal',
                ];

            case self::PRODUCT_BOOST:
            default:
                return [
                    'currency'           => 'USD',
                    'fix_rate'           => $platformFxRate,
                    'pricing_mode'       => 'percentage',
                    'percentage_markup'  => 30.0,
                    'fixed_markup'       => 0.0,
                    'min_selling_price'  => null,
                    'min_profit'         => null,
                    'rounding_rule'      => '2_decimal',
                ];
        }
    }

    /**
     * Retrieve the active pricing settings for a product.
     * Guaranteed independent retrieval per product.
     */
    public function getPricingSettings(string $productKey): ProductPricingSetting
    {
        $defaults = $this->getDefaultSettings($productKey);

        // BOOST special handling: can mirror primary provider (Momopanel) if available
        if ($productKey === self::PRODUCT_BOOST) {
            $provider = Provider::where('name', 'Momopanel')->first() ?? Provider::first();
            if ($provider) {
                $defaults['currency'] = $provider->getCurrency();
                $defaults['pricing_mode'] = $provider->getPricingMode();
                $defaults['percentage_markup'] = $provider->getPercentageMarkup();
                $defaults['fixed_markup'] = $provider->getFixedMarkupPerK();
                $defaults['min_selling_price'] = $provider->getMinSellingPrice();
                $defaults['min_profit'] = $provider->getMinProfit();
                $defaults['rounding_rule'] = $provider->getRoundingRule();
            }
        }

        // 1. Check if product_pricing_settings table exists
        $hasTable = false;
        try {
            $hasTable = Schema::hasTable('product_pricing_settings');
        } catch (\Throwable $e) {
            $hasTable = false;
        }

        if ($hasTable) {
            try {
                $record = ProductPricingSetting::where('product_key', $productKey)->first();
                if ($record) {
                    return $record;
                }
            } catch (\Throwable $e) {
                Log::warning("ProductPricingSetting query failed: " . $e->getMessage());
            }
        }

        // 2. Fallback to settings table
        $currency = Setting::get("pricing_{$productKey}_currency", $defaults['currency']);
        $fixRate = (float) Setting::get("pricing_{$productKey}_fix_rate", $defaults['fix_rate']);
        $pricingMode = Setting::get("pricing_{$productKey}_pricing_mode", $defaults['pricing_mode']);
        $percentage = (float) Setting::get("pricing_{$productKey}_percentage_markup", $defaults['percentage_markup']);
        $fixed = (float) Setting::get("pricing_{$productKey}_fixed_markup", $defaults['fixed_markup']);
        $minPrice = Setting::get("pricing_{$productKey}_min_selling_price", $defaults['min_selling_price']);
        $minProfit = Setting::get("pricing_{$productKey}_min_profit", $defaults['min_profit']);
        $rounding = Setting::get("pricing_{$productKey}_rounding_rule", $defaults['rounding_rule']);

        // Return hydrated model instance
        $model = new ProductPricingSetting([
            'product_key'        => $productKey,
            'currency'           => strtoupper($currency ?: 'USD'),
            'fix_rate'           => $fixRate > 0 ? $fixRate : 1500.0,
            'pricing_mode'       => $pricingMode ?: 'percentage',
            'percentage_markup'  => $percentage,
            'fixed_markup'       => $fixed,
            'min_selling_price'  => $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null,
            'min_profit'         => $minProfit !== null && $minProfit !== '' ? (float) $minProfit : null,
            'rounding_rule'      => $rounding ?: '2_decimal',
        ]);

        return $model;
    }

    /**
     * Update and persist pricing settings for a product independently.
     */
    public function updatePricingSettings(string $productKey, array $data, ?User $admin = null): ProductPricingSetting
    {
        $currency = strtoupper($data['currency'] ?? 'USD');
        if (!in_array($currency, ['USD', 'NGN'])) {
            throw new InvalidArgumentException("Invalid currency '{$currency}'. Supported: USD, NGN.");
        }

        $fixRate = (float) ($data['fix_rate'] ?? 1500.0);
        if ($fixRate <= 0) {
            throw new InvalidArgumentException("Active fix rate must be greater than zero.");
        }

        $pricingMode = $data['pricing_mode'] ?? 'percentage';
        if (!in_array($pricingMode, ['percentage', 'fixed', 'combined', 'fixed_per_k'])) {
            throw new InvalidArgumentException("Invalid pricing mode '{$pricingMode}'.");
        }
        // Normalize fixed_per_k to fixed for consistency
        if ($pricingMode === 'fixed_per_k') {
            $pricingMode = 'fixed';
        }

        $percentage = (float) ($data['percentage_markup'] ?? 0.0);
        if ($percentage < 0 || $percentage > 5000) {
            throw new InvalidArgumentException("Percentage markup must be between 0% and 5,000%.");
        }

        $fixedMarkup = (float) ($data['fixed_markup'] ?? ($data['fixed_markup_per_k'] ?? 0.0));
        if ($fixedMarkup < 0) {
            throw new InvalidArgumentException("Fixed markup cannot be negative.");
        }

        $minPrice = isset($data['min_selling_price']) && $data['min_selling_price'] !== '' && $data['min_selling_price'] !== null 
            ? (float) $data['min_selling_price'] 
            : null;
        if ($minPrice !== null && $minPrice < 0) {
            throw new InvalidArgumentException("Minimum selling price cannot be negative.");
        }

        $minProfit = isset($data['min_profit']) && $data['min_profit'] !== '' && $data['min_profit'] !== null 
            ? (float) $data['min_profit'] 
            : null;
        if ($minProfit !== null && $minProfit < 0) {
            throw new InvalidArgumentException("Minimum profit margin cannot be negative.");
        }

        $rounding = $data['rounding_rule'] ?? '2_decimal';
        if (!in_array($rounding, ['2_decimal', 'whole_naira', 'nearest_10'])) {
            throw new InvalidArgumentException("Invalid rounding rule '{$rounding}'.");
        }

        $payload = [
            'product_key'        => $productKey,
            'currency'           => $currency,
            'fix_rate'           => $fixRate,
            'pricing_mode'       => $pricingMode,
            'percentage_markup'  => $percentage,
            'fixed_markup'       => $fixedMarkup,
            'min_selling_price'  => $minPrice,
            'min_profit'         => $minProfit,
            'rounding_rule'      => $rounding,
        ];

        // 1. Persist to dedicated table if present
        $savedModel = null;
        try {
            if (Schema::hasTable('product_pricing_settings')) {
                $savedModel = ProductPricingSetting::updateOrCreate(
                    ['product_key' => $productKey],
                    $payload
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Failed writing to product_pricing_settings table: " . $e->getMessage());
        }

        // 2. Mirror to settings table for 100% resilience & caching
        Setting::set("pricing_{$productKey}_currency", $currency);
        Setting::set("pricing_{$productKey}_fix_rate", (string) $fixRate);
        Setting::set("pricing_{$productKey}_pricing_mode", $pricingMode);
        Setting::set("pricing_{$productKey}_percentage_markup", (string) $percentage);
        Setting::set("pricing_{$productKey}_fixed_markup", (string) $fixedMarkup);
        Setting::set("pricing_{$productKey}_min_selling_price", $minPrice !== null ? (string) $minPrice : '');
        Setting::set("pricing_{$productKey}_min_profit", $minProfit !== null ? (string) $minProfit : '');
        Setting::set("pricing_{$productKey}_rounding_rule", $rounding);

        // 3. Keep product-specific legacy keys synchronized
        if ($productKey === self::PRODUCT_VIRTUAL_NUMBERS) {
            Setting::set('provider_usd_rate', (string) $fixRate);
            Setting::set('vn_markup_ngn', (string) $fixedMarkup);
        } elseif ($productKey === self::PRODUCT_LOGS) {
            Setting::set('markup_logs_market', (string) $percentage);
        } elseif ($productKey === self::PRODUCT_VTU) {
            Setting::set('markup_vtu_data', (string) $percentage);
            Setting::set('markup_vtu_airtime', (string) $percentage);
            Setting::set('markup_vtu_cable', (string) $percentage);
            Setting::set('markup_vtu_electricity', (string) $percentage);
        } elseif ($productKey === self::PRODUCT_BOOST) {
            // Synchronize Momopanel provider model if present
            $provider = Provider::where('name', 'Momopanel')->first() ?? Provider::first();
            if ($provider) {
                $provider->update([
                    'currency'           => $currency,
                    'pricing_mode'       => $pricingMode,
                    'percentage_markup'  => $percentage,
                    'fixed_markup_per_k' => $fixedMarkup,
                    'min_selling_price'  => $minPrice,
                    'min_profit'         => $minProfit,
                    'rounding_rule'      => $rounding,
                ]);
            }
        }

        Setting::clearCache();

        ActivityLog::log("Updated Pricing & Markup for {$productKey}", $admin ?: auth()->user(), [
            'product_key'  => $productKey,
            'new_settings' => $payload,
        ]);

        return $savedModel ?: new ProductPricingSetting($payload);
    }

    /**
     * Convert raw provider cost to NGN based on product's configured currency and active fix rate.
     */
    public function convertProviderCostToNgn(float $rawCost, string $currency = 'USD', float $fixRate = 1500.0): float
    {
        if ($rawCost < 0) {
            throw new InvalidArgumentException("Provider cost cannot be negative.");
        }

        if ($rawCost == 0.0) {
            return 0.0;
        }

        $curr = strtoupper($currency);
        if ($curr === 'USD') {
            if ($fixRate <= 0) {
                throw new InvalidArgumentException("Active fix rate is zero or invalid.");
            }
            return round($rawCost * $fixRate, 4);
        }

        // Native NGN: no currency conversion needed
        return round($rawCost, 4);
    }

    /**
     * Calculate retail selling price and profit breakdown according to BOOST's reference algorithm.
     *
     * @param float $rawCost Wholesale cost from provider
     * @param string $productKey 'vtu', 'logs', 'virtual_numbers', 'boost'
     * @param array $overrides Optional in-flight parameter overrides
     * @return array Calculation breakdown
     */
    public function calculateSellingPrice(float $rawCost, string $productKey, array $overrides = []): array
    {
        $settings = $this->getPricingSettings($productKey);

        $currency = strtoupper($overrides['currency'] ?? $settings->getCurrency());
        $fixRate = (float) ($overrides['fix_rate'] ?? $settings->getFixRate());
        $pricingMode = $overrides['pricing_mode'] ?? $settings->getPricingMode();
        $percentage = (float) ($overrides['percentage_markup'] ?? $settings->getPercentageMarkup());
        $fixed = (float) ($overrides['fixed_markup'] ?? $settings->getFixedMarkup());
        $minProfit = isset($overrides['min_profit']) ? ($overrides['min_profit'] !== null ? (float)$overrides['min_profit'] : null) : $settings->getMinProfit();
        $minSellingPrice = isset($overrides['min_selling_price']) ? ($overrides['min_selling_price'] !== null ? (float)$overrides['min_selling_price'] : null) : $settings->getMinSellingPrice();
        $roundingRule = $overrides['rounding_rule'] ?? $settings->getRoundingRule();

        // 1. Convert cost to NGN using the product's active fix rate
        $costNgn = $this->convertProviderCostToNgn($rawCost, $currency, $fixRate);

        // 2. Base markup calculation based on configured pricing mode
        if ($pricingMode === 'fixed' || $pricingMode === 'fixed_per_k') {
            $rawSellingPrice = $costNgn + $fixed;
            $formula = "₦" . number_format($costNgn, 2) . " (Cost) + ₦" . number_format($fixed, 2) . " (Fixed Markup)";
        } elseif ($pricingMode === 'combined') {
            $percentageAddition = ($costNgn * $percentage) / 100.0;
            $rawSellingPrice = $costNgn + $percentageAddition + $fixed;
            $formula = "₦" . number_format($costNgn, 2) . " × (1 + {$percentage}%) + ₦" . number_format($fixed, 2);
        } else {
            // Default: percentage mode
            $percentageAddition = ($costNgn * $percentage) / 100.0;
            $rawSellingPrice = $costNgn + $percentageAddition;
            $formula = "₦" . number_format($costNgn, 2) . " × (1 + {$percentage}%)";
        }

        // 3. Minimum profit rule enforcement
        if ($minProfit !== null && $minProfit > 0) {
            $currentProfit = $rawSellingPrice - $costNgn;
            if ($currentProfit < $minProfit) {
                $rawSellingPrice = $costNgn + $minProfit;
                $formula .= " [Min Profit ₦" . number_format($minProfit, 2) . " enforced]";
            }
        }

        // 4. Minimum selling price rule enforcement
        if ($minSellingPrice !== null && $minSellingPrice > 0) {
            if ($rawSellingPrice < $minSellingPrice) {
                $rawSellingPrice = $minSellingPrice;
                $formula .= " [Min Price ₦" . number_format($minSellingPrice, 2) . " enforced]";
            }
        }

        // 5. Strict Financial Safety: Never sell at a loss!
        if ($rawSellingPrice < $costNgn) {
            $rawSellingPrice = $costNgn;
        }

        // 6. Rounding Policy
        if ($roundingRule === 'whole_naira') {
            $finalSellingPrice = ceil($rawSellingPrice);
        } elseif ($roundingRule === 'nearest_10') {
            $finalSellingPrice = ceil($rawSellingPrice / 10.0) * 10;
        } else {
            // Standard 2 decimal places
            $finalSellingPrice = round($rawSellingPrice, 2);
        }

        $profitNgn = max(0.0, round($finalSellingPrice - $costNgn, 2));
        $profitMarginPercent = $costNgn > 0 ? round(($profitNgn / $costNgn) * 100, 2) : 0.0;

        return [
            'product_key'           => $productKey,
            'provider_rate_raw'     => $rawCost,
            'provider_currency'     => $currency,
            'exchange_rate'         => $fixRate,
            'cost_ngn'              => $costNgn,
            'pricing_mode'          => $pricingMode,
            'percentage_markup'     => $percentage,
            'fixed_markup'          => $fixed,
            'min_profit'            => $minProfit,
            'min_selling_price'     => $minSellingPrice,
            'rounding_rule'         => $roundingRule,
            'raw_selling_price'     => $rawSellingPrice,
            'selling_price'         => $finalSellingPrice,
            'profit_ngn'            => $profitNgn,
            'profit_margin_percent' => $profitMarginPercent,
            'formula'               => $formula,
        ];
    }

    /**
     * Helper to get active fix rate for a product.
     */
    public function getFixRate(string $productKey): float
    {
        return $this->getPricingSettings($productKey)->getFixRate();
    }
}
