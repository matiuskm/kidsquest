@extends('layouts.base')

@section('body-class', 'min-h-screen bg-gradient-to-b from-sky-200 via-pink-100 to-yellow-100 text-xl text-slate-800 font-semibold')

@section('body')
    <main class="mx-auto max-w-xl px-4 pb-28 pt-6">
        @if (session('sound'))
            <div x-data x-init="$store.fx.play(@js(session('sound')))"></div>
        @endif
        @if (session('birthday'))
            <div x-data x-init="$store.fx.confetti(); $store.fx.play('points')"></div>
            <div class="mb-4 rounded-3xl bg-pink-300 p-5 text-center text-2xl font-extrabold">🎂 Selamat ulang tahun, {{ session('birthday') }}! 🎉<br>Bonus ⭐ {{ config('kidsquest.birthday_bonus') }} poin untukmu!</div>
        @endif
        @auth
            <div x-data x-init="$store.fx.watch('points-{{ auth()->id() }}', {{ auth()->user()->points }}, 'points', true)"></div>
            <div class="mb-4 flex items-center justify-between">
                <span>{{ auth()->user()->avatar }} {{ auth()->user()->name }}</span>
                <span class="rounded-full bg-yellow-300 px-4 py-1">⭐ {{ auth()->user()->points }} poin</span>
            </div>
        @endauth
        @yield('content')
    </main>

    @auth
        <nav class="fixed inset-x-0 bottom-0 border-t-4 border-white bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-xl items-center justify-around py-2 text-lg">
                <a href="{{ route('child.missions') }}" class="rounded-2xl px-4 py-2 hover:bg-yellow-100">🎯 Misi</a>
                <a href="{{ route('child.rewards') }}" class="rounded-2xl px-4 py-2 hover:bg-yellow-100">🎁 Hadiah</a>
                <a href="{{ route('child.profile') }}" class="rounded-2xl px-4 py-2 hover:bg-yellow-100">🙂 Saya</a>
                <form method="POST" action="{{ route('logout') }}" x-data="logoutForm" @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="push_endpoint">
                    <button class="rounded-2xl px-4 py-2 hover:bg-pink-100">👋 Ganti anak / Keluar</button>
                </form>
            </div>
        </nav>
    @endauth
@endsection
