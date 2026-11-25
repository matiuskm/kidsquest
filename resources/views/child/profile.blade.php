@extends('layouts.child')

@section('title', __('Saya'))

@section('content')
    <h1 class="mb-4 text-4xl font-extrabold">{{ __('Profilku 🙂') }}</h1>

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-200 p-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-rose-200 p-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('child.profile.update') }}" class="space-y-5 rounded-3xl bg-white p-6 shadow-lg">
        @csrf @method('PUT')
        <label class="block">{{ __('Namaku') }}
            <input name="name" value="{{ old('name', $child->name) }}" maxlength="30" class="mt-1 w-full rounded-2xl border-4 border-sky-200 p-3" required>
        </label>
        <div>{{ __('Avatarku') }} <x-avatar-picker :selected="$child->avatar" /></div>
        <div class="space-y-2 rounded-2xl bg-sky-50 p-4">
            <div>{{ __('Ganti PIN (boleh dikosongkan)') }}</div>
            <input name="current_pin" type="password" inputmode="numeric" maxlength="4" placeholder="{{ __('PIN lama') }}" autocomplete="off" class="w-full rounded-2xl border-4 border-sky-200 p-3">
            <input name="pin" type="password" inputmode="numeric" maxlength="4" placeholder="{{ __('PIN baru (4 angka)') }}" autocomplete="off" class="w-full rounded-2xl border-4 border-sky-200 p-3">
        </div>
        <p class="text-base">{{ __('🎂 Ulang tahun:') }} {{ $child->birthday_day }}/{{ $child->birthday_month }}</p>
        <button class="w-full rounded-2xl bg-pink-400 py-3 text-white">{{ __('Simpan') }}</button>
    </form>

    <x-locale-switch class="mt-4 text-center text-base underline" />
    <x-push-toggle class="mt-6 text-center text-base" />
@endsection
