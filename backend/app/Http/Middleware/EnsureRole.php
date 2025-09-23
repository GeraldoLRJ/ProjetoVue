<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureRole
{
    /**
     * Handle an incoming request.
     * Usage: middleware('role:master') or middleware('role:admin,user')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth('api')->user();
        if (!$user) {
            throw new AccessDeniedHttpException('Unauthenticated');
        }

        $allowed = [];
        foreach ($roles as $arg) {
            foreach (explode(',', (string)$arg) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $allowed[] = $piece;
                }
            }
        }

        if (!in_array($user->role, $allowed, true)) {
            throw new AccessDeniedHttpException('Forbidden: insufficient role');
        }

        return $next($request);
    }
}
