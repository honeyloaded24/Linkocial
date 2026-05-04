<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$email = 'awogor@fyncore.com';
$user = User::where('email', $email)->first();

if ($user) {
    $user->update([
        'role' => 'admin',
        'is_admin' => true,
        'email_verified_at' => now()
    ]);
    echo "SUCCESS: $email is now a verified ADMIN HQ user.\n";
} else {
    echo "ERROR: User with email $email not found.\n";
}
