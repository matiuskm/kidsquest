@extends('layouts.parent')

@section('title', 'Beranda')

@section('content')
@include('parent._status')

<x-push-toggle class="mb-4 text-sm text-slate-600" />

<section class="mb-8">
    <h2 class="mb-2 font-bold">Perlu diperiksa @if ($pending->count() + $claims->count() > 0)<span class="rounded-full bg-red-600 px-2 text-xs text-white">{{ $pending->count() + $claims->count() }}</span>@endif</h2>
    <ul class="divide-y rounded-xl bg-white shadow">
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
        @if ($pending->isEmpty() && $claims->isEmpty())
            <li class="p-4 text-sm text-slate-500">Tidak ada yang menunggu. 🎉</li>
        @endif
    </ul>
</section>

<section>
    <h2 class="mb-2 font-bold">Anak</h2>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($children as $row)
            <a href="{{ route('parent.history', ['anak' => $row['child']->id]) }}" class="flex items-center justify-between rounded-xl bg-white p-4 shadow">
                <span>{{ $row['child']->avatar }} {{ $row['child']->name }}</span>
                <span class="text-sm text-slate-500">Hari ini {{ $row['done'] }}/{{ $row['total'] }} · ⭐ {{ $row['child']->points }}</span>
            </a>
        @empty
            <p class="text-sm text-slate-500">Belum ada anak.</p>
        @endforelse
    </div>
</section>
@endsection
