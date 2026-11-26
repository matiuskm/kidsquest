<?php

namespace App\Http\Requests;

use App\Support\ExamGrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamResultRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // "IPA", " IPA. " dan "IPA" harus dianggap pelajaran yang sama.
        is_string($this->subject) && $this->merge(['subject' => trim(preg_replace('/\s+/u', ' ', $this->subject), ' .,;:!?_-')]);
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
            'subject.required' => __('Tulis mata pelajarannya ya.'),
            'score.required' => __('Isi nilainya ya.'),
            'score.integer' => __('Nilai harus angka bulat.'),
            'letter.in' => __('Pilih huruf dari A sampai E (boleh + atau -).'),
            'score.between' => __('Nilai harus antara 0 sampai 100.'),
            'photo.required' => __('Foto kertas ulangannya dulu ya.'),
            'photo.image' => __('Itu bukan foto. Coba lagi ya.'),
            'photo.max' => __('Fotonya terlalu besar (maksimal 10 MB).'),
            'photo.uploaded' => __('Fotonya terlalu besar (maksimal 10 MB).'),
        ];
    }
}
