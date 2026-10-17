<x-guest-layout>
    @section('title', 'Potwierdź tożsamość')

    {{-- Potwierdzenie przed wrażliwą operacją: hasło albo (gdy włączone) Microsoft 365 z ponownym logowaniem. --}}
    @php
        $hasPassword = filled(auth()->user()?->getAuthPassword());
        $msOn = $siteSettings->microsoftLoginEnabled() && Route::has('auth.microsoft.confirm');
        $btn = 'flex min-h-11 w-full items-center justify-center gap-2 rounded px-5 py-2 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    @endphp

    <h1 class="mb-1 text-xl font-bold text-ink">Potwierdź tożsamość</h1>
    <p class="mb-6 text-sm text-ink">To jest chroniona sekcja panelu. {{ $hasPassword ? 'Potwierdź hasłem' : 'Potwierdź' }}{{ $msOn ? ($hasPassword ? ' albo kontem Microsoft 365' : ' kontem Microsoft 365') : '' }}, aby kontynuować.</p>

    @if (session('error'))
        <p role="alert" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</p>
    @endif

    @if ($hasPassword)
        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="mb-1 block text-sm font-bold">Hasło</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    class="min-h-11 w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                @error('password') <p id="password-error" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                Potwierdź hasłem
            </button>
        </form>
    @endif

    @if ($msOn)
        @if ($hasPassword)
            <p class="my-5 flex items-center gap-3 text-xs text-muted">
                <span class="h-px flex-1 bg-gray-200" aria-hidden="true"></span> albo <span class="h-px flex-1 bg-gray-200" aria-hidden="true"></span>
            </p>
        @endif
        <a href="{{ route('auth.microsoft.confirm') }}" class="{{ $btn }} border border-gray-500 bg-white text-ink hover:bg-gray-50">
            @include('auth.partials.microsoft-logo')
            Potwierdź przez Microsoft 365
        </a>
        <p class="mt-2 text-xs text-muted">Microsoft poprosi o ponowne zalogowanie na Twoje konto.</p>
    @elseif (! $hasPassword)
        <p class="text-sm text-ink">Twoje konto nie ma hasła, a logowanie przez Microsoft 365 jest wyłączone — poproś administratora o ustawienie hasła.</p>
    @endif
</x-guest-layout>
