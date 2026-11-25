@extends('layouts.child')

@section('title', 'Masuk')

@section('content')
<div x-data="{ child: @js(old('child_id') ? $children->firstWhere('id', (int) old('child_id'))?->only('id', 'name', 'avatar') : null) }">
    <h1 class="mb-6 text-center text-4xl font-extrabold">Siapa yang main hari ini? 🌈</h1>

    <div x-show="!child" class="grid grid-cols-2 gap-4">
        @forelse ($children as $c)
            <button type="button" @click="child = @js($c->only('id', 'name', 'avatar'))"
                class="rounded-3xl bg-white p-6 text-center shadow-lg transition hover:scale-105 active:scale-95">
                <div class="text-6xl">{{ $c->avatar }}</div>
                <div class="mt-2 text-2xl">{{ $c->name }}</div>
            </button>
        @empty
            <p class="col-span-2 text-center">Belum ada anak. Minta orang tua menambahkan dulu ya.</p>
        @endforelse
    </div>

    <form x-show="child" x-cloak method="POST" action="{{ route('child.pin') }}" class="rounded-3xl bg-white p-6 text-center shadow-lg">
        @csrf
        <input type="hidden" name="child_id" :value="child?.id">
        <div class="text-7xl" x-text="child?.avatar"></div>
        <div class="mt-2 text-3xl" x-text="child?.name"></div>
        <input type="password" name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off"
            placeholder="PIN" class="mt-4 w-40 rounded-2xl border-4 border-sky-200 p-3 text-center text-4xl tracking-widest" required>
        @error('pin') <p class="mt-3 text-red-600">{{ $message }}</p> @enderror
        <div class="mt-4 flex justify-center gap-3">
            <button type="button" @click="child = null" class="rounded-2xl bg-slate-100 px-5 py-3">Kembali</button>
            <button class="rounded-2xl bg-pink-400 px-6 py-3 text-white">Masuk 🚀</button>
        </div>
    </form>

    <p class="mt-8 text-center text-base"><a href="{{ route('home') }}" class="underline">← Beranda</a> · <a href="{{ route('login') }}" class="underline">Masuk sebagai orang tua</a></p>
</div>
@endsection
