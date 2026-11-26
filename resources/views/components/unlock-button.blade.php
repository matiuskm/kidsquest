@props(['child'])
<form method="POST" action="{{ route('parent.children.unlock', $child) }}">
    @csrf
    <button class="rounded-full bg-rose-600 px-4 py-1.5 text-sm font-semibold text-white">🔓 {{ __('Buka kunci') }}</button>
</form>
