@extends('admin.layout')

@section('title', 'Audyt opisów alternatywnych')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-ink">Audyt opisów alternatywnych</h1>
            <p class="text-sm text-muted">Obrazy w bibliotece bez opisu alternatywnego (alt). Opis czyta na głos czytnik ekranu, więc jego brak wyklucza osoby niewidome.</p>
        </div>
        <a href="{{ route('admin.multimedia.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do multimediów
        </a>
    </div>

    @if ($total === 0)
        <div class="rounded-lg border border-green-200 bg-green-50 p-8 text-center">
            <i class="fa-solid fa-circle-check mb-2 text-2xl text-green-600" aria-hidden="true"></i>
            <p class="font-bold text-green-800">Wszystkie obrazy mają opis alternatywny.</p>
            <p class="text-sm text-green-700">Świetnie — nic nie wymaga uzupełnienia.</p>
        </div>
    @else
        <p class="mb-4 text-sm text-muted">
            Do uzupełnienia: <span class="font-bold text-ink">{{ $total }}</span>
            {{ trans_choice('obraz|obrazy|obrazów', $total) }}.
            Wpisz krótki opis tego, co przedstawia zdjęcie (np. „Wolontariusze pakują paczki"), a nie „zdjęcie" czy nazwę pliku.
            Opis zapisuje się na pliku i jest używany przy wstawianiu go do treści.
        </p>

        <ul class="space-y-3">
            @foreach ($rows as $row)
                <li class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 sm:flex-row sm:items-center">
                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener"
                        class="block h-20 w-20 flex-none overflow-hidden rounded border border-gray-200 bg-gray-50">
                        <img src="{{ $row['url'] }}" alt="Podgląd pliku {{ $row['file_name'] }}" class="h-full w-full object-cover">
                    </a>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-ink" title="{{ $row['file_name'] }}">{{ $row['file_name'] }}</p>
                        <p class="text-xs text-muted">
                            {{ $row['size'] }} ·
                            @if ($row['owner']['url'])
                                <a href="{{ $row['owner']['url'] }}" class="text-brand hover:underline">{{ $row['owner']['label'] }}</a>
                            @else
                                {{ $row['owner']['label'] }}
                            @endif
                        </p>

                        <form method="POST" action="{{ route('admin.multimedia.alt', $row['id']) }}" class="mt-2 flex flex-wrap items-center gap-2">
                            @csrf
                            @method('PUT')
                            <label class="sr-only" for="alt-{{ $row['id'] }}">Opis alternatywny dla pliku {{ $row['file_name'] }}</label>
                            <input type="text" id="alt-{{ $row['id'] }}" name="alt" maxlength="255" required
                                placeholder="Opisz, co przedstawia zdjęcie…"
                                class="min-w-0 flex-1 rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <button type="submit" class="rounded bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                <i class="fa-solid fa-check" aria-hidden="true"></i> Zapisz opis
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $rows->links() }}
        </div>
    @endif
@endsection
