{{--
    Wspólny widok typów „Konsultacje tyflo" i „Szkolenia tyflo".
    Zmienne: $page; $kind ('consultations' | 'trainings'); $embedded (true = podstrona działania:
    bez nagłówka, skrótów i kolumny bocznej, treść strony wypisuje widok działania); $headingTag
    (poziom nagłówków sekcji w osadzeniu), $onGray (osadzenie na szarym tle).
    Samodzielna strona: nagłówek z lead i skrótami → dwie kolumny. Treść: o konsultacjach/szkoleniu
    (+ dla kogo) → [szkolenia: najważniejsze informacje, program, wymagania] → prowadzący →
    harmonogram (tabela z <caption>, th scope, zakończone w <details>) → jak się zgłosić →
    formularz → dostępność. Kolumna boczna: najbliższy wolny termin i menu działu (na telefonie
    najpierw ona). Płasko, kolory marki, status tekstem + ikoną.
    Dane: $page->typeData(); harmonogram i etykiety: App\Support\TyfloSchedule.
--}}
@php
    $embedded = $embedded ?? false;
    $kind = $kind ?? 'consultations';
    $trainings = $kind === 'trainings';
    $L = \App\Support\TyfloSchedule::labels($kind);
    $sfx = $embedded ? '-'.$page->id : '';
    $hTag = $headingTag ?? ($embedded ? 'h3' : 'h2');
    $hSub = $hTag === 'h2' ? 'h3' : 'h4';
    // Sekcja osadzona na szarym tle (działanie) — karty i paski tabeli na białym, żeby się odcinały.
    $card = ($onGray ?? false) ? 'bg-white' : 'bg-gray-50';
    $zebra = ($onGray ?? false) ? 'odd:bg-white' : 'odd:bg-gray-50';

    $td = $page->typeData();
    $slots = \App\Support\TyfloSchedule::slots($td['slots']);
    $upcoming = $slots->reject(fn ($s) => $s['past'])->values();
    $past = $slots->filter(fn ($s) => $s['past'])->sortByDesc(fn ($s) => $s['date']->format('Y-m-d').' '.($s['time_from'] ?: '00:00'))->values();
    $next = $upcoming->first(fn ($s) => $s['bookable']);

    $people = collect($td['consultants'])->filter(fn ($c) => filled($c['name'] ?? null));
    $audience = collect($td['audience'])->filter(fn ($a) => filled($a['text'] ?? null));
    $program = $trainings ? collect($td['tt_program'])->filter(fn ($m) => filled($m['title'] ?? null)) : collect();
    $requirements = $trainings ? collect($td['requirements'])->filter(fn ($r) => filled($r['text'] ?? null)) : collect();
    $facts = $trainings ? array_filter([
        'Czas trwania' => $td['tt_duration'] ?? null,
        'Koszt' => $td['tt_cost'] ?? null,
        'Wielkość grupy' => $td['tt_group'] ?? null,
        'Po szkoleniu' => $td['tt_certificate'] ?? null,
    ], fn ($v) => filled($v)) : [];
    $factIcons = ['Czas trwania' => 'fa-clock', 'Koszt' => 'fa-coins', 'Wielkość grupy' => 'fa-users', 'Po szkoleniu' => 'fa-certificate'];
    $formSlug = $td['tc_form'] ?? '';
    $hasForm = filled($formSlug);

    $ctaUrl = trim((string) ($td['cta_url'] ?? ''));
    $ctaUrl = preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $ctaUrl) ? $ctaUrl : '';
    if ($ctaUrl === '' && $hasForm) {
        $ctaUrl = '#zgloszenie'.$sfx;
    }
    $ctaLabel = filled($td['cta_label'] ?? null) ? $td['cta_label'] : $L['cta'];

    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
    $hasSignup = $hasContact || $hasForm || $ctaUrl !== '' || filled($td['tc_signup_note'] ?? null);
    $localNav = ! $embedded && ($menuSiblings ?? collect())->isNotEmpty();
    // Treść główną wypisuje widok działania (osadzenie) albo ta sekcja (samodzielna strona).
    $showAbout = ! $embedded && ($page->content || $audience->isNotEmpty());
    $showAudienceOnly = $embedded && $audience->isNotEmpty();

    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $btn = 'inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $hCls = 'mb-4 text-2xl font-bold text-ink';
    $hSubCls = 'mb-2 mt-6 text-lg font-bold text-ink';

    $jump = array_filter([
        'o-konsultacjach' => $showAbout ? $L['about'] : null,
        'program' => $program->isNotEmpty() ? 'Program' : null,
        'konsultanci' => $people->isNotEmpty() ? $L['people'] : null,
        'harmonogram' => 'Harmonogram',
        'jak-sie-zgloszyc' => $hasSignup ? 'Jak się zgłosić' : null,
        'dostepnosc' => filled($td['tc_accessibility'] ?? null) ? 'Dostępność' : null,
    ]);
