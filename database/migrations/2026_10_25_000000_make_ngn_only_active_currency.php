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
                    'is_default' => true,
                    'is_active' => true,
                ]);
            } else {
                DB::table('currencies')->insert([
                    'code' => 'NGN',
                    'symbol' => '₦',
                    'exchange_rate' => 1450.0000,
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
