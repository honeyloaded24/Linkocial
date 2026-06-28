<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\VirtualNumberController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Create a dummy user or use an existing user
$user = User::first();
if (!$user) {
    echo "Creating dummy user...\n";
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@test.com',
        'password' => bcrypt('password'),
        'username' => 'testuser',
        'role' => 'user',
        'balance' => 100.00,
    ]);
}

Auth::login($user);
echo "Logged in as: " . $user->email . " (Balance: " . $user->balance . ")\n\n";

$controller = app(VirtualNumberController::class);

echo "--- Testing getCountries() ---\n";
$countriesResponse = $controller->getCountries();
$countriesData = json_decode($countriesResponse->content(), true);
echo "Number of countries returned: " . count($countriesData) . "\n";
if (count($countriesData) > 0) {
    echo "First country sample:\n";
    print_r($countriesData[0]);
}

echo "\n--- Testing getProducts() for Country 1 ---\n";
$request = Request::create('/api/virtual-numbers/products', 'GET', ['country' => 1]);
$productsResponse = $controller->getProducts($request);
$productsData = json_decode($productsResponse->content(), true);
echo "Number of products returned for US (Country 1): " . count($productsData) . "\n";
if (count($productsData) > 0) {
    echo "First 3 products sample:\n";
    print_r(array_slice($productsData, 0, 3));
}
