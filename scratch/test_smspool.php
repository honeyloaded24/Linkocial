<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\SmsPoolService();

try {
    echo "--- Balance ---\n";
    print_r($service->getBalance());
    
    echo "--- Countries (first 5) ---\n";
    $countries = $service->getCountries();
    print_r(array_slice($countries, 0, 5, true));
    
    echo "--- Pricing for country 1 (first 5) ---\n";
    $prices = $service->getPricesForCountry(1);
    print_r(array_slice($prices, 0, 5));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
