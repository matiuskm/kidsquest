<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Sesi parent mengikuti sesi anak (30 hari), tapi parent otomatis keluar bila diam: 2 jam di HP sendiri, 10 menit di HP lain. */
class ParentIdleTimeout
{
    private const OWN_DEVICE_SECONDS = 7200;

    private const OTHER_DEVICE_SECONDS = 600;

    public function handle(Request $request, Closure $next): Response
    {
        // Tombol Keluar dibiarkan jalan agar langganan push perangkat ikut terhapus.
        if (! $request->user()?->isParent() || $request->routeIs('logout')) {
            return $next($request);
        }

        $last = $request->session()->get('parent_last_seen');
        $limit = $request->session()->get('parent_own_device') ? self::OWN_DEVICE_SECONDS : self::OTHER_DEVICE_SECONDS;

        if ($last && now()->timestamp - $last > $limit) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson() ? response()->json([], 401) : redirect()->route('login');
        }

        // Polling chat berjalan sendiri, bukan tanda orang tua masih memegang HP.
        if (! $request->routeIs('chat.poll')) {
            $request->session()->put('parent_last_seen', now()->timestamp);
        }

        return $next($request);
    }
}
