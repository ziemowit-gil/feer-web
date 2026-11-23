{{--
    „Warsztaty w tej ofercie” pod formularzem oferty (moduł „Mikrooferta – Kreatywni”): karty warsztatów z miniaturą
    grafiki, nazwą, grupą, ceną, informacją o opisach i widoczności; Wyżej/Niżej, Edytuj, podstrona, Usuń
    (KreatywniOfferItemController). Nowa oferta: najpierw zapis, potem karty. Cele ≥ 44 px.
    Zmienne: $offer.
--}}
@php
    $iconBtn = 'inline-flex h-11 w-11 items-center justify-center rounded text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark disabled:cursor-not-allowed disabled:opacity-40';
    $cards = $offer->exists ? $offer->cards()->with('media')->get() : collect();
@endphp
<section id="warsztaty" class="mt-6 scroll-mt-24 rounded-md bg-white p-6" aria-labelledby="ko-cards-title">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="ko-cards-title" class="text-base font-bold text-ink">Warsztaty w tej ofercie @if ($offer->exists)<span class="font-normal text-muted">({{ $cards->count() }})</span>@endif</h2>
            <p class="mt-1 max-w-2xl text-sm text-ink">Każdy warsztat to karta w bloku oferty z grafiką, nazwą, krótkim opisem i ceną oraz własna podstrona ze zdjęciem i dłuższym opisem.</p>
        </div>
        @if ($offer->exists)
            <a href="{{ route('admin.kreatywni-oferty.warsztaty.create', $offer) }}" class="inline-flex min-h-11 items-center gap-2 rounded bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj warsztat
            </a>
        @endif
    </div>

    @if (! $offer->exists)
        <p class="text-sm text-ink">Najpierw zapisz ofertę — potem dodasz tu warsztaty z grafikami i opisami (albo wpisz ich listę w polu „Warsztaty” powyżej).</p>
    @elseif ($cards->isEmpty())
        <p class="text-sm text-ink">Ta oferta nie ma jeszcze warsztatów. Dodaj pierwszy albo wpisz kilka naraz w polu „Dodaj kilka warsztatów naraz” powyżej.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <caption class="sr-only">Warsztaty w ofercie „{{ $offer->name }}”: grafika i nazwa, grupa, cena, opisy, widoczność i działania</caption>
                <thead class="bg-gray-50 text-ink">
                    <tr>
                        <th scope="col" class="px-3 py-3 font-bold">Warsztat</th>
                        <th scope="col" class="px-3 py-3 font-bold">Grupa</th>
                        <th scope="col" class="px-3 py-3 font-bold">Cena</th>
                        <th scope="col" class="px-3 py-3 font-bold">Opisy</th>
                        <th scope="col" class="px-3 py-3 font-bold">W ofercie</th>
                        <th scope="col" class="px-3 py-3 text-right font-bold"><span class="sr-only">Działania</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($cards as $card)
                        <tr class="align-middle">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($card->imageUrl())
                                        <img src="{{ $card->imageUrl() }}" alt="" class="h-11 w-16 flex-none rounded object-cover">
                                    @else
                                        <span class="flex h-11 w-16 flex-none items-center justify-center rounded bg-gray-100 text-muted" title="Bez grafiki"><i class="fa-solid fa-image" aria-hidden="true"></i><span class="sr-only">Bez grafiki</span></span>
                                    @endif
                                    <span class="font-bold text-ink">{{ $card->title }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-ink">{{ $card->group ?: '—' }}</td>
                            <td class="px-3 py-3 text-ink">{{ $card->price ?: '—' }}</td>
                            <td class="px-3 py-3 text-xs text-ink">
                                {{ $card->description ? 'krótki' : 'bez krótkiego' }}<span class="block text-muted">{{ $card->details ? 'podstrona: jest' : 'podstrona: krótki opis' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-ink">
                                @if ($card->is_active)
                                    <i class="fa-solid fa-eye mr-1 text-muted" aria-hidden="true"></i>Widoczny
                                @else
                                    <i class="fa-solid fa-eye-slash mr-1 text-muted" aria-hidden="true"></i>Ukryty
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @foreach (['up' => ['fa-arrow-up', 'Wyżej', $loop->first], 'down' => ['fa-arrow-down', 'Niżej', $loop->last]] as $dir => [$icon, $label, $off])
                                        <form method="POST" action="{{ route('admin.kreatywni-oferty.warsztaty.przesun', [$offer, $card]) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="{{ $dir }}">
                                            <button type="submit" class="{{ $iconBtn }}" @disabled($off) title="{{ $label }}">
                                                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i><span class="sr-only">{{ $label }}: {{ $card->title }}</span>
                                            </button>
                                        </form>
                                    @endforeach
                                    <a href="{{ route('admin.kreatywni-oferty.warsztaty.edit', [$offer, $card]) }}" class="{{ $iconBtn }}" title="Edytuj">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj: {{ $card->title }}</span>
                                    </a>
                                    @if ($card->publicUrl() && $card->is_active)
                                        <a href="{{ $card->publicUrl() }}" class="{{ $iconBtn }}" title="Podstrona warsztatu" target="_blank" rel="noopener">
                                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span class="sr-only">Podstrona: {{ $card->title }} (nowa karta)</span>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.kreatywni-oferty.warsztaty.destroy', [$offer, $card]) }}" onsubmit="return confirm(@js('Usunąć warsztat „'.$card->title.'” z oferty? Jego podstrona też zniknie. Tego nie można cofnąć.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="{{ $iconBtn }} hover:bg-red-50 hover:text-red-700" title="Usuń">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń: {{ $card->title }}</span>
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
</section>
