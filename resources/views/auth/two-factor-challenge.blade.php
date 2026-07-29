<x-guest-layout>
    @section('title', 'Weryfikacja dwuetapowa')
    @section('brand_heading', 'Weryfikacja dwuetapowa')
    @section('brand_lead', 'Potwierdź logowanie drugim składnikiem, aby chronić konto panelu.')

    <div class="mb-6 flex flex-col items-center text-center">
        <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-brand">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        </span>
        <h1 class="text-xl font-bold text-ink">Weryfikacja dwuetapowa</h1>
        <p class="mt-1 text-sm text-muted">Podaj drugi składnik, aby dokończyć logowanie.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
            <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if ($totpAvailable)
        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="mb-1.5 block text-sm font-bold">Kod z aplikacji uwierzytelniającej</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus
                    placeholder="np. 123456 lub kod zapasowy"
                    class="w-full rounded-lg border-gray-300 text-center font-mono tracking-widest focus:border-brand focus:ring-brand/30">
                <p class="mt-1.5 text-xs text-muted">Możesz też wpisać jednorazowy kod zapasowy.</p>
            </div>
            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Zaloguj się
            </button>
        </form>
    @endif

    @if ($yubikeyAvailable)
        @if ($totpAvailable)
            <div class="my-6 flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-muted">
                <span class="h-px flex-1 bg-gray-200"></span> lub <span class="h-px flex-1 bg-gray-200"></span>
            </div>
        @endif
        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="yubikey" class="mb-1.5 block text-sm font-bold"><i class="fa-solid fa-key text-muted" aria-hidden="true"></i> Klucz YubiKey</label>
                <input id="yubikey" name="yubikey" autocomplete="off" @if(!$totpAvailable) autofocus @endif
                    placeholder="Kliknij tutaj i dotknij klucza"
                    class="w-full rounded-lg border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand/30"
                    onchange="if (this.value.trim().length === 44) this.form.submit();">
                <p class="mt-1.5 text-xs text-muted">Ustaw kursor w polu i dotknij metalowego styku klucza.</p>
            </div>
            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-ink shadow-sm transition hover:bg-gray-50">
                <i class="fa-solid fa-key" aria-hidden="true"></i> Zaloguj kluczem
            </button>
        </form>
    @endif

    <p class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-muted transition hover:text-brand">Anuluj i wróć do logowania</a>
    </p>
</x-guest-layout>
