<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('currencies')) {
            // Delete all other currencies except NGN
            DB::table('currencies')->where('code', '!=', 'NGN')->delete();

            // Ensure NGN is the active and default currency
            $ngn = DB::table('currencies')->where('code', 'NGN')->first();
            if ($ngn) {
                DB::table('currencies')->where('code', 'NGN')->update([
                    'exchange_rate' => 1.0000,
                    'is_default' => true,
                    'is_active' => true,
                ]);
            } else {
                DB::table('currencies')->insert([
                    'code' => 'NGN',
                    'symbol' => '₦',
                    'exchange_rate' => 1.0000,
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Set all existing users' currency to NGN
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'currency_code')) {
            DB::table('users')->where('currency_code', '!=', 'NGN')->update([
                'currency_code' => 'NGN',
            ]);
        }

        // Ensure single currency settings exist in settings table
        if (Schema::hasTable('settings')) {
            $defaultSettings = [
                'currency' => 'NGN',
                'currency_symbol' => '₦',
                'usd_to_ngn_rate' => '1500',
                'ngn_to_usd_rate' => '0.0006666667',
                'provider_usd_rate' => '1500',
                'vn_markup_ngn' => '1500',
                'affiliate_min_payout' => '5000',
            ];

            foreach ($defaultSettings as $key => $val) {
                if (!DB::table('settings')->where('key', $key)->exists()) {
                    DB::table('settings')->insert([
                        'key' => $key,
                        'value' => $val,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe rollback: restore USD currency row if needed
        if (Schema::hasTable('currencies')) {
            if (!DB::table('currencies')->where('code', 'USD')->exists()) {
                DB::table('currencies')->insert([
                    'code' => 'USD',
                    'symbol' => '$',
                    'exchange_rate' => 1.0000,
                    'is_default' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
