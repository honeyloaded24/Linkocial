<?php

namespace App\Services;

use App\Models\Setting;

class VtuDataService
{
    protected static $plansCache = null;

    /**
     * Get all raw plans from JSON config.
     */
    public static function getRawPlans(): array
    {
        if (self::$plansCache === null) {
            $path = config_path('vtu_data_plans.json');
            if (file_exists($path)) {
                self::$plansCache = json_decode(file_get_contents($path), true) ?: [];
            } else {
                self::$plansCache = [];
            }
        }
        return self::$plansCache;
    }

    /**
     * Get base wholesale price for a specific network and plan ID.
     */
    public static function getPlanBasePrice($network, $planId): ?float
    {
        $plans = self::getRawPlans();
        $net = (string) $network;

        if (isset($plans[$net])) {
            foreach ($plans[$net] as $plan) {
                if ($plan['id'] == $planId) {
                    return (float) $plan['price'];
                }
            }
        }

        return null;
    }

    /**
     * Get customer selling price (with markup included) for a specific network and plan ID.
     */
    public static function getCustomerPrice($network, $planId): ?float
    {
        $basePrice = self::getPlanBasePrice($network, $planId);
        if ($basePrice === null) {
            return null;
        }

        $markupRate = (float) Setting::get('markup_vtu_data', 0);
        $multiplier = 1 + ($markupRate / 100);

        return round($basePrice * $multiplier, 2);
    }

    /**
     * Get all plans with customer prices already applied (filtering out unsupported/provider references).
     */
    public static function getPlansWithCustomerPrices(): array
    {
        $rawPlans = self::getRawPlans();
        $markupRate = (float) Setting::get('markup_vtu_data', 0);
        $multiplier = 1 + ($markupRate / 100);

        $result = [];
        foreach ($rawPlans as $net => $plans) {
            $result[$net] = [];
            foreach ($plans as $plan) {
                // Filter out any provider mentions
                if (stripos($plan['name'], 'gongoz') !== false) {
                    continue;
                }

                $basePrice = (float) $plan['price'];
                $finalPrice = round($basePrice * $multiplier, 2);
                $result[$net][] = [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'price' => $finalPrice,
                ];
            }
        }

        return $result;
    }

    /**
     * Get JSON representation of customer-ready plans.
     */
    public static function getPlansJsonWithCustomerPrices(): string
    {
        return json_encode(self::getPlansWithCustomerPrices());
    }
}
