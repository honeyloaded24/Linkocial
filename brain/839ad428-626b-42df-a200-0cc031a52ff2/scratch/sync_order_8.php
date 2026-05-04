<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Models\Transaction;

// Target Order #8 from the user's screenshot
$orderId = 8;
$order = Order::find($orderId);

if (!$order) {
    echo "Order #$orderId not found.\n";
    exit;
}

echo "Order found. Current Status: {$order->status}, Refunded: " . ($order->is_refunded ? 'Yes' : 'No') . "\n";

// The provider ID from the user's SmartPanel screenshot
$providerOrderId = '60862512';

if ($order->status === 'failed' && $order->is_refunded) {
    echo "This looks like a 'Ghost Success' case.\n";
    
    // 1. Re-debit the user if they were accidentally refunded
    $user = User::find($order->user_id);
    if ($user) {
        echo "Re-debiting user {$user->email} for the amount: {$order->charge}\n";
        $user->decrement('balance', (float)$order->charge);
        
        Transaction::add(
            $user->id,
            $order->charge,
            'debit',
            'order_correction',
            "Re-debit for order #{$order->id} (Correction of accidental auto-refund after successful provider submission)"
        );
    }

    // 2. Sync with provider data
    echo "Updating order status to 'pending' and attaching provider ID $providerOrderId\n";
    $order->update([
        'status' => 'pending',
        'provider_order_id' => $providerOrderId,
        'is_refunded' => false,
        'error_log' => 'Manually synced after accidental auto-refund.'
    ]);

    echo "Sync complete! Order #$orderId is now 'pending' and tracked.\n";
} else {
    echo "Order #$orderId does not meet the 'Ghost Success' criteria for automatic fix.\n";
}