@endphp
@if ($embedded)
<div class="mt-8 space-y-10" data-page-type="{{ $page->type }}" data-embedded>
@else
<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="{{ $page->type }}">
    @include('page.partials.typed._head')

    <nav aria-label="Na tej stronie" class="-mt-2 mb-10">
        <ul role="list" class="flex flex-wrap gap-x-6 gap-y-2">
            @foreach ($jump as $anchor => $label)
                <li><a href="#{{ $anchor }}" class="{{ $link }}">{{ $label }}</a></li>
            @endforeach
        </ul>
    </nav>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <aside class="lg:order-last" aria-label="Najbliższy termin i menu działu">
            <div class="space-y-6 lg:sticky lg:top-24">
                <section class="rounded-md bg-brand-light p-6" aria-labelledby="tc-next">
                    <h2 id="tc-next" class="text-lg font-bold text-ink">{{ $L['next'] }}</h2>
                    @if ($next)
                        <p class="mt-3 text-lg font-bold text-ink">
                            <time datetime="{{ $next['date']->format('Y-m-d') }}">{{ \App\Support\TyfloSchedule::dateLabel($next['date']) }}</time>@if ($next['date_to']) – <time datetime="{{ $next['date_to']->format('Y-m-d') }}">{{ \App\Support\TyfloSchedule::dateLabel($next['date_to']) }}</time>@endif
                        </p>
                        <ul role="list" class="mt-2 space-y-1 text-ink">
                            @if ($next['time_label'])<li><i class="fa-solid fa-clock mr-2 text-brand-dark" aria-hidden="true"></i><span class="sr-only">Godzina: </span>{{ $next['time_label'] }}</li>@endif
                            @if ($next['mode_label'] || $next['place'])<li><i class="fa-solid fa-location-dot mr-2 text-brand-dark" aria-hidden="true"></i><span class="sr-only">Forma i miejsce: </span>{{ collect([$next['mode_label'], $next['place']])->filter()->implode(', ') }}</li>@endif
                            <li><i class="fa-solid {{ $next['status'] === 'few' ? 'fa-hourglass-half' : 'fa-circle-check' }} mr-2 text-brand-dark" aria-hidden="true"></i><span class="sr-only">Status: </span>{{ $next['status_label'] }}</li>
                        </ul>
                        @if ($ctaUrl !== '')
                            <a href="{{ $ctaUrl }}" class="{{ $btn }} mt-5 w-full">{{ $ctaLabel }} <i class="fa-solid {{ $ctaUrl === '#zgloszenie' ? 'fa-arrow-down' : 'fa-arrow-right' }} text-sm" aria-hidden="true"></i></a>
                        @endif
                    @else
                        <p class="mt-3 text-ink">Obecnie nie ma wolnych terminów. Sprawdź <a href="#harmonogram" class="{{ $link }}">harmonogram</a> lub skontaktuj się z nami.</p>
                    @endif
                </section>
                @if ($localNav)
                    <div>@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                @endif
            </div>
        </aside>

        <div class="min-w-0 space-y-12">
