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
        $key = 'pin:'.$this->integer('child_id');

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            throw ValidationException::withMessages(['pin' => "Terlalu banyak percobaan. Coba lagi dalam {$minutes} menit."]);
        }

        $child = User::children()->find($this->integer('child_id'));

        if (! $child || ! Hash::check($this->string('pin'), $child->pin_hash)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);

            throw ValidationException::withMessages(['pin' => 'PIN salah, coba lagi ya.']);
        }

        RateLimiter::clear($key);

        return $child;
    }
}
