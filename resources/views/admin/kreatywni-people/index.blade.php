@extends('admin.layout')

@section('title', 'Osoby – Kreatywni')

{{--
    Mikro moduł „Osoby – Kreatywni” (KreatywniPersonController): osoby z sekcji „Kto prowadzi warsztaty” na stronie
    „O nas” i w sekcji Zespół na stronie głównej. Kolejność: Wyżej/Niżej. Pusty moduł = strona bierze osoby z tekstów
    szablonu; przycisk importu przenosi je tutaj. Cele ≥ 44 px, kontrast ink/muted na bieli.
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $iconBtn = 'inline-flex h-11 w-11 items-center justify-center rounded text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark disabled:cursor-not-allowed disabled:opacity-40';
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-2xl text-sm text-ink">
            Osoby, które prowadzą warsztaty. Pokazują się w sekcji „Kto prowadzi warsztaty” na stronie „O nas” i w sekcji
            Zespół na stronie głównej (gdy jest włączona w Ustawienia → Szablon). Kolejność tutaj = kolejność na stronie.
        </p>
        <a href="{{ route('admin.kreatywni-osoby.create') }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj osobę
        </a>
    </div>

    @if ($people->isEmpty())
        <div class="rounded-md bg-white px-6 py-10 text-center">
            <p class="font-bold text-ink">Nie ma jeszcze osób w module</p>
            <p class="mx-auto mt-1 max-w-lg text-sm text-ink">
                Do tego czasu strona pokazuje osoby wpisane w tekstach szablonu (Ustawienia → Szablon).
                @if ($importable > 0) Możesz je przenieść tutaj jednym kliknięciem i dodać im zdjęcia. @endif
            </p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                @if ($importable > 0)
                    <form method="POST" action="{{ route('admin.kreatywni-osoby.import') }}">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                            <i class="fa-solid fa-file-import" aria-hidden="true"></i> Przenieś osoby z tekstów ({{ $importable }})
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.kreatywni-osoby.create') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Dodaj osobę</a>
            </div>
        </div>
    @else
        <div class="overflow-x-auto rounded-md bg-white">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <caption class="sr-only">Osoby prowadzące warsztaty: zdjęcie, imię i nazwisko, rola, widoczność i działania</caption>
                <thead class="bg-gray-50 text-ink">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-bold">Osoba</th>
                        <th scope="col" class="px-4 py-3 font-bold">Rola</th>
                        <th scope="col" class="px-4 py-3 font-bold">Na stronie</th>
                        <th scope="col" class="px-4 py-3 text-right font-bold"><span class="sr-only">Działania</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($people as $person)
                        <tr class="align-middle">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($person->photoUrl())
                                        <img src="{{ $person->photoUrl() }}" alt="" class="h-11 w-11 flex-none rounded-full object-cover">
                                    @else
                                        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand-dark text-sm font-bold text-white" aria-hidden="true">{{ $person->initials() }}</span>
                                    @endif
                                    <span class="font-bold text-ink">{{ $person->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink">{{ $person->role ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink">
                                @if ($person->is_active)
                                    <i class="fa-solid fa-eye mr-1 text-muted" aria-hidden="true"></i>Widoczna
                                @else
                                    <i class="fa-solid fa-eye-slash mr-1 text-muted" aria-hidden="true"></i>Ukryta
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @foreach (['up' => ['fa-arrow-up', 'Wyżej', $loop->first], 'down' => ['fa-arrow-down', 'Niżej', $loop->last]] as $dir => [$icon, $label, $off])
                                        <form method="POST" action="{{ route('admin.kreatywni-osoby.przesun', $person) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="{{ $dir }}">
                                            <button type="submit" class="{{ $iconBtn }}" @disabled($off) title="{{ $label }}">
                                                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i><span class="sr-only">{{ $label }}: {{ $person->name }}</span>
                                            </button>
                                        </form>
                                    @endforeach
                                    <a href="{{ route('admin.kreatywni-osoby.edit', $person) }}" class="{{ $iconBtn }}" title="Edytuj">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj: {{ $person->name }}</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.kreatywni-osoby.destroy', $person) }}" onsubmit="return confirm(@js('Usunąć osobę „'.$person->name.'”? Tego nie można cofnąć.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="{{ $iconBtn }} hover:bg-red-50 hover:text-red-700" title="Usuń">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń: {{ $person->name }}</span>
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
@endsection
