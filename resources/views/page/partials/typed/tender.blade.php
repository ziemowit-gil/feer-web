{{--
    Typ „Zapytanie ofertowe" (zasada konkurencyjności). Dwie kolumny: treść
    (przedmiot zamówienia → warunki → kryteria → składanie ofert → wykluczenia →
    dokumentacja → zmiany i wyjaśnienia → wynik) | karta postępowania (status,
    znak sprawy, terminy, CPV, Baza Konkurencyjności), kontakt i menu działu.
    Płasko: bez etykiet i obramowań, tylko kolory firmowe.
    Status liczy Page::tenderStatus() (ręczny albo z terminu i wyniku).
--}}
@php $feer = ($siteSettings->site_template ?? 'default') === 'feer'; @endphp
@php
    $td = $page->typeData();
    $status = $page->tenderStatus();
    $deadline = $page->tenderDeadline();
    $project = $page->project;

    $fmtDate = function (?string $raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        try {
            $d = \Illuminate\Support\Carbon::parse($raw)->locale('pl');
        } catch (\Throwable) {
            return null;
        }

        return $d->translatedFormat(str_contains($raw, 'T') || str_contains($raw, ':') ? 'j F Y, \g\o\d\z. H:i' : 'j F Y');
    };

    // Dni kalendarzowe do terminu ofert (0 = dziś mija termin).
    $daysLeft = ($status === 'open' && $deadline) ? (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false) : null;

    $facts = array_filter([
        'Status' => \App\Models\Page::TENDER_STATUSES[$status],
        'Znak sprawy' => $td['tender_number'] ?? null,
        'Data publikacji' => $fmtDate($td['tender_published_at'] ?? null),
        'Termin składania ofert' => $fmtDate($td['tender_deadline'] ?? null),
        'Kod CPV' => $td['tender_cpv'] ?? null,
        'Miejsce realizacji' => $td['tender_place'] ?? null,
        'Termin realizacji' => $td['tender_period'] ?? null,
        'Data rozstrzygnięcia' => $fmtDate($td['tender_result_at'] ?? null),
    ], fn ($v) => filled($v));
    $factIcons = [
        'Status' => 'fa-circle-info', 'Znak sprawy' => 'fa-hashtag', 'Data publikacji' => 'fa-calendar',
        'Termin składania ofert' => 'fa-calendar-check', 'Kod CPV' => 'fa-tag', 'Miejsce realizacji' => 'fa-location-dot',
        'Termin realizacji' => 'fa-clock', 'Data rozstrzygnięcia' => 'fa-gavel',
    ];

    $bkUrl = preg_match('~^https?://~i', (string) ($td['tender_bk_url'] ?? '')) ? $td['tender_bk_url'] : null;
    $notices = collect($td['notices'])->filter(fn ($n) => filled($n['title'] ?? null) || filled($n['text'] ?? null))
        ->sortByDesc(fn ($n) => (string) ($n['date'] ?? ''))->values();
    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);

    $card = 'rounded-md bg-gray-50';
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $btn = 'inline-flex min-h-12 w-full items-center justify-center gap-2 '.($feer ? 'rounded-md' : 'rounded-full').' bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $docIcon = function (string $url) {
        $ext = strtoupper(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

        return [$ext !== '' && $ext !== 'HTML' ? $ext : null];
    };
@endphp

