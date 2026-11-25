<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bahasa mengikuti pilihan user (kolom `locale`); tamu memakai bawaan app. */
class SetLocale
{
    public const LOCALES = ['id', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (in_array($locale, self::LOCALES, true)) {
            app()->setLocale($locale);
        }
        Carbon::setLocale(app()->getLocale());

        return $next($request);
    }
}
