<x-guest-layout>
    @section('title', 'Logowanie')
    @section('brand_heading', 'Panel administracyjny')
    @section('brand_lead', 'Zaloguj się, aby zarządzać treścią serwisu ' . $siteSettings->site_name . '.')

    {{--
        Płasko, kolory firmowe: bez ramek ozdobnych i kolorów spoza marki.
        Pola formularza mają obramowanie (granica pola musi być widoczna —
        WCAG 1.4.11, kontrast 3:1). Przyciski: brand-dark z białym tekstem.
        Błędy: tekst + ikona + aria-invalid/aria-describedby (bez czerwieni).
    --}}
    @php
        $msOnly = $siteSettings->microsoftOnlyLogin();
        $emergency = $emergency ?? false;
        $showLocalForm = ! $msOnly || $emergency;
        $field = 'w-full rounded-md border border-gray-500 bg-white py-2.5 pl-10 text-ink focus:border-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-dark';
        $primary = 'flex w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-5 py-3 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 disabled:cursor-not-allowed';
        $secondary = 'flex w-full items-center justify-center gap-2.5 rounded-md bg-brand-light px-5 py-3 text-base font-bold text-ink transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $notice = 'mb-5 flex items-start gap-2 rounded-md bg-brand-light px-4 py-3 text-sm text-ink';
    @endphp

    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-widest text-muted">Panel administracyjny</p>
        <h1 class="mt-1 text-2xl font-bold text-ink">Zaloguj się</h1>
        <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
    </div>

    @if ($emergency)
        <p class="{{ $notice }} font-bold">
            <i class="fa-solid fa-shield-halved mt-0.5 text-brand-dark" aria-hidden="true"></i>
            <span>Dostęp awaryjny — logowanie hasłem.</span>
        </p>
    @endif

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if (session('error'))
        <div class="{{ $notice }}" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-0.5 text-brand-dark" aria-hidden="true"></i>
            <span><strong>Nie udało się zalogować.</strong> {{ session('error') }}</span>
        </div>
    @endif

    @if ($msOnly && ! $emergency)
        {{-- Tryb „tylko Microsoft": wyłącznie przycisk Microsoft 365 --}}
        <p class="mb-6 text-ink">Zaloguj się kontem organizacji — tymi samymi danymi, co do poczty elektronicznej.</p>
        <a href="{{ route('auth.microsoft.redirect') }}" class="{{ $primary }}">
            @include('auth.partials.microsoft-logo')
            Zaloguj się przez Microsoft 365
        </a>
    @endif

    @if ($showLocalForm)
        <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate
            x-data="{ submitting: false, showPassword: false }" @submit="submitting = true">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block font-bold text-ink">E-mail</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted" aria-hidden="true">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        class="{{ $field }} pr-3">
                </div>
                @error('email')
                    <p id="email-error" class="mt-1.5 flex items-start gap-1.5 text-sm font-bold text-ink"><i class="fa-solid fa-circle-exclamation mt-0.5 text-brand-dark" aria-hidden="true"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block font-bold text-ink">Hasło</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted" aria-hidden="true">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input id="password" :type="showPassword ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        class="{{ $field }} pr-12">
                    <button type="button" @click="showPassword = !showPassword" :aria-pressed="showPassword.toString()"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                        aria-label="Pokaż hasło">
                        <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password')
                    <p id="password-error" class="mt-1.5 flex items-start gap-1.5 text-sm font-bold text-ink"><i class="fa-solid fa-circle-exclamation mt-0.5 text-brand-dark" aria-hidden="true"></i>{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-ink">
                    <input type="checkbox" name="remember" class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                    Zapamiętaj mnie
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm {{ $link }}">Nie pamiętasz hasła?</a>
                @endif
            </div>

            <button type="submit" :disabled="submitting" class="{{ $primary }}">
                <i class="fa-solid" :class="submitting ? 'fa-circle-notch fa-spin' : 'fa-right-to-bracket'" aria-hidden="true"></i>
                <span x-text="submitting ? 'Logowanie…' : 'Zaloguj się'">Zaloguj się</span>
            </button>
        </form>

        @if (! $msOnly && ($siteSettings->microsoftLoginEnabled() || $siteSettings->googleLoginEnabled()))
            <p class="my-6 flex items-center gap-3 text-sm font-bold text-muted">
                <span class="h-px flex-1 bg-gray-200" aria-hidden="true"></span>
                albo
                <span class="h-px flex-1 bg-gray-200" aria-hidden="true"></span>
            </p>

            <div class="space-y-3">
                @if ($siteSettings->microsoftLoginEnabled())
                    <a href="{{ route('auth.microsoft.redirect') }}" class="{{ $secondary }}">
                        @include('auth.partials.microsoft-logo')
                        Zaloguj się przez Microsoft 365
                    </a>
                @endif

                @if ($siteSettings->googleLoginEnabled())
                    <a href="{{ route('auth.google.redirect') }}" class="{{ $secondary }}">
                        <svg class="h-5 w-5" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.4-.4-3.5z"/>
                            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.8 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4c-7.5 0-14 4.2-17.7 10.7z"/>
                            <path fill="#4CAF50" d="M24 44c5.5 0 10.4-1.9 14.2-5.2l-6.6-5.6C29.6 34.9 26.9 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.6 5.1C9.9 39.7 16.4 44 24 44z"/>
                            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.4l6.6 5.6C41.6 36.6 44 30.8 44 24c0-1.2-.1-2.4-.4-3.5z"/>
                        </svg>
                        Zaloguj się przez Google
                    </a>
                @endif
            </div>
            @if ($siteSettings->microsoftLoginEnabled())
                <p class="mt-3 text-sm text-ink">Konto Microsoft 365 to te same dane, co do poczty organizacji.</p>
            @endif
        @endif
    @endif
</x-guest-layout>
