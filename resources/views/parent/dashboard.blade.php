@extends('layouts.parent')

@section('title', __('Beranda'))

@php
    $queue = $pending->count() + $exams->count() + $claims->count();
    $done = $children->sum('done');
    $total = $children->sum('total');
@endphp

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-extrabold">{{ __('Halo,') }} {{ auth()->user()->name }} 👋</h1>
    <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, j F Y') }}</p>
</div>

@include('parent._status')
<x-push-toggle class="mb-4 text-sm text-slate-600" />

@foreach ($locked as $child)
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-rose-50 p-4 text-sm ring-1 ring-rose-200">
        <span>🔒 {{ __('Akun :name terkunci karena PIN salah berkali-kali.', ['name' => $child->name]) }}</span>
        <x-unlock-button :child="$child" />
    </div>
@endforeach

<section class="mb-6 grid grid-cols-3 gap-3 text-center">
    <div @class(['rounded-2xl p-4 shadow-sm ring-1', 'bg-amber-50 ring-amber-200' => $queue > 0, 'bg-white ring-slate-200' => $queue === 0])>
        <div class="text-3xl font-extrabold">{{ $queue }}</div>
        <div class="text-xs text-slate-500">{{ __('Perlu diperiksa') }}</div>
    </div>
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="text-3xl font-extrabold">{{ $done }}<span class="text-lg text-slate-400">/{{ $total }}</span></div>
        <div class="text-xs text-slate-500">{{ __('Misi selesai hari ini') }}</div>
    </div>
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="text-3xl font-extrabold">{{ $children->count() }}</div>
        <div class="text-xs text-slate-500">{{ __('Anak') }}</div>
    </div>
</section>

<section class="mb-8">
    <h2 class="mb-3 font-bold">{{ __('Perlu diperiksa') }}</h2>
    <ul class="space-y-3">
        @foreach ($pending as $s)
            <x-review-item
                :title="$s->task->title.' · '.__(':n poin', ['n' => $s->task->points])"
                :subtitle="$s->child->name.' · '.$s->period_date->format('d/m/Y')"
                :image="route('photos.show', $s)"
                :approve="route('parent.submissions.approve', $s)"
                :reject="route('parent.submissions.reject', $s)" />
        @endforeach
        @foreach ($exams as $e)
            <x-review-item
                :title="'📝 '.$e->subject.' · '.__('nilai').' '.$e->mark()"
                :subtitle="$e->child->name.' · '.$e->created_at->format('d/m/Y').(($last = $e->lastApproved()) ? ' · '.__('Terakhir disetujui:').' '.$last->mark().' ('.$last->reviewed_at->format('d/m').')' : '').(($same = $e->sameMarkApproved()) ? ' · ⚠️ '.__('Nilai yang sama sudah disetujui :date. Cek fotonya di Riwayat.', ['date' => $same->reviewed_at->format('d/m')]) : '')"
                :image="route('photos.exam', $e)"
                :approve="route('parent.exams.approve', $e)"
                :reject="route('parent.exams.reject', $e)">
                <x-slot:fields>
                    @if ($e->isLetter())
                        <select name="letter" aria-label="{{ __('Nilai') }}" class="rounded-full border px-2 py-1 text-sm">
                            @foreach (\App\Support\ExamGrade::LETTERS as $l)
                                <option value="{{ $l }}" @selected($e->claimed_letter === $l)>{{ $l }}</option>
                            @endforeach
                        </select>
                    @else
                        <input name="score" type="number" min="0" max="100" value="{{ $e->claimed_score }}" aria-label="{{ __('Nilai') }}" class="w-16 rounded-full border px-2 py-1 text-sm">
                    @endif
                </x-slot:fields>
            </x-review-item>
        @endforeach
        @foreach ($claims as $c)
            <x-review-item
                :title="'🎁 '.$c->reward->name.' · '.__(':n poin', ['n' => $c->cost])"
                :subtitle="__(':name ingin menukar', ['name' => $c->child->name])"
                :approve="route('parent.redemptions.approve', $c)"
                :reject="route('parent.redemptions.reject', $c)" />
        @endforeach
        @if ($queue === 0)
            <li class="rounded-2xl bg-white p-6 text-center text-sm text-slate-500 ring-1 ring-slate-200">{{ __('Semua beres. Tidak ada yang menunggu 🎉') }}</li>
        @endif
    </ul>
</section>

<section>
    <h2 class="mb-3 font-bold">{{ __('Anak') }}</h2>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($children as $row)
            @php($pct = $row['total'] ? round($row['done'] / $row['total'] * 100) : 0)
            <a href="{{ route('parent.history', ['anak' => $row['child']->id]) }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:shadow">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-3"><span class="text-4xl">{{ $row['child']->avatar }}</span><span class="font-semibold">{{ $row['child']->name }}</span></span>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">⭐ {{ $row['child']->points }}</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div></div>
                <div class="mt-1 text-xs text-slate-500">{{ __('Hari ini :done/:total misi selesai', ['done' => $row['done'], 'total' => $row['total']]) }}</div>
            </a>
        @empty
            <p class="text-sm text-slate-500">{{ __('Belum ada anak.') }} <a href="{{ route('parent.children.create') }}" class="text-indigo-600 underline">{{ __('Tambah anak') }}</a></p>
        @endforelse
    </div>
</section>
@endsection
