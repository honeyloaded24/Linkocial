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
        $this->error('This legacy balance conversion command is disabled because it changes wallet balances without transactional ledger entries.');
        $this->line('Use wallet:recover-legacy-balances for audited, idempotent recovery or the Admin currency reconciliation workflow.');
        return Command::FAILURE;
    }
}
