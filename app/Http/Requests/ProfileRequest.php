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
            'pin' => [Rule::requiredIf($this->user()->must_change_pin), 'nullable', 'digits:4', function ($attr, $value, $fail) {
                if (Hash::check((string) $value, $this->user()->pin_hash)) {
                    $fail(__('PIN baru harus beda dari yang lama.'));
                } elseif (preg_match('/^(\d)\1{3}$/', $value) || str_contains('0123456789', $value) || str_contains('9876543210', $value)) {
                    $fail(__('PIN terlalu gampang ditebak.'));
                }
            }],
            'current_pin' => [
                'required_with:pin', 'nullable',
                fn ($attr, $value, $fail) => Hash::check((string) $value, $this->user()->pin_hash) || $fail(__('PIN lama salah.')),
            ],
        ];
    }

    /** Hanya nama, avatar, dan PIN baru; tidak pernah tanggal lahir atau role. */
    public function profileData(): array
    {
        return array_filter($this->safe()->only(['name', 'avatar', 'pin']), fn ($v) => $v !== null);
    }
}
