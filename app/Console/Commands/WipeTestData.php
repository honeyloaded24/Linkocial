<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class WipeTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:wipe-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe all test data (Users, Orders, Tickets, Logs) while preserving Infrastructure (Providers, Services, Blogs, Settings)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('WARNING: This will delete ALL users, orders, tickets, and logs. Are you sure?')) {
            return;
        }

        $this->info('🚀 Starting Production Hardening: Database Sanitization...');

        // 1. Wipe Transactional Tables
        $tablesToWipe = [
            'orders',
            'order_logs',
            'payments',
            'login_logs',
            'activity_logs',
            'transactions',
            'tickets',
            'ticket_messages',
            'withdrawal_requests',
            'notifications',
            'affiliate_transactions',
            'cache',
            'jobs',
        ];

        foreach ($tablesToWipe as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("✅ Wiped table: {$table}");
            }
        }

        // 2. Wipe ALL Users
        $deletedUsers = User::query()->delete();
        $this->info("👤 User Sanitization: Deleted {$deletedUsers} users (Total Wipe).");

        // 3. Keep Infrastructure
        $this->info('🛡️ Infrastructure Preservation: Skipped providers, services, blogs, and settings.');

        $this->info('✨ Database is now Production-Ready!');
    }
}
