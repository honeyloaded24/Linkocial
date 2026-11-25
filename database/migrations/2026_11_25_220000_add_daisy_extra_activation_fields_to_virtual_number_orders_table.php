<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_number_orders', function (Blueprint $table) {
            $table->string('daisy_extra_activation_id')->nullable();
            $table->string('daisy_extra_activation_status', 24)->nullable();
            $table->timestamp('daisy_extra_activation_requested_at')->nullable();
            $table->decimal('daisy_extra_activation_fee', 12, 4)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('virtual_number_orders', function (Blueprint $table) {
            $table->dropColumn([
                'daisy_extra_activation_id',
                'daisy_extra_activation_status',
                'daisy_extra_activation_requested_at',
                'daisy_extra_activation_fee',
            ]);
        });
    }
};
