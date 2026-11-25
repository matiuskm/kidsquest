@extends('layouts.base')

@section('body-class', 'min-h-screen bg-gradient-to-b from-sky-200 via-pink-100 to-yellow-100 text-xl text-slate-800 font-semibold')

@section('body')
    <main class="mx-auto max-w-xl px-4 pb-28 pt-6">
        @if (session('sound'))
            <div x-data x-init="$store.fx.play(@js(session('sound')))"></div>
        @endif
        @if (session('birthday'))
            <div x-data x-init="$store.fx.confetti(); $store.fx.play('points')"></div>
            <div class="mb-4 rounded-3xl bg-pink-300 p-5 text-center text-2xl font-extrabold">{{ __('🎂 Selamat ulang tahun, :name! 🎉', ['name' => session('birthday')]) }}<br>{{ __('Bonus ⭐ :n poin untukmu!', ['n' => config('kidsquest.birthday_bonus')]) }}</div>
        @endif
        @auth
            @php $points = auth()->user()->points; @endphp
            <div x-data x-init="$store.fx.watch('points-{{ auth()->id() }}', {{ $points }}, 'points', true)"></div>
            <div class="mb-4 flex items-center justify-between">
                <span>{{ auth()->user()->avatar }} {{ auth()->user()->name }}</span>
                <span class="rounded-full bg-yellow-300 px-4 py-1">{{ __('⭐ :n poin', ['n' => $points]) }}</span>
            </div>
        @endauth
        @yield('content')
    </main>

    @auth
        <nav class="fixed inset-x-0 bottom-0 border-t-4 border-white bg-white/95 backdrop-blur">
            @php
                $tabs = [['child.missions', '🎯', 'Misi'], ['child.exams', '📝', 'Nilai'], ['child.rewards', '🎁', 'Hadiah'], ['chat.*', '💬', 'Chat'], ['child.profile', '🙂', 'Saya']];
                $tab = 'flex flex-1 flex-col items-center rounded-2xl px-1 py-1 text-xs leading-tight';
            @endphp
            <div class="mx-auto flex max-w-xl items-stretch gap-1 px-1 py-1">
                @foreach ($tabs as [$route, $icon, $label])
                    <a href="{{ route($route === 'chat.*' ? 'chat.index' : $route) }}" @if (request()->routeIs($route)) aria-current="page" @endif
                        class="relative {{ $tab }} {{ request()->routeIs($route) ? 'bg-yellow-200' : 'hover:bg-yellow-100' }}"><span class="text-2xl">{{ $icon }}</span>{{ __($label) }}
                        @if ($route === 'chat.*' && auth()->user()->unread_messages > 0)
                            <span data-unread="{{ auth()->user()->unread_messages }}" class="absolute right-1 top-0 rounded-full bg-rose-500 px-1.5 text-xs text-white">{{ auth()->user()->unread_messages }}</span>
                        @endif
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" x-data="logoutForm" @submit.prevent="submit($event)" class="flex flex-1">
                    @csrf
                    <input type="hidden" name="push_endpoint">
                    <button class="{{ $tab }} w-full hover:bg-pink-100"><span class="text-2xl">🚪</span>{{ __('Keluar') }}</button>
                </form>
            </div>
        </nav>
    @endauth
@endsection
