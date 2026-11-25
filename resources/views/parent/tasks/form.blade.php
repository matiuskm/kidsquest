@extends('layouts.parent')

@section('title', $task->exists ? 'Ubah tugas' : 'Tugas baru')

@php($rec = old('recurrence', $task->recurrence?->value ?? $task->recurrence))
@php($days = old('weekdays', $task->weekdays ?? []))

@section('content')
<form method="POST" action="{{ $task->exists ? route('parent.tasks.update', $task) : route('parent.tasks.store') }}"
    x-data="{ rec: @js($rec) }" class="mx-auto max-w-lg space-y-4 rounded-xl bg-white p-6 shadow">
    @csrf
    @if ($task->exists) @method('PUT') @endif
    <h1 class="text-xl font-bold">{{ $task->exists ? 'Ubah tugas' : 'Tugas baru' }}</h1>

    @if ($errors->any())
        <div class="rounded bg-red-100 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <label class="block text-sm">Judul
        <input name="title" value="{{ old('title', $task->title) }}" class="w-full rounded border p-2" required maxlength="100">
    </label>
    <label class="block text-sm">Keterangan
        <textarea name="description" class="w-full rounded border p-2" maxlength="500">{{ old('description', $task->description) }}</textarea>
    </label>
    <label class="block text-sm">Poin
        <input name="points" type="number" min="1" max="1000" value="{{ old('points', $task->points) }}" class="w-full rounded border p-2" required>
    </label>

    <label class="block text-sm">Pengulangan
        <select name="recurrence" x-model="rec" class="w-full rounded border p-2">
            @foreach (['once' => 'Sekali', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'] as $v => $l)
                <option value="{{ $v }}">{{ $l }}</option>
            @endforeach
        </select>
    </label>

    <div x-show="rec === 'weekly'" x-cloak class="flex flex-wrap gap-3 text-sm">
        @foreach (\App\Support\Labels::WEEKDAYS as $i => $label)
            <label><input type="checkbox" name="weekdays[]" value="{{ $i }}" @checked(in_array($i, array_map('intval', $days)))> {{ $label }}</label>
        @endforeach
    </div>
    <label x-show="rec === 'monthly'" x-cloak class="block text-sm">Tanggal tiap bulan
        <input name="month_day" type="number" min="1" max="31" value="{{ old('month_day', $task->month_day) }}" class="w-full rounded border p-2">
    </label>

    <div class="grid grid-cols-2 gap-3">
        <label class="block text-sm">Mulai
            <input name="start_date" type="date" value="{{ old('start_date', $task->start_date?->toDateString() ?? now()->toDateString()) }}" class="w-full rounded border p-2" required>
        </label>
        <label class="block text-sm">Sampai (opsional)
            <input name="end_date" type="date" value="{{ old('end_date', $task->end_date?->toDateString()) }}" class="w-full rounded border p-2">
        </label>
    </div>

    <fieldset class="text-sm">
        <legend>Untuk anak</legend>
        @foreach ($children as $c)
            <label class="mr-4"><input type="checkbox" name="children[]" value="{{ $c->id }}" @checked(in_array($c->id, array_map('intval', $selected)))> {{ $c->avatar }} {{ $c->name }}</label>
        @endforeach
    </fieldset>

    <label class="block text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $task->active))> Aktif</label>

    <div class="flex gap-3">
        <button class="rounded bg-indigo-600 px-4 py-2 text-white">Simpan</button>
        <a href="{{ route('parent.tasks.index') }}" class="px-4 py-2 text-slate-600">Batal</a>
    </div>
</form>
@endsection
