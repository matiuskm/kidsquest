@extends('layouts.child')

@section('title', 'Nilai')

@section('content')
    <h1 class="mb-4 text-4xl font-extrabold">Nilai ulangan 📝</h1>

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-200 p-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-rose-200 p-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('child.exams.store') }}" enctype="multipart/form-data"
        x-data="photoUpload" @submit="busy = true" class="kq-pop mb-6 space-y-3 rounded-3xl bg-white p-5 shadow-lg">
        @csrf
        <input name="subject" value="{{ old('subject') }}" maxlength="60" required placeholder="Mata pelajaran" class="w-full rounded-2xl border-2 border-sky-200 px-4 py-3">
        <input name="score" value="{{ old('score') }}" type="number" inputmode="numeric" min="0" max="100" required placeholder="Nilai (0-100)" class="w-full rounded-2xl border-2 border-sky-200 px-4 py-3">
        <label class="block cursor-pointer rounded-2xl border-4 border-dashed border-sky-300 p-4 text-center">
            <span x-show="!preview">📷 Foto kertas ulangan</span>
            <img x-show="preview" :src="preview" class="mx-auto max-h-48 rounded-xl" alt="">
            <input type="file" name="photo" accept="image/*" capture="environment" class="sr-only" required @change="shrink($event)">
        </label>
        <p x-show="error" x-text="error" x-cloak class="text-center text-base text-rose-700"></p>
        <button :disabled="busy || working || error" class="w-full rounded-2xl bg-pink-400 py-3 text-white disabled:opacity-50">Kirim 🚀</button>
    </form>

    <div class="space-y-3">
        @forelse ($exams as $exam)
            @php
                $done = $exam->status === \App\Enums\ReviewStatus::Approved;
                $rejected = $exam->status === \App\Enums\ReviewStatus::Rejected;
            @endphp
            <article class="{{ $done ? 'bg-emerald-100' : ($rejected ? 'bg-rose-100' : 'bg-amber-100') }} rounded-3xl p-4 shadow">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xl font-extrabold">{{ $exam->subject }}</h2>
                    @if ($done)
                        <span class="shrink-0 rounded-full bg-yellow-300 px-3 py-1 text-lg">{{ $exam->grade()->letter }} · ⭐ {{ $exam->points_awarded }}</span>
                    @endif
                </div>
                <p class="text-base">
                    Nilai {{ $exam->final_score ?? $exam->claimed_score }} ·
                    {{ $done ? '✅ Disetujui' : ($rejected ? '🔁 Ditolak, kirim ulang ya' : '⏳ Menunggu ortu') }}
                </p>
                @if ($rejected && $exam->reject_note)
                    <p class="text-base text-rose-700">Kata ortu: {{ $exam->reject_note }}</p>
                @endif
            </article>
        @empty
            <p class="rounded-3xl bg-white p-6 text-center">Belum ada nilai. Habis ulangan, kirim di sini ya! 🌟</p>
        @endforelse
    </div>
@endsection
