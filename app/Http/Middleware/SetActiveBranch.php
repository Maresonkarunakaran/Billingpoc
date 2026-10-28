<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $activeBranchId = session('active_branch_id');

            if (! $activeBranchId) {
                $defaultBranch = $user->default_branch_id
                    ?? $user->branches()->first()?->id;

                if ($defaultBranch) {
                    session(['active_branch_id' => $defaultBranch]);
                }
            }
        }

        return $next($request);
    }
}