@endif
            @if ($showAbout)
                <section id="o-konsultacjach" class="scroll-mt-24" aria-labelledby="tc-about">
                    <{{ $hTag }} id="tc-about" class="{{ $hCls }}">{{ $L['about'] }}</{{ $hTag }}>
                    @if ($page->content)
                        <div class="prose max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
                    @endif
                    @if ($audience->isNotEmpty())
                        <{{ $hSub }} class="{{ $hSubCls }}" id="tc-audience">Dla kogo</{{ $hSub }}>
                        <ul class="list-disc space-y-1 pl-6 text-ink marker:text-brand-dark" aria-labelledby="tc-audience">
                            @foreach ($audience as $a)<li>{{ $a['text'] }}</li>@endforeach
                        </ul>
                    @endif
                </section>
            @elseif ($showAudienceOnly)
                <section aria-labelledby="tc-audience{{ $sfx }}">
                    <{{ $hTag }} class="{{ $hCls }}" id="tc-audience{{ $sfx }}">Dla kogo</{{ $hTag }}>
                    <ul class="list-disc space-y-1 pl-6 text-ink marker:text-brand-dark" aria-labelledby="tc-audience{{ $sfx }}">
                        @foreach ($audience as $a)<li>{{ $a['text'] }}</li>@endforeach
                    </ul>
                </section>
            @endif

            @if ($facts)
                <section aria-labelledby="tt-facts{{ $sfx }}">
                    <{{ $hTag }} id="tt-facts{{ $sfx }}" class="{{ $hCls }}">Najważniejsze informacje</{{ $hTag }}>
                    <dl class="grid gap-4 rounded-md {{ $card }} p-6 sm:grid-cols-2">
                        @foreach ($facts as $label => $value)
                            <div class="flex gap-3">
                                <i class="fa-solid {{ $factIcons[$label] }} mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                <div>
                                    <dt class="text-sm font-bold text-ink">{{ $label }}</dt>
                                    <dd class="font-semibold text-ink">{{ $value }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif

            @if ($program->isNotEmpty())
                <section id="program{{ $sfx }}" class="scroll-mt-24" aria-labelledby="tt-program{{ $sfx }}">
                    <{{ $hTag }} id="tt-program{{ $sfx }}" class="{{ $hCls }}">Program szkolenia</{{ $hTag }}>
                    <ol class="list-decimal space-y-3 pl-6 text-ink marker:font-bold marker:text-brand-dark">
                        @foreach ($program as $m)
                            <li>
                                <span class="font-bold">{{ $m['title'] }}</span>
                                @if (filled($m['text'] ?? null))<span class="mt-1 block leading-relaxed">{{ $m['text'] }}</span>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($requirements->isNotEmpty())
                <section aria-labelledby="tt-req{{ $sfx }}">
                    <{{ $hTag }} id="tt-req{{ $sfx }}" class="{{ $hCls }}">Wymagania i co przygotować</{{ $hTag }}>
                    <ul class="list-disc space-y-1 pl-6 text-ink marker:text-brand-dark">
                        @foreach ($requirements as $r)<li>{{ $r['text'] }}</li>@endforeach
                    </ul>
                </section>
            @endif

            @if ($people->isNotEmpty())
                <section id="konsultanci{{ $sfx }}" class="scroll-mt-24" aria-labelledby="tc-people{{ $sfx }}">
                    <{{ $hTag }} id="tc-people{{ $sfx }}" class="{{ $hCls }}">{{ $L['people'] }}</{{ $hTag }}>
                    <ul role="list" class="grid gap-4 sm:grid-cols-2">
                        @foreach ($people as $c)
                            <li class="flex flex-col rounded-md {{ $card }} p-5">
                                <{{ $hSub }} class="text-lg font-bold text-ink">{{ $c['name'] }}</{{ $hSub }}>
                                @if (filled($c['role'] ?? null))<p class="font-semibold text-brand-dark">{{ $c['role'] }}</p>@endif
                                @if (filled($c['text'] ?? null))<p class="mt-2 leading-relaxed text-ink">{{ $c['text'] }}</p>@endif
                                @if (filled($c['email'] ?? null))
                                    <p class="mt-auto pt-3"><a class="{{ $link }} break-all" href="mailto:{{ $c['email'] }}"><i class="fa-solid fa-envelope mr-1.5" aria-hidden="true"></i>{{ $c['email'] }}<span class="sr-only"> — napisz do: {{ $c['name'] }}</span></a></p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section id="harmonogram{{ $sfx }}" class="scroll-mt-24" aria-labelledby="tc-schedule{{ $sfx }}">
                <{{ $hTag }} id="tc-schedule{{ $sfx }}" class="{{ $hCls }}">{{ $L['schedule'] }}</{{ $hTag }}>
                @if (filled($td['tc_schedule_note'] ?? null))
                    <p class="mb-4 flex gap-3 text-ink" role="note"><i class="fa-solid fa-circle-info mt-1 text-brand-dark" aria-hidden="true"></i><span class="whitespace-pre-line"><span class="sr-only">Informacja o harmonogramie: </span>{{ $td['tc_schedule_note'] }}</span></p>
                @endif

                @if ($upcoming->isNotEmpty())
                    @include('page.partials.typed._tyflo-table', [
                        'slots' => $upcoming,
                        'labelId' => 'tc-schedule'.$sfx,
                        'caption' => 'Nadchodzące terminy '.$L['genitive'].': data, godzina, forma i miejsce, liczba miejsc, status'.($upcoming->contains(fn ($s) => $s['consultant']) ? ', '.mb_strtolower($L['person']) : '').'.',
                        'bookUrl' => $ctaUrl,
                        'showBook' => true,
                        'zebra' => $zebra,
                        'person' => $L['person'],
                        'enrol' => $L['enrol'],
                    ])
                @else
                    <p class="rounded-md {{ $card }} p-5 text-ink">{{ $L['empty'] }}@if ($hasContact && ! $embedded) Zapytaj o najbliższy termin. Dane do kontaktu znajdziesz w sekcji <a href="#jak-sie-zgloszyc" class="{{ $link }}">Jak się zgłosić</a>.@endif</p>
                @endif

                @if ($past->isNotEmpty())
                    <details class="mt-6 rounded-md {{ $card }} p-4">
                        <summary class="cursor-pointer rounded text-base font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Zakończone terminy ({{ $past->count() }})</summary>
                        <div class="mt-4">
                            <p id="tc-past{{ $sfx }}" class="sr-only">Zakończone terminy {{ $L['genitive'] }}</p>
                            @include('page.partials.typed._tyflo-table', [
                                'slots' => $past,
                                'labelId' => 'tc-past'.$sfx,
                                'caption' => 'Zakończone terminy '.$L['genitive'].'.',
                                'bookUrl' => '',
                                'showBook' => false,
                                'zebra' => $zebra,
                                'person' => $L['person'],
                                'enrol' => $L['enrol'],
                            ])
                        </div>
                    </details>
                @endif
            </section>

            @if ($hasSignup)
                <section id="jak-sie-zgloszyc{{ $sfx }}" class="scroll-mt-24" aria-labelledby="tc-signup{{ $sfx }}">
                    <{{ $hTag }} id="tc-signup{{ $sfx }}" class="{{ $hCls }}">Jak się zgłosić</{{ $hTag }}>
                    @if (filled($td['tc_signup_note'] ?? null))
                        <p class="mb-4 whitespace-pre-line leading-relaxed text-ink">{{ $td['tc_signup_note'] }}</p>
                    @endif
                    @if ($hasContact)
                        <ul role="list" class="space-y-2 text-ink">
                            @if (filled($td['contact_name'] ?? null))<li class="font-bold">{{ $td['contact_name'] }}</li>@endif
                            @if (filled($td['contact_phone'] ?? null))
                                <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $td['contact_phone']) }}" class="{{ $link }}"><i class="fa-solid fa-phone mr-1.5" aria-hidden="true"></i><span class="sr-only">Telefon: </span>{{ $td['contact_phone'] }}</a></li>
                            @endif
                            @if (filled($td['contact_email'] ?? null))
                                <li><a href="mailto:{{ $td['contact_email'] }}" class="{{ $link }} break-all"><i class="fa-solid fa-envelope mr-1.5" aria-hidden="true"></i><span class="sr-only">E-mail: </span>{{ $td['contact_email'] }}</a></li>
                            @endif
                        </ul>
                    @endif
                    @if ($ctaUrl !== '' && $ctaUrl !== '#zgloszenie'.$sfx)
                        <p class="mt-5"><a href="{{ $ctaUrl }}" class="{{ $btn }}">{{ $ctaLabel }} <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i></a></p>
                    @endif
                </section>
            @endif

            @if ($hasForm)
                <section id="zgloszenie{{ $sfx }}" class="scroll-mt-24" aria-labelledby="tc-form{{ $sfx }}">
                    <{{ $hTag }} id="tc-form{{ $sfx }}" class="{{ $hCls }}">Formularz zgłoszeniowy</{{ $hTag }}>
                    {!! \App\Support\ShortcodeParser::render('[formularz:'.$formSlug.']') !!}
                </section>
            @endif

            @if (filled($td['tc_accessibility'] ?? null))
                <section id="dostepnosc{{ $sfx }}" class="scroll-mt-24" aria-labelledby="tc-a11y{{ $sfx }}">
                    <{{ $hTag }} id="tc-a11y{{ $sfx }}" class="{{ $hCls }}">{{ $L['a11y'] }}</{{ $hTag }}>
                    <p class="whitespace-pre-line leading-relaxed text-ink">{{ $td['tc_accessibility'] }}</p>
                </section>
            @endif
@if ($embedded)
</div>
@else
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
@endif
