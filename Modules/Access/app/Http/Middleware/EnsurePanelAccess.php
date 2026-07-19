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
            abort(401, 'Unauthenticated.');
        }

        $allowed = match ($panel) {
            'customer' => $user->hasRole('customer'),
            'clinic' => $user->hasRole('clinic'),
            'admin' => $user->hasAnyRole(['admin', 'expert']),
            default => false,
        };

        if (! $allowed) {
            abort(403, 'شما به این پنل دسترسی ندارید.');
        }

        return $next($request);
    }
}
