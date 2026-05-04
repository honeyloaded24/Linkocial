<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('username');
            $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null')->after('referral_code');
            $table->decimal('commission_balance', 15, 4)->default(0)->after('balance');
            $table->decimal('total_earned_commission', 15, 4)->default(0)->after('commission_balance');
            $table->decimal('total_withdrawn_commission', 15, 4)->default(0)->after('total_earned_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'referral_code', 
                'referred_by', 
                'commission_balance', 
                'total_earned_commission', 
                'total_withdrawn_commission'
            ]);
        });
    }
};
