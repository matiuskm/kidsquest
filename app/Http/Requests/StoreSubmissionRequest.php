<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function rules(): array
    {
        return ['photo' => ['required', 'file', 'image', 'max:10240']];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Ambil fotonya dulu ya.',
            'photo.image' => 'Itu bukan foto. Coba lagi ya.',
            'photo.max' => 'Fotonya terlalu besar (maksimal 10 MB).',
            'photo.uploaded' => 'Fotonya terlalu besar (maksimal 10 MB).',
        ];
    }
}
