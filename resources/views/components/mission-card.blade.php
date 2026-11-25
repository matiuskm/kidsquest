@props(['mission'])

@php
    use App\Enums\MissionStatus;
    $m = $mission;
    $style = match ($m->status) {
        MissionStatus::Todo => 'bg-white',
        MissionStatus::Pending => 'bg-amber-100',
        MissionStatus::Rejected => 'bg-rose-100',
        MissionStatus::Done => 'bg-emerald-100',
    };
    $badge = match ($m->status) {
        MissionStatus::Todo => '🎯 Ayo kerjakan!',
        MissionStatus::Pending => '⏳ Menunggu ortu',
        MissionStatus::Rejected => '🔁 Coba lagi',
        MissionStatus::Done => '✅ Selesai!',
    };
@endphp

<article class="kq-pop {{ $style }} rounded-3xl p-5 shadow-lg">
    <div class="flex items-start justify-between gap-3">
        <h2 class="text-2xl font-extrabold">{{ $m->task->title }}</h2>
        <span class="shrink-0 rounded-full bg-yellow-300 px-3 py-1 text-lg">⭐ {{ $m->task->points }}</span>
    </div>
    @if ($m->task->description)
        <p class="mt-1 text-base font-medium text-slate-600">{{ $m->task->description }}</p>
    @endif
    <p class="mt-2 text-base">{{ $badge }}</p>

    @if ($m->status === MissionStatus::Rejected && $m->submission?->reject_note)
        <p class="mt-1 text-base text-rose-700">Kata ortu: {{ $m->submission->reject_note }}</p>
    @endif

    @if (in_array($m->status, [MissionStatus::Todo, MissionStatus::Rejected], true))
        <form method="POST" action="{{ route('child.submit', $m->task) }}" enctype="multipart/form-data"
            x-data="photoUpload" @submit="busy = true" class="mt-3 space-y-3">
            @csrf
            <label class="block cursor-pointer rounded-2xl border-4 border-dashed border-sky-300 p-4 text-center">
                <span x-show="!preview">📷 Ambil foto bukti</span>
                <img x-show="preview" :src="preview" class="mx-auto max-h-48 rounded-xl" alt="">
                <input type="file" name="photo" accept="image/*" capture="environment" class="sr-only" required @change="shrink($event)">
            </label>
            <button :disabled="busy" class="w-full rounded-2xl bg-pink-400 py-3 text-white disabled:opacity-50">Kirim 🚀</button>
        </form>
    @endif
</article>
