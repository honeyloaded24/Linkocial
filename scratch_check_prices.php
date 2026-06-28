<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fivesim = app(App\Services\FiveSimService::class);
try {
    $prices = $fivesim->getPrices('nigeria', 'tiktok');
    echo "PRICES DATA:\n" . json_encode($prices, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
