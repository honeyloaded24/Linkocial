<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\SmsPoolService();

try {
    // Attempting to buy a number for service 9999 (invalid service) in country 1
    // to trigger SMSPool's API error or balance check
    $service->buyNumber(1, 9999);
} catch (\Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n";
}
