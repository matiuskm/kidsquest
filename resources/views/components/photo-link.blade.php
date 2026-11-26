@props(['model', 'route'])
@if ($model->photo_path)
    <a href="{{ route($route, $model) }}" target="_blank" class="text-xs text-indigo-600 underline">📷 {{ __('Foto') }}</a>
@endif
