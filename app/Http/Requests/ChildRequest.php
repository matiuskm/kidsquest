<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai untuk membuat (POST) dan mengubah (PUT) anak. Tanggal lahir hanya diterima saat membuat. */
class ChildRequest extends FormRequest
{
    public function rules(): array
    {
        $create = $this->isMethod('POST');

        return [
            'name' => ['required', 'string', 'max:30'],
            'avatar' => ['required', Rule::in(config('kidsquest.avatars'))],
            'pin' => [$create ? 'required' : 'nullable', 'digits:4'],
            ...($create ? [
                'birthday_month' => ['required', 'integer', 'between:1,12'],
                'birthday_day' => ['required', 'integer', 'between:1,31', function ($attr, $value, $fail) {
                    // 2024 kabisat: 29 Feb sah.
                    if (! checkdate((int) $this->input('birthday_month'), (int) $value, 2024)) {
                        $fail('Tanggal lahir tidak valid.');
                    }
                }],
            ] : []),
        ];
    }

    /** Atribut user; PIN dikosongkan bila tidak diisi saat update. */
    public function childData(): array
    {
        return array_filter($this->validated(), fn ($v) => $v !== null);
    }
}
