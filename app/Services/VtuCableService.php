<?php

namespace App\Services;

use App\Models\Setting;

class VtuCableService
{
    /**
     * Official Gongozconcept Cable TV Plans and Wholesale Base Prices in NGN
     */
    protected static array $plans = [
        // GOTV (Provider ID: 1)
        '1' => [
            95 => ['package' => 'GOtv Smallie - Monthly', 'price' => 1900],
            93 => ['package' => 'GOtv Jinja', 'price' => 3900],
            96 => ['package' => 'gotv-smallie-3months/quarterly', 'price' => 5100],
            94 => ['package' => 'GOtv Jolli', 'price' => 5800],
            92 => ['package' => 'GOtv Max', 'price' => 8500],
            97 => ['package' => 'GOtv Smallie - Yearly', 'price' => 15000],
        ],
        // DSTV (Provider ID: 2)
        '2' => [
            74 => ['package' => 'DStv Padi', 'price' => 4400],
            75 => ['package' => 'DStv Yanga', 'price' => 6000],
            85 => ['package' => 'DStv Padi + ExtraView', 'price' => 10400],
            86 => ['package' => 'DStv Padi + ExtraView', 'price' => 10400],
            76 => ['package' => 'Dstv Confam', 'price' => 11000],
            84 => ['package' => 'DStv Yanga + ExtraView', 'price' => 12000],
            83 => ['package' => 'DStv Confam + ExtraView', 'price' => 17000],
            79 => ['package' => 'DStv Asia', 'price' => 18400],
            77 => ['package' => 'DStv Compact', 'price' => 19000],
            88 => ['package' => 'DStv Compact + Extra View', 'price' => 25000],
            89 => ['package' => 'DStv Compact + French Touch', 'price' => 26000],
            80 => ['package' => 'DStv Compact plus', 'price' => 30000],
            82 => ['package' => 'DSTV PREMIUM-ASIA', 'price' => 50500],
            90 => ['package' => 'DStv Premium + Extra View', 'price' => 50500],
            81 => ['package' => 'DStv Premium-French', 'price' => 69000],
        ],
        // STARTIME (Provider ID: 3)
        '3' => [
            103 => ['package' => 'Nova - 600 Naira - 1 Week', 'price' => 600],
            104 => ['package' => 'Basic - 1250 Naira - 1 Week', 'price' => 1250],
            105 => ['package' => 'Smart - 1300 Naira - 1 Week', 'price' => 1300],
            91  => ['package' => 'NOVA 1 MONTH', 'price' => 2100],
            101 => ['package' => 'Nova - 2,100 Naira - 1 Month', 'price' => 2100],
            106 => ['package' => 'Classic - 2300 Naira - 1 Week', 'price' => 2300],
            107 => ['package' => 'Super - 3000 Naira - 1 Week', 'price' => 3000],
            100 => ['package' => 'Smart - 3800 Naira - 1 Month', 'price' => 3800],
            99  => ['package' => 'Basic - 5,100 Naira - 1 Month', 'price' => 5100],
            98  => ['package' => 'Classic - 6800 Naira - 1 Month', 'price' => 6800],
            102 => ['package' => 'Super - 9000 Naira - 1 Month', 'price' => 9000],
        ],
    ];

    /**
     * Get base wholesale price for a cable provider and plan ID
     */
    public static function getPlanBasePrice($cablename, $planId): ?float
    {
        $cableKey = (string) $cablename;
        // Normalize name to ID if passed as string name
        $nameMap = ['gotv' => '1', 'dstv' => '2', 'startime' => '3'];
        if (isset($nameMap[strtolower($cableKey)])) {
            $cableKey = $nameMap[strtolower($cableKey)];
        }

        $intPlanId = (int) $planId;

        if (isset(self::$plans[$cableKey][$intPlanId])) {
            return (float) self::$plans[$cableKey][$intPlanId]['price'];
        }

        // Search across all providers as fallback in case cable ID is generic
        foreach (self::$plans as $prov => $planList) {
            if (isset($planList[$intPlanId])) {
                return (float) $planList[$intPlanId]['price'];
            }
        }

        return null;
    }

    /**
     * Get customer selling price (including admin markup)
     */
    public static function getCustomerPrice($cablename, $planId): ?float
    {
        $basePrice = self::getPlanBasePrice($cablename, $planId);
        if ($basePrice === null) {
            return null;
        }

        try {
            $calc = app(ProductPricingService::class)->calculateSellingPrice($basePrice, ProductPricingService::PRODUCT_VTU);
            return (float) $calc['selling_price'];
        } catch (\Throwable $e) {
            $markupRate = (float) Setting::get('markup_vtu_cable', 0);
            return round($basePrice * (1 + ($markupRate / 100)), 2);
        }
    }
}
