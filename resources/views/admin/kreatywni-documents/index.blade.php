@extends('admin.layout')

@section('title', 'Dokumenty – Kreatywni')

{{--
    Mikro moduł „Dokumenty – Kreatywni” (KreatywniDocumentController): dokumenty fundacji w sekcji „Dokumenty” na
    stronie „O nas”. Dokument prowadzi do pliku, opublikowanej strony albo adresu; bez nich się nie pokazuje (kolumna
    „Na stronie” mówi dlaczego). Kolejność: Wyżej/Niżej. Cele ≥ 44 px, kontrast ink/muted na bieli.
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $iconBtn = 'inline-flex h-11 w-11 items-center justify-center rounded text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark disabled:cursor-not-allowed disabled:opacity-40';
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-2xl text-sm text-ink">
            Dokumenty fundacji (statut, regulaminy, polityki) do pobrania albo przeczytania. Pokazują się w sekcji
            „Dokumenty” na stronie
            @if ($aboutUrl)<a href="{{ $aboutUrl }}#dokumenty" class="font-bold text-brand-dark underline underline-offset-2">„O nas”</a>@else „O nas”@endif.
            Każdy dokument prowadzi do pliku, a bez pliku — do strony serwisu (gdy jest opublikowana) albo podanego adresu;
            dokument bez nich jest na liście jako „w przygotowaniu”. Dokumenty są też w menu „O nas”. Kolejność tutaj = kolejność na stronie.
        </p>
        <a href="{{ route('admin.kreatywni-dokumenty.create') }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj dokument
        </a>
    </div>

    @if ($documents->isEmpty())
        <div class="rounded-md bg-white px-6 py-10 text-center">
            <p class="font-bold text-ink">Nie ma jeszcze dokumentów w module</p>
            <p class="mx-auto mt-1 max-w-lg text-sm text-ink">
                Sekcja „Dokumenty” na stronie „O nas” pojawi się, gdy dodasz pierwszy dokument z plikiem, stroną albo adresem.
                @if ($importable > 0) Możesz też przenieść tutaj dokumenty ze stron pod „Fundacją” — razem z załączonymi plikami. @endif
            </p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                @if ($importable > 0)
                    <form method="POST" action="{{ route('admin.kreatywni-dokumenty.import') }}">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                            <i class="fa-solid fa-file-import" aria-hidden="true"></i> Przenieś dokumenty ze stron ({{ $importable }})
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.kreatywni-dokumenty.create') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Dodaj dokument</a>
            </div>
        </div>
    @else
        <div class="overflow-x-auto rounded-md bg-white">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <caption class="sr-only">Dokumenty fundacji: nazwa, dokąd prowadzi, widoczność na stronie i działania</caption>
                <thead class="bg-gray-50 text-ink">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-bold">Dokument</th>
                        <th scope="col" class="px-4 py-3 font-bold">Prowadzi do</th>
                        <th scope="col" class="px-4 py-3 font-bold">Na stronie</th>
                        <th scope="col" class="px-4 py-3 text-right font-bold"><span class="sr-only">Działania</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($documents as $document)
                        @php $target = $document->target(); @endphp
                        <tr class="align-middle">
                            <td class="px-4 py-3">
                                <span class="block font-bold text-ink">{{ $document->title }}</span>
                                @if ($document->description)<span class="block text-xs text-muted">{{ \Illuminate\Support\Str::limit($document->description, 120) }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-ink">
                                @if (($target['kind'] ?? null) === 'file')
                                    <i class="fa-solid fa-file-arrow-down mr-1 text-muted" aria-hidden="true"></i>Plik ({{ $target['meta'] }})
                                @elseif (($target['kind'] ?? null) === 'page')
                                    <i class="fa-solid fa-file-lines mr-1 text-muted" aria-hidden="true"></i>Strona „{{ $document->page?->title }}”
                                @elseif (($target['kind'] ?? null) === 'link')
                                    <i class="fa-solid fa-link mr-1 text-muted" aria-hidden="true"></i>{{ \Illuminate\Support\Str::limit($document->url, 50) }}
                                @elseif ($document->page_id && $document->page)
                                    <span class="text-red-800"><i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>Strona „{{ $document->page->title }}” jest szkicem — dodaj plik albo opublikuj stronę</span>
                                @else
                                    <span class="text-red-800"><i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>Brak pliku, strony i adresu</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink">
                                @if (! $document->is_active)
                                    <i class="fa-solid fa-eye-slash mr-1 text-muted" aria-hidden="true"></i>Ukryty
                                @elseif ($target)
                                    <i class="fa-solid fa-eye mr-1 text-muted" aria-hidden="true"></i>Widoczny
                                @else
                                    <i class="fa-solid fa-hourglass-half mr-1 text-muted" aria-hidden="true"></i>„W przygotowaniu”, bez linku
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @foreach (['up' => ['fa-arrow-up', 'Wyżej', $loop->first], 'down' => ['fa-arrow-down', 'Niżej', $loop->last]] as $dir => [$icon, $label, $off])
                                        <form method="POST" action="{{ route('admin.kreatywni-dokumenty.przesun', $document) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="{{ $dir }}">
                                            <button type="submit" class="{{ $iconBtn }}" @disabled($off) title="{{ $label }}">
                                                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i><span class="sr-only">{{ $label }}: {{ $document->title }}</span>
                                            </button>
                                        </form>
                                    @endforeach
                                    <a href="{{ route('admin.kreatywni-dokumenty.edit', $document) }}" class="{{ $iconBtn }}" title="Edytuj">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj: {{ $document->title }}</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.kreatywni-dokumenty.destroy', $document) }}" onsubmit="return confirm(@js('Usunąć dokument „'.$document->title.'” z listy? Plik dokumentu też zostanie usunięty. Strona serwisu zostaje.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="{{ $iconBtn }} hover:bg-red-50 hover:text-red-700" title="Usuń">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń: {{ $document->title }}</span>
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
