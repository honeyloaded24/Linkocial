<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_number_orders', function (Blueprint $table) {
            $table->timestamp('cancel_available_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_number_orders', function (Blueprint $table) {
            $table->dropColumn('cancel_available_at');
        });
    }
};
