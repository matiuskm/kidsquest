<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/** Perangkat yang pernah dipakai orang tua login. Hanya di sini daftar anak dan form PIN terbuka. */
final class FamilyDevice
{
    private const COOKIE = 'kq_family';

    public static function known(Request $request): bool
    {
        // Cookie palsu gagal didekripsi oleh EncryptCookies, jadi terbaca kosong.
        return (bool) $request->cookie(self::COOKIE);
    }

    /** shortcut: perangkat tidak bisa dicabut satu per satu; bila HP hilang, ganti nama cookie. */
    public static function remember(): void
    {
        Cookie::queue(Cookie::forever(self::COOKIE, '1'));
    }
}
