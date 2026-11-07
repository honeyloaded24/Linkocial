<?php

namespace App\Console\Commands;

use App\Services\AiPlugService;
use Illuminate\Console\Command;

class SyncAiPlugCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aiplug:sync';

    /**
     * The console command name aliases.
     *
     * @var array
     */
    protected $aliases = ['sync:aiplug-catalog'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize AI Marketplace live catalog and prices from AIPlug';

    /**
     * Execute the console command.
     */
    public function handle(AiPlugService $aiPlugService): int
    {
        $this->info('Connecting to AIPlug to synchronize AI Marketplace catalog...');

        if (!$aiPlugService->isConfigured()) {
            $this->error('AIPlug API key is not configured. Set it in Admin Settings or .env (AIPLUG_API_KEY).');
            return Command::FAILURE;
        }

        try {
            $result = $aiPlugService->syncCatalog();

            if (!$result['success']) {
                $this->error('Sync failed: ' . ($result['message'] ?? 'Unknown error'));
                return Command::FAILURE;
            }

            $this->info($result['message']);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Exception during sync: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
