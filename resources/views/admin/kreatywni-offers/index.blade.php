@extends('admin.layout')

@section('title', 'Mikrooferta – Kreatywni')

{{--
    Mikro moduł „Mikrooferta – Kreatywni” (KreatywniOfferController): oferty z bloku sprzedażowego na stronie głównej
    i na górze strony „Oferta” — karta cenowa i warsztaty w grupach. Kolejność: Wyżej/Niżej. Pusty moduł = strona
    bierze ofertę z tekstów szablonu; przycisk importu przenosi ją tutaj. Cele ≥ 44 px, kontrast ink/muted na bieli.
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $iconBtn = 'inline-flex h-11 w-11 items-center justify-center rounded text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark disabled:cursor-not-allowed disabled:opacity-40';
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-2xl text-sm text-ink">
            Oferty warsztatów: ciemna karta z ceną, korzyściami i przyciskiem oraz lista warsztatów w grupach.
            Pokazują się w sekcji „Oferta warsztatów” na stronie głównej (gdy jest włączona w Ustawienia → Szablon)
            i na górze strony „Oferta”. Kolejność tutaj = kolejność na stronie.
        </p>
        <a href="{{ route('admin.kreatywni-oferty.create') }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj ofertę
        </a>
    </div>

    @if ($offers->isEmpty())
        <div class="rounded-md bg-white px-6 py-10 text-center">
            <p class="font-bold text-ink">Nie ma jeszcze ofert w module</p>
            <p class="mx-auto mt-1 max-w-lg text-sm text-ink">
                Do tego czasu strona pokazuje ofertę wpisaną w tekstach szablonu (Ustawienia → Szablon → Teksty: Oferta warsztatów).
                @if ($importable) Możesz ją przenieść tutaj jednym kliknięciem i dodać kolejne oferty. @endif
            </p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                @if ($importable)
                    <form method="POST" action="{{ route('admin.kreatywni-oferty.import') }}">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                            <i class="fa-solid fa-file-import" aria-hidden="true"></i> Przenieś ofertę z tekstów
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.kreatywni-oferty.create') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Dodaj ofertę</a>
            </div>
        </div>
    @else
        <div class="overflow-x-auto rounded-md bg-white">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <caption class="sr-only">Oferty warsztatów: nazwa i cena, liczba warsztatów, wygląd, widoczność i działania</caption>
                <thead class="bg-gray-50 text-ink">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-bold">Oferta</th>
                        <th scope="col" class="px-4 py-3 font-bold">Warsztaty</th>
                        <th scope="col" class="px-4 py-3 font-bold">Na stronie</th>
                        <th scope="col" class="px-4 py-3 text-right font-bold"><span class="sr-only">Działania</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($offers as $offer)
                        <tr class="align-middle">
                            <td class="px-4 py-3">
                                <span class="block font-bold text-ink">{{ $offer->name }}</span>
                                @if ($offer->price)
                                    <span class="block text-ink">{{ $offer->price }}@if ($offer->price_unit) {{ $offer->price_unit }}@endif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink">
                                {{ $offer->itemCount() }} {{ trans_choice('warsztat|warsztaty|warsztatów', $offer->itemCount()) }}
                                <span class="block text-xs text-muted">{{ \App\Support\KreatywniTexts::OFFER_STYLES[$offer->itemsStyle()][0] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink">
                                @if ($offer->is_active)
                                    <i class="fa-solid fa-eye mr-1 text-muted" aria-hidden="true"></i>Widoczna
                                @else
                                    <i class="fa-solid fa-eye-slash mr-1 text-muted" aria-hidden="true"></i>Ukryta
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @foreach (['up' => ['fa-arrow-up', 'Wyżej', $loop->first], 'down' => ['fa-arrow-down', 'Niżej', $loop->last]] as $dir => [$icon, $label, $off])
                                        <form method="POST" action="{{ route('admin.kreatywni-oferty.przesun', $offer) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="{{ $dir }}">
                                            <button type="submit" class="{{ $iconBtn }}" @disabled($off) title="{{ $label }}">
                                                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i><span class="sr-only">{{ $label }}: {{ $offer->name }}</span>
                                            </button>
                                        </form>
                                    @endforeach
                                    <a href="{{ route('admin.kreatywni-oferty.edit', $offer) }}" class="{{ $iconBtn }}" title="Edytuj">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj: {{ $offer->name }}</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.kreatywni-oferty.destroy', $offer) }}" onsubmit="return confirm(@js('Usunąć ofertę „'.$offer->name.'”? Tego nie można cofnąć.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="{{ $iconBtn }} hover:bg-red-50 hover:text-red-700" title="Usuń">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń: {{ $offer->name }}</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($offers->where('is_active', true)->isEmpty())
            <p class="mt-3 text-sm text-ink" role="status">
                <i class="fa-solid fa-circle-info mr-1 text-muted" aria-hidden="true"></i>
                Wszystkie oferty są ukryte, więc sekcja oferty na stronie głównej się nie pokazuje.
            </p>
        @endif
    @endif
@endsection
