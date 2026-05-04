<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Service;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

// 1. Get or Create a Test User
$email = 'simulated_test@linkocial.test';
$user = User::where('email', $email)->first();

if (!$user) {
    $user = User::create([
        'first_name' => 'Simulation',
        'last_name' => 'Tester',
        'username' => 'sim_tester',
        'email' => $email,
        'password' => bcrypt('password123'),
        'balance' => 2000.00, // Pre-fund with $2000 so we have enough
    ]);
} else {
    $user->update(['balance' => 2000.00]); // Reset balance
}

echo "TEST USER: {$user->email} | Current Balance: {$user->balance}\n";

// 2. Find an Active Service
$service = Service::where('is_active', true)->first();

if (!$service) {
    echo "ERROR: No active service found to test with.\n";
    exit;
}

echo "TEST SERVICE: {$service->name} | Price: {$service->selling_price}\n";

// 3. Simulate placing an order
$quantity = 1000;
$charge = ($service->selling_price / 1000) * $quantity;

echo "ORDER: Quantity $quantity | Total Charge: $charge\n";

DB::transaction(function () use ($user, $service, $quantity, $charge) {
    // Deduct Balance
    $user->decrement('balance', $charge);
    $user->refresh(); // Get updated balance

    // Create Order
    $order = Order::create([
        'user_id' => $user->id,
        'service_id' => $service->id,
        'link' => 'https://social.link/test-profile',
        'quantity' => $quantity,
        'charge' => $charge,
        'status' => 'pending',
    ]);

    // Use the official helper to create Transaction Log
    // Transaction::add($userId, $amount, $type, $txType, $description, $ref)
    Transaction::add(
        $user->id, 
        $charge, 
        'debit', 
        'order', 
        "Order #{$order->id} - {$service->name}"
    );

    echo "SUCCESS: Order #{$order->id} created and transaction logged.\n";
    echo "FINAL BALANCE: {$user->balance}\n";
});

echo "SIMULATION COMPLETE.\n";
