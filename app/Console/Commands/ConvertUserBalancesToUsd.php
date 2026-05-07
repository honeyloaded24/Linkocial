<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ConvertUserBalancesToUsd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:convert-to-usd {rate=1450 : The conversion rate from NGN to USD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-time migration to convert user balance from NGN to USD base';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rate = (float) $this->argument('rate');
        
        if ($rate <= 0) {
            $this->error("Invalid rate!");
            return;
        }

        $this->info("Starting user balance conversion using rate: 1 USD = {$rate} NGN");

        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            $oldBalance = $user->balance;
            $newBalance = $oldBalance / $rate;
            
            $user->update([
                'balance' => $newBalance,
                'currency_code' => 'NGN' // Keep display as NGN for existing users
            ]);
            
            $count++;
        }

        $this->info("Successfully converted {$count} user balances to USD base and set their display currency to NGN.");
    }
}
