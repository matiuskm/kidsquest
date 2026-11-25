<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /** Landing untuk tamu (tanpa nama anak); yang sudah login langsung ke halamannya. */
    public function __invoke()
    {
        $user = auth()->user();

        return $user ? redirect()->route($user->homeRoute()) : view('landing');
    }
}
