@props(['selected' => null])

<div class="flex flex-wrap gap-2" role="radiogroup">
    @foreach (config('kidsquest.avatars') as $emoji)
        <label class="cursor-pointer">
            <input type="radio" name="avatar" value="{{ $emoji }}" class="peer sr-only" @checked(old('avatar', $selected) === $emoji) required>
            <span class="block rounded-2xl border-4 border-transparent p-2 text-4xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50">{{ $emoji }}</span>
        </label>
    @endforeach
</div>
