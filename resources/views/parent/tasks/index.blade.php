@extends('layouts.parent')

@section('title', 'Tugas')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-bold">Tugas</h1>
    <a href="{{ route('parent.tasks.create') }}" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">+ Tambah tugas</a>
</div>

@include('parent._status')

<ul class="divide-y rounded-xl bg-white shadow">
    @forelse ($tasks as $task)
        <li class="flex items-center justify-between gap-3 p-4">
            <div>
                <div class="font-semibold">{{ $task->title }} @unless($task->active)<span class="text-xs text-slate-400">(nonaktif)</span>@endunless</div>
                <div class="text-sm text-slate-500">
                    {{ $task->points }} poin · {{ \App\Support\Labels::recurrence($task) }} · {{ $task->children->pluck('name')->join(', ') }}
                </div>
            </div>
            <div class="flex shrink-0 gap-3 text-sm">
                <a href="{{ route('parent.tasks.edit', $task) }}" class="text-indigo-600">Ubah</a>
                <form method="POST" action="{{ route('parent.tasks.destroy', $task) }}" onsubmit="return confirm('Hapus tugas ini?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600">Hapus</button>
                </form>
            </div>
        </li>
    @empty
        <li class="p-4 text-slate-500">Belum ada tugas.</li>
    @endforelse
</ul>
@endsection
