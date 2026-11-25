@props(['title', 'subtitle' => null, 'image' => null, 'approve', 'reject'])

<li class="kq-pop flex flex-col gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:flex-row" x-data="{ rejecting: false }">
    @if ($image)
        <a href="{{ $image }}" target="_blank" class="shrink-0"><img src="{{ $image }}" class="h-40 w-full rounded-xl object-cover sm:h-32 sm:w-32" alt="Foto bukti" loading="lazy"></a>
    @endif
    <div class="flex-1">
        <div class="font-semibold">{{ $title }}</div>
        <div class="text-sm text-slate-500">{{ $subtitle }}</div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ $approve }}" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <button :disabled="busy" class="rounded-full bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">✓ Setuju</button>
            </form>
            <button type="button" @click="rejecting = !rejecting" class="rounded-full bg-rose-50 px-4 py-1.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">✗ Tolak</button>
            <form x-show="rejecting" x-cloak method="POST" action="{{ $reject }}" class="flex w-full gap-2 sm:w-auto" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <input name="note" maxlength="200" placeholder="Alasan (opsional)" class="min-w-0 flex-1 rounded-full border px-3 py-1 text-sm">
                <button :disabled="busy" class="rounded-full bg-rose-600 px-4 py-1.5 text-sm font-semibold text-white disabled:opacity-50">Kirim</button>
            </form>
        </div>
    </div>
</li>
