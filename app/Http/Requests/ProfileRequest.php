<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'avatar' => ['required', Rule::in(config('kidsquest.avatars'))],
            'pin' => ['nullable', 'digits:4'],
            'current_pin' => [
                'required_with:pin', 'nullable',
                fn ($attr, $value, $fail) => Hash::check((string) $value, $this->user()->pin_hash) || $fail('PIN lama salah.'),
            ],
        ];
    }

    /** Hanya nama, avatar, dan PIN baru; tidak pernah tanggal lahir atau role. */
    public function profileData(): array
    {
        return array_filter($this->safe()->only(['name', 'avatar', 'pin']), fn ($v) => $v !== null);
    }
}
