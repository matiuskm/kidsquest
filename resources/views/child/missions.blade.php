@extends('layouts.child')

@section('title', __('Misi'))

@section('content')
    <h1 class="mb-4 text-4xl font-extrabold">{{ __('Misi hari ini') }} {{ auth()->user()->avatar }}</h1>

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-200 p-4">{{ session('status') }}</div>
    @endif
    @error('photo')
        <div class="mb-4 rounded-2xl bg-rose-200 p-4">{{ $message }}</div>
    @enderror

    <div x-data x-init="$store.fx.watch('rejected-{{ auth()->id() }}', {{ $missions->where('status', \App\Enums\MissionStatus::Rejected)->count() }}, 'reject')"></div>

    <div class="space-y-4">
        @forelse ($missions as $mission)
            <x-mission-card :mission="$mission" />
        @empty
            <p class="rounded-3xl bg-white p-6 text-center">{{ __('Tidak ada misi hari ini. Santai dulu ya 😎') }}</p>
        @endforelse
    </div>
@endsection
