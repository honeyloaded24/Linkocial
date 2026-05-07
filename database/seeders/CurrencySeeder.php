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
        $currencies = [
            [
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1.0,
                'is_default' => true,
                'is_active' => true
            ],
            [
                'code' => 'NGN',
                'symbol' => '₦',
                'exchange_rate' => 1450.0,
                'is_default' => false,
                'is_active' => true
            ],
            [
                'code' => 'GHS',
                'symbol' => 'GH₵',
                'exchange_rate' => 14.80,
                'is_default' => false,
                'is_active' => true
            ],
            [
                'code' => 'KES',
                'symbol' => 'KSh',
                'exchange_rate' => 129.50,
                'is_default' => false,
                'is_active' => true
            ],
            [
                'code' => 'ZAR',
                'symbol' => 'R',
                'exchange_rate' => 18.20,
                'is_default' => false,
                'is_active' => true
            ],
            [
                'code' => 'XAF',
                'symbol' => 'FCFA',
                'exchange_rate' => 605.0,
                'is_default' => false,
                'is_active' => true
            ],
            [
                'code' => 'XOF',
                'symbol' => 'CFA',
                'exchange_rate' => 605.0,
                'is_default' => false,
                'is_active' => true
            ],
            [
                'code' => 'EGP',
                'symbol' => 'E£',
                'exchange_rate' => 48.30,
                'is_default' => false,
                'is_active' => true
            ],
            [
                'code' => 'TZS',
                'symbol' => 'TSh',
                'exchange_rate' => 2600.0,
                'is_default' => false,
                'is_active' => true
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
