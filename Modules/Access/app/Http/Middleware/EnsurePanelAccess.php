<?php

namespace Modules\Access\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelAccess
{
    public function handle(Request $request, Closure $next, string $panel): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // اگر از spatie استفاده می‌کنی:
        if (! method_exists($user, 'hasRole') || ! $user->hasRole($panel)) {
            return response()->json(['message' => 'Forbidden panel access.'], 403);
        }

        return $next($request);
    }
}

