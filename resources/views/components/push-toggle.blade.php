<div x-data="pushToggle" x-show="state !== 'unsupported'" x-cloak {{ $attributes }}>
    <button type="button" x-show="state === 'off'" @click="enable" class="underline">{{ __('🔔 Aktifkan notifikasi') }}</button>
    <span x-show="state === 'on'">{{ __('🔔 Notifikasi aktif ·') }} <button type="button" @click="disable" class="underline">{{ __('matikan') }}</button></span>
    <span x-show="state === 'denied'">{{ __('🔕 Notifikasi diblokir di pengaturan browser.') }}</span>
</div>
