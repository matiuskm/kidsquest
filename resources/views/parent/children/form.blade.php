@extends('layouts.parent')

@section('title', $child->exists ? 'Ubah anak' : 'Anak baru')

@section('content')
<form method="POST" action="{{ $child->exists ? route('parent.children.update', $child) : route('parent.children.store') }}"
    class="mx-auto max-w-md space-y-4 rounded-xl bg-white p-6 shadow">
    @csrf
    @if ($child->exists) @method('PUT') @endif
    <h1 class="text-xl font-bold">{{ $child->exists ? 'Ubah anak' : 'Anak baru' }}</h1>

    @if ($errors->any())
        <div class="rounded bg-red-100 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <label class="block text-sm">Nama
        <input name="name" value="{{ old('name', $child->name) }}" class="w-full rounded border p-2" required maxlength="30">
    </label>

    <div class="text-sm">Avatar <x-avatar-picker :selected="$child->avatar" /></div>

    <label class="block text-sm">{{ $child->exists ? 'Reset PIN (kosongkan bila tidak diubah)' : 'PIN awal (4 digit)' }}
        <input name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" class="w-full rounded border p-2" @required(! $child->exists)>
    </label>

    @if ($child->exists)
        <p class="text-sm text-slate-500">Tanggal lahir: {{ $child->birthday_day }}/{{ $child->birthday_month }} (tidak bisa diubah)</p>
    @else
        <div class="grid grid-cols-2 gap-3">
            <label class="block text-sm">Tanggal lahir
                <input name="birthday_day" type="number" min="1" max="31" value="{{ old('birthday_day') }}" class="w-full rounded border p-2" required>
            </label>
            <label class="block text-sm">Bulan lahir
                <input name="birthday_month" type="number" min="1" max="12" value="{{ old('birthday_month') }}" class="w-full rounded border p-2" required>
            </label>
        </div>
        <p class="text-xs text-slate-500">Tanggal dan bulan lahir tidak bisa diubah setelah disimpan.</p>
    @endif

    <div class="flex gap-3">
        <button class="rounded bg-indigo-600 px-4 py-2 text-white">Simpan</button>
        <a href="{{ route('parent.children.index') }}" class="px-4 py-2 text-slate-600">Batal</a>
    </div>
</form>
@endsection
