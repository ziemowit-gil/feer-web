@extends('admin.layout')

@section('title', 'Dostęp — ' . $page->title)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8">

    {{-- Nagłówek --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.podstrony.edit', $page) }}" class="text-sm text-muted hover:text-brand">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> {{ $page->title }}
        </a>
        <span class="text-muted">/</span>
        <h1 class="text-xl font-bold text-ink">Zarządzanie dostępem</h1>
    </div>

    @if (session('status'))
        <div role="status" class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i>
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div role="alert" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i class="fa-solid fa-circle-exclamation mr-1" aria-hidden="true"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Wygenerowane dane logowania (jednorazowy pokaz) --}}
    @if ($newCredentials)
        <div role="alert" class="mb-6 rounded-lg border-2 border-green-400 bg-green-50 p-5">
            <p class="mb-3 font-bold text-green-800">
                <i class="fa-solid fa-key mr-1" aria-hidden="true"></i>
                Nowe dane logowania dla: <strong>{{ $newCredentials['name'] }}</strong>
            </p>
            <dl class="space-y-2 rounded bg-white p-4 font-mono text-sm">
                <div class="flex gap-3">
                    <dt class="w-20 font-bold text-muted">Login:</dt>
                    <dd class="font-bold text-ink select-all">{{ $newCredentials['login'] }}</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-20 font-bold text-muted">Hasło:</dt>
                    <dd class="font-bold text-ink select-all">{{ $newCredentials['password'] }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-xs text-green-700">
                <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                Hasło widoczne jest tylko teraz. Skopiuj je i przekaż użytkownikowi — po odświeżeniu strony nie będzie można go odczytać.
            </p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">

        {{-- Lista użytkowników --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold text-ink">Użytkownicy dostępu ({{ $users->count() }})</h2>
                @if ($users->isNotEmpty())
                    <a href="{{ route('admin.podstrony.dostep.eksport', $page) }}"
                        class="inline-flex items-center gap-1 rounded border border-gray-300 bg-white px-3 py-1 text-sm text-muted hover:bg-gray-50">
                        <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksportuj CSV
                    </a>
                @endif
            </div>

            @if ($users->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-10 text-center text-sm text-muted">
                    Brak użytkowników. Dodaj pierwszego za pomocą formularza.
                </div>
            @else
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th class="px-4 py-2">Nazwa</th>
                                <th class="px-4 py-2">Login</th>
                                <th class="px-4 py-2">Aktywny</th>
                                <th class="px-4 py-2">Ostatnie logowanie</th>
                                <th class="px-4 py-2 text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($users as $user)
                                <tr class="{{ $user->is_active ? '' : 'bg-gray-50 opacity-70' }}">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-ink">{{ $user->name }}</p>
                                        @if ($user->notes)
                                            <p class="text-xs text-muted">{{ $user->notes }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-ink">{{ $user->login }}</td>
                                    <td class="px-4 py-3">
                                        @if ($user->is_active)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">
                                                <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i> Aktywny
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-gray-600">
                                                Dezaktywowany
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">
                                        {{ $user->last_login_at ? $user->last_login_at->format('d.m.Y H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Reset hasła --}}
                                            <form method="POST" action="{{ route('admin.podstrony.dostep.reset', [$page, $user]) }}"
                                                onsubmit="return confirm('Wygenerować nowe hasło dla {{ addslashes($user->name) }}?')">
                                                @csrf
                                                <button type="submit" title="Resetuj hasło"
                                                    class="rounded px-2 py-1 text-xs text-muted hover:bg-brand-light hover:text-brand">
                                                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                                    <span class="sr-only">Resetuj hasło</span>
                                                </button>
                                            </form>
                                            {{-- Aktywuj / dezaktywuj --}}
                                            <form method="POST" action="{{ route('admin.podstrony.dostep.aktywuj', [$page, $user]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="{{ $user->is_active ? 'Dezaktywuj' : 'Aktywuj' }}"
                                                    class="rounded px-2 py-1 text-xs text-muted {{ $user->is_active ? 'hover:bg-amber-50 hover:text-amber-600' : 'hover:bg-green-50 hover:text-green-700' }}">
                                                    <i class="fa-solid {{ $user->is_active ? 'fa-ban' : 'fa-circle-check' }}" aria-hidden="true"></i>
                                                    <span class="sr-only">{{ $user->is_active ? 'Dezaktywuj' : 'Aktywuj' }}</span>
                                                </button>
                                            </form>
                                            {{-- Usuń --}}
                                            <form method="POST" action="{{ route('admin.podstrony.dostep.destroy', [$page, $user]) }}"
                                                onsubmit="return confirm('Usunąć użytkownika {{ addslashes($user->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Usuń"
                                                    class="rounded px-2 py-1 text-xs text-muted hover:bg-red-50 hover:text-red-600">
                                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                    <span class="sr-only">Usuń</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Formularz dodawania --}}
        <div>
            <h2 class="mb-3 font-bold text-ink">Dodaj nowego użytkownika</h2>
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <p class="mb-4 text-xs text-muted">Login i hasło (13 znaków) zostaną wygenerowane automatycznie. Hasło zobaczysz tylko raz po kliknięciu przycisku.</p>

                <form method="POST" action="{{ route('admin.podstrony.dostep.store', $page) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="brand_name" class="mb-1 block text-sm font-bold">Imię i nazwisko / nazwa <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="text" id="brand_name" name="name" value="{{ old('name') }}" required
                            placeholder="np. Agencja XYZ / Jan Kowalski"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand @error('name') border-red-400 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="brand_notes" class="mb-1 block text-sm font-bold">Notatka <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="brand_notes" name="notes" value="{{ old('notes') }}"
                            placeholder="np. partner projektowy"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <button type="submit"
                        class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> Generuj dane logowania
                    </button>
                </form>
            </div>

            <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4 text-xs text-muted space-y-1">
                <p><strong>Format hasła:</strong> 13 znaków — małe litery, cyfry i znaki specjalne (!@#$%^&*).</p>
                <p><strong>Login:</strong> 10 znaków alfanumerycznych, unikatowy dla tej strony.</p>
                <p>Hasło jest zaszyfrowane w bazie — po zamknięciu tego komunikatu nie możesz go odczytać. W razie potrzeby użyj przycisku <em>Resetuj hasło</em>.</p>
            </div>
        </div>

    </div>
</div>
@endsection
