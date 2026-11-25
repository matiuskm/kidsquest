@extends('layouts.base')

@section('body-class', 'min-h-screen bg-slate-100 text-slate-800')

@section('body')
    <header class="bg-gradient-to-r from-indigo-600 to-fuchsia-500 text-white shadow">
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="text-lg font-extrabold tracking-tight">🌟 KidsQuest</a>
            @auth
                <x-locale-switch class="ml-auto text-sm" />
                <form method="POST" action="{{ route('logout') }}" x-data="logoutForm" @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="push_endpoint">
                    <button class="rounded-full bg-white/15 px-3 py-1 text-sm hover:bg-white/25">{{ __('Keluar') }}</button>
                </form>
            @endauth
        </div>
        @auth
            <nav class="mx-auto flex max-w-4xl gap-1 overflow-x-auto px-3 pb-2 text-sm">
                @foreach ([
                    ['parent.dashboard', 'Beranda', 'parent.dashboard'],
                    ['parent.children.index', 'Anak', 'parent.children.*'],
                    ['parent.tasks.index', 'Tugas', 'parent.tasks.*'],
                    ['parent.rewards.index', 'Hadiah', 'parent.rewards.*'],
                    ['parent.history', 'Riwayat', 'parent.history'],
                    ['chat.index', 'Chat', 'chat.*'],
                ] as [$route, $label, $active])
                    <a href="{{ route($route) }}" @class(['whitespace-nowrap rounded-full px-4 py-1.5', 'bg-white text-indigo-700 font-semibold' => request()->routeIs($active), 'hover:bg-white/20' => ! request()->routeIs($active)])>{{ __($label) }}@if ($route === 'chat.index' && auth()->user()->unread_messages > 0) <span data-unread="{{ auth()->user()->unread_messages }}" class="ml-1 rounded-full bg-rose-500 px-1.5 text-xs text-white">{{ auth()->user()->unread_messages }}</span>@endif</a>
                @endforeach
            </nav>
        @endauth
    </header>

    <main class="mx-auto max-w-4xl px-4 py-6">
        @yield('content')
    </main>

    <footer class="py-4 text-center text-xs text-slate-400">KidsQuest v{{ config('app.version') }}</footer>
@endsection
