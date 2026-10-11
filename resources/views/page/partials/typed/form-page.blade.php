{{--
    Typ „Formularz”: tytuł i lead → dwie kolumny: wstęp (treść strony) i formularz | najważniejsze
    informacje (czas wypełnienia, termin i limit z ustawień formularza), co przygotować, co dalej,
    pomoc w wypełnieniu, kontakt. Na telefonie informacje są nad formularzem. Płasko, kolory marki.
--}}
@php
    $feer = ($siteSettings->site_template ?? 'default') === 'feer';
    $td = $page->typeData();
    $formSlug = (string) ($td['fp_form'] ?? '');
    $form = filled($formSlug) && ($siteSettings->isModuleEnabled('forms') ?? true)
        ? \App\Models\FormDefinition::where('slug', $formSlug)->where('is_active', true)->first()
        : null;
    $availability = $form?->availability();
    $until = $availability['until'] ?? null;

    $facts = array_filter([
        'Czas wypełnienia' => $td['fp_time'] ?? null,
        'Zgłoszenia do' => $until && ($availability['open'] ?? false) ? $until->locale('pl')->translatedFormat('j F Y, \g\o\d\z. H:i') : null,
        'Pozostałe miejsca' => ($availability['open'] ?? false) && ($availability['remaining'] ?? null) !== null ? (string) $availability['remaining'] : null,
    ], fn ($v) => filled($v));
    $factIcons = ['Czas wypełnienia' => 'fa-clock', 'Zgłoszenia do' => 'fa-calendar-days', 'Pozostałe miejsca' => 'fa-users'];

    $requirements = array_values(array_filter($td['requirements'] ?? [], fn ($r) => filled($r['text'] ?? null)));
    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
    $hasAside = $facts || $requirements || filled($td['fp_after'] ?? null) || filled($td['fp_help'] ?? null) || $hasContact || ($menuSiblings ?? collect())->isNotEmpty();
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp

<section class="mx-auto max-w-6xl px-4 py-12">
    <header class="mb-8 max-w-3xl">
        <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
        @if ($feer)<span class="mb-5 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
        @if (filled($td['lead'] ?? null))
            <p class="text-lg leading-relaxed text-ink">{{ $td['lead'] }}</p>
        @endif
    </header>

    <div class="{{ $hasAside ? 'grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]' : 'max-w-3xl' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Informacje o formularzu">
                <div class="space-y-6 lg:sticky lg:top-24">
                    @if ($facts || $form)
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="fp-summary">
                            <h2 id="fp-summary" class="sr-only">Najważniejsze informacje</h2>
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
                            @if ($form && ($availability['open'] ?? false))
                                <a href="#formularz-strony" class="{{ $facts ? 'mt-6' : '' }} inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                    Przejdź do formularza <i class="fa-solid fa-arrow-down text-sm" aria-hidden="true"></i>
                                </a>
                            @elseif ($form)
                                <p class="{{ $facts ? 'mt-4' : '' }} font-bold text-ink">{{ $availability['message'] }}</p>
                            @endif
                        </section>
                    @endif

                    @if ($requirements)
                        <section class="rounded-md bg-gray-50 p-6" aria-labelledby="fp-prepare">
                            <h2 id="fp-prepare" class="text-lg font-bold text-ink">Co przygotować</h2>
                            <ul class="mt-3 space-y-2">
                                @foreach ($requirements as $row)
                                    <li class="flex items-start gap-3 text-ink">
                                        <i class="fa-solid fa-check mt-1 text-brand-dark" aria-hidden="true"></i>
                                        <span>{{ $row['text'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (filled($td['fp_after'] ?? null))
                        <section class="rounded-md bg-gray-50 p-6" aria-labelledby="fp-after">
                            <h2 id="fp-after" class="text-lg font-bold text-ink">Co dalej po wysłaniu</h2>
                            <p class="mt-2 whitespace-pre-line text-ink">{{ $td['fp_after'] }}</p>
                        </section>
                    @endif

                    @if (filled($td['fp_help'] ?? null))
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="fp-help">
                            <h2 id="fp-help" class="flex items-center gap-2 text-lg font-bold text-ink">
                                <i class="fa-solid fa-universal-access text-brand-dark" aria-hidden="true"></i> Pomoc w wypełnieniu
                            </h2>
                            <p class="mt-2 whitespace-pre-line text-ink">{{ $td['fp_help'] }}</p>
                        </section>
                    @endif

                    @if ($hasContact)
                        <section class="rounded-md bg-gray-50 p-6" aria-labelledby="fp-contact">
                            <h2 id="fp-contact" class="text-lg font-bold text-ink">Masz pytania?</h2>
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
                        @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            @include('partials.page-content-image')

            @if ($page->content)
                <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            {{-- Nagłówek nad formularzem jest opcjonalny — bez niego tytułem sekcji jest nazwa formularza (h2). --}}
            <div id="formularz-strony" class="{{ $page->content ? 'mt-10' : '' }} scroll-mt-24">
                @if (filled($td['fp_heading'] ?? null))
                    <h2 class="text-2xl font-bold text-ink">{{ $td['fp_heading'] }}</h2>
                @endif
                @if ($form)
                    @include('formularz._embed', ['form' => $form, 'headingTag' => filled($td['fp_heading'] ?? null) ? 'h3' : 'h2'])
                @else
                    <p class="mt-4 rounded-md bg-gray-50 p-5 text-ink">Formularz jest chwilowo niedostępny. Skontaktuj się z nami, a przyjmiemy zgłoszenie w inny sposób.</p>
                @endif
            </div>

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
