<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChildLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'child_id' => ['required', 'integer'],
            'pin' => ['required', 'digits:4'],
            'push_endpoint' => ['nullable', 'string'],
        ];
    }
}
