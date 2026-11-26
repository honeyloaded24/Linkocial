<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('virtual_number_orders')
            ->where('provider_key', 'daisysms')
            ->where('server', 'server3')
            ->update([
                'server' => 'server4',
                'operator' => 'Server 4',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('virtual_number_orders')
            ->where('provider_key', 'daisysms')
            ->where('server', 'server4')
            ->update([
                'server' => 'server3',
                'operator' => 'Server 3',
                'updated_at' => now(),
            ]);
    }
};
