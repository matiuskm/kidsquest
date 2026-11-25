@extends('layouts.parent')

@section('title', 'Riwayat')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-bold">Riwayat</h1>
    <form method="GET">
        <select name="anak" onchange="this.form.submit()" class="rounded border p-1 text-sm">
            <option value="">Semua anak</option>
            @foreach ($children as $c)
                <option value="{{ $c->id }}" @selected($selected === $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </form>
</div>

@include('parent._status')

@if ($selected)
    <form method="POST" action="{{ route('parent.points.adjust', $selected) }}" class="mb-6 flex flex-wrap items-end gap-2 rounded-xl bg-white p-4 shadow">
        @csrf
        <label class="text-sm">Sesuaikan poin <input name="amount" type="number" placeholder="+10 / -5" class="w-24 rounded border p-1" required></label>
        <label class="text-sm">Catatan <input name="note" maxlength="100" class="rounded border p-1" required></label>
        <button class="rounded bg-indigo-600 px-3 py-1 text-sm text-white">Simpan</button>
        @error('amount') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        @error('note') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </form>
@endif

<h2 class="mb-2 font-bold">Keputusan tugas</h2>
<ul class="mb-8 divide-y rounded-xl bg-white shadow">
    @forelse ($submissions as $s)
        <li class="flex justify-between p-3 text-sm">
            <span>{{ $s->child->name }} · {{ $s->task->title }}</span>
            <span class="{{ $s->status === \App\Enums\ReviewStatus::Approved ? 'text-green-700' : 'text-red-700' }}">
                {{ $s->status === \App\Enums\ReviewStatus::Approved ? 'Disetujui' : 'Ditolak' }} · {{ $s->reviewed_at?->format('d/m H:i') }}
            </span>
        </li>
    @empty
        <li class="p-3 text-sm text-slate-500">Belum ada.</li>
    @endforelse
</ul>

<h2 class="mb-2 font-bold">Nilai ulangan</h2>
<ul class="mb-8 divide-y rounded-xl bg-white shadow">
    @forelse ($exams as $e)
        <li class="flex justify-between p-3 text-sm">
            <span>{{ $e->child->name }} · {{ $e->subject }}@if ($e->status === \App\Enums\ReviewStatus::Approved) · {{ $e->mark() }} ({{ $e->grade()->letter }}{{ $e->points_awarded ? ', +'.$e->points_awarded : '' }})@endif</span>
            <span class="{{ $e->status === \App\Enums\ReviewStatus::Approved ? 'text-green-700' : 'text-red-700' }}">
                {{ $e->status === \App\Enums\ReviewStatus::Approved ? 'Disetujui' : 'Ditolak' }} · {{ $e->reviewed_at?->format('d/m H:i') }}
            </span>
        </li>
    @empty
        <li class="p-3 text-sm text-slate-500">Belum ada.</li>
    @endforelse
</ul>

<h2 class="mb-2 font-bold">Mutasi poin</h2>
<ul class="divide-y rounded-xl bg-white shadow">
    @forelse ($transactions as $t)
        <li class="flex justify-between p-3 text-sm">
            <span>{{ $t->user->name }} · {{ $t->type === \App\Enums\LedgerType::Exam ? 'Nilai ulangan' : $t->type->value }} {{ $t->note }}</span>
            <span class="font-semibold {{ $t->amount >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ $t->amount > 0 ? '+' : '' }}{{ $t->amount }} · {{ $t->created_at->format('d/m H:i') }}</span>
        </li>
    @empty
        <li class="p-3 text-sm text-slate-500">Belum ada.</li>
    @endforelse
</ul>
@endsection
