<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectTo(
            guests: '/login',
            users: '/dashboard'
        );

        // Alias custom middleware
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Exempt API and payment webhooks from CSRF protection
        $middleware->preventRequestForgery(except: [
            '/api/*',
            '/api',
            '/api/v1',
            '/v1',
            '/api/webhooks/korapay',
            '/api/webhooks/billstack',
            '/webhooks/aiplug',
            '/api/webhooks/aiplug',
        ]);

        $middleware->append(\App\Http\Middleware\CheckMaintenanceMode::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Routing\Exceptions\InvalidSignatureException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your link is invalid or has expired.'], 403);
            }
            return redirect()->route('login')->with('error', 'Your verification link has expired or is invalid. Please log in to request a fresh link.');
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
            if (\Illuminate\Support\Facades\Auth::check()) {
                return redirect()->route('dashboard')->with('error', 'Unauthorized action: ' . ($e->getMessage() ?: 'You do not have permission for this request.'));
            }
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        });
    })->create();
