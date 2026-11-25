<?php

namespace App\Http\Requests;

use App\Enums\Recurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai untuk store dan update (DRY). Otorisasi lewat middleware role:parent. */
class TaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'points' => ['required', 'integer', 'min:1', 'max:1000'],
            'recurrence' => ['required', Rule::enum(Recurrence::class)],
            'weekdays' => ['required_if:recurrence,weekly', 'nullable', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'month_day' => ['required_if:recurrence,monthly', 'nullable', 'integer', 'between:1,31'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'children' => ['required', 'array', 'min:1'],
            'children.*' => ['integer', Rule::exists('users', 'id')->where('role', 'child')->whereNull('deleted_at')],
        ];
    }

    /** Tugas yang sudah punya kiriman tidak boleh pindah jadwal: kunci periodenya (mis. tugas sekali) bisa bergeser dan poin cair dua kali. */
    public function after(): array
    {
        return [function ($validator) {
            $task = $this->route('task');

            if (! $task?->exists || ! $task->submissions()->exists()) {
                return;
            }

            $task->recurrence->value !== $this->input('recurrence') && $validator->errors()->add('recurrence', 'Jadwal tidak bisa diubah karena tugas ini sudah pernah dikerjakan. Buat tugas baru.');
            $task->start_date->toDateString() !== $this->input('start_date') && $validator->errors()->add('start_date', 'Tanggal mulai tidak bisa diubah karena tugas ini sudah pernah dikerjakan.');
        }];
    }

    /** Atribut tabel `tasks`, dengan kolom yang tak relevan untuk recurrence-nya dikosongkan. */
    public function taskData(): array
    {
        $data = $this->safe()->except('children');
        $recurrence = Recurrence::from($data['recurrence']);

        $data['weekdays'] = $recurrence === Recurrence::Weekly
            ? collect($data['weekdays'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all()
            : null;
        $data['month_day'] = $recurrence === Recurrence::Monthly ? (int) $data['month_day'] : null;
        $data['active'] = $this->boolean('active');

        return $data;
    }

    public function childIds(): array
    {
        return array_map('intval', $this->validated('children'));
    }
}
