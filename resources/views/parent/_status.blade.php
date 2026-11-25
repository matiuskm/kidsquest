@if (session('status'))
    <div class="mb-4 rounded bg-green-100 p-3 text-sm text-green-800">{{ session('status') }}</div>
@endif
@error('review')
    <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-800">{{ $message }}</div>
@enderror
@error('score')
    <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-800">{{ $message }}</div>
@enderror
@error('letter')
    <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-800">{{ $message }}</div>
@enderror
