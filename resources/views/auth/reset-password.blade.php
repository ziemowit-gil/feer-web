<x-guest-layout>
    @section('title', 'Ustaw nowe hasło')

    <h1 class="mb-6 text-xl font-bold text-ink">Ustaw nowe hasło</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="mb-1 block text-sm font-bold">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-bold">Nowe hasło</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-bold">Powtórz nowe hasło</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            Ustaw hasło
        </button>
    </form>
</x-guest-layout>
