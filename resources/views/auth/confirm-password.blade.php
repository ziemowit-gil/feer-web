<x-guest-layout>
    @section('title', 'Potwierdź hasło')

    <h1 class="mb-1 text-xl font-bold text-ink">Potwierdź hasło</h1>
    <p class="mb-6 text-sm text-muted">To jest chroniona sekcja panelu. Potwierdź hasło, aby kontynuować.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="mb-1 block text-sm font-bold">Hasło</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            Potwierdź
        </button>
    </form>
</x-guest-layout>
