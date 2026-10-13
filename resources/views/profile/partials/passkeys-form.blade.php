{{-- Profil: klucze dostępu (passkeys / WebAuthn) — logowanie bez hasła kluczem YubiKey, biometrią albo telefonem. --}}
@php
    $credentials = auth()->user()->webauthnCredentials;
    $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold';
@endphp

@if (session('status') === 'passkey-added')
    <p class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm font-bold text-ink" role="status">Klucz dostępu dodany — możesz nim logować się bez hasła.</p>
@elseif (session('status') === 'passkey-removed')
    <p class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm font-bold text-ink" role="status">Klucz dostępu usunięty.</p>
@endif

@if ($credentials->isEmpty())
    <p class="text-sm text-ink">Nie masz jeszcze kluczy dostępu.</p>
@else
    <ul class="space-y-2" role="list">
        @foreach ($credentials as $credential)
            <li class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-gray-50 px-4 py-3 text-sm">
                <span>
                    <span class="block font-bold text-ink"><i class="fa-solid fa-key mr-1 text-brand-dark" aria-hidden="true"></i>{{ $credential->name }}</span>
                    <span class="block text-muted">Dodano {{ $credential->created_at->format('d.m.Y') }}{{ $credential->last_used_at ? ' · ostatnio użyty '.$credential->last_used_at->locale('pl')->diffForHumans() : ' · jeszcze nieużyty' }}</span>
                </span>
                <form method="POST" action="{{ route('passkeys.destroy', $credential) }}" onsubmit="return confirm(@js('Usunąć klucz „'.$credential->name.'”? Nie będzie można nim się logować.'))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="{{ $btn }} bg-white text-red-800 hover:bg-red-50">Usuń<span class="sr-only"> klucz {{ $credential->name }}</span></button>
                </form>
            </li>
        @endforeach
    </ul>
@endif

<div class="mt-5" x-data="{
        name: '', busy: false, error: '', supported: true,
        async add() {
            this.error = ''; this.busy = true;
            try {
                const w = window.feerWebAuthn;
                const o = await w.post(@js(route('passkeys.options')));
                o.challenge = w.b64ToBuf(o.challenge);
                o.user.id = w.b64ToBuf(o.user.id);
                o.excludeCredentials = (o.excludeCredentials || []).map(c => ({ ...c, id: w.b64ToBuf(c.id) }));
                const cred = await navigator.credentials.create({ publicKey: o });
                await w.post(@js(route('passkeys.store')), { name: this.name, credential: w.credentialToJson(cred) });
                window.location.reload();
            } catch (e) { this.error = window.feerWebAuthn.message(e); this.busy = false; }
        }
    }" x-init="supported = window.feerWebAuthn.supported()">
    <template x-if="supported">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[14rem] flex-1">
                <label for="passkey-name" class="mb-1 block text-sm font-bold text-ink">Nazwa klucza</label>
                <input id="passkey-name" type="text" x-model="name" maxlength="120" placeholder="np. YubiKey służbowy, Laptop — Windows Hello"
                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
            </div>
            <button type="button" @click="add()" :disabled="busy" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink disabled:opacity-60">
                <i class="fa-solid" :class="busy ? 'fa-circle-notch fa-spin' : 'fa-plus'" aria-hidden="true"></i>
                <span x-text="busy ? 'Potwierdź na urządzeniu…' : 'Dodaj klucz dostępu'">Dodaj klucz dostępu</span>
            </button>
        </div>
    </template>
    <p x-show="! supported" x-cloak class="text-sm text-ink">Ta przeglądarka nie obsługuje kluczy dostępu.</p>
    <p x-show="error" x-cloak class="mt-3 flex items-start gap-2 text-sm font-bold text-ink" role="alert">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-brand-dark" aria-hidden="true"></i><span x-text="error"></span>
    </p>
    <p class="mt-3 text-xs text-muted">Klucz działa tylko na tej domenie ({{ request()->getHost() }}). Przy logowaniu urządzenie zapyta o PIN, odcisk palca albo twarz.</p>
</div>
@include('auth.partials.webauthn-js')
