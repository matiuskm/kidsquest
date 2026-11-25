@extends('layouts.parent')

@section('title', 'Beranda')

@php
    $queue = $pending->count() + $claims->count();
    $done = $children->sum('done');
    $total = $children->sum('total');
@endphp

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-extrabold">Halo, {{ auth()->user()->name }} 👋</h1>
    <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, j F Y') }}</p>
</div>

@include('parent._status')
<x-push-toggle class="mb-4 text-sm text-slate-600" />

<section class="mb-6 grid grid-cols-3 gap-3 text-center">
    <div @class(['rounded-2xl p-4 shadow-sm ring-1', 'bg-amber-50 ring-amber-200' => $queue > 0, 'bg-white ring-slate-200' => $queue === 0])>
        <div class="text-3xl font-extrabold">{{ $queue }}</div>
        <div class="text-xs text-slate-500">Perlu diperiksa</div>
    </div>
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="text-3xl font-extrabold">{{ $done }}<span class="text-lg text-slate-400">/{{ $total }}</span></div>
        <div class="text-xs text-slate-500">Misi selesai hari ini</div>
    </div>
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="text-3xl font-extrabold">{{ $children->count() }}</div>
        <div class="text-xs text-slate-500">Anak</div>
    </div>
</section>

<section class="mb-8">
    <h2 class="mb-3 font-bold">Perlu diperiksa</h2>
    <ul class="space-y-3">
        @foreach ($pending as $s)
            <x-review-item
                :title="$s->task->title.' · '.$s->task->points.' poin'"
                :subtitle="$s->child->name.' · '.$s->period_date->format('d/m/Y')"
                :image="route('photos.show', $s)"
                :approve="route('parent.submissions.approve', $s)"
                :reject="route('parent.submissions.reject', $s)" />
        @endforeach
        @foreach ($claims as $c)
            <x-review-item
                :title="'🎁 '.$c->reward->name.' · '.$c->cost.' poin'"
                :subtitle="$c->child->name.' ingin menukar'"
                :approve="route('parent.redemptions.approve', $c)"
                :reject="route('parent.redemptions.reject', $c)" />
        @endforeach
        @if ($queue === 0)
            <li class="rounded-2xl bg-white p-6 text-center text-sm text-slate-500 ring-1 ring-slate-200">Semua beres. Tidak ada yang menunggu 🎉</li>
        @endif
    </ul>
</section>

<section>
    <h2 class="mb-3 font-bold">Anak</h2>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($children as $row)
            @php($pct = $row['total'] ? round($row['done'] / $row['total'] * 100) : 0)
            <a href="{{ route('parent.history', ['anak' => $row['child']->id]) }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:shadow">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-3"><span class="text-4xl">{{ $row['child']->avatar }}</span><span class="font-semibold">{{ $row['child']->name }}</span></span>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">⭐ {{ $row['child']->points }}</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div></div>
                <div class="mt-1 text-xs text-slate-500">Hari ini {{ $row['done'] }}/{{ $row['total'] }} misi selesai</div>
            </a>
        @empty
            <p class="text-sm text-slate-500">Belum ada anak. <a href="{{ route('parent.children.create') }}" class="text-indigo-600 underline">Tambah anak</a></p>
        @endforelse
    </div>
</section>
@endsection
