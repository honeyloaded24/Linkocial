<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class CleanOrphanedServices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'smm:clean-orphans';

    /**
     * The console command description.
     */
    protected $description = 'Clean up database SMM services and mappings that reference deleted/non-existent providers and have no orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database cleanup of orphaned SMM services...');

        // 1. Delete mappings referencing non-existent SMM providers
        $badMappingsCount = DB::table('provider_service_mappings')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('providers')
                      ->whereRaw('providers.id = provider_service_mappings.provider_id');
            })
            ->delete();

        $this->info("Deleted {$badMappingsCount} orphaned provider service mappings (where provider was deleted).");

        // 2. Find and delete SMM services that have no mappings and no orders
        $orphanedServices = Service::whereDoesntHave('mappings')
            ->whereDoesntHave('orders')
            ->get();

        $orphansCount = $orphanedServices->count();

        foreach ($orphanedServices as $service) {
            $service->delete();
        }

        $this->info("Deleted {$orphansCount} SMM services with no active provider mappings and no orders.");
        $this->info('Database cleanup completed successfully!');
    }
}
