<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orderId = 8;
$order = Order::find($orderId);

if (!$order) {
    echo "Order #$orderId not found.\n";
    exit;
}

echo "Order #$orderId Financials:\n";
echo "Charge: " . $order->charge . "\n";
echo "Provider Cost: " . $order->provider_cost . "\n";
echo "Profit: " . ($order->charge - $order->provider_cost) . "\n";
