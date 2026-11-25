@extends($layout)

@section('title', __('Chat').' '.$other->name)

@section('content')
    @php $me = auth()->user(); $last = $messages->last()?->id ?? 0; @endphp
    <div class="mb-3 flex items-center gap-3">
        <a href="{{ route('chat.index') }}" class="text-2xl" aria-label="{{ __('Kembali') }}">←</a>
        <h1 class="text-xl font-extrabold">{{ $other->isParent() ? '👤' : $other->avatar }} {{ $other->name }}</h1>
    </div>

    @if ($errors->any())
        <div class="mb-3 rounded-2xl bg-rose-200 p-3 text-base">{{ $errors->first() }}</div>
    @endif

    @php
        $bubble = fn (bool $mine) => 'max-w-[80%] rounded-2xl px-4 py-2 text-base break-words '.($mine ? 'ml-auto bg-indigo-500 text-white' : 'mr-auto bg-white shadow');
    @endphp
    <div class="space-y-2"
        x-data="{ items: [], last: {{ $last }},
            async tick() {
                if (document.hidden) return;
                const r = await fetch(@js(route('chat.poll', $other)) + '?after=' + this.last, { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                d.messages.forEach(m => { this.items.push(m); this.last = Math.max(this.last, m.id); });
                if (d.messages.length) this.$nextTick(() => window.scrollTo(0, document.body.scrollHeight));
            } }"
        x-init="setInterval(() => tick(), 5000); window.scrollTo(0, document.body.scrollHeight)">
        @foreach ($messages as $m)
            <div class="{{ $bubble($m->sender_id === $me->id) }}">{{ $m->body }}<div class="text-xs opacity-60">{{ $m->created_at->format('H:i') }}</div></div>
        @endforeach
        <template x-for="m in items" :key="m.id">
            <div :class="m.mine ? 'ml-auto bg-indigo-500 text-white' : 'mr-auto bg-white shadow'" class="max-w-[80%] rounded-2xl px-4 py-2 text-base break-words">
                <span x-text="m.body"></span><div class="text-xs opacity-60" x-text="m.time"></div>
            </div>
        </template>
    </div>

    <form method="POST" action="{{ route('chat.store', $other) }}" x-data="{ busy: false }" @submit="busy = true"
        class="sticky bottom-24 mt-4 flex gap-2 rounded-2xl bg-white/95 p-2 shadow-lg">
        @csrf
        <input name="body" maxlength="500" required autocomplete="off" placeholder="{{ __('Tulis pesan…') }}" class="min-w-0 flex-1 rounded-xl border px-3 py-2 text-base">
        <button :disabled="busy" class="rounded-xl bg-indigo-600 px-4 py-2 text-base text-white disabled:opacity-50">{{ __('Kirim') }}</button>
    </form>
@endsection
