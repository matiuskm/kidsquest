@props(['title', 'subtitle' => null, 'image' => null, 'approve', 'reject'])

<li class="flex flex-col gap-3 p-4 sm:flex-row" x-data="{ rejecting: false }">
    @if ($image)
        <a href="{{ $image }}" target="_blank"><img src="{{ $image }}" class="h-28 w-28 rounded object-cover" alt="Foto bukti" loading="lazy"></a>
    @endif
    <div class="flex-1">
        <div class="font-semibold">{{ $title }}</div>
        <div class="text-sm text-slate-500">{{ $subtitle }}</div>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ $approve }}">
                @csrf
                <button class="rounded bg-green-600 px-3 py-1 text-sm text-white">Setuju</button>
            </form>
            <button type="button" @click="rejecting = !rejecting" class="rounded bg-red-100 px-3 py-1 text-sm text-red-700">Tolak</button>
            <form x-show="rejecting" x-cloak method="POST" action="{{ $reject }}" class="flex gap-2">
                @csrf
                <input name="note" maxlength="200" placeholder="Alasan (opsional)" class="rounded border p-1 text-sm">
                <button class="rounded bg-red-600 px-3 py-1 text-sm text-white">Kirim</button>
            </form>
        </div>
    </div>
</li>
