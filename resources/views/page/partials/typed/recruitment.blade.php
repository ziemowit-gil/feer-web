{{--
    Typ „Rekrutacja do projektu": status naboru i najważniejsze fakty (terminy,
    miejsca, okres, forma, koszt) → przycisk zgłoszenia → opis projektu → dla kogo
    → kryteria (obowiązkowe i premiujące) → wsparcie → etapy → dokumenty →
    dostępność rekrutacji → formularz → pytania → kontakt → finansowanie.
    Status liczy Page::recruitmentStatus() (ręczny albo z dat naboru).
--}}
@php $feer = ($siteSettings->site_template ?? 'default') === 'feer'; @endphp
@php
    $td = $page->typeData();
    $status = $page->recruitmentStatus();
    $accepts = $page->recruitmentAcceptsApplications();
    $start = $page->recruitmentDate('start');
    $end = $page->recruitmentDate('end');
    $project = $page->project;

    // Data po polsku; godzinę pokazujemy tylko, gdy ją podano.
    $fmt = function (?\Illuminate\Support\Carbon $d, string $raw) {
        if (! $d) {
            return null;
        }
        $hasTime = str_contains($raw, 'T') || str_contains($raw, ':');

        return $d->locale('pl')->translatedFormat($hasTime ? 'j F Y, \g\o\d\z. H:i' : 'j F Y');
    };
    $startLabel = $fmt($start, (string) ($td['rec_starts_at'] ?? ''));
    $endLabel = $fmt($end, (string) ($td['rec_ends_at'] ?? ''));

    // Dni kalendarzowe do końca naboru (0 = dziś ostatni dzień).
    $daysLeft = ($status === 'open' && $end) ? (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) : null;

    $statusLabel = \App\Models\Page::RECRUITMENT_STATUSES[$status];

    $ctaUrl = trim((string) ($td['cta_url'] ?? ''));
    $ctaUrl = preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $ctaUrl) ? $ctaUrl : '';
    $formSlug = $td['rec_form'] ?? '';
    $hasForm = $accepts && filled($formSlug);
    if ($ctaUrl === '' && $hasForm) {
        $ctaUrl = '#zgloszenie';
    }
    $ctaLabel = filled($td['cta_label'] ?? null) ? $td['cta_label'] : ($status === 'reserve' ? 'Zapisz się na listę rezerwową' : 'Zgłoś się do projektu');
    $showCta = $accepts && $ctaUrl !== '';
    $ctaExternal = \Illuminate\Support\Str::startsWith($ctaUrl, 'http');

    $facts = array_filter([
        'Status naboru' => $statusLabel,
        'Termin naboru' => match (true) {
            $startLabel && $endLabel => $startLabel.' – '.$endLabel,
            (bool) $endLabel => 'do '.$endLabel,
            (bool) $startLabel => 'od '.$startLabel,
            default => $status === 'continuous' ? 'Nabór ciągły: zgłoszenia przyjmujemy na bieżąco' : null,
        },
        'Liczba miejsc' => $td['rec_places'] ?? null,
        'Okres wsparcia' => $td['rec_period'] ?? null,
        'Miejsce i forma' => $td['rec_mode'] ?? null,
        'Koszt' => $td['rec_cost'] ?? null,
    ], fn ($v) => filled($v));
    $factIcons = ['Status naboru' => 'fa-circle-info', 'Termin naboru' => 'fa-calendar-days', 'Liczba miejsc' => 'fa-users', 'Okres wsparcia' => 'fa-clock', 'Miejsce i forma' => 'fa-location-dot', 'Koszt' => 'fa-coins'];

    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
    $card = 'rounded-md bg-gray-50';
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $btn = 'inline-flex min-h-12 items-center gap-2 '.($feer ? 'rounded-md' : 'rounded-full').' bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
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
                Nabór w ramach projektu <a href="{{ route('projects.show', $project) }}" class="{{ $link }}">{{ $project->title }}</a>
            </p>
        @endif
    </header>

    {{-- Dwie kolumny: treść | karta naboru, kontakt i menu działu. Na telefonie
         karta naboru jest pierwsza — najważniejsze informacje od razu. --}}
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <aside class="lg:order-last" aria-label="Nabór — najważniejsze informacje">
            <div class="lg:sticky lg:top-24">
                {{-- Najważniejsze informacje i zgłoszenie --}}
                <section class="rounded-md bg-brand-light p-6" aria-labelledby="rec-summary">
                    <h2 id="rec-summary" class="sr-only">Najważniejsze informacje o naborze</h2>

                    @if ($daysLeft !== null && $daysLeft <= 14)
                        <p class="mb-5 flex items-center gap-2 font-bold text-ink">
                            <i class="fa-solid fa-bell text-brand-dark" aria-hidden="true"></i>
                            @if ($daysLeft <= 0)
                                Dziś ostatni dzień naboru.
                            @else
                                Do końca naboru: {{ $daysLeft === 1 ? '1 dzień' : $daysLeft.' dni' }}.
                            @endif
                        </p>
                    @endif

                    @if ($facts)
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
                    @endif

                    <div class="{{ $facts ? 'mt-6' : '' }}">
                        @if ($showCta)
                            <a href="{{ $ctaUrl }}" class="{{ $btn }} w-full justify-center" @if ($ctaExternal) target="_blank" rel="noopener" @endif>
                                {{ $ctaLabel }} <i class="fa-solid {{ $ctaUrl === '#zgloszenie' ? 'fa-arrow-down' : 'fa-arrow-right' }} text-sm" aria-hidden="true"></i>
                                @if ($ctaExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                            </a>
                        @elseif ($status === 'planned')
                            <p class="text-ink"><strong>Nabór jeszcze się nie rozpoczął.</strong> @if ($startLabel) Zgłoszenia przyjmiemy od {{ $startLabel }}. @endif</p>
                        @elseif ($status === 'suspended')
                            <p class="text-ink"><strong>Nabór jest wstrzymany.</strong> Informację o wznowieniu opublikujemy na tej stronie.</p>
                        @elseif ($status === 'closed')
                            <p class="text-ink"><strong>Nabór został zakończony.</strong> Dziękujemy za wszystkie zgłoszenia.</p>
                        @elseif ($accepts)
                            <p class="text-ink">Zgłoszenia przyjmujemy. Skontaktuj się z nami.</p>
                        @endif
                        @if ($status === 'closed' && filled($td['rec_results'] ?? null))
                            <p class="mt-3 whitespace-pre-line text-ink">{{ $td['rec_results'] }}</p>
                        @endif
                    </div>
                </section>
                @if ($hasContact)
                    <section class="mt-6 rounded-md bg-gray-50 p-6" aria-labelledby="rec-contact">
                        <h2 id="rec-contact" class="text-lg font-bold text-ink">Masz pytania?</h2>
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
            @include('partials.page-content-image')

            @if ($page->content)
                <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @if ($td['audience'])
                <section class="mt-10" aria-labelledby="rec-audience">
                    <h2 id="rec-audience" class="mb-4 text-2xl font-bold text-ink">Dla kogo</h2>
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($td['audience'] as $row)
                            <li class="flex items-start gap-3 {{ $card }} px-4 py-3">
                                <i class="fa-solid fa-user-check mt-1 text-brand-dark" aria-hidden="true"></i>
                                <span class="text-ink">{{ $row['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['requirements'] || $td['bonus'])
                <section class="mt-10" aria-labelledby="rec-criteria">
                    <h2 id="rec-criteria" class="mb-4 text-2xl font-bold text-ink">Kryteria udziału</h2>
                    @if ($td['requirements'])
                        <p class="mb-3 text-ink">Aby wziąć udział, musisz spełnić wszystkie warunki:</p>
                        <ul class="space-y-2">
                            @foreach ($td['requirements'] as $row)
                                <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-1 text-brand-dark" aria-hidden="true"></i><span class="text-ink">{{ $row['text'] }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($td['bonus'])
                        <h3 class="mb-3 mt-6 text-lg font-bold text-ink">Kryteria premiujące</h3>
                        <p class="mb-3 text-ink">Nie są obowiązkowe. Dają pierwszeństwo, gdy zgłoszeń jest więcej niż miejsc.</p>
                        <ul class="space-y-2">
                            @foreach ($td['bonus'] as $row)
                                <li class="flex items-start justify-between gap-4 {{ $card }} px-4 py-3">
                                    <span class="text-ink">{{ $row['text'] ?? '' }}</span>
                                    @if (filled($row['points'] ?? null))
                                        <span class="shrink-0 font-bold text-ink">{{ $row['points'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            @if ($td['benefits'])
                <section class="mt-10" aria-labelledby="rec-benefits">
                    <h2 id="rec-benefits" class="mb-4 text-2xl font-bold text-ink">Co oferujemy uczestnikom</h2>
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ($td['benefits'] as $row)
                            <li class="{{ $card }} p-5">
                                <i class="{{ filled($row['icon'] ?? null) ? $row['icon'] : 'fa-solid fa-check' }} mb-3 block text-xl text-brand-dark" aria-hidden="true"></i>
                                @if (filled($row['title'] ?? null))<h3 class="text-base font-bold text-ink">{{ $row['title'] }}</h3>@endif
                                @if (filled($row['text'] ?? null))<p class="mt-1 text-sm leading-relaxed text-ink">{{ $row['text'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['steps'])
                <section class="mt-10" aria-labelledby="rec-steps">
                    <h2 id="rec-steps" class="mb-4 text-2xl font-bold text-ink">Jak się zgłosić</h2>
                    <ol class="space-y-5">
                        @foreach ($td['steps'] as $i => $row)
                            <li>
                                <h3 class="text-base font-bold text-ink"><span class="sr-only">Etap </span>{{ $i + 1 }}. {{ $row['title'] ?? '' }}</h3>
                                @if (filled($row['text'] ?? null))<p class="mt-1 leading-relaxed text-ink">{{ $row['text'] }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($td['docs'])
                <section class="mt-10" aria-labelledby="rec-docs">
                    <h2 id="rec-docs" class="mb-4 text-2xl font-bold text-ink">Dokumenty rekrutacyjne</h2>
                    <ul class="space-y-2">
                        @foreach ($td['docs'] as $doc)
                            @continue(blank($doc['url'] ?? null) || blank($doc['title'] ?? null))
                            @php
                                $ext = strtoupper(pathinfo(parse_url($doc['url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                                $isFile = $ext !== '' && $ext !== 'HTML';
                            @endphp
                            <li class="flex items-start gap-3 {{ $card }} px-4 py-3">
                                <i class="fa-solid {{ $isFile ? 'fa-file-arrow-down' : 'fa-link' }} mt-1 text-brand-dark" aria-hidden="true"></i>
                                <div>
                                    <a href="{{ $doc['url'] }}" class="{{ $link }}" @if ($isFile) download @endif>{{ $doc['title'] }}@if ($isFile) <span class="font-normal text-muted">({{ $ext }})</span>@endif</a>
                                    @if (filled($doc['note'] ?? null))<p class="text-sm text-muted">{{ $doc['note'] }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($td['rec_accessibility'] ?? null))
                <section class="mt-10 rounded-md bg-brand-light p-5" aria-labelledby="rec-a11y">
                    <h2 id="rec-a11y" class="mb-2 flex items-center gap-2 text-lg font-bold text-ink">
                        <i class="fa-solid fa-universal-access text-brand-dark" aria-hidden="true"></i> Dostępność rekrutacji
                    </h2>
                    <p class="whitespace-pre-line text-ink">{{ $td['rec_accessibility'] }}</p>
                </section>
            @endif

            @if ($hasForm)
                <section id="zgloszenie" class="mt-10 scroll-mt-24" aria-labelledby="rec-form">
                    <h2 id="rec-form" class="mb-4 text-2xl font-bold text-ink">Formularz zgłoszeniowy</h2>
                    {!! \App\Support\ShortcodeParser::render('[formularz:'.$formSlug.']') !!}
                </section>
            @endif

            @if ($status !== 'closed' && filled($td['rec_results'] ?? null))
                <p class="mt-8 flex gap-3 text-ink"><i class="fa-solid fa-circle-info mt-1 text-brand-dark" aria-hidden="true"></i><span class="whitespace-pre-line">{{ $td['rec_results'] }}</span></p>
            @endif

            @if ($td['rec_faq'])
                <section class="mt-10" aria-labelledby="rec-faq">
                    <h2 id="rec-faq" class="mb-4 text-2xl font-bold text-ink">Pytania i odpowiedzi</h2>
                    <div class="space-y-2">
                        @foreach ($td['rec_faq'] as $row)
                            @continue(blank($row['q'] ?? null))
                            <details class="group {{ $card }}">
                                <summary class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 font-bold text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    {{ $row['q'] }}
                                    <i class="fa-solid fa-chevron-down text-sm transition group-open:rotate-180" aria-hidden="true"></i>
                                </summary>
                                @if (filled($row['a'] ?? null))
                                    <p class="whitespace-pre-line px-4 pb-3 text-ink">{{ $row['a'] }}</p>
                                @endif
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif


            @if ($project && filled($project->funding_notice))
                <p class="mt-10 text-sm text-muted">{{ $project->funding_notice }}</p>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
