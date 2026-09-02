<x-guest-layout>
    @section('title', 'Logowanie certyfikatem — super-admin')
    @section('brand_heading', 'Dostęp superadmina')
    @section('brand_lead', 'Logowanie głównego administratora certyfikatem klienta (.pfx).')

    <div class="mb-6 flex flex-col items-center text-center">
        <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        </span>
        <h1 class="text-xl font-bold text-ink">Logowanie certyfikatem</h1>
        <p class="mt-1 text-sm text-muted">Dostęp wyłącznie dla głównego administratora instalacji.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.login') }}" class="space-y-5" enctype="multipart/form-data">
        @csrf

        <div>
            <label for="certificate" class="mb-1.5 block text-sm font-bold">Plik certyfikatu (.pfx)</label>
            <input id="certificate" type="file" name="certificate" accept=".pfx,.p12" required
                class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand @error('certificate') border-red-400 @enderror">
            @error('certificate')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="passphrase" class="mb-1.5 block text-sm font-bold">Hasło certyfikatu</label>
            <input id="passphrase" type="password" name="passphrase" required autocomplete="current-password"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand @error('passphrase') border-red-400 @enderror">
            @error('passphrase')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="flex w-full items-center justify-center gap-2.5 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            Zaloguj się
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-muted">
        <a href="{{ route('login') }}" class="rounded hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Wróć do zwykłego logowania</a>
    </p>
</x-guest-layout>