<section class="mx-auto max-w-6xl px-4 py-12">
    <header class="mb-8 max-w-3xl">
        <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
        @if ($feer)<span class="mb-5 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
        @if (filled($td['lead'] ?? null))
            <p class="text-lg leading-relaxed text-ink">{{ $td['lead'] }}</p>
        @endif
        @if ($project)
            <p class="mt-4 text-sm text-ink">
                <i class="fa-solid fa-diagram-project mr-1 text-brand-dark" aria-hidden="true"></i>
                Zamówienie w ramach projektu <a href="{{ route('projects.show', $project) }}" class="{{ $link }}">{{ $project->title }}</a>
            </p>
        @endif
    </header>

    {{-- Dwie kolumny: treść | karta postępowania, kontakt, menu działu (na telefonie karta pierwsza). --}}
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <aside class="lg:order-last" aria-label="Postępowanie — najważniejsze informacje">
            <div class="lg:sticky lg:top-24">
                <section class="rounded-md bg-brand-light p-6" aria-labelledby="tender-summary">
                    <h2 id="tender-summary" class="sr-only">Najważniejsze informacje o postępowaniu</h2>

                    @if ($daysLeft !== null && $daysLeft <= 14)
                        <p class="mb-5 flex items-center gap-2 font-bold text-ink">
                            <i class="fa-solid fa-bell text-brand-dark" aria-hidden="true"></i>
                            @if ($daysLeft <= 0)
                                Dziś mija termin składania ofert.
                            @else
                                Do terminu składania ofert: {{ $daysLeft === 1 ? '1 dzień' : $daysLeft.' dni' }}.
                            @endif
                        </p>
                    @endif

                    <dl class="grid gap-y-4">
                        @foreach ($facts as $label => $value)
                            <div class="flex gap-3">
                                <i class="fa-solid {{ $factIcons[$label] }} mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
                                    <dd class="font-semibold text-ink">{{ $value }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>

                    @if ($bkUrl && $status === 'open')
                        <a href="{{ $bkUrl }}" class="{{ $btn }} mt-6" target="_blank" rel="noopener">
                            Złóż ofertę w Bazie Konkurencyjności <i class="fa-solid fa-arrow-up-right-from-square text-sm" aria-hidden="true"></i>
                            <span class="sr-only"> (otwiera się w nowej karcie)</span>
                        </a>
                    @elseif ($bkUrl)
                        <p class="mt-6"><a href="{{ $bkUrl }}" class="{{ $link }}" target="_blank" rel="noopener">Ogłoszenie w Bazie Konkurencyjności<span class="sr-only"> (otwiera się w nowej karcie)</span></a></p>
                    @endif
                    @if ($status === 'evaluation')
                        <p class="mt-6 text-ink"><strong>Termin składania ofert minął.</strong> Trwa ocena ofert. Wynik opublikujemy na tej stronie.</p>
                    @endif
                </section>

                @if ($hasContact)
                    <section class="mt-6 rounded-md bg-gray-50 p-6" aria-labelledby="tender-contact">
                        <h2 id="tender-contact" class="text-lg font-bold text-ink">Pytania do zapytania</h2>
                        <ul class="mt-3 space-y-1 text-ink">
                            @if (filled($td['contact_name'] ?? null))<li class="font-bold">{{ $td['contact_name'] }}</li>@endif
                            @if (filled($td['contact_email'] ?? null))
                                <li><a href="mailto:{{ $td['contact_email'] }}" class="{{ $link }}"><i class="fa-solid fa-envelope mr-1.5" aria-hidden="true"></i>{{ $td['contact_email'] }}</a></li>
                            @endif
                            @if (filled($td['contact_phone'] ?? null))
                                <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $td['contact_phone']) }}" class="{{ $link }}"><i class="fa-solid fa-phone mr-1.5" aria-hidden="true"></i>{{ $td['contact_phone'] }}</a></li>
                            @endif
                        </ul>
                    </section>
                @endif

                @if (($menuSiblings ?? collect())->isNotEmpty())
                    <div class="mt-6">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                @endif
            </div>
        </aside>

        <div class="min-w-0">
            @if (in_array($status, ['awarded', 'cancelled'], true))
                <section class="mb-10 rounded-md bg-brand-light p-6" aria-labelledby="tender-result">
                    <h2 id="tender-result" class="mb-2 flex items-center gap-2 text-xl font-bold text-ink">
                        <i class="fa-solid {{ $status === 'awarded' ? 'fa-gavel' : 'fa-ban' }} text-brand-dark" aria-hidden="true"></i>
                        {{ $status === 'awarded' ? 'Wynik postępowania' : 'Postępowanie unieważnione' }}
                    </h2>
                    @if (filled($td['tender_result'] ?? null))
                        <p class="whitespace-pre-line text-ink">{{ $td['tender_result'] }}</p>
                    @endif
                </section>
            @endif

            @include('partials.page-content-image')

            @if ($page->content)
                <section aria-labelledby="tender-subject">
                    <h2 id="tender-subject" class="mb-4 text-2xl font-bold text-ink">Przedmiot zamówienia</h2>
                    <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
                </section>
            @endif

            @if ($td['requirements'])
                <section class="mt-10" aria-labelledby="tender-conditions">
                    <h2 id="tender-conditions" class="mb-4 text-2xl font-bold text-ink">Warunki udziału</h2>
                    <ul class="space-y-2">
                        @foreach ($td['requirements'] as $row)
                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-1 text-brand-dark" aria-hidden="true"></i><span class="text-ink">{{ $row['text'] }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['criteria'])
                <section class="mt-10" aria-labelledby="tender-criteria">
                    <h2 id="tender-criteria" class="mb-4 text-2xl font-bold text-ink">Kryteria oceny ofert</h2>
                    <ul class="space-y-2">
                        @foreach ($td['criteria'] as $row)
                            <li class="{{ $card }} px-4 py-3">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="font-bold text-ink">{{ $row['name'] ?? '' }}</span>
                                    @if (filled($row['weight'] ?? null))
                                        <span class="shrink-0 font-bold text-ink"><span class="sr-only">waga: </span>{{ $row['weight'] }}</span>
                                    @endif
                                </div>
                                @if (filled($row['text'] ?? null))<p class="mt-1 text-sm text-ink">{{ $row['text'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($td['tender_submission'] ?? null))
                <section class="mt-10" aria-labelledby="tender-submission">
                    <h2 id="tender-submission" class="mb-4 text-2xl font-bold text-ink">Jak złożyć ofertę</h2>
                    <p class="whitespace-pre-line text-ink">{{ $td['tender_submission'] }}</p>
                </section>
            @endif

            @if (filled($td['tender_exclusions'] ?? null))
                <section class="mt-10" aria-labelledby="tender-exclusions">
                    <h2 id="tender-exclusions" class="mb-4 text-2xl font-bold text-ink">Wykluczenia</h2>
                    <p class="whitespace-pre-line text-ink">{{ $td['tender_exclusions'] }}</p>
                </section>
            @endif

            @if ($td['docs'])
                <section class="mt-10" aria-labelledby="tender-docs">
                    <h2 id="tender-docs" class="mb-4 text-2xl font-bold text-ink">Dokumentacja</h2>
                    <ul class="space-y-2">
                        @foreach ($td['docs'] as $doc)
                            @continue(blank($doc['url'] ?? null) || blank($doc['title'] ?? null))
                            @php [$ext] = $docIcon($doc['url']); @endphp
                            <li class="flex items-start gap-3 {{ $card }} px-4 py-3">
                                <i class="fa-solid {{ $ext ? 'fa-file-arrow-down' : 'fa-link' }} mt-1 text-brand-dark" aria-hidden="true"></i>
                                <div>
                                    <a href="{{ $doc['url'] }}" class="{{ $link }}" @if ($ext) download @endif>{{ $doc['title'] }}@if ($ext) <span class="font-normal text-muted">({{ $ext }})</span>@endif</a>
                                    @if (filled($doc['note'] ?? null))<p class="text-sm text-muted">{{ $doc['note'] }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($notices->isNotEmpty())
                <section class="mt-10" aria-labelledby="tender-notices">
                    <h2 id="tender-notices" class="mb-4 text-2xl font-bold text-ink">Zmiany i wyjaśnienia</h2>
                    <ol class="space-y-4" reversed>
                        @foreach ($notices as $n)
                            <li class="{{ $card }} px-4 py-3">
                                @if ($nDate = $fmtDate($n['date'] ?? null))
                                    <p class="text-sm font-bold text-muted"><time datetime="{{ $n['date'] }}">{{ $nDate }}</time></p>
                                @endif
                                @if (filled($n['title'] ?? null))<h3 class="font-bold text-ink">{{ $n['title'] }}</h3>@endif
                                @if (filled($n['text'] ?? null))<p class="mt-1 whitespace-pre-line text-ink">{{ $n['text'] }}</p>@endif
                                @if (filled($n['url'] ?? null) && preg_match('~^(https?://|/)~i', $n['url']))
                                    <p class="mt-2"><a href="{{ $n['url'] }}" class="{{ $link }}">Pobierz dokument<span class="sr-only">: {{ $n['title'] ?? 'zmiana lub wyjaśnienie' }}</span></a></p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($project && filled($project->funding_notice))
                <p class="mt-10 text-sm text-muted">{{ $project->funding_notice }}</p>
            @endif

            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
