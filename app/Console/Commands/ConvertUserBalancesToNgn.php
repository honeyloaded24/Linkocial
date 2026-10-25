<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ConvertUserBalancesToNgn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:convert-to-ngn 
                            {rate=1450 : The historical conversion rate from USD to NGN} 
                            {--max-threshold=200 : Only convert balances <= this threshold (assumed USD)}
                            {--dry-run : Preview changes without updating the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely restore legacy USD-divided user wallet balances back to NGN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rate = (float) $this->argument('rate');
        $threshold = (float) $this->option('max-threshold');
        $isDryRun = $this->option('dry-run');

        if ($rate <= 0) {
            $this->error("Invalid conversion rate specified: {$rate}");
            return Command::FAILURE;
        }

        $this->info("Scanning users with balance > 0 and <= {$threshold} (legacy USD denomination)...");
        if ($isDryRun) {
            $this->warn("DRY RUN MODE: No user wallet balances will be modified.");
        }

        $users = User::where('balance', '>', 0)
            ->where('balance', '<=', $threshold)
            ->get();

        $totalFound = $users->count();

        if ($totalFound === 0) {
            $this->info("No legacy USD user balances found below threshold {$threshold}. All active balances appear to already be in NGN.");
            return Command::SUCCESS;
        }

        $this->info("Found {$totalFound} user balance(s) eligible for conversion at 1 USD = {$rate} NGN.");

        $bar = $this->output->createProgressBar($totalFound);
        $bar->start();

        $convertedCount = 0;
        foreach ($users as $user) {
            $oldBalance = (float) $user->balance;
            $newBalance = round($oldBalance * $rate, 2);

            if (!$isDryRun) {
                $user->update([
                    'balance' => $newBalance,
                    'currency_code' => 'NGN'
                ]);
            }

            $convertedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE: Would restore {$convertedCount} user wallet balances to NGN.");
        } else {
            $this->info("SUCCESS: Restored {$convertedCount} user wallet balances to NGN using rate {$rate}.");
        }

        return Command::SUCCESS;
    }
}
