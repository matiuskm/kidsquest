// Langganan Web Push + pembersihan langganan device saat keluar.
const withTimeout = (promise, ms = 1500) => Promise.race([promise, new Promise((_, rej) => setTimeout(rej, ms))]);

const currentSubscription = async () => {
    const reg = await withTimeout(navigator.serviceWorker.ready);
    return reg.pushManager.getSubscription();
};

const keyToBytes = (b64) => {
    const raw = atob((b64 + '='.repeat((4 - (b64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from(raw, (c) => c.charCodeAt(0));
};

const csrf = () => document.querySelector('meta[name=csrf-token]').content;
const call = (method, body) => fetch('/push/subscribe', {
    method,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    body: JSON.stringify(body),
});

export const pushToggle = () => ({
    state: 'unsupported',

    async init() {
        if (!('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window)) return;
        if (Notification.permission === 'denied') return (this.state = 'denied');
        try {
            this.state = (await currentSubscription()) ? 'on' : 'off';
        } catch {
            this.state = 'off';
        }
    },

    async enable() {
        const key = document.querySelector('meta[name=vapid-public-key]')?.content;
        if (!key) return;
        if ((await Notification.requestPermission()) !== 'granted') return (this.state = 'denied');

        const reg = await withTimeout(navigator.serviceWorker.ready, 5000);
        const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyToBytes(key) });
        const json = sub.toJSON();
        const res = await call('POST', { ...json, contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0] });
        this.state = res.ok ? 'on' : 'off';
    },

    async disable() {
        const sub = await currentSubscription();
        if (sub) {
            await call('DELETE', { endpoint: sub.endpoint });
            await sub.unsubscribe();
        }
        this.state = 'off';
    },
});

// Form keluar / login anak: kirim endpoint device ini supaya server membersihkan langganannya.
const endpointForm = (unsubscribe) => () => ({
    async submit(event) {
        const form = event.target;
        try {
            const sub = await currentSubscription();
            if (sub) {
                form.querySelector('[name=push_endpoint]').value = sub.endpoint;
                if (unsubscribe) await sub.unsubscribe();
            }
        } catch {
            // tanpa langganan / service worker; lanjutkan
        }
        form.submit();
    },
});

export const logoutForm = endpointForm(true);
export const pinForm = endpointForm(false);
