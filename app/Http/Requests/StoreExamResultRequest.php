<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:60'],
            'score' => ['required', 'integer', 'between:0,100'],
            'photo' => ['required', 'file', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Tulis mata pelajarannya ya.',
            'score.required' => 'Isi nilainya ya.',
            'score.integer' => 'Nilai harus angka bulat.',
            'score.between' => 'Nilai harus antara 0 sampai 100.',
            'photo.required' => 'Foto kertas ulangannya dulu ya.',
            'photo.image' => 'Itu bukan foto. Coba lagi ya.',
            'photo.max' => 'Fotonya terlalu besar (maksimal 10 MB).',
            'photo.uploaded' => 'Fotonya terlalu besar (maksimal 10 MB).',
        ];
    }
}
