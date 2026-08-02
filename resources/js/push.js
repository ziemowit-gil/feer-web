// Web Push — rejestracja Service Workera i obsługa zgody na powiadomienia.

const VAPID_PUBLIC = document.querySelector('meta[name="vapid-public-key"]')?.content;

/** Konwertuje Base64URL na Uint8Array wymagany przez pushManager.subscribe(). */
async function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

/** Prosi o zgodę, subskrybuje push i wysyła klucze na serwer. */
async function subscribePush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

    const reg  = await navigator.serviceWorker.ready;
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') return;

    const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: await urlBase64ToUint8Array(VAPID_PUBLIC),
    });

    const key  = sub.getKey('p256dh');
    const auth = sub.getKey('auth');

    await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({
            endpoint: sub.endpoint,
            p256dh:   btoa(String.fromCharCode(...new Uint8Array(key))),
            auth:     btoa(String.fromCharCode(...new Uint8Array(auth))),
        }),
    });

    document.getElementById('push-prompt')?.remove();
    localStorage.setItem('push-subscribed', '1');
}

// Rejestracja Service Workera przy załadowaniu strony.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}

// Podpięcie przycisku zgody, jeśli baner jest obecny w DOM.
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('push-subscribed')) {
        document.getElementById('push-prompt')?.remove();
        return;
    }
    document.getElementById('push-subscribe-btn')?.addEventListener('click', subscribePush);
});

window.subscribePush = subscribePush;
