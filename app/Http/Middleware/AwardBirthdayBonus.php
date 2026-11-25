<?php

namespace App\Http\Middleware;

use App\Services\BirthdayBonus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AwardBirthdayBonus
{
    public function __construct(private BirthdayBonus $bonus) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isChild() && $this->bonus->award($user, now()->toImmutable())) {
            $request->session()->now('birthday', $user->name); // hanya request ini
        }

        return $next($request);
    }
}
