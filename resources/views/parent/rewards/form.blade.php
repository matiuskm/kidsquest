@extends('layouts.parent')

@section('title', $reward->exists ? 'Ubah hadiah' : 'Hadiah baru')

@section('content')
<form method="POST" action="{{ $reward->exists ? route('parent.rewards.update', $reward) : route('parent.rewards.store') }}"
    class="mx-auto max-w-md space-y-4 rounded-xl bg-white p-6 shadow">
    @csrf
    @if ($reward->exists) @method('PUT') @endif
    <h1 class="text-xl font-bold">{{ $reward->exists ? 'Ubah hadiah' : 'Hadiah baru' }}</h1>

    @if ($errors->any())
        <div class="rounded bg-red-100 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <label class="block text-sm">Nama
        <input name="name" value="{{ old('name', $reward->name) }}" class="w-full rounded border p-2" required maxlength="100">
    </label>
    <label class="block text-sm">Ikon (emoji)
        <input name="icon" value="{{ old('icon', $reward->icon) }}" class="w-full rounded border p-2" maxlength="16">
    </label>
    <label class="block text-sm">Harga (poin)
        <input name="cost" type="number" min="1" value="{{ old('cost', $reward->cost) }}" class="w-full rounded border p-2" required>
    </label>
    <label class="block text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $reward->active))> Tersedia</label>

    <div class="flex gap-3">
        <button class="rounded bg-indigo-600 px-4 py-2 text-white">Simpan</button>
        <a href="{{ route('parent.rewards.index') }}" class="px-4 py-2 text-slate-600">Batal</a>
    </div>
</form>
@endsection
