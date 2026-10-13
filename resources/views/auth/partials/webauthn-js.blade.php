{{-- Pomocnicze funkcje WebAuthn (passkeys): base64url ↔ ArrayBuffer i zamiana odpowiedzi przeglądarki na JSON. --}}
@once
<script>
    window.feerWebAuthn = {
        supported: () => !! (window.PublicKeyCredential && navigator.credentials),
        b64ToBuf(s) { s = s.replace(/-/g, '+').replace(/_/g, '/'); while (s.length % 4) s += '='; return Uint8Array.from(atob(s), c => c.charCodeAt(0)).buffer; },
        bufToB64(b) { return btoa(String.fromCharCode(...new Uint8Array(b))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''); },
        async post(url, body) {
            const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(body || {}) });
            const data = await r.json().catch(() => ({}));
            if (! r.ok) throw new Error(data.message || 'Nie udało się — spróbuj ponownie.');
            return data;
        },
        credentialToJson(c) {
            const r = c.response, out = { id: c.id, rawId: this.bufToB64(c.rawId), type: c.type, response: { clientDataJSON: this.bufToB64(r.clientDataJSON) } };
            if (r.attestationObject) { out.response.attestationObject = this.bufToB64(r.attestationObject); out.response.transports = r.getTransports ? r.getTransports() : []; }
            if (r.authenticatorData) { out.response.authenticatorData = this.bufToB64(r.authenticatorData); out.response.signature = this.bufToB64(r.signature); out.response.userHandle = r.userHandle ? this.bufToB64(r.userHandle) : null; }
            return out;
        },
        message(e) {
            if (e && e.name === 'NotAllowedError') return 'Anulowano albo minął czas na potwierdzenie na urządzeniu.';
            if (e && e.name === 'InvalidStateError') return 'Ten klucz jest już dodany do konta.';
            return (e && e.message) || 'Nie udało się — spróbuj ponownie.';
        },
    };
</script>
@endonce
