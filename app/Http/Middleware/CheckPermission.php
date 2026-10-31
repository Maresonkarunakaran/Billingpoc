<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('pos/search') || $request->is('pos/checkout')) {
                return response()->json([
                    'error' => 'Access Denied',
                    'message' => 'Unauthorized action: missing permission ['.$permission.']',
                ], 403);
            }

            abort(403, 'Access Denied: You do not have permission to access this page.');
        }

        return $next($request);
    }
}
