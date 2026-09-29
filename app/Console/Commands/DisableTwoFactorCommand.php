<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DisableTwoFactorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:disable-2fa 
                            {--all : Disable 2FA for all users across the platform}
                            {--user= : Disable 2FA for a specific user by email, username, or ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely disable Two-Factor Authentication (2FA) for all users or a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $all = $this->option('all');
        $userInput = $this->option('user');

        if (!$all && !$userInput) {
            $totalWith2fa = User::where('two_factor_enabled', true)->count();
            $this->warn("Currently {$totalWith2fa} user(s) have 2FA enabled.");
            $this->info("Usage:");
            $this->line("  php artisan users:disable-2fa --all          (Disables 2FA for all users)");
            $this->line("  php artisan users:disable-2fa --user=email   (Disables 2FA for a specific user)");
            return 0;
        }

        if ($all) {
            if (!$this->confirm('Are you sure you want to disable 2FA for ALL users across the platform?', true)) {
                $this->info('Operation cancelled.');
                return 0;
            }

            $affected = User::where('two_factor_enabled', true)
                ->orWhereNotNull('two_factor_code')
                ->update([
                    'two_factor_enabled' => false,
                    'two_factor_code' => null,
                    'two_factor_expires_at' => null,
                ]);

            $this->info("SUCCESS: 2FA has been disabled and cleared for {$affected} user(s).");
            return 0;
        }

        if ($userInput) {
            $user = User::where('email', $userInput)
                ->orWhere('username', $userInput)
                ->orWhere('id', $userInput)
                ->first();

            if (!$user) {
                $this->error("User '{$userInput}' not found.");
                return 1;
            }

            $user->update([
                'two_factor_enabled' => false,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ]);

            $this->info("SUCCESS: 2FA has been disabled for {$user->username} ({$user->email}).");
            return 0;
        }

        return 0;
    }
}
