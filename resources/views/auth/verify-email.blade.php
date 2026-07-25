<x-guest-layout>
    @section('title', 'Potwierdź adres e-mail')

    <h1 class="mb-1 text-xl font-bold text-ink">Potwierdź adres e-mail</h1>
    <p class="mb-6 text-sm text-muted">Kliknij link wysłany na Twój adres e-mail, aby aktywować konto. Jeśli nie dotarł, możemy wysłać go ponownie.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-bold text-green-700">
            Nowy link weryfikacyjny został wysłany na podany adres e-mail.
        </div>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                Wyślij link ponownie
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-muted underline hover:text-brand">
                Wyloguj się
            </button>
        </form>
    </div>
</x-guest-layout>
