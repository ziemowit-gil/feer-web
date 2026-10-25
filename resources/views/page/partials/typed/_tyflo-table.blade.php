{{--
    Tabela terminów konsultacji tyflo (nadchodzące lub zakończone).
    Zmienne: $slots (z App\Support\TyfloSchedule::slots), $caption (opis dla czytników ekranu),
    $labelId (id nagłówka sekcji), $bookUrl (adres zapisu albo ''), $showBook (czy link „Zapisz się”),
    $person (nagłówek kolumny osoby prowadzącej), $enrol (np. „na konsultacje” — kontekst linku dla czytnika).
    Region przewijalny jest fokusowalny (WCAG 2.1.1) — na wąskim ekranie tabela przewija się w poziomie.
    Status, forma i uwagi są tekstem; ikony są ozdobne (aria-hidden), kolor niczego nie koduje.
--}}
@php
    $hasConsultant = $slots->contains(fn ($s) => $s['consultant']);
    $hasNote = $slots->contains(fn ($s) => $s['note']);
    $statusIcons = ['open' => 'fa-circle-check', 'few' => 'fa-hourglass-half', 'full' => 'fa-circle-xmark', 'cancelled' => 'fa-ban'];
    $tcLink = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<div role="region" tabindex="0" aria-labelledby="{{ $labelId }}" class="overflow-x-auto rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" data-tyflo-table>
    <table class="w-full min-w-[40rem] text-left text-base text-ink">
        <caption class="sr-only">{{ $caption }}</caption>
        <thead class="bg-brand-light text-sm font-bold text-ink">
            <tr>
                <th scope="col" class="px-4 py-3">Data</th>
                <th scope="col" class="px-4 py-3">Godzina</th>
                <th scope="col" class="px-4 py-3">Forma i miejsce</th>
                @if ($hasConsultant)<th scope="col" class="px-4 py-3">{{ $person }}</th>@endif
                <th scope="col" class="px-4 py-3">Liczba miejsc</th>
                <th scope="col" class="px-4 py-3">Status</th>
                @if ($hasNote)<th scope="col" class="px-4 py-3">Uwagi</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach ($slots as $s)
                <tr class="align-top {{ $zebra ?? 'odd:bg-gray-50' }} hover:bg-brand-light" data-slot-status="{{ $s['status'] }}">
                    <th scope="row" class="px-4 py-3 font-bold">
                        <time datetime="{{ $s['date']->format('Y-m-d') }}">{{ \App\Support\TyfloSchedule::dateLabel($s['date']) }}</time>
                        @if ($s['date_to'])<span class="block font-normal">do <time datetime="{{ $s['date_to']->format('Y-m-d') }}">{{ \App\Support\TyfloSchedule::dateLabel($s['date_to']) }}</time></span>@endif
                    </th>
                    <td class="whitespace-nowrap px-4 py-3">
                        @if ($s['time_label'])
                            @if ($s['time_from'] && $s['time_to'])
                                <time datetime="{{ $s['time_from'] }}">{{ $s['time_from'] }}</time> – <time datetime="{{ $s['time_to'] }}">{{ $s['time_to'] }}</time>
                            @else
                                {{ $s['time_label'] }}
                            @endif
                        @else
                            —<span class="sr-only">godzina nie podana</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($s['mode_label'])<span class="font-semibold">{{ $s['mode_label'] }}</span>@endif
                        @if ($s['place'])<span class="{{ $s['mode_label'] ? 'block' : '' }}">{{ $s['place'] }}</span>@endif
                        @if (! $s['mode_label'] && ! $s['place'])—<span class="sr-only">nie podano</span>@endif
                    </td>
                    @if ($hasConsultant)<td class="px-4 py-3">{{ $s['consultant'] ?? '—' }}</td>@endif
                    <td class="px-4 py-3">{{ $s['seats'] ?? '—' }}@unless ($s['seats'])<span class="sr-only">nie podano</span>@endunless</td>
                    <td class="px-4 py-3">
                        <span class="font-bold"><i class="fa-solid {{ $statusIcons[$s['status']] }} mr-1.5 text-brand-dark" aria-hidden="true"></i>{{ $s['status_label'] }}</span>
                        @if ($showBook && $bookUrl !== '' && $s['bookable'])
                            <a href="{{ $bookUrl }}" class="{{ $tcLink }} mt-1 block">Zapisz się<span class="sr-only"> {{ $enrol }}: {{ \App\Support\TyfloSchedule::dateLabel($s['date']) }}{{ $s['time_from'] ? ', godz. '.$s['time_from'] : '' }}</span></a>
                        @endif
                    </td>
                    @if ($hasNote)<td class="px-4 py-3">{{ $s['note'] ?? '—' }}</td>@endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
