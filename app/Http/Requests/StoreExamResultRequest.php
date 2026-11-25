<?php

namespace App\Http\Requests;

use App\Support\ExamGrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamResultRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->letter !== null && $this->merge(['letter' => strtoupper(trim((string) $this->letter))]);
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:60'],
            'score' => ['required_without:letter', 'nullable', 'prohibits:letter', 'integer', 'between:0,100'],
            'letter' => ['required_without:score', 'nullable', Rule::in(ExamGrade::LETTERS)],
            'photo' => ['required', 'file', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Tulis mata pelajarannya ya.',
            'score.required' => 'Isi nilainya ya.',
            'score.integer' => 'Nilai harus angka bulat.',
            'letter.in' => 'Pilih huruf dari A sampai E (boleh + atau -).',
            'score.between' => 'Nilai harus antara 0 sampai 100.',
            'photo.required' => 'Foto kertas ulangannya dulu ya.',
            'photo.image' => 'Itu bukan foto. Coba lagi ya.',
            'photo.max' => 'Fotonya terlalu besar (maksimal 10 MB).',
            'photo.uploaded' => 'Fotonya terlalu besar (maksimal 10 MB).',
        ];
    }
}
