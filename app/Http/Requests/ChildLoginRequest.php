<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ChildLoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    private const LOCK_SECONDS = 300;

    /** Batas harian menahan tebak-tebakan PIN (10.000 kombinasi) dari internet. */
    private const DAILY_MAX = 20;

    private const DAILY_SECONDS = 86400;

    /** Dibuka lagi saat orang tua mereset PIN. */
    public static function clearLockout(int $childId): void
    {
        RateLimiter::clear("pin:{$childId}");
        RateLimiter::clear("pin-day:{$childId}");
    }

    public function rules(): array
    {
        return [
            'child_id' => ['required', 'integer'],
            'pin' => ['required', 'digits:4'],
        ];
    }

    /** @throws ValidationException */
    public function authenticate(): User
    {
        $id = $this->integer('child_id');
        $key = "pin:{$id}";
        $dayKey = "pin-day:{$id}";

        $locked = RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS) ? $key
            : (RateLimiter::tooManyAttempts($dayKey, self::DAILY_MAX) ? $dayKey : null);

        if ($locked) {
            $minutes = (int) ceil(RateLimiter::availableIn($locked) / 60);

            throw ValidationException::withMessages(['pin' => "Terlalu banyak percobaan. Coba lagi dalam {$minutes} menit."]);
        }

        $child = User::children()->find($this->integer('child_id'));

        if (! $child || ! Hash::check($this->string('pin'), $child->pin_hash)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            RateLimiter::hit($dayKey, self::DAILY_SECONDS);

            throw ValidationException::withMessages(['pin' => 'PIN salah, coba lagi ya.']);
        }

        self::clearLockout($id);

        return $child;
    }
}
