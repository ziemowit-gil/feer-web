@php
    $user = auth()->user();
    $pending = filled($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);
    $enabled = $user->hasTotpEnabled();
    $yubicoConfigured = $siteSettings->yubicoConfigured();
    $recoveryCodes = session('recovery_codes');
    $totpQr = $pending ? app(\App\Services\TwoFactor\TwoFactorService::class)->qrCodeDataUri($user, $user->two_factor_secret) : null;
@endphp

<header class="mb-5">
    <h2 class="flex items-center gap-2 text-lg font-bold text-ink">
        <i class="fa-solid fa-shield-halved text-brand" aria-hidden="true"></i>
        Logowanie dwuetapowe (2FA)
    </h2>
    <p class="mt-1 text-sm text-muted">
        Dodatkowa ochrona konta panelu przy logowaniu hasłem: aplikacja uwierzytelniająca (TOTP) lub klucz sprzętowy YubiKey.
        Logowanie przez Microsoft 365 korzysta z zabezpieczeń MS i pomija ten krok.
    </p>
</header>

@if (session('status') === 'two-factor-required')
    <div class="mb-5 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
        <span>Jako administrator musisz skonfigurować co najmniej jedną metodę 2FA, aby korzystać z panelu.</span>
    </div>
@endif

{{-- Świeżo wygenerowane kody zapasowe --}}
@if ($recoveryCodes)
    <div class="mb-5 rounded-lg border border-brand/30 bg-brand-light/60 p-4">
        <p class="mb-2 flex items-center gap-2 text-sm font-bold text-brand-dark">
            <i class="fa-solid fa-key" aria-hidden="true"></i> Kody zapasowe — zapisz je w bezpiecznym miejscu
        </p>
        <p class="mb-3 text-xs text-muted">Każdy kod działa jednorazowo. Użyj ich, gdy nie masz dostępu do aplikacji lub klucza.</p>
        <ul class="grid grid-cols-2 gap-x-6 gap-y-1 font-mono text-sm text-ink">
            @foreach ($recoveryCodes as $code)
                <li>{{ $code }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ===================== TOTP ===================== --}}
<div class="rounded-lg border border-gray-200 p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="font-bold text-ink">Aplikacja uwierzytelniająca (TOTP)</p>
            <p class="text-xs text-muted">Google Authenticator, Microsoft Authenticator, 1Password itp.</p>
        </div>
        @if ($enabled)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Aktywne
            </span>
        @elseif ($pending)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">
                <i class="fa-solid fa-clock" aria-hidden="true"></i> Do potwierdzenia
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600">Wyłączone</span>
        @endif
    </div>

    @if ($pending)
        <div class="mt-4 grid gap-5 sm:grid-cols-[auto_1fr] sm:items-start">
            <img src="{{ $totpQr }}" alt="Kod QR do skonfigurowania aplikacji uwierzytelniającej" class="h-40 w-40 rounded border border-gray-200 bg-white p-1">
            <div class="space-y-3">
                <p class="text-sm text-muted">Zeskanuj kod QR w aplikacji uwierzytelniającej lub wpisz klucz ręcznie:</p>
                <code class="block break-all rounded bg-gray-100 px-2 py-1 font-mono text-xs">{{ $user->two_factor_secret }}</code>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-2">
                    @csrf
                    <label for="totp_code" class="block text-sm font-bold">Kod z aplikacji</label>
                    <div class="flex gap-2">
                        <input id="totp_code" name="code" inputmode="numeric" autocomplete="one-time-code" required
                            placeholder="123456"
                            class="w-40 rounded border-gray-300 text-center font-mono tracking-widest focus:border-brand focus:ring-brand">
                        <button type="submit" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">Potwierdź i włącz</button>
                    </div>
                    @error('code') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </form>
                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-muted hover:text-red-600">Anuluj konfigurację</button>
                </form>
            </div>
        </div>
    @elseif ($enabled)
        <div class="mt-4 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('two-factor.recovery') }}">
                @csrf
                <button type="submit" class="rounded border border-gray-300 px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i> Nowe kody zapasowe
                </button>
            </form>
            <form method="POST" action="{{ route('two-factor.disable') }}" onsubmit="return confirm('Wyłączyć aplikację TOTP?');">
                @csrf @method('DELETE')
                <button type="submit" class="rounded border border-red-300 px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Wyłącz TOTP
                </button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-4">
            @csrf
            <button type="submit" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Włącz aplikację TOTP
            </button>
        </form>
    @endif
</div>

{{-- ===================== YubiKey ===================== --}}
<div class="mt-4 rounded-lg border border-gray-200 p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="font-bold text-ink">Klucz sprzętowy YubiKey</p>
            <p class="text-xs text-muted">Jednorazowe hasło Yubico OTP (dotknięcie klucza).</p>
        </div>
        @if ($user->hasYubikey())
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ count($user->yubikey_ids) }} klucz(e)
            </span>
        @endif
    </div>

    @if (! $yubicoConfigured)
        <p class="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            Uwierzytelnianie kluczem YubiKey nie jest jeszcze skonfigurowane. Administrator musi podać dane Yubico API w
            <strong>Ustawienia → Logowanie</strong>.
        </p>
    @else
        @if ($user->hasYubikey())
            <ul class="mt-3 divide-y divide-gray-100 rounded border border-gray-100">
                @foreach ($user->yubikey_ids as $publicId)
                    <li class="flex items-center justify-between px-3 py-2 text-sm">
                        <span class="font-mono text-muted">{{ $publicId }}</span>
                        <form method="POST" action="{{ route('two-factor.yubikey.remove') }}" onsubmit="return confirm('Usunąć ten klucz?');">
                            @csrf @method('DELETE')
                            <input type="hidden" name="public_id" value="{{ $publicId }}">
                            <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-700">Usuń</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('two-factor.yubikey.add') }}" class="mt-3 space-y-2">
            @csrf
            <label for="yubikey_otp" class="block text-sm font-bold">Dodaj klucz</label>
            <div class="flex gap-2">
                <input id="yubikey_otp" name="otp" autocomplete="off" required
                    placeholder="Kliknij i dotknij klucza YubiKey"
                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand"
                    onchange="if (this.value.trim().length === 44) this.form.submit();">
                <button type="submit" class="flex-none rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zarejestruj</button>
            </div>
            @error('otp') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </form>
    @endif
</div>
