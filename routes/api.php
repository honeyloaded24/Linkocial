<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Return current user details
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Orders API
    Route::post('/orders', [OrderController::class, 'store']);
    
    // Payments API (Initiate)
    Route::post('/payments/deposit', [PaymentController::class, 'initializeDeposit']);
});

// Public Webhook Routes
// Note: Ensure this route is excluded from VerifyCsrfToken middleware in bootstrap/app.php or app/Http/Middleware/VerifyCsrfToken.php
// Route::post('/webhooks/paystack', [PaymentController::class, 'webhook'])->name('payment.callback');
Route::post('/webhooks/korapay', [PaymentController::class, 'korapayWebhook']);
Route::post('/webhooks/heleket', [PaymentController::class, 'heleketWebhook']);
Route::post('/webhooks/vtu', [\App\Http\Controllers\Api\VtuWebhookController::class, 'handle']);

// Standard SMM Reseller API (v1) - Rate Limited
Route::middleware('throttle:api')->group(function () {
    Route::post('/v1', [App\Http\Controllers\ResellerApiController::class, 'handle']);
    Route::get('/v1', [App\Http\Controllers\ResellerApiController::class, 'handle']);
});

// Public Marketing API (v1)
Route::get('/v1/public-stats', [App\Http\Controllers\Api\PublicStatsController::class, 'index'])->name('api.public-stats');
