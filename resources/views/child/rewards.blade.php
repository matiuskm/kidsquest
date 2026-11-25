@extends('layouts.child')

@section('title', 'Hadiah')

@section('content')
    <h1 class="mb-4 text-4xl font-extrabold">Tukar hadiah 🎁</h1>

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-200 p-4">{{ session('status') }}</div>
    @endif
    @error('reward')
        <div class="mb-4 rounded-2xl bg-rose-200 p-4">{{ $message }}</div>
    @enderror

    <div class="space-y-4">
        @forelse ($rewards as $reward)
            @php($affordable = auth()->user()->points >= $reward->cost)
            <article class="flex items-center justify-between gap-3 rounded-3xl bg-white p-5 shadow-lg {{ $affordable ? '' : 'opacity-70' }}">
                <div>
                    <div class="text-2xl font-extrabold">{{ $reward->icon }} {{ $reward->name }}</div>
                    <div class="text-base">⭐ {{ $reward->cost }} poin</div>
                </div>
                <form method="POST" action="{{ route('child.claim', $reward) }}">
                    @csrf
                    <button class="rounded-2xl bg-pink-400 px-5 py-3 text-white disabled:bg-slate-300" @disabled(! $affordable)>Tukar</button>
                </form>
            </article>
        @empty
            <p class="rounded-3xl bg-white p-6 text-center">Belum ada hadiah. Tanya ortu ya!</p>
        @endforelse
    </div>

    @if ($claims->isNotEmpty())
        <h2 class="mb-2 mt-8 text-2xl font-extrabold">Klaim kamu</h2>
        <ul class="space-y-2 text-base">
            @foreach ($claims as $c)
                <li class="flex justify-between rounded-2xl bg-white/80 p-3">
                    <span>{{ $c->reward->icon }} {{ $c->reward->name }}</span>
                    <span>{{ match ($c->status) {
                        \App\Enums\ReviewStatus::Pending => '⏳ Menunggu',
                        \App\Enums\ReviewStatus::Approved => '✅ Disetujui',
                        \App\Enums\ReviewStatus::Rejected => '↩️ Ditolak, poin kembali',
                    } }}</span>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
