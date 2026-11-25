@extends('layouts.base')

@section('body-class', 'min-h-screen bg-slate-50 text-slate-800')

@section('body')
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3">
            <a href="{{ route('parent.dashboard') }}" class="font-bold text-indigo-700">KidsQuest</a>
            @auth
                <nav class="flex gap-4 text-sm">
                    <a href="{{ route('parent.tasks.index') }}">Tugas</a>
                </nav>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm text-slate-600 hover:underline">Keluar</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-6">
        @yield('content')
    </main>

    <footer class="py-4 text-center text-xs text-slate-400">KidsQuest v{{ config('app.version') }}</footer>
@endsection
