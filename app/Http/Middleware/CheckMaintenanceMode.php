<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = \App\Models\Setting::get('maintenance_mode', '0') === '1';
        
        if ($isMaintenance && !$request->is('login', 'logout', 'hq/*')) {
            if (!auth()->check() || !auth()->user()->is_admin) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'System is under maintenance. Please try again later.'], 503);
                }
                return response()->view('errors.maintenance', [], 503);
            }
        }

        return $next($request);
    }
}
