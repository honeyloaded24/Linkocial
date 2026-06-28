<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "--- Migration Status ---\n";
try {
    $migrations = DB::table('migrations')->get();
    foreach ($migrations as $m) {
        echo "Migration: " . $m->migration . "\n";
    }
} catch (\Exception $e) {
    echo "Error fetching migrations: " . $e->getMessage() . "\n";
}

echo "\n--- Columns in virtual_number_orders ---\n";
try {
    $columns = Schema::getColumnListing('virtual_number_orders');
    print_r($columns);
} catch (\Exception $e) {
    echo "Error listing columns: " . $e->getMessage() . "\n";
}
