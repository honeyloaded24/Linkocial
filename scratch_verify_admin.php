<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'awogorm@gmail.com';
$user = User::where('email', $email)->first();

if ($user) {
    $user->email_verified_at = now();
    $user->two_factor_enabled = false;
    $user->save();
    echo "SUCCESS: User $email has been verified and 2FA has been disabled.\n";
} else {
    echo "ERROR: User $email not found.\n";
}
