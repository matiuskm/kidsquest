@extends('layouts.parent')

@section('title', __('Hadiah'))

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-bold">{{ __('Hadiah') }}</h1>
    <a href="{{ route('parent.rewards.create') }}" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">{{ __('+ Tambah hadiah') }}</a>
</div>

@include('parent._status')

<ul class="divide-y rounded-xl bg-white shadow">
    @forelse ($rewards as $reward)
        <li class="flex items-center justify-between gap-3 p-4">
            <div>
                <div class="font-semibold">{{ $reward->icon }} {{ $reward->name }} @unless($reward->active)<span class="text-xs text-slate-400">{{ __('(nonaktif)') }}</span>@endunless</div>
                <div class="text-sm text-slate-500">{{ __(':n poin', ['n' => $reward->cost]) }}</div>
            </div>
            <div class="flex shrink-0 gap-3 text-sm">
                <a href="{{ route('parent.rewards.edit', $reward) }}" class="text-indigo-600">{{ __('Ubah') }}</a>
                <form method="POST" action="{{ route('parent.rewards.destroy', $reward) }}" onsubmit="return confirm(@js(__('Hapus hadiah ini?')))">
                    @csrf @method('DELETE')
                    <button class="text-red-600">{{ __('Hapus') }}</button>
                </form>
            </div>
        </li>
    @empty
        <li class="p-4 text-slate-500">{{ __('Belum ada hadiah.') }}</li>
    @endforelse
</ul>
@endsection
