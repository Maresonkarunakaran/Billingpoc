<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMasterAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_master_admin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Master admin access required.'], 403);
            }

            abort(403, 'Unauthorized access. Software Master Admin privileges are required to view this area.');
        }

        return $next($request);
    }
}
