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
        // 1. Clamp any existing negative balances back to 0.0000
        DB::table('users')->where('balance', '<', 0)->update(['balance' => 0]);

        // 2. Attempt to add a CHECK constraint to guarantee balance cannot drop below 0 at database level
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_balance_non_negative CHECK (balance >= 0)');
            } elseif ($driver === 'sqlite') {
                // SQLite supports CHECK constraints during table creation, but DB queries in Laravel will already be protected by atomic deductBalance
            }
        } catch (\Throwable $e) {
            // Silently ignore if check constraint is already present or unsupported by database version
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE users DROP CHECK chk_users_balance_non_negative');
            }
        } catch (\Throwable $e) {
            // Ignore on rollback failure
        }
    }
};
