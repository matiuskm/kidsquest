<div x-data="pushToggle" x-show="state !== 'unsupported'" x-cloak {{ $attributes }}>
    <button type="button" x-show="state === 'off'" @click="enable" class="underline">🔔 Aktifkan notifikasi</button>
    <span x-show="state === 'on'">🔔 Notifikasi aktif · <button type="button" @click="disable" class="underline">matikan</button></span>
    <span x-show="state === 'denied'">🔕 Notifikasi diblokir di pengaturan browser.</span>
</div>
