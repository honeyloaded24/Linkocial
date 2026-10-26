<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\Service;
use App\Models\ProviderServiceMapping;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SmmPricingService
{
    protected CurrencyConversionService $currencyService;
    protected SmmProviderService $smmService;

    public function __construct(CurrencyConversionService $currencyService, SmmProviderService $smmService)
    {
        $this->currencyService = $currencyService;
        $this->smmService = $smmService;
    }

    /**
     * Get the authoritative USD to NGN exchange rate.
     */
    public function getExchangeRate(): float
    {
        return $this->currencyService->getUsdToNgnRate();
    }

    /**
     * Convert raw provider cost to NGN based on provider currency.
     */
    public function convertProviderRateToNgn(float $rawProviderRate, string $providerCurrency = 'USD'): float
    {
        if ($rawProviderRate < 0) {
            throw new InvalidArgumentException("Provider rate cannot be negative.");
        }

        if ($rawProviderRate == 0.0) {
            return 0.0;
        }

        $currency = strtoupper($providerCurrency);

        if ($currency === 'USD') {
            $fxRate = $this->getExchangeRate();
            if ($fxRate <= 0) {
                throw new InvalidArgumentException("Configured USD to NGN exchange rate is zero or invalid.");
            }
            return round($rawProviderRate * $fxRate, 4);
        }

        // Already denominated in NGN
        return round($rawProviderRate, 4);
    }

    /**
     * Calculate Linkocial customer selling price for a service rate.
     *
     * @param float $rawProviderRate Wholesale rate per 1,000 units from provider
     * @param Provider $provider Provider model with markup configurations
     * @param Service|null $service Optional target service model
     * @return array Calculation breakdown
     */
    public function calculateSellingPrice(float $rawProviderRate, Provider $provider, ?Service $service = null): array
    {
        $currency = $provider->getCurrency();
        $costNgn = $this->convertProviderRateToNgn($rawProviderRate, $currency);
        $fxRate = $this->getExchangeRate();

        $pricingMode = $provider->getPricingMode();
        $percentage = $provider->getPercentageMarkup();
        $fixedPerK = $provider->getFixedMarkupPerK();
        $minProfit = $provider->getMinProfit();
        $minSellingPrice = $provider->getMinSellingPrice();
        $roundingRule = $provider->getRoundingRule();

        // 1. Calculate base selling price according to pricing mode
        if ($pricingMode === 'fixed_per_k') {
            $rawSellingPrice = $costNgn + $fixedPerK;
            $formula = "₦" . number_format($costNgn, 2) . " (Cost) + ₦" . number_format($fixedPerK, 2) . " (Fixed Markup)";
        } elseif ($pricingMode === 'combined') {
            $percentageAddition = ($costNgn * $percentage) / 100.0;
            $rawSellingPrice = $costNgn + $percentageAddition + $fixedPerK;
            $formula = "₦" . number_format($costNgn, 2) . " × (1 + {$percentage}%) + ₦" . number_format($fixedPerK, 2);
        } else {
            // Default: Percentage markup
            $percentageAddition = ($costNgn * $percentage) / 100.0;
            $rawSellingPrice = $costNgn + $percentageAddition;
            $formula = "₦" . number_format($costNgn, 2) . " × (1 + {$percentage}%)";
        }

        // 2. Minimum profit rule (if configured)
        if ($minProfit !== null && $minProfit > 0) {
            $currentProfit = $rawSellingPrice - $costNgn;
            if ($currentProfit < $minProfit) {
                $rawSellingPrice = $costNgn + $minProfit;
                $formula .= " [Min Profit ₦" . number_format($minProfit, 2) . " enforced]";
            }
        }

        // 3. Minimum selling price rule (if configured)
        if ($minSellingPrice !== null && $minSellingPrice > 0) {
            if ($rawSellingPrice < $minSellingPrice) {
                $rawSellingPrice = $minSellingPrice;
                $formula .= " [Min Price ₦" . number_format($minSellingPrice, 2) . " enforced]";
            }
        }

        // 4. Strict Financial Safety: Never sell at a loss!
        if ($rawSellingPrice < $costNgn) {
            $rawSellingPrice = $costNgn;
        }

        // 5. Apply Rounding Policy
        if ($roundingRule === 'whole_naira') {
            $finalSellingPrice = ceil($rawSellingPrice);
        } elseif ($roundingRule === 'nearest_10') {
            $finalSellingPrice = ceil($rawSellingPrice / 10.0) * 10;
        } else {
            // Standard 2 decimal places
            $finalSellingPrice = round($rawSellingPrice, 2);
        }

        $profitNgn = round($finalSellingPrice - $costNgn, 2);
        $profitMarginPercent = $costNgn > 0 ? round(($profitNgn / $costNgn) * 100, 2) : 0.0;

        return [
            'provider_rate_raw'     => $rawProviderRate,
            'provider_currency'     => $currency,
            'exchange_rate'         => $fxRate,
            'cost_ngn'              => $costNgn,
            'pricing_mode'          => $pricingMode,
            'percentage_markup'     => $percentage,
            'fixed_markup_per_k'    => $fixedPerK,
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
     * Calculate authoritative charge for customer order with quantity and role discounts.
     */
    public function calculateCustomerCharge(Service $service, int $quantity, ?User $user = null): array
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException("Order quantity must be at least 1.");
        }

        $sellingPrice = (float) $service->selling_price;
        $isPackage = strtolower($service->type ?? '') === 'package';

        // Per-1000 units vs package fixed rate
        $baseCharge = $isPackage ? $sellingPrice : (($sellingPrice / 1000.0) * $quantity);

        // Tiered Role Discounts
        $discountPercent = 0.0;
        if ($user) {
            if ($user->role === 'reseller') {
                $discountPercent = (float) \App\Models\Setting::get('reseller_discount', 5);
            } elseif ($user->role === 'vip') {
                $discountPercent = (float) \App\Models\Setting::get('vip_discount', 10);
            }
        }

        $discountAmount = ($baseCharge * $discountPercent) / 100.0;
        $finalCharge = round($baseCharge - $discountAmount, 2);

        return [
            'unit_price'       => $sellingPrice,
            'quantity'         => $quantity,
            'is_package'       => $isPackage,
            'base_charge'      => round($baseCharge, 2),
            'discount_percent' => $discountPercent,
            'discount_amount'  => round($discountAmount, 2),
            'final_charge'     => $finalCharge,
        ];
    }

    /**
     * Preview Momopanel price synchronization without modifying the database.
     *
     * @param Provider $provider
     * @return array
     */
    public function previewProviderSync(Provider $provider): array
    {
        $rawServices = $this->smmService->getServices($provider);

        if (!$rawServices || !is_array($rawServices)) {
            return [
                'success' => false,
                'error'   => "Failed to retrieve services from {$provider->name}. Check API credentials and endpoint status.",
                'items'   => [],
            ];
        }

        $previewItems = [];
        $newServicesCount = 0;
        $priceIncreasesCount = 0;
        $priceDecreasesCount = 0;
        $unchangedCount = 0;
        $totalCostChangeNgn = 0.0;

        foreach ($rawServices as $ps) {
            $providerServiceId = (string) ($ps['service'] ?? ($ps['id'] ?? ''));
            if (empty($providerServiceId)) {
                continue;
            }

            $name = trim($ps['name'] ?? 'Unknown Service');
            $category = trim($ps['category'] ?? 'General');
            $rawRate = (float) ($ps['rate'] ?? 0);
            $type = trim($ps['type'] ?? 'Default');
            $min = (int) ($ps['min'] ?? 1);
            $max = (int) ($ps['max'] ?? 10000);

            $calc = $this->calculateSellingPrice($rawRate, $provider);
            $newSellingPrice = $calc['selling_price'];
            $costNgn = $calc['cost_ngn'];

            // Check existing mapping
            $mapping = ProviderServiceMapping::where('provider_id', $provider->id)
                ->where('provider_service_id', $providerServiceId)
                ->with('service')
                ->first();

            $existingService = $mapping ? $mapping->service : null;
            $oldCostUsd = $mapping ? (float) $mapping->original_price : null;
            $oldSellingPrice = $existingService ? (float) $existingService->selling_price : null;

            $status = 'new';
            $diffNgn = 0.0;

            if ($existingService) {
                $diffNgn = round($newSellingPrice - $oldSellingPrice, 2);
                if ($diffNgn > 0) {
                    $status = 'increase';
                    $priceIncreasesCount++;
                } elseif ($diffNgn < 0) {
                    $status = 'decrease';
                    $priceDecreasesCount++;
                } else {
                    $status = 'unchanged';
                    $unchangedCount++;
                }
            } else {
                $newServicesCount++;
            }

            $previewItems[] = [
                'provider_service_id' => $providerServiceId,
                'name'                => $name,
                'category'            => $category,
                'type'                => $type,
                'min'                 => $min,
                'max'                 => $max,
                'provider_rate_usd'   => $rawRate,
                'cost_ngn'            => $costNgn,
                'old_selling_price'   => $oldSellingPrice,
                'new_selling_price'   => $newSellingPrice,
                'diff_ngn'            => $diffNgn,
                'profit_ngn'          => $calc['profit_ngn'],
                'profit_margin'       => $calc['profit_margin_percent'],
                'status'              => $status,
                'is_existing'         => $existingService !== null,
                'service_id'          => $existingService ? $existingService->id : null,
            ];
        }

        return [
            'success'               => true,
            'provider_name'         => $provider->name,
            'exchange_rate'         => $this->getExchangeRate(),
            'total_services'        => count($previewItems),
            'new_services'          => $newServicesCount,
            'price_increases'       => $priceIncreasesCount,
            'price_decreases'       => $priceDecreasesCount,
            'unchanged'             => $unchangedCount,
            'items'                 => $previewItems,
        ];
    }

    /**
     * Execute live Momopanel price synchronization with database transactions.
     *
     * @param Provider $provider
     * @param array|null $selectedServiceIds Optional filter to sync only specific provider service IDs
     * @return array Sync result summary
     */
    public function executeProviderSync(Provider $provider, ?array $selectedServiceIds = null): array
    {
        $rawServices = $this->smmService->getServices($provider);

        if (!$rawServices || !is_array($rawServices)) {
            $msg = "Synchronization failed: Unable to fetch service list from {$provider->name}.";
            $provider->update([
                'last_sync_status'  => 'failed',
                'last_sync_message' => $msg,
            ]);
            Log::error("SMM Sync Failed: " . $msg);
            return ['success' => false, 'error' => $msg];
        }

        $syncedCount = 0;
        $newCount = 0;
        $errorCount = 0;
        $fxRate = $this->getExchangeRate();

        DB::beginTransaction();
        try {
            foreach ($rawServices as $ps) {
                $providerServiceId = (string) ($ps['service'] ?? ($ps['id'] ?? ''));
                if (empty($providerServiceId)) {
                    continue;
                }

                if ($selectedServiceIds !== null && !in_array($providerServiceId, $selectedServiceIds)) {
                    continue;
                }

                $name = trim($ps['name'] ?? 'Unknown Service');
                $category = trim($ps['category'] ?? 'General');
                $rawRate = (float) ($ps['rate'] ?? 0);
                $type = trim($ps['type'] ?? 'Default');
                $min = (int) ($ps['min'] ?? 1);
                $max = (int) ($ps['max'] ?? 10000);
                $desc = $ps['desc'] ?? null;
                $averageTime = $ps['average_time'] ?? null;

                $calc = $this->calculateSellingPrice($rawRate, $provider);
                $calculatedSellingPrice = $calc['selling_price'];

                $mapping = ProviderServiceMapping::where('provider_id', $provider->id)
                    ->where('provider_service_id', $providerServiceId)
                    ->first();

                if ($mapping) {
                    $mapping->update(['original_price' => $rawRate]);

                    if ($mapping->service) {
                        $mapping->service->update([
                            'selling_price' => $calculatedSellingPrice,
                            'category'      => $category,
                            'description'   => $desc,
                            'average_time'  => $averageTime,
                            'min'           => $min,
                            'max'           => $max,
                            'type'          => $type,
                        ]);
                    }
                    $syncedCount++;
                } else {
                    // Create new service mapped to this provider (marked for review before public activation)
                    $internalService = Service::create([
                        'name'          => "[Review] {$name}",
                        'category'      => $category,
                        'description'   => $desc,
                        'average_time'  => $averageTime,
                        'selling_price' => $calculatedSellingPrice,
                        'min'           => $min,
                        'max'           => $max,
                        'is_active'     => false,
                        'type'          => $type,
                    ]);

                    ProviderServiceMapping::create([
                        'service_id'          => $internalService->id,
                        'provider_id'         => $provider->id,
                        'provider_service_id' => $providerServiceId,
                        'original_price'      => $rawRate,
                        'priority'            => 1,
                        'is_active'           => true,
                    ]);
                    $newCount++;
                }
            }

            $successMsg = "Successfully synchronized {$syncedCount} existing services and imported {$newCount} new services at FX rate ₦" . number_format($fxRate, 2) . "/$.";

            $provider->update([
                'last_synced_at'    => now(),
                'last_sync_status'  => 'success',
                'last_sync_message' => $successMsg,
            ]);

            ActivityLog::log("Synchronized provider prices for {$provider->name}", auth()->user(), [
                'provider_id'   => $provider->id,
                'synced_count'  => $syncedCount,
                'new_count'     => $newCount,
                'exchange_rate' => $fxRate,
            ]);

            DB::commit();

            return [
                'success'      => true,
                'synced_count' => $syncedCount,
                'new_count'    => $newCount,
                'errors'       => $errorCount,
                'message'      => $successMsg,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMsg = "Sync execution error: " . $e->getMessage();
            Log::error("SMM Sync DB Transaction Error: " . $e->getMessage());

            $provider->update([
                'last_sync_status'  => 'failed',
                'last_sync_message' => $errorMsg,
            ]);

            return [
                'success' => false,
                'error'   => $errorMsg,
            ];
        }
    }
}
