<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route($role === 'parent' ? 'login' : 'child.pick');
        }

        abort_unless($user->role->value === $role, 403);

        return $next($request);
    }
}
