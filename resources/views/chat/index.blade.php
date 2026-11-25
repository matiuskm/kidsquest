@extends($layout)

@section('title', __('Chat'))

@section('content')
    <h1 class="mb-4 text-2xl font-extrabold">{{ __('Chat 💬') }}</h1>

    <ul class="divide-y rounded-2xl bg-white shadow">
        @forelse ($contacts as $c)
            <li>
                <a href="{{ route('chat.show', $c) }}" class="flex items-center justify-between gap-3 p-4">
                    <span>{{ $c->isParent() ? '👤' : $c->avatar }} {{ $c->name }}</span>
                    @if (($unread[$c->id] ?? 0) > 0)
                        <span data-unread="{{ $unread[$c->id] }}" class="rounded-full bg-rose-500 px-2 text-sm text-white">{{ $unread[$c->id] }}</span>
                    @endif
                </a>
            </li>
        @empty
            <li class="p-4 text-slate-500">{{ __('Belum ada teman chat.') }}</li>
        @endforelse
    </ul>
@endsection
