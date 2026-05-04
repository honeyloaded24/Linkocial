<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emails = ['testdoe@test.com', 'awogor@fyncore.com', 'property9jacom@gmail.com'];
$count = \App\Models\User::whereIn('email', $emails)->update([
    'email_verified_at' => now(),
    'two_factor_enabled' => false
]);

echo "SUCCESS: $count users have been verified and 2FA has been disabled.\n";
