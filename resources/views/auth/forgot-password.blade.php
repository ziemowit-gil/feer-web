<x-guest-layout>
    @section('title', 'Nie pamiętasz hasła?')

    <h1 class="mb-1 text-xl font-bold text-ink">Nie pamiętasz hasła?</h1>
    <p class="mb-6 text-sm text-muted">Podaj adres e-mail konta, a wyślemy link do ustawienia nowego hasła.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-bold">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            Wyślij link do resetu hasła
        </button>
    </form>
</x-guest-layout>
