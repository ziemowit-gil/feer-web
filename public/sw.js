// Service Worker FEER — obsługuje cache offline, powiadomienia push i kliknięcia powiadomień.
const CACHE = 'feer-v1';
const PRECACHE = ['/'];

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(PRECACHE)));
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Strategia network-first: próbuje sieć, przy braku zwraca cache.
self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});

// Odbiór powiadomienia push — wyświetla natywne powiadomienie systemowe.
self.addEventListener('push', e => {
    if (!e.data) return;

    const data = e.data.json();

    e.waitUntil(
        self.registration.showNotification(data.title || 'FEER', {
            body:  data.body  || '',
            icon:  data.icon  || '/img/pwa-icon-192.png',
            badge: '/img/pwa-icon-192.png',
            data:  { url: data.url || '/' },
        })
    );
});

// Kliknięcie powiadomienia — otwiera lub skupia właściwy URL.
self.addEventListener('notificationclick', e => {
    e.notification.close();

    e.waitUntil(
        clients.matchAll({ type: 'window' }).then(wins => {
            const target = e.notification.data?.url || '/';
            const match = wins.find(w => w.url === target && 'focus' in w);
            return match ? match.focus() : clients.openWindow(target);
        })
    );
});
