@extends('layouts.parent')

@section('title', 'Masuk orang tua')

@section('content')
<form method="POST" action="{{ route('login.store') }}" class="mx-auto max-w-sm space-y-4 rounded-xl bg-white p-6 shadow">
    @csrf
    <h1 class="text-xl font-bold">Masuk orang tua</h1>
    <div>
        <label class="block text-sm" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded border p-2" required autofocus>
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm" for="password">Password</label>
        <input id="password" name="password" type="password" class="w-full rounded border p-2" required>
    </div>
    <button class="w-full rounded bg-indigo-600 p-2 text-white">Masuk</button>
    <p class="text-center text-sm"><a href="{{ route('home') }}" class="underline">← Beranda</a></p>
</form>
@endsection
