// KidsQuest service worker: cache aset statis, tampilkan push, buka halaman saat notifikasi diklik.
const CACHE = 'kq-static-v2';
const STATIC = /^\/(build|icons|sounds)\//;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    // Halaman (HTML) selalu ke jaringan: berisi data pribadi anak, tidak boleh di-cache.
    if (event.request.method !== 'GET' || url.origin !== self.location.origin || !STATIC.test(url.pathname)) return;

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(event.request);
            const fresh = fetch(event.request).then((res) => {
                if (res.ok) cache.put(event.request, res.clone());
                return res;
            });
            return cached || fresh;
        }),
    );
});

self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    event.waitUntil(self.registration.showNotification(data.title || 'KidsQuest', {
        body: data.body,
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/icon-192.png',
        data: data.data || {},
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            const open = clients.find((c) => new URL(c.url).pathname === target && 'focus' in c);
            return open ? open.focus() : self.clients.openWindow(target);
        }),
    );
});
