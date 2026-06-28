<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$response = Http::get('https://api.smspool.net/service/retrieve_all');
echo "Status: " . $response->status() . "\n";
$arr = json_decode($response->body(), true);
echo "Count: " . (is_array($arr) ? count($arr) : 'not an array') . "\n";
if (is_array($arr) && count($arr) > 0) {
    echo "First 5 items:\n" . json_encode(array_slice($arr, 0, 5), JSON_PRETTY_PRINT) . "\n";
}
