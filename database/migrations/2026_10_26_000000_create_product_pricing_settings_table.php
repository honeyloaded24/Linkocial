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
        if (!Schema::hasTable('product_pricing_settings')) {
            Schema::create('product_pricing_settings', function (Blueprint $table) {
                $table->id();
                $table->string('product_key', 50)->unique();
                $table->string('currency', 10)->default('USD');
                $table->decimal('fix_rate', 14, 4)->default(1500.0000);
                $table->string('pricing_mode', 30)->default('percentage');
                $table->decimal('percentage_markup', 8, 2)->default(0.00);
                $table->decimal('fixed_markup', 14, 2)->default(0.00);
                $table->decimal('min_selling_price', 14, 2)->nullable();
                $table->decimal('min_profit', 14, 2)->nullable();
                $table->string('rounding_rule', 30)->default('2_decimal');
                $table->timestamps();
            });
        }

        // Initialize default configurations safely preserving existing product prices
        $defaultUsdRate = (float) (\App\Models\Setting::get('usd_to_ngn_rate') ?: \App\Models\Setting::get('provider_usd_rate', 1500.0));
        $vnMarkupNgn = (float) \App\Models\Setting::get('vn_markup_ngn', 1500.0);
        $logsMarkup = (float) \App\Models\Setting::get('markup_logs_market', 20.0);
        $vtuDataMarkup = (float) \App\Models\Setting::get('markup_vtu_data', 0.0);

        $initials = [
            'vtu' => [
                'currency' => 'NGN',
                'fix_rate' => $defaultUsdRate,
                'pricing_mode' => 'percentage',
                'percentage_markup' => $vtuDataMarkup,
                'fixed_markup' => 0.00,
                'min_selling_price' => null,
                'min_profit' => null,
                'rounding_rule' => '2_decimal',
            ],
            'logs' => [
                'currency' => 'NGN',
                'fix_rate' => $defaultUsdRate,
                'pricing_mode' => 'percentage',
                'percentage_markup' => $logsMarkup,
                'fixed_markup' => 0.00,
                'min_selling_price' => null,
                'min_profit' => null,
                'rounding_rule' => '2_decimal',
            ],
            'virtual_numbers' => [
                'currency' => 'USD',
                'fix_rate' => (float) (\App\Models\Setting::get('provider_usd_rate', $defaultUsdRate)),
                'pricing_mode' => 'fixed',
                'percentage_markup' => 0.00,
                'fixed_markup' => $vnMarkupNgn,
                'min_selling_price' => null,
                'min_profit' => null,
                'rounding_rule' => '2_decimal',
            ],
        ];

        foreach ($initials as $key => $vals) {
            if (!DB::table('product_pricing_settings')->where('product_key', $key)->exists()) {
                DB::table('product_pricing_settings')->insert(array_merge($vals, [
                    'product_key' => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_pricing_settings');
    }
};
