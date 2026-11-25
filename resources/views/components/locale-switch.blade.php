@php $next = app()->getLocale() === 'id' ? 'en' : 'id'; @endphp
<form method="POST" action="{{ route('locale.update') }}" {{ $attributes }}>
    @csrf
    <input type="hidden" name="locale" value="{{ $next }}">
    <button title="{{ __('Ganti bahasa') }}">🌐 {{ strtoupper($next) }}</button>
</form>
