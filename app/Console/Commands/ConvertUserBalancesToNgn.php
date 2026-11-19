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
        $this->error('This legacy balance conversion command is disabled because it changes wallet balances without transactional ledger entries.');
        $this->line('Use wallet:recover-legacy-balances for audited, idempotent recovery or the Admin currency reconciliation workflow.');
        return Command::FAILURE;
    }
}
