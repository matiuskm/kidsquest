<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Setelah akunnya dibuka dari kunci, anak harus ganti PIN sebelum memakai app. */
class ForcePinChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_pin && ! $request->routeIs('child.profile', 'child.profile.update')) {
            return redirect()->route('child.profile');
        }

        return $next($request);
    }
}
