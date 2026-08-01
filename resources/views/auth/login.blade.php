<x-guest-layout>
    @section('title', 'Logowanie')
    @section('brand_heading', 'Panel administracyjny')
    @section('brand_lead', 'Zaloguj się, aby zarządzać treścią serwisu ' . $siteSettings->site_name . '.')

    @php $msOnly = $siteSettings->microsoftOnlyLogin(); @endphp

    <div class="mb-6 flex flex-col items-center text-center">
        <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-brand">
            <i class="fa-solid fa-lock"></i>
        </span>
        <h1 class="text-xl font-bold text-ink">Zaloguj się</h1>
        <p class="mt-1 text-sm text-muted">Panel administracyjny — {{ $siteSettings->site_name }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($msOnly)
        {{-- Tryb "tylko MS": główny przycisk MS, formularz ukryty za linkiem awaryjnym --}}
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

        <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" class="mt-8">
            <button type="button" @click="open = !open"
                class="mx-auto flex items-center gap-1.5 text-xs text-muted transition hover:text-ink"
                :aria-expanded="open">
                <i class="fa-solid fa-shield-halved text-[10px]"></i>
                <span>Dostęp awaryjny</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="open" x-cloak x-transition class="mt-4">
                <p class="mb-4 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Formularz dostępu awaryjnego — tylko dla kont z uprawnieniem lokalnego logowania.
                </p>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5{{ $msOnly ? '' : '' }}"
        x-data="{ submitting: false, showPassword: false }" @submit="submitting = true">
        @csrf

        @error('email')
            <p class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <div>
            <label for="email" class="mb-1.5 block text-sm font-bold">E-mail</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                    <i class="fa-solid fa-envelope"></i>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full rounded-lg border-gray-300 pl-10 transition focus:border-brand focus:ring-brand/30">
            </div>
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

    @if ($msOnly)
            </div>
        </div>
    @elseif ($siteSettings->microsoftLoginEnabled())
        <div class="my-6 flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-muted">
            <span class="h-px flex-1 bg-gray-200"></span>
            lub
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

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
</x-guest-layout>
