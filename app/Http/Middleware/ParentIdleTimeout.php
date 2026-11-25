<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Sesi parent mengikuti sesi anak (30 hari, agar anak tak sering input PIN), tapi parent otomatis keluar bila diam 2 jam. */
class ParentIdleTimeout
{
    private const IDLE_SECONDS = 7200;

    public function handle(Request $request, Closure $next): Response
    {
        // Rute chat dipakai bersama anak; sesi anak tidak boleh kena batas ini.
        if (! $request->user()?->isParent()) {
            return $next($request);
        }

        $last = $request->session()->get('parent_last_seen');

        if ($last && now()->timestamp - $last > self::IDLE_SECONDS) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        $request->session()->put('parent_last_seen', now()->timestamp);

        return $next($request);
    }
}
