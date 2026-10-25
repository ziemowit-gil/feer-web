{{--
    Typ „Konsultacje tyflo": nagłówek z lead i skrótami → dwie kolumny. Treść: o konsultacjach
    (+ dla kogo) → konsultanci → harmonogram (tabela z <caption>, th scope, zakończone w <details>)
    → jak się zgłosić → formularz → dostępność. Kolumna boczna: najbliższy wolny termin i menu
    działu (na telefonie najpierw kolumna boczna). Płasko, kolory marki, status tekstem + ikoną.
    Dane: $page->typeData(); harmonogram: App\Support\TyfloSchedule.
--}}
@php
    $td = $page->typeData();
    $slots = \App\Support\TyfloSchedule::slots($td['slots']);
    $upcoming = $slots->reject(fn ($s) => $s['past'])->values();
    $past = $slots->filter(fn ($s) => $s['past'])->sortByDesc(fn ($s) => $s['date']->format('Y-m-d').' '.($s['time_from'] ?: '00:00'))->values();
    $next = $upcoming->first(fn ($s) => $s['bookable']);

    $consultants = collect($td['consultants'])->filter(fn ($c) => filled($c['name'] ?? null));
    $audience = collect($td['audience'])->filter(fn ($a) => filled($a['text'] ?? null));
    $formSlug = $td['tc_form'] ?? '';
    $hasForm = filled($formSlug);

    $ctaUrl = trim((string) ($td['cta_url'] ?? ''));
    $ctaUrl = preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $ctaUrl) ? $ctaUrl : '';
    if ($ctaUrl === '' && $hasForm) {
        $ctaUrl = '#zgloszenie';
    }
    $ctaLabel = filled($td['cta_label'] ?? null) ? $td['cta_label'] : 'Zapisz się na konsultacje';

    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
    $hasSignup = $hasContact || $hasForm || $ctaUrl !== '' || filled($td['tc_signup_note'] ?? null);
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();

    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $btn = 'inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $h2 = 'mb-4 text-2xl font-bold text-ink';

    $jump = array_filter([
        'o-konsultacjach' => ($page->content || $audience->isNotEmpty()) ? 'O konsultacjach' : null,
        'konsultanci' => $consultants->isNotEmpty() ? 'Konsultanci' : null,
        'harmonogram' => 'Harmonogram',
        'jak-sie-zgloszyc' => $hasSignup ? 'Jak się zgłosić' : null,
        'dostepnosc' => filled($td['tc_accessibility'] ?? null) ? 'Dostępność' : null,
    ]);
@endphp
<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="tyflo_consultations">
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
                    <h2 id="tc-next" class="text-lg font-bold text-ink">Najbliższy wolny termin</h2>
                    @if ($next)
                        <p class="mt-3 text-lg font-bold text-ink">
                            <time datetime="{{ $next['date']->format('Y-m-d') }}">{{ \App\Support\TyfloSchedule::dateLabel($next['date']) }}</time>
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
            @if ($page->content || $audience->isNotEmpty())
                <section id="o-konsultacjach" class="scroll-mt-24" aria-labelledby="tc-about">
                    <h2 id="tc-about" class="{{ $h2 }}">O konsultacjach</h2>
                    @if ($page->content)
                        <div class="prose max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
                    @endif
                    @if ($audience->isNotEmpty())
                        <h3 class="mb-2 mt-6 text-lg font-bold text-ink" id="tc-audience">Dla kogo</h3>
                        <ul class="list-disc space-y-1 pl-6 text-ink marker:text-brand-dark" aria-labelledby="tc-audience">
                            @foreach ($audience as $a)<li>{{ $a['text'] }}</li>@endforeach
                        </ul>
                    @endif
                </section>
            @endif

            @if ($consultants->isNotEmpty())
                <section id="konsultanci" class="scroll-mt-24" aria-labelledby="tc-people">
                    <h2 id="tc-people" class="{{ $h2 }}">Konsultanci</h2>
                    <ul role="list" class="grid gap-4 sm:grid-cols-2">
                        @foreach ($consultants as $c)
                            <li class="flex flex-col rounded-md bg-gray-50 p-5">
                                <h3 class="text-lg font-bold text-ink">{{ $c['name'] }}</h3>
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

            <section id="harmonogram" class="scroll-mt-24" aria-labelledby="tc-schedule">
                <h2 id="tc-schedule" class="{{ $h2 }}">Harmonogram konsultacji</h2>
                @if (filled($td['tc_schedule_note'] ?? null))
                    <p class="mb-4 flex gap-3 text-ink" role="note"><i class="fa-solid fa-circle-info mt-1 text-brand-dark" aria-hidden="true"></i><span class="whitespace-pre-line"><span class="sr-only">Informacja o harmonogramie: </span>{{ $td['tc_schedule_note'] }}</span></p>
                @endif

                @if ($upcoming->isNotEmpty())
                    @include('page.partials.typed._tyflo-table', [
                        'slots' => $upcoming,
                        'labelId' => 'tc-schedule',
                        'caption' => 'Nadchodzące terminy konsultacji tyflo: data, godzina, forma i miejsce, liczba miejsc, status'.($upcoming->contains(fn ($s) => $s['consultant']) ? ', konsultant' : '').'.',
                        'bookUrl' => $ctaUrl,
                        'showBook' => true,
                    ])
                @else
                    <p class="rounded-md bg-gray-50 p-5 text-ink">Nowe terminy konsultacji zostaną wkrótce opublikowane.@if ($hasContact) Zapytaj o najbliższy termin — dane do kontaktu znajdziesz w sekcji <a href="#jak-sie-zgloszyc" class="{{ $link }}">Jak się zgłosić</a>.@endif</p>
                @endif

                @if ($past->isNotEmpty())
                    <details class="mt-6 rounded-md bg-gray-50 p-4">
                        <summary class="cursor-pointer rounded text-base font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Zakończone terminy ({{ $past->count() }})</summary>
                        <div class="mt-4">
                            <p id="tc-past" class="sr-only">Zakończone terminy konsultacji</p>
                            @include('page.partials.typed._tyflo-table', [
                                'slots' => $past,
                                'labelId' => 'tc-past',
                                'caption' => 'Zakończone terminy konsultacji tyflo.',
                                'bookUrl' => '',
                                'showBook' => false,
                            ])
                        </div>
                    </details>
                @endif
            </section>

            @if ($hasSignup)
                <section id="jak-sie-zgloszyc" class="scroll-mt-24" aria-labelledby="tc-signup">
                    <h2 id="tc-signup" class="{{ $h2 }}">Jak się zgłosić</h2>
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
                    @if ($ctaUrl !== '' && $ctaUrl !== '#zgloszenie')
                        <p class="mt-5"><a href="{{ $ctaUrl }}" class="{{ $btn }}">{{ $ctaLabel }} <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i></a></p>
                    @endif
                </section>
            @endif

            @if ($hasForm)
                <section id="zgloszenie" class="scroll-mt-24" aria-labelledby="tc-form">
                    <h2 id="tc-form" class="{{ $h2 }}">Formularz zgłoszeniowy</h2>
                    {!! \App\Support\ShortcodeParser::render('[formularz:'.$formSlug.']') !!}
                </section>
            @endif

            @if (filled($td['tc_accessibility'] ?? null))
                <section id="dostepnosc" class="scroll-mt-24" aria-labelledby="tc-a11y">
                    <h2 id="tc-a11y" class="{{ $h2 }}">Dostępność konsultacji</h2>
                    <p class="whitespace-pre-line leading-relaxed text-ink">{{ $td['tc_accessibility'] }}</p>
                </section>
            @endif

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
