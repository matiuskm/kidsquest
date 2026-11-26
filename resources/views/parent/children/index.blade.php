@extends('layouts.parent')

@section('title', __('Anak'))

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-bold">{{ __('Anak') }}</h1>
    <a href="{{ route('parent.children.create') }}" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">{{ __('+ Tambah anak') }}</a>
</div>

@include('parent._status')

<ul class="divide-y rounded-xl bg-white shadow">
    @forelse ($children as $child)
        <li class="flex flex-wrap items-center justify-between gap-3 p-4">
            <div>
                <div class="font-semibold">{{ $child->avatar }} {{ $child->name }}</div>
                <div class="text-sm text-slate-500">{{ __('Lahir') }} {{ $child->birthday_day }}/{{ $child->birthday_month }} · ⭐ {{ $child->points }}</div>
            </div>
            <div class="flex shrink-0 gap-3 text-sm">
                @if ($child->pin_locked_at)
                    <x-unlock-button :child="$child" />
                @endif
                <form method="POST" action="{{ route('parent.children.chat', $child) }}">
                    @csrf
                    <button class="text-slate-600" title="{{ __('Chat dengan anak lain') }}">💬 {{ $child->peer_chat ? __('Aktif') : __('Mati') }}</button>
                </form>
                <a href="{{ route('parent.children.edit', $child) }}" class="text-indigo-600">{{ __('Ubah') }}</a>
                <form method="POST" action="{{ route('parent.children.destroy', $child) }}" onsubmit="return confirm(@js(__('Hapus anak ini? Foto buktinya ikut terhapus, riwayat poin tetap tersimpan.')))">
                    @csrf @method('DELETE')
                    <button class="text-red-600">{{ __('Hapus') }}</button>
                </form>
            </div>
            <form method="POST" action="{{ route('parent.points.adjust', $child) }}" class="flex w-full flex-wrap items-center gap-2 text-sm">
                @csrf
                <input name="amount" type="number" required placeholder="+10" aria-label="{{ __('Poin') }}" class="w-20 rounded border p-1">
                <input name="note" maxlength="100" placeholder="{{ __('Catatan (opsional)') }}" class="min-w-0 flex-1 rounded border p-1">
                <button class="rounded bg-emerald-600 px-3 py-1 text-white">🎁 {{ __('Kasih poin') }}</button>
            </form>
        </li>
    @empty
        <li class="p-4 text-slate-500">{{ __('Belum ada anak.') }}</li>
    @endforelse
</ul>
@endsection
