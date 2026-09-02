<x-guest-layout>
    @section('title', 'Logowanie')
    @section('brand_heading', 'Panel administracyjny')
    @section('brand_lead', 'Zaloguj się, aby zarządzać treścią serwisu ' . $siteSettings->site_name . '.')

    @php
        $msOnly = $siteSettings->microsoftOnlyLogin();
        $emergency = $emergency ?? false;
        $showLocalForm = ! $msOnly || $emergency;
    @endphp

    <div class="mb-6 flex flex-col items-center text-center">
        <p class="mb-3 text-3xl leading-none">
            <span style="font-family:'Pacifico',cursive;color:var(--color-brand)">We</span><span style="font-family:'Pacifico',cursive;font-weight:300">CMS</span>
        </p>
        <h1 class="text-xl font-bold text-ink">Zaloguj się</h1>
        <p class="mt-1 text-sm text-muted">{{ $siteSettings->site_name }}</p>
    </div>

    @if ($emergency)
        <div class="mb-5 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
            <i class="fa-solid fa-shield-halved shrink-0"></i>
            <span>Dostęp awaryjny — logowanie hasłem</span>
        </div>
    @endif

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($msOnly && ! $emergency)
        {{-- Tryb "tylko MS": wyświetl wyłącznie przycisk Microsoft --}}
        <div class="mb-5 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <p>Logowanie do panelu edytora i administratora CMS odbywa się za pomocą usługi jednokrotnego logowania — tymi samymi danymi, co do poczty elektronicznej organizacji.</p>
            <p class="mt-2 font-medium">Użyj przycisku niżej i zaloguj się tak samo jak do poczty.</p>
        </div>
        <a href="{{ route('auth.microsoft.redirect') }}"
            class="flex w-full items-center justify-center gap-2.5 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark hover:shadow-md active:scale-[0.99]">
            <svg class="h-5 w-5" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            Zaloguj się przez Microsoft 365
        </a>
    @endif

    @if ($showLocalForm)
        <form method="POST" action="{{ route('login') }}" class="space-y-5"
            x-data="{ submitting: false, showPassword: false }" @submit="submitting = true">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-sm font-bold">E-mail</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="w-full rounded-lg border-gray-300 pl-10 transition focus:border-brand focus:ring-brand/30">
                </div>
                @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-bold">Hasło</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                        class="w-full rounded-lg border-gray-300 pl-10 pr-10 transition focus:border-brand focus:ring-brand/30">
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted transition hover:text-brand"
                        :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'">
                        <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-muted">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand focus:ring-brand/30">
                    Zapamiętaj mnie
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand transition hover:text-brand-dark">Nie pamiętasz hasła?</a>
                @endif
            </div>

            <button type="submit" :disabled="submitting"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark hover:shadow-md active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70">
                <i class="fa-solid" :class="submitting ? 'fa-circle-notch fa-spin' : 'fa-right-to-bracket'"></i>
                <span x-text="submitting ? 'Logowanie…' : 'Zaloguj się'"></span>
            </button>
        </form>

        @if (! $msOnly && $siteSettings->microsoftLoginEnabled())
            <div class="my-6 flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-muted">
                <span class="h-px flex-1 bg-gray-200"></span>
                lub
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>

            <p class="mb-3 text-sm text-muted">Użyj przycisku niżej i zaloguj się tak samo jak do poczty.</p>

            <a href="{{ route('auth.microsoft.redirect') }}"
                class="flex w-full items-center justify-center gap-2.5 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-ink shadow-sm transition hover:bg-gray-50 hover:shadow-md active:scale-[0.99]">
                <svg class="h-5 w-5" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                    <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                </svg>
                Zaloguj się przez Microsoft 365
            </a>
        @endif

        @if (! $msOnly && $siteSettings->googleLoginEnabled())
            <div class="my-6 flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-muted">
                <span class="h-px flex-1 bg-gray-200"></span>
                lub
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>

            <a href="{{ route('auth.google.redirect') }}"
                class="flex w-full items-center justify-center gap-2.5 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-ink shadow-sm transition hover:bg-gray-50 hover:shadow-md active:scale-[0.99]">
                <svg class="h-5 w-5" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.4-.4-3.5z"/>
                    <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.8 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4c-7.5 0-14 4.2-17.7 10.7z"/>
                    <path fill="#4CAF50" d="M24 44c5.5 0 10.4-1.9 14.2-5.2l-6.6-5.6C29.6 34.9 26.9 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.6 5.1C9.9 39.7 16.4 44 24 44z"/>
                    <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.4l6.6 5.6C41.6 36.6 44 30.8 44 24c0-1.2-.1-2.4-.4-3.5z"/>
                </svg>
                Zaloguj się przez Google
            </a>
        @endif
    @endif
</x-guest-layout>
