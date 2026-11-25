@extends('layouts.parent')

@section('title', 'Anak')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-bold">Anak</h1>
    <a href="{{ route('parent.children.create') }}" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">+ Tambah anak</a>
</div>

@include('parent._status')

<ul class="divide-y rounded-xl bg-white shadow">
    @forelse ($children as $child)
        <li class="flex items-center justify-between gap-3 p-4">
            <div>
                <div class="font-semibold">{{ $child->avatar }} {{ $child->name }}</div>
                <div class="text-sm text-slate-500">Lahir {{ $child->birthday_day }}/{{ $child->birthday_month }} · ⭐ {{ $child->points }}</div>
            </div>
            <div class="flex shrink-0 gap-3 text-sm">
                <form method="POST" action="{{ route('parent.children.chat', $child) }}">
                    @csrf
                    <button class="text-slate-600" title="Chat dengan anak lain">💬 {{ $child->peer_chat ? 'Aktif' : 'Mati' }}</button>
                </form>
                <a href="{{ route('parent.children.edit', $child) }}" class="text-indigo-600">Ubah</a>
                <form method="POST" action="{{ route('parent.children.destroy', $child) }}" onsubmit="return confirm('Hapus anak ini? Foto buktinya ikut terhapus, riwayat poin tetap tersimpan.')">
                    @csrf @method('DELETE')
                    <button class="text-red-600">Hapus</button>
                </form>
            </div>
        </li>
    @empty
        <li class="p-4 text-slate-500">Belum ada anak.</li>
    @endforelse
</ul>
@endsection
