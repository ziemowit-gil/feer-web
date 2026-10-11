{{--
    Typ „Sprawozdania roczne": treść strony + tabela sprawozdań z modułu Sprawozdania (AnnualReport).
    Płasko: tabela bez linii — pogrubiony nagłówek na jasnoniebieskim tle i naprzemienne tło wierszy.
    Menu działu w kolumnie bocznej, gdy jest.
--}}
@php
    $reportsOn = $siteSettings->isModuleEnabled('reports');
    $td = $page->typeData();
    $reports = $reportsOn ? \App\Models\AnnualReport::published()->with('media')->get() : collect();
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $link = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="reports">
    @include('page.partials.typed._head')

    <div class="grid gap-10 {{ $localNav ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($localNav)
            <aside class="lg:order-last" aria-label="Menu działu">
                <div class="lg:sticky lg:top-24">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
            </aside>
        @endif

        <div class="min-w-0">
            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
            @endif

            @if (! $reportsOn)
                <p class="text-muted">Moduł „Sprawozdania” jest wyłączony — włącz go w Ustawienia → Moduły, aby pokazać tabelę.</p>
            @elseif ($reports->isEmpty())
                <p class="text-muted">Sprawozdania zostaną wkrótce opublikowane.</p>
            @else
                {{-- Przewijanie poziome tylko wewnątrz tabeli (wąskie ekrany); kontener dostępny z klawiatury. --}}
                <div class="overflow-x-auto rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" tabindex="0" role="region" aria-label="Tabela sprawozdań rocznych">
                    <table class="w-full text-left text-base text-ink">
                        <caption class="sr-only">Sprawozdania roczne według lat</caption>
                        <thead>
                            <tr class="bg-brand-light">
                                <th scope="col" class="px-4 py-3 font-bold">Rok</th>
                                @foreach (\App\Models\AnnualReport::TYPES as $tl)<th scope="col" class="px-4 py-3 font-bold">{{ $tl }}</th>@endforeach
                                <th scope="col" class="px-4 py-3 font-bold">Załączniki</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr id="rok-{{ $report->year }}" class="even:bg-gray-50">
                                    <th scope="row" class="px-4 py-3 align-top text-lg font-bold">{{ $report->year }}</th>
                                    @foreach (\App\Models\AnnualReport::TYPES as $typeKey => $typeLabel)
                                        <td class="px-4 py-3 align-top">
                                            @if ($report->fileUrlFor($typeKey))
                                                <a href="{{ $report->fileUrlFor($typeKey) }}" download class="{{ $link }}"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Pobierz<span class="sr-only"> {{ mb_strtolower($typeLabel) }} {{ $report->year }} (PDF)</span></a>
                                            @else
                                                <span class="text-muted">{{ $report->messageFor($typeKey) ?? '—' }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 align-top">
                                        @php $extra = $report->getMedia('additional'); @endphp
                                        @if ($extra->isEmpty())<span class="text-muted">—</span>@else
                                            <ul class="space-y-1 text-sm" role="list">
                                                @foreach ($extra as $m)<li><a href="{{ $m->getUrl() }}" download class="{{ str_replace('min-h-11', 'min-h-8', $link) }}">{{ $m->name ?: $m->file_name }}<span class="sr-only"> ({{ strtoupper($m->extension ?? 'plik') }})</span></a></li>@endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
