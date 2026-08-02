@extends('admin.layout')

@section('title', 'Przenoszenie treści (eksport / import)')

@section('content')
    <div class="max-w-3xl space-y-6">
        <p class="text-sm text-muted">
            Narzędzie do przeniesienia treści na inny hosting bez utraty danych. Eksport pakuje treść z bazy oraz
            pliki mediów do jednej paczki ZIP. Import wgrywa ją metodą „upsert po ID" — <strong>nie usuwa</strong>
            istniejących wpisów, więc wgranie na działający system nie wywala treści.
        </p>

        {{-- Eksport --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-base font-bold text-ink">1. Eksport treści</h2>
            <p class="mt-1 text-sm text-muted">Pobierz paczkę ZIP z bieżącą treścią i mediami (z tego serwera).</p>
            <a href="{{ route('admin.tresc.export') }}" class="mt-4 inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-download" aria-hidden="true"></i> Pobierz paczkę ZIP
            </a>
        </div>

        {{-- Import --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-base font-bold text-ink">2. Import treści</h2>
            <p class="mt-1 text-sm text-muted">Wgraj paczkę ZIP wyeksportowaną z innej instalacji. Dane o tym samym ID zostaną nadpisane, reszta pozostaje bez zmian.</p>

            <form method="POST" action="{{ route('admin.tresc.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3"
                onsubmit="return confirm('Zaimportować treść z tej paczki? Wpisy o tym samym ID zostaną nadpisane.');">
                @csrf
                <input type="file" name="package" accept=".zip" required
                    class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                @error('package') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="inline-flex items-center gap-2 rounded border border-brand px-5 py-2.5 text-sm font-bold text-brand hover:bg-brand-light">
                    <i class="fa-solid fa-upload" aria-hidden="true"></i> Wgraj i zaimportuj
                </button>
            </form>
        </div>

        {{-- Bezpieczne wdrożenie --}}
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
            <h2 class="text-base font-bold">Bezpieczne wdrożenie na nowy hosting</h2>
            <ol class="mt-2 list-decimal space-y-1 pl-5">
                <li>Wgraj kod aplikacji na nowy serwer i uruchom <code>php artisan migrate --force</code> (migracje są addytywne — <strong>nigdy</strong> nie używaj <code>migrate:fresh</code> ani seedów na produkcji).</li>
                <li>Wyeksportuj treść ze starej instalacji (przycisk powyżej lub <code>php artisan content:export</code>).</li>
                <li>Zaimportuj paczkę na nowym serwerze (formularz powyżej lub <code>php artisan content:import plik.zip</code>).</li>
            </ol>
            <p class="mt-2 text-xs">Eksport pomija konta użytkowników, uprawnienia oraz zgłoszenia z formularzy (dane osobowe). Blog „Wiem FEER" działa na osobnej bazie i nie wchodzi w skład paczki.</p>
        </div>

        <details class="rounded-lg border border-gray-200 bg-white p-4 text-sm">
            <summary class="cursor-pointer font-bold text-ink">Co obejmuje paczka ({{ count($tables) }} tabel)</summary>
            <p class="mt-2 break-words text-xs text-muted">{{ implode(', ', $tables) }}</p>
        </details>
    </div>
@endsection
