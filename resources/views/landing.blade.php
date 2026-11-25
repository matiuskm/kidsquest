@extends('layouts.child')

@section('title', __('Selamat datang'))

@section('content')
<div class="kq-pop pt-6 text-center">
    <div class="text-8xl">🌟</div>
    <h1 class="mt-2 text-5xl font-extrabold tracking-tight text-indigo-700">KidsQuest</h1>
    <p class="mx-auto mt-3 max-w-xs text-2xl">{{ __('Selesaikan misi, kumpulkan bintang, tukar hadiah!') }}</p>

    <ol class="mx-auto mt-8 grid max-w-sm gap-3 text-left text-lg">
        <li class="flex items-center gap-4 rounded-3xl bg-white p-4 shadow-lg"><span class="text-4xl">🎯</span> {{ __('Ambil misi hari ini') }}</li>
        <li class="flex items-center gap-4 rounded-3xl bg-white p-4 shadow-lg"><span class="text-4xl">📷</span> {{ __('Kirim foto buktinya') }}</li>
        <li class="flex items-center gap-4 rounded-3xl bg-white p-4 shadow-lg"><span class="text-4xl">⭐</span> {{ __('Kumpulkan poin, tukar hadiah 🎁') }}</li>
    </ol>

    <a href="{{ route('child.pick') }}" class="mt-8 inline-block w-full max-w-sm rounded-3xl bg-pink-400 py-4 text-3xl font-extrabold text-white shadow-xl transition hover:scale-105 active:scale-95">{{ __('Mulai petualangan 🚀') }}</a>

    <p class="mt-8 text-base"><a href="{{ route('login') }}" class="underline">{{ __('Masuk sebagai orang tua') }}</a></p>
</div>
@endsection
