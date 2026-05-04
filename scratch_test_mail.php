<?php

use Illuminate\Support\Facades\Mail;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Mail::raw('Congratulations! Your SMTP configuration for Linkocial is working perfectly.', function ($message) {
        $message->to('awogorm@gmail.com')
                ->subject('Linkocial SMTP Test');
    });
    echo "Success! Test email sent to awogorm@gmail.com\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
