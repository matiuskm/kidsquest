@extends('layouts.parent')

@section('title', 'Beranda')

@section('content')
@include('parent._status')

<section class="mb-8">
    <h2 class="mb-2 font-bold">Perlu diperiksa @if ($pending->isNotEmpty())<span class="rounded-full bg-red-600 px-2 text-xs text-white">{{ $pending->count() }}</span>@endif</h2>
    <ul class="divide-y rounded-xl bg-white shadow">
        @forelse ($pending as $s)
            <x-review-item
                :title="$s->task->title.' · '.$s->task->points.' poin'"
                :subtitle="$s->child->name.' · '.$s->period_date->format('d/m/Y')"
                :image="route('photos.show', $s)"
                :approve="route('parent.submissions.approve', $s)"
                :reject="route('parent.submissions.reject', $s)" />
        @empty
            <li class="p-4 text-sm text-slate-500">Tidak ada yang menunggu. 🎉</li>
        @endforelse
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
