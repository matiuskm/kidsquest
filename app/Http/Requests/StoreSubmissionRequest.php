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
            'photo.required' => __('Ambil fotonya dulu ya.'),
            'photo.image' => __('Itu bukan foto. Coba lagi ya.'),
            'photo.max' => __('Fotonya terlalu besar (maksimal 10 MB).'),
            'photo.uploaded' => __('Fotonya terlalu besar (maksimal 10 MB).'),
        ];
    }
}
