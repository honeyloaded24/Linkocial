<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all(['id', 'email', 'username', 'role']);
foreach($users as $user) {
    echo "ID: {$user->id} | Email: {$user->email} | Username: {$user->username} | Role: {$user->role}\n";
}
