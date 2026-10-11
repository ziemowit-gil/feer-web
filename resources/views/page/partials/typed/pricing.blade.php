{{--
    Typ „Cennik i opłaty". Dwie kolumny: treść i tabele cen pogrupowane po kategoriach
    (opcjonalne grupy) | karta „Jak płacić" z przyciskiem i menu działu.
    Płasko: tabele bez linii — pogrubiony nagłówek i naprzemienne tło wierszy.
    Dane: $page->typeData().
--}}
@php
    $td = $page->typeData();
    $rows = collect($td['price_rows'])->filter(fn ($r) => filled($r['item'] ?? null));
    $groups = $rows->groupBy(fn ($r) => trim((string) ($r['group'] ?? '')));
    $payParas = array_values(array_filter(preg_split('/\R{2,}/', trim((string) ($td['payment_info'] ?? '')))));
    $ctaUrl = trim((string) ($td['cta_url'] ?? ''));
    $ctaUrl = preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $ctaUrl) ? $ctaUrl : '';
    $hasCta = filled($td['cta_label'] ?? null) && $ctaUrl !== '';
    $ctaExternal = \Illuminate\Support\Str::startsWith($ctaUrl, 'http');
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $hasAside = $payParas || $hasCta || $localNav;

    $btn = 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
@endphp
@once
    <style>
        @media print {
            /* Naprzemienne tło wierszy także na wydruku (tabele nie mają linii). */
            .tp-table { font-size: 11pt; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .tp-table tr { break-inside: avoid; }
            .tp-table .tp-amount { white-space: normal; }
        }
    </style>
@endonce
<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="pricing">
    @include('page.partials.typed._head')

    {{-- Dwie kolumny: cennik | jak płacić, przycisk, menu działu (na telefonie karta pierwsza). --}}
    <div class="grid gap-10 {{ $hasAside ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Cennik — płatności">
                <div class="lg:sticky lg:top-24">
                    @if ($payParas || $hasCta)
                        <section class="rounded-md bg-brand-light p-6" @if ($payParas) aria-labelledby="tp-pay-h" @else aria-label="Zamówienie" @endif>
                            @if ($payParas)
                                <h2 id="tp-pay-h" class="mb-3 flex items-center gap-2 text-lg font-bold text-ink"><i class="fa-solid fa-coins text-brand-dark" aria-hidden="true"></i> Jak płacić</h2>
                                <div class="space-y-3 leading-relaxed text-ink">
                                    @foreach ($payParas as $para)<p>{!! nl2br(e($para)) !!}</p>@endforeach
                                </div>
                            @endif
                            @if ($hasCta)
                                <a href="{{ $ctaUrl }}" class="{{ $btn }} {{ $payParas ? 'mt-6' : '' }}" @if ($ctaExternal) target="_blank" rel="noopener" @endif>
                                    {{ $td['cta_label'] }} <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                                    @if ($ctaExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                                </a>
                            @endif
                        </section>
                    @endif
                    @if ($localNav)
                        <div class="{{ $payParas || $hasCta ? 'mt-6' : '' }}">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
            @endif

            @forelse ($groups as $gName => $items)
                @php $gid = 'tp-g-'.$loop->index; @endphp
                <section class="{{ $loop->first ? '' : 'mt-10' }}" @if ($gName !== '') aria-labelledby="{{ $gid }}" @else aria-label="Cennik" @endif>
                    @if ($gName !== '')<h2 id="{{ $gid }}" class="mb-4 text-2xl font-bold text-ink">{{ $gName }}</h2>@endif
                    {{-- Przewijanie poziome tylko wewnątrz tabeli (wąskie ekrany); kontener dostępny z klawiatury. --}}
                    <div class="overflow-x-auto rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" tabindex="0" role="region" aria-label="{{ $gName !== '' ? 'Tabela: '.$gName : 'Tabela cen' }}">
                        <table class="tp-table w-full text-left text-base text-ink">
                            <caption class="sr-only">{{ $gName !== '' ? 'Cennik: '.$gName : 'Cennik' }}</caption>
                            <thead>
                                <tr class="bg-brand-light">
                                    <th scope="col" class="px-4 py-3 font-bold">Pozycja</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Opis</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Cena</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $r)
                                    <tr class="even:bg-gray-50">
                                        <th scope="row" class="px-4 py-3 align-top font-bold">{{ $r['item'] }}</th>
                                        <td class="px-4 py-3 align-top leading-snug">{{ $r['note'] ?? '' }}</td>
                                        <td class="tp-amount whitespace-nowrap px-4 py-3 align-top font-bold">{{ $r['price'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @empty
                <p class="text-muted">Cennik zostanie wkrótce uzupełniony.</p>
            @endforelse

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
