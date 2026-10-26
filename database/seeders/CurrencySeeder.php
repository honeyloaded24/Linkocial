<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete all other currencies except NGN
        Currency::where('code', '!=', 'NGN')->delete();

        $currencies = [
            [
                'code' => 'NGN',
                'symbol' => '₦',
                'exchange_rate' => 1450.0,
                'is_default' => true,
                'is_active' => true
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        \App\Models\Setting::updateOrCreate(
            ['key' => 'usd_to_ngn_rate'],
            ['value' => '1500']
        );
        \App\Models\Setting::updateOrCreate(
            ['key' => 'ngn_to_usd_rate'],
            ['value' => '0.0006666667']
        );
    }
}
