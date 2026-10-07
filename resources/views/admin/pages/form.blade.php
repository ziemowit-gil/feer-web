@extends('admin.layout')

@section('title', $page->exists
    ? ($isPersonForm ? 'Edytuj osobę' : 'Edytuj stronę')
    : ($isPersonForm ? 'Nowa osoba' : 'Nowa strona')
)

@section('content')
    @if ($isPersonForm)
        <nav class="mb-4 flex items-center gap-1.5 text-sm text-muted" aria-label="Breadcrumb">
            <a href="{{ route('admin.osoby.index') }}" class="text-brand hover:underline">
                <i class="fa-solid fa-users mr-1" aria-hidden="true"></i>Osoby
            </a>
            <span aria-hidden="true">/</span>
            <span class="text-ink">{{ $page->exists ? $page->title : 'Nowa osoba' }}</span>
        </nav>
    @endif
    @php
        $currentType = old('type', $page->type ?? 'standard');
        $scheduleItems = old('schedule_items', $page->schedule_items ?? []);
        $scheduleItems = is_array($scheduleItems) ? array_values($scheduleItems) : [];
        $hasProject = (bool) old('project_id', $page->project_id);

        $aboutStats = array_values((array) old('about_stats', $page->about_stats ?? []));
        $aboutTimeline = array_values((array) old('about_timeline', $page->about_timeline ?? []));
        $aboutValues = array_values((array) old('about_values', $page->about_values ?? []));
        $aboutPress = array_values((array) old('about_press', $page->about_press ?? []));
        $faqItems = array_values((array) old('faq_items', $page->faq_items ?? []));
    @endphp

    @unless ($isPersonForm)
        {{-- Nagłówek formularza: tytuł, status, typ i adres strony — od razu widać, co się edytuje. --}}
        <header class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ $page->exists ? 'Edycja strony' : 'Nowa strona' }}</p>
                <h1 class="mt-1 truncate text-2xl font-bold text-ink">{{ $page->exists ? $page->title : 'Nowa strona' }}</h1>
                @if ($page->exists)
                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $page->is_published ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-900' }}">
                            <i class="fa-solid {{ $page->is_published ? 'fa-circle-check' : 'fa-pen' }}" aria-hidden="true"></i>{{ $page->is_published ? 'Opublikowana' : 'Szkic' }}
                        </span>
                        <span><i class="fa-solid {{ \App\Models\Page::TYPE_ICONS[$page->type] ?? 'fa-file-lines' }} mr-1" aria-hidden="true"></i>{{ trim(\Illuminate\Support\Str::before(\App\Models\Page::TYPES[$page->type] ?? 'Standardowa', ' (')) }}</span>
                        <span class="font-mono text-xs">{{ $page->publicUrl() }}</span>
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($page->exists && $page->is_published)
                    <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Zobacz stronę<span class="sr-only"> (otwiera się w nowej karcie)</span>
                    </a>
                @endif
                <button type="submit" form="page-edit-form" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Zapisz
                </button>
            </div>
        </header>
    @endunless

    <style>
        /* Czytelniejszy formularz strony: większe pola i etykiety, więcej oddechu, zakładki jako pasek z linią pod aktywną. */
        /* Zakładki jak w formularzu projektu: pigułki — aktywna w kolorze marki z białym tekstem, pozostałe jasnoszare. */
        [data-page-form-tabs] [role="tablist"] { background: transparent; padding: 0; border: 0; border-radius: 0; gap: .5rem; margin-bottom: 1.5rem; }
        [data-page-form-tabs] [data-ftab-btn] { border-radius: .375rem; padding: .5rem 1rem; font-size: .875rem; font-weight: 700; margin: 0; border: 0; box-shadow: none !important; background: #f3f4f6 !important; color: #1a1a1a !important; transition: background-color .15s; }
        [data-page-form-tabs] [data-ftab-btn]:hover { background: #e5e7eb !important; }
        [data-page-form-tabs] [data-ftab-btn][aria-selected="true"] { background: var(--color-brand) !important; color: #fff !important; }
        [data-page-form-tabs] [data-ftab-btn]:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 2px; }
        [data-page-form-tabs] form > [data-ftab-panel] > div.rounded-lg, [data-page-form-tabs] form > [data-ftab-panel] div.space-y-5.rounded-lg { border-radius: .75rem; padding: 1.75rem; }
        [data-page-form-tabs] form .space-y-5 > * + * { margin-top: 1.75rem; }
        [data-page-form-tabs] form label.block.text-sm, [data-page-form-tabs] form label.mb-1.block { font-size: .95rem; color: #1a1a1a; margin-bottom: .4rem; }
        [data-page-form-tabs] form input[type="text"], [data-page-form-tabs] form input[type="url"], [data-page-form-tabs] form input[type="number"],
        [data-page-form-tabs] form input[type="date"], [data-page-form-tabs] form input[type="datetime-local"], [data-page-form-tabs] form select, [data-page-form-tabs] form textarea { font-size: .95rem; padding-top: .6rem; padding-bottom: .6rem; border-radius: .5rem; }
        [data-page-form-tabs] form p.text-xs.text-muted { font-size: .8125rem; line-height: 1.45; }
        /* Panel „Publikacja": karty nie rozciągają się do wysokości sąsiada, a pola wyboru wyglądają jak przełączniki
           (wyłączony: szary #6B7280 — granica ≥ 3:1; włączony: kolor marki). */
        [data-page-form-tabs] [data-ftab-panel="ustawienia"] .grid { align-items: start; }
        [data-page-form-tabs] [data-ftab-panel="ustawienia"] label.flex > input[type="checkbox"] { appearance: none; -webkit-appearance: none; flex: none; width: 2.75rem; height: 1.5rem; border-radius: 9999px; border: 0; background-color: #6b7280; position: relative; cursor: pointer; transition: background-color .2s; margin-top: .125rem; }
        [data-page-form-tabs] [data-ftab-panel="ustawienia"] label.flex > input[type="checkbox"]::after { content: ""; position: absolute; top: .1875rem; left: .1875rem; width: 1.125rem; height: 1.125rem; border-radius: 9999px; background: #fff; transition: transform .2s; }
        [data-page-form-tabs] [data-ftab-panel="ustawienia"] label.flex > input[type="checkbox"]:checked { background-color: var(--color-brand); }
        [data-page-form-tabs] [data-ftab-panel="ustawienia"] label.flex > input[type="checkbox"]:checked::after { transform: translateX(1.25rem); }
        [data-page-form-tabs] [data-ftab-panel="ustawienia"] label.flex > input[type="checkbox"]:focus-visible { outline: 2px solid #1a1a1a; outline-offset: 2px; }
        [data-page-form-tabs] [data-main-form-actions] { position: sticky; bottom: 0; z-index: 20; margin-top: 1.5rem; padding: .85rem 1.25rem; background: rgba(255,255,255,.96); border-top: 1px solid #e5e7eb; border-radius: .75rem .75rem 0 0; }
    </style>

    <div data-page-form-tabs>
        <div class="mb-6 flex flex-wrap items-center gap-1.5 rounded-xl bg-gray-100/70 p-1.5" role="tablist">
            <button type="button" data-ftab-btn="tresc" role="tab" aria-selected="true"
                class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand shadow-sm transition-all">
                <i class="fa-solid fa-align-left" aria-hidden="true"></i> Treść
            </button>
            <button type="button" data-ftab-btn="typ" role="tab" aria-selected="false"
                class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-500 transition-all hover:text-ink">
                <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> Typ i układ
            </button>
            <button type="button" data-ftab-btn="ustawienia" role="tab" aria-selected="false"
                class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-500 transition-all hover:text-ink">
                <i class="fa-solid fa-gear" aria-hidden="true"></i> Publikacja
            </button>
            <button type="button" data-ftab-btn="seo" role="tab" aria-selected="false"
                class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-500 transition-all hover:text-ink">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> SEO
            </button>
            @if ($page->exists)
                @if ($currentType !== 'wspolpraca')
                <button type="button" data-ftab-btn="pliki" data-wspolpraca-tab role="tab" aria-selected="false"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-500 transition-all hover:text-ink">
                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Pliki
                    @if ($page->attachments->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-200 px-1.5 py-0.5 text-xs">{{ $page->attachments->count() }}</span>
                    @endif
                </button>
                <button type="button" data-ftab-btn="galeria" data-wspolpraca-tab role="tab" aria-selected="false"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-500 transition-all hover:text-ink">
                    <i class="fa-solid fa-images" aria-hidden="true"></i> Galeria
                    @if ($page->images->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-200 px-1.5 py-0.5 text-xs">{{ $page->images->count() }}</span>
                    @endif
                </button>
                <button type="button" data-ftab-btn="etr" data-wspolpraca-tab role="tab" aria-selected="false"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-500 transition-all hover:text-ink">
                    <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> ETR
                    @if ($page->etr?->is_enabled)
                        <span class="ml-1 rounded-full bg-sky-100 px-1.5 py-0.5 text-xs text-sky-700">aktywna</span>
                    @endif
                </button>
                @endif
                <a href="{{ $page->previewUrl() }}" target="_blank" rel="noopener"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-amber-600 transition-all hover:text-amber-700"
                    title="Podgląd strony przed publikacją (link ważny 14 dni)">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Podgląd
                </a>
                <a href="{{ route('admin.historia.index', ['type' => 'page', 'id' => $page->id]) }}"
                    class="ml-auto rounded-lg px-4 py-2 text-sm font-semibold text-gray-400 transition-all hover:text-ink">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historia
                </a>
            @endif
        </div>

        @if ($page->exists)
            @include('admin.partials.edit-lock', ['lockType' => 'page', 'lockId' => $page->id])
        @endif

        <form id="page-edit-form" method="POST" action="{{ $page->exists ? route('admin.podstrony.update', $page) : route('admin.podstrony.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if ($page->exists) @method('PUT') @endif

            {{-- ============================ TREŚĆ ============================ --}}
            <div data-ftab-panel="tresc" class="space-y-6">
                @include('admin.partials.template-panel', [
                    'templateType'   => 'page',
                    'templateFields' => ['content', 'meta_title', 'meta_description'],
                ])

                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
                            <input type="text" id="title" name="title" value="{{ old('title', $page->title) }}" required
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted">/</span>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="zostanie wygenerowany z tytułu"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            </div>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Główny edytor treści — ukryty dla typów bez swobodnej treści
                         („O organizacji" i „Przeniesiono do BIP" mają własne pola). --}}
                    <div data-content-field class="{{ in_array($currentType, ['about', 'bip_move', 'wspolpraca', 'contact'], true) ? 'hidden' : '' }}">
                        <label class="mb-1 block text-sm font-bold">Treść</label>
                        @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $page->content), 'revisionable' => $page->exists ? ['type' => 'page', 'id' => $page->id] : null])
                        @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Zdjęcie w treści --}}
                    <div data-hide-for="contact" class="{{ $currentType === 'contact' ? 'hidden' : '' }}">
                        <p class="mb-1 text-sm font-bold">Zdjęcie w treści <span class="font-normal text-muted">(opcjonalne)</span></p>
                        <p class="mb-3 text-xs text-muted">Pojawia się poniżej tytułu, przed główną treścią strony. Ustaw szerokość, by dopasować do układu.</p>
                        <div class="flex items-start gap-4">
                            @if (filled(old('content_image', $page->content_image ?? null)))
                                <img src="{{ old('content_image', $page->content_image) }}" alt=""
                                    class="h-20 w-32 shrink-0 rounded object-cover">
                                <label class="mt-1 flex items-center gap-1.5 text-sm text-red-600">
                                    <input type="checkbox" name="remove_content_image" value="1"
                                        class="rounded border-gray-300 text-brand focus:ring-brand">
                                    Usuń zdjęcie
                                </label>
                            @else
                                <span class="flex h-20 w-32 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-300" aria-hidden="true">
                                    <i class="fa-solid fa-image text-2xl"></i>
                                </span>
                            @endif
                            <div class="min-w-0 flex-1 space-y-2">
                                <input type="file" name="content_image_file" accept="image/*"
                                    aria-label="Wgraj zdjęcie"
                                    class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand-light file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-brand">
                                <input type="text" name="content_image" value="{{ old('content_image', $page->content_image ?? '') }}"
                                    placeholder="…albo wklej URL zdjęcia"
                                    class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="content_image_alt" class="mb-1 block text-xs font-bold">Tekst alternatywny (dostępność)</label>
                                        <input type="text" id="content_image_alt" name="content_image_alt"
                                            value="{{ old('content_image_alt', $page->content_image_alt ?? '') }}"
                                            placeholder="Opisz co przedstawia zdjęcie"
                                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    </div>
                                    <div>
                                        <label for="content_image_width" class="mb-1 block text-xs font-bold">Szerokość</label>
                                        <select id="content_image_width" name="content_image_width"
                                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            <option value="" {{ old('content_image_width', $page->content_image_width ?? '') === '' ? 'selected' : '' }}>Pełna (100%)</option>
                                            <option value="max-w-2xl" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-2xl' ? 'selected' : '' }}>Duże (ok. 672px)</option>
                                            <option value="max-w-xl" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-xl' ? 'selected' : '' }}>Średnie (ok. 576px)</option>
                                            <option value="max-w-lg" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-lg' ? 'selected' : '' }}>Małe (ok. 512px)</option>
                                            <option value="max-w-xs" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-xs' ? 'selected' : '' }}>Bardzo małe (ok. 320px)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ TYP I UKŁAD ============================ --}}
            <div data-ftab-panel="typ" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div class="{{ $isPersonForm ? 'sm:w-1/2' : '' }}">
                        @if ($isPersonForm)
                            <input type="hidden" name="type" value="about_person">
                            <label class="mb-1 block text-sm font-bold">Typ strony</label>
                            <p class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-muted">
                                <i class="fa-solid fa-user mr-1.5 text-brand" aria-hidden="true"></i>
                                Osoba (typ stały — zarządzaj przez moduł <a href="{{ route('admin.osoby.index') }}" class="text-brand underline">Osoby</a>)
                            </p>
                        @else
                            @php
                                // Wybór typu: karty z ikoną, nazwą i opisem, pogrupowane i z wyszukiwarką. Natywny <select> zostaje
                                // (ukryty) — nadal niesie wartość formularza, a skrypt strony nasłuchuje na jego zdarzeniu „change".
                                $typeGroups = [
                                    'Treść' => ['standard', 'about', 'about_person', 'faq', 'glossary', 'guide', 'case_study', 'service', 'legacy'],
                                    'Układy i kafelki' => ['links_hub', 'tiles_grid', 'wspolpraca', 'contact', 'brand_assets'],
                                    'Wydarzenia i szkolenia' => ['event', 'schedule', 'training_institution'],
                                    'Wewnętrzne i przekierowania' => ['internal', 'internal_hub', 'bip_move'],
                                ];
                                $typeCards = [];
                                foreach (\App\Models\Page::TYPES as $value => $label) {
                                    if ($siteSettings->isOptionBlocked('page_types', $value) && $currentType !== $value) { continue; }
                                    $typeCards[$value] = [
                                        'full' => $label,
                                        'name' => trim(\Illuminate\Support\Str::before($label, ' (')),
                                        'desc' => \Illuminate\Support\Str::contains($label, ' (') ? rtrim(\Illuminate\Support\Str::after($label, ' ('), ')') : '',
                                        'icon' => \App\Models\Page::TYPE_ICONS[$value] ?? 'fa-file-lines',
                                    ];
                                }
                            @endphp
                            <select id="type" name="type" data-page-type-select class="sr-only" tabindex="-1" aria-hidden="true">
                                @foreach ($typeCards as $value => $card)
                                    <option value="{{ $value }}" {{ $currentType === $value ? 'selected' : '' }}>{{ $card['full'] }}</option>
                                @endforeach
                            </select>
                            @include('admin.partials.modal-picker', [
                                'pickerId' => 'type-picker', 'title' => 'Typ strony', 'options' => $typeCards, 'groups' => $typeGroups,
                                'current' => $currentType, 'carrier' => ['select' => 'type'],
                            ])
                            <p class="mt-1 text-xs text-muted">„Wydarzenie" dodaje pola o terminie, miejscu i rejestracji. „Harmonogram zajęć / spotkań" dodaje tabelę terminów oraz miejsce na informację o zmianie. „Oferta", „Poradnik", „Słownik" i „Studium przypadku" mają własne sekcje pól poniżej. Każdy typ ma inny układ na stronie.</p>
                            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @endif
                    </div>

                    <div data-hide-for="contact" class="border-t border-gray-100 pt-5 sm:w-1/2 {{ $currentType === 'contact' ? 'hidden' : '' }}">
                        @php
                            $tplIcons = ['default' => 'fa-file-lines', 'wide' => 'fa-expand', 'hero' => 'fa-image', 'landing' => 'fa-bullhorn', 'portal' => 'fa-globe', 'minimal' => 'fa-minimize'];
                            $tplCards = [];
                            foreach (\App\Models\Page::TEMPLATES as $value => $label) {
                                $tplCards[$value] = [
                                    'name' => trim(\Illuminate\Support\Str::before($label, ' (')),
                                    'desc' => \Illuminate\Support\Str::contains($label, ' (') ? rtrim(\Illuminate\Support\Str::after($label, ' ('), ')') : '',
                                    'icon' => $tplIcons[$value] ?? 'fa-file-lines',
                                ];
                            }
                            $tplCurrent = old('page_template', $page->page_template ?? 'default');
                        @endphp
                        <select id="page_template" name="page_template" class="sr-only" tabindex="-1" aria-hidden="true">
                            @foreach (\App\Models\Page::TEMPLATES as $value => $label)
                                <option value="{{ $value }}" {{ $tplCurrent === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @include('admin.partials.modal-picker', [
                            'pickerId' => 'template-picker', 'title' => 'Szablon wizualny', 'options' => $tplCards,
                            'current' => $tplCurrent, 'carrier' => ['select' => 'page_template'],
                        ])
                        <p class="mt-1 text-xs text-muted">Zmienia wygląd i układ strony publicznej. Działa dla stron typu „Standardowa", „Wewnętrzna" i „FAQ". Dla pozostałych typów układ jest stały.</p>
                        @error('page_template') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @include('admin.pages.partials.types.event')

                    @include('admin.pages.partials.types.schedule')

                    @include('admin.pages.partials.types.about')

                    @include('admin.pages.partials.types.faq')

                    @include('admin.pages.partials.types.bipmove')

                    @include('admin.pages.partials.types.internal')

                    @include('admin.pages.partials.types.hub')

                    @include('admin.pages.partials.types.training')

                    @include('admin.pages.partials.types.tiles')

                    @include('admin.pages.partials.types.legacy')

                    @include('admin.pages.partials.types.brand')

                    @include('admin.pages.partials.types.about-person')

                    @include('admin.pages.partials.types.contact')
                </div>
            </div>

                    @include('admin.pages.partials.type-data-fields', ['currentType' => $currentType])

                    @include('admin.pages.partials.types.cooperation')
                    </div>

            {{-- ==================== PUBLIKACJA I POWIĄZANIA ==================== --}}
            <div data-ftab-panel="ustawienia" class="hidden space-y-6">
                {{-- Karta: widoczność i status --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6"
                    x-data="{ pub: {{ old('is_published', $page->is_published ?? true) ? 'true' : 'false' }} }">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-ink">Widoczność i status</h2>
                        <p class="mt-0.5 text-xs text-muted">Decyduje, czy i jak strona pojawia się w serwisie.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}
                                @change="pub = $event.target.checked"
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Opublikowana</span>
                                <span class="block text-xs text-muted">Strona jest dostępna publicznie.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="is_archived" value="1" {{ old('is_archived', $page->is_archived ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-clock-rotate-left text-muted" aria-hidden="true"></i> Treść archiwalna</span>
                                <span class="block text-xs text-muted">Pokazuje baner, że treść może być nieaktualna (pozostaje w wyszukiwarce).</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="show_in_menu" value="1" {{ old('show_in_menu', $page->show_in_menu ?? true) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Dodaj do menu</span>
                                <span class="block text-xs text-muted">Tylko strony główne (bez rodzica i projektu) trafiają do nawigacji.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="hidden" name="show_side_nav" value="0">
                            <input type="checkbox" name="show_side_nav" value="1" {{ old('show_side_nav', $page->show_side_nav ?? true) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="flex-1">
                                <span class="block text-sm font-bold">Nawigacja po podstronach działu</span>
                                <span class="block text-xs text-muted">Lista podstron w tym dziale. Wyłącz dla stron bez rozbudowanej struktury. Styl ustawiony na stronie głównej działu obowiązuje dla wszystkich jej podstron.</span>
                                @php
                                    $sideNavStyle = old('side_nav_style', $page->side_nav_style ?? 'sidebar');
                                    if (! array_key_exists($sideNavStyle, \App\Models\Page::SIDE_NAV_STYLES)) {
                                        $sideNavStyle = 'sidebar';
                                    }
                                    $sideNavIcons = ['sidebar' => 'fa-table-columns', 'tabs' => 'fa-window-maximize', 'tree' => 'fa-sitemap', 'tiles' => 'fa-table-cells-large'];
                                    $sideNavHints = [
                                        'sidebar' => 'Lista podstron tego poziomu w prawej kolumnie (z jedną zagnieżdżoną gałęzią).',
                                        'tabs'    => 'Poziomy pasek zakładek nad treścią — jeden poziom podstron.',
                                        'tiles'   => 'Podstrony działu jako duże, kolorowe kafelki pod treścią strony (jak w serwisach urzędowych). Na samych podstronach działa boczna lista rodzeństwa.',
                                        'tree'    => 'Lewa kolumna z całym działem: ścieżka „Jesteś tu", wszystkie poziomy podstron, zwijane gałęzie z licznikiem — jak drzewo stron w TYPO3.',
                                    ];
                                @endphp
                                {{-- Prawdziwe pola radio zostają (ukryte) jako nośnik wartości; wybór odbywa się w oknie dialogowym. --}}
                                <span class="sr-only">
                                    @foreach (\App\Models\Page::SIDE_NAV_STYLES as $styleKey => $styleLabel)
                                        <input type="radio" name="side_nav_style" value="{{ $styleKey }}" {{ $sideNavStyle === $styleKey ? 'checked' : '' }} tabindex="-1" aria-hidden="true">
                                    @endforeach
                                </span>
                                @php
                                    $navCards = [];
                                    foreach (\App\Models\Page::SIDE_NAV_STYLES as $styleKey => $styleLabel) {
                                        $navCards[$styleKey] = ['name' => trim(\Illuminate\Support\Str::before($styleLabel, ' (')), 'desc' => $sideNavHints[$styleKey], 'icon' => $sideNavIcons[$styleKey]];
                                    }
                                @endphp
                                <span class="mt-3 block" @click.stop.prevent>
                                    @include('admin.partials.modal-picker', [
                                        'pickerId' => 'nav-style-picker', 'title' => 'Styl nawigacji po podstronach', 'options' => $navCards,
                                        'current' => $sideNavStyle, 'carrier' => ['radio' => 'side_nav_style'],
                                    ])
                                </span>
                                @foreach (\App\Models\Page::SIDE_NAV_STYLES as $styleKey => $styleLabel)
                                    <span id="side-nav-hint-{{ $styleKey }}" class="sr-only">{{ $sideNavHints[$styleKey] }}</span>
                                @endforeach
                                <span class="mt-2 block text-xs text-muted" x-data="{ s: '{{ $sideNavStyle }}' }" @change.window="if ($event.target.name === 'side_nav_style') s = $event.target.value">
                                    <template x-for="[k, v] of Object.entries({{ \Illuminate\Support\Js::from($sideNavHints) }})" :key="k">
                                        <span x-show="s === k" x-text="v"></span>
                                    </template>
                                </span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="is_system" value="1" {{ old('is_system', $page->is_system ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Strona systemowa</span>
                                <span class="block text-xs text-muted">Wymagana strona serwisu — nie można jej usunąć.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 {{ in_array($currentType, ['about', 'contact'], true) ? 'hidden' : '' }}" data-gallery-toggle>
                            <input type="hidden" name="show_gallery" value="0">
                            <input type="checkbox" name="show_gallery" value="1" {{ old('show_gallery', $page->show_gallery ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Pokaż galerię zdjęć</span>
                                <span class="block text-xs text-muted">Wyświetla zdjęcia z zakładki „Galeria".</span>
                            </span>
                        </label>

                        @if (auth()->user()->isAdmin())
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="hidden" name="is_locked" value="0">
                                <input type="checkbox" name="is_locked" value="1" {{ old('is_locked', $page->is_locked ?? false) ? 'checked' : '' }}
                                    class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                                <span>
                                    <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-lock text-brand" aria-hidden="true"></i> Zablokuj do edycji</span>
                                    <span class="block text-xs text-muted">Edytować, klonować i usuwać może tylko administrator.</span>
                                </span>
                            </label>
                        @elseif ($page->is_locked ?? false)
                            <p class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                                <i class="fa-solid fa-lock mt-0.5" aria-hidden="true"></i>
                                <span>Ta strona jest zablokowana do edycji przez administratora.</span>
                            </p>
                        @endif
                    </div>

                    {{-- Harmonogram: data i godzina pierwszego pokazania strony --}}
                    <div x-show="pub" x-cloak class="mt-4 flex flex-wrap items-end gap-4 rounded-lg border border-blue-100 bg-blue-50/50 p-4">
                        <div>
                            <label for="publish_at" class="mb-1 block text-sm font-bold text-ink">
                                <i class="fa-regular fa-clock mr-1 text-blue-400" aria-hidden="true"></i>
                                Opublikuj dopiero od
                                <span class="font-normal text-muted">(opcjonalnie)</span>
                            </label>
                            <input type="datetime-local" id="publish_at" name="publish_at"
                                value="{{ old('publish_at', $page->publish_at?->format('Y-m-d\TH:i')) }}"
                                class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('publish_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <p class="text-xs text-muted">
                            Puste = widoczna natychmiast. Podaj datę, aby strona pojawiła się publicznie dopiero od tej chwili — wcześniej niedostępna dla odwiedzających.
                        </p>
                    </div>
                </div>

                {{-- Karta: powiązania i kolejność --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-ink">Powiązania i kolejność</h2>
                        <p class="mt-0.5 text-xs text-muted">Umiejscowienie strony w strukturze serwisu.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="parent_id" class="mb-1 block text-sm font-bold">Nadrzędna strona</label>
                            <select id="parent_id" name="parent_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="">— brak (strona główna) —</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}" {{ (int) old('parent_id', $page->parent_id ?? request('parent_id')) === $option->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Strony z tym samym rodzicem tworzą wspólne, osobne podmenu.</p>
                            @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="project_id" class="mb-1 block text-sm font-bold">Powiąż z projektem</label>
                            <select id="project_id" name="project_id" data-project-select class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="">— brak —</option>
                                @foreach ($projectOptions as $option)
                                    <option value="{{ $option->id }}" {{ (int) old('project_id', $page->project_id) === $option->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Strona zachowa własny adres, a dodatkowo pojawi się na stronie projektu.</p>
                            @error('project_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2 {{ $hasProject ? '' : 'hidden' }}" data-project-display-wrap>
                            <label for="project_display" class="mb-1 block text-sm font-bold">Jak pokazać na stronie projektu</label>
                            <select id="project_display" name="project_display" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand sm:w-2/3">
                                @foreach (\App\Models\Page::PROJECT_DISPLAYS as $value => $label)
                                    <option value="{{ $value }}" {{ old('project_display', $page->project_display ?? 'link') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Jako sam odnośnik, jako zakładka albo jako sekcja w treści projektu.</p>
                            @error('project_display') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                            <input type="number" id="order" name="order" min="0" value="{{ old('order', $page->order) }}"
                                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Mniejsza liczba = wyżej.</p>
                        </div>
                    </div>
                </div>

                {{-- ==================== DOSTĘPNOŚĆ STRONY ==================== --}}
                @php $currentWip = old('wip_mode', $page->wip_mode); @endphp
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Dostępność strony</p>
                        <p class="mt-1 text-xs text-muted">Tymczasowo wyłącz stronę lub oznacz, że jest w przygotowaniu. Działa niezależnie od statusu publikacji.</p>
                    </div>

                    {{-- Wyłącz stronę --}}
                    <div class="border-t border-gray-100 pt-5">
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="is_disabled" value="1" {{ old('is_disabled', $page->is_disabled ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand" data-disable-toggle>
                            <span>
                                <span class="block text-sm font-bold">Wyłącz stronę</span>
                                <span class="block text-xs text-muted">Odwiedzający zamiast treści zobaczą pełnoekranowy komunikat, że strona jest tymczasowo niedostępna. Wyłączenie obejmuje także wszystkie podstrony tej strony.</span>
                            </span>
                        </label>
                        <div class="mt-3 sm:pl-6" data-disable-message>
                            <label for="disabled_message" class="mb-1 block text-sm font-bold">Komunikat <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="disabled_message" name="disabled_message" rows="2" placeholder="{{ \App\Models\Page::DEFAULT_DISABLED_MESSAGE }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('disabled_message', $page->disabled_message) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu.</p>
                            @error('disabled_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Strona w przygotowaniu --}}
                    <div class="border-t border-gray-100 pt-5">
                        <p class="text-sm font-bold">Strona w przygotowaniu</p>
                        <p class="mb-3 text-xs text-muted">Oznacz, że trwają prace nad stroną — wybierz, jak poinformować odwiedzających.</p>

                        <div class="space-y-2" data-wip-modes>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="wip_mode" value="" {{ ! $currentWip ? 'checked' : '' }}
                                    class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">Wyłączone <span class="text-muted">— strona działa normalnie</span></span>
                            </label>
                            @foreach (\App\Models\Page::WIP_MODES as $value => $label)
                                <label class="flex items-start gap-2">
                                    <input type="radio" name="wip_mode" value="{{ $value }}" {{ $currentWip === $value ? 'checked' : '' }}
                                        class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('wip_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-3 sm:pl-6 {{ $currentWip ? '' : 'hidden' }}" data-wip-message>
                            <label for="wip_message" class="mb-1 block text-sm font-bold">Treść komunikatu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="wip_message" name="wip_message" rows="2" placeholder="{{ \App\Models\Page::DEFAULT_WIP_NOTICE_MESSAGE }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('wip_message', $page->wip_message) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu dla wybranego trybu.</p>
                            @error('wip_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ SEO ============================ --}}
            <div data-ftab-panel="seo" class="hidden space-y-6">
                @include('admin.partials.seo-fields', ['model' => $page])
            </div>

            <div class="flex items-center gap-3" data-main-form-actions>
                <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
                @if ($isPersonForm)
                    <a href="{{ route('admin.osoby.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
                @else
                    <a href="{{ route('admin.podstrony.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
                @endif
            </div>
        </form>

        @if ($page->exists)
            <div data-ftab-panel="pliki" class="hidden">
                @include('admin.partials.attachments', [
                    'attachments' => $page->attachments,
                    'storeRoute' => route('admin.podstrony.pliki.store', $page),
                    'brandSections' => $page->isBrandAssets() ? ($page->brand_sections ?? []) : [],
                ])
            </div>
            <div data-ftab-panel="galeria" class="hidden">
                @include('admin.partials.page-images', ['page' => $page])
            </div>
            <div data-ftab-panel="etr" class="hidden">
                @php $etrModel = $page->etr; @endphp
                <div class="mb-4 rounded-xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800">
                    <strong>Wersja ETR (łatwa do czytania)</strong> — uproszczony tekst dla osób z trudnościami w czytaniu.
                    Gdy włączysz ETR, na tej stronie pojawi się przycisk pozwalający przełączyć się na prostszą wersję.
                    <a href="{{ route('etr.about') }}" target="_blank" class="ml-1 underline hover:text-sky-900">Co to jest ETR? →</a>
                </div>

                <form method="POST" action="{{ route('admin.etr.update', ['type' => 'podstrona', 'id' => $page->id]) }}"
                    class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    @csrf @method('PUT')

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_enabled" value="1"
                            {{ old('is_enabled', $etrModel?->is_enabled ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        <span class="font-bold text-ink">Włącz wersję ETR dla tej strony</span>
                    </label>

                    <div>
                        <label for="etr_title" class="mb-1 block text-sm font-bold">Tytuł (uproszczony) <span class="font-normal text-muted">— opcjonalny, zastąpi oryginalny tytuł w widoku ETR</span></label>
                        <input type="text" id="etr_title" name="etr_title"
                            value="{{ old('etr_title', $etrModel?->etr_title) }}"
                            placeholder="{{ $page->title }}"
                            class="w-full rounded border-gray-300 focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label for="etr_summary" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">— 1–3 zdania prostym językiem</span></label>
                        <textarea id="etr_summary" name="etr_summary" rows="3"
                            placeholder="Krótkie, proste wyjaśnienie o czym jest ta strona."
                            class="w-full rounded border-gray-300 focus:border-sky-500 focus:ring-sky-500">{{ old('etr_summary', $etrModel?->etr_summary) }}</textarea>
                    </div>

                    <div>
                        <label for="etr_content" class="mb-1 block text-sm font-bold">Treść ETR <span class="font-normal text-muted">— prosty tekst, jedno zdanie w akapicie</span></label>
                        <textarea id="etr_content" name="etr_content" rows="12"
                            placeholder="Pisz prostymi słowami.&#10;&#10;Krótkie zdania.&#10;&#10;Jedna myśl — jeden akapit."
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-sky-500 focus:ring-sky-500">{{ old('etr_content', $etrModel?->etr_content) }}</textarea>
                        <p class="mt-1 text-xs text-muted">Puste wiersze tworzą nowe akapity. Nie używaj formatowania HTML.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700">Zapisz ETR</button>
                        @if ($etrModel)
                            <form method="POST" action="{{ route('admin.etr.destroy', ['type' => 'podstrona', 'id' => $page->id]) }}"
                                onsubmit="return confirm('Usuń całą wersję ETR tej strony?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-bold text-red-600 hover:text-red-700">Usuń ETR</button>
                            </form>
                        @endif
                    </div>
                </form>
            </div>
        @endif
    </div>{{-- /page-form-tabs --}}

    <script>
        (function () {
            // --- Type-specific fields (event / schedule) -------------------
            const typeSelect = document.querySelector('[data-page-type-select]');
            const eventFields = document.querySelector('[data-event-fields]');
            const scheduleFields = document.querySelector('[data-schedule-fields]');
            const aboutFields = document.querySelector('[data-about-fields]');
            const faqFields = document.querySelector('[data-faq-fields]');
            const bipMoveFields = document.querySelector('[data-bipmove-fields]');
            const internalFields = document.querySelector('[data-internal-fields]');
            const hubFields = document.querySelector('[data-hub-fields]');
            const legacyFields = document.querySelector('[data-legacy-fields]');
            const trainingFields = document.querySelector('[data-training-fields]');
            const tilesFields = document.querySelector('[data-tiles-fields]');
            const contentField = document.querySelector('[data-content-field]');
            const brandFields = document.querySelector('[data-brand-fields]');
            const aboutPersonFields = document.querySelector('[data-about-person-fields]');
            const cooperationFields = document.querySelector('[data-cooperation-fields]');
            if (typeSelect) {
                typeSelect.addEventListener('change', function () {
                    if (eventFields) eventFields.classList.toggle('hidden', typeSelect.value !== 'event');
                    if (scheduleFields) scheduleFields.classList.toggle('hidden', typeSelect.value !== 'schedule');
                    if (aboutFields) aboutFields.classList.toggle('hidden', typeSelect.value !== 'about');
                    if (faqFields) faqFields.classList.toggle('hidden', typeSelect.value !== 'faq');
                    if (bipMoveFields) bipMoveFields.classList.toggle('hidden', typeSelect.value !== 'bip_move');
                    if (internalFields) internalFields.classList.toggle('hidden', ! ['internal', 'internal_hub'].includes(typeSelect.value));
                    if (hubFields) hubFields.classList.toggle('hidden', ! ['internal_hub', 'links_hub'].includes(typeSelect.value));
                    if (legacyFields) legacyFields.classList.toggle('hidden', typeSelect.value !== 'legacy');
                    if (trainingFields) trainingFields.classList.toggle('hidden', typeSelect.value !== 'training_institution');
                    document.querySelectorAll('[data-type-fields]').forEach((el) => el.classList.toggle('hidden', el.dataset.typeFields !== typeSelect.value));
                    if (brandFields) brandFields.classList.toggle('hidden', typeSelect.value !== 'brand_assets');
                    if (aboutPersonFields) aboutPersonFields.classList.toggle('hidden', typeSelect.value !== 'about_person');
                    if (cooperationFields) cooperationFields.classList.toggle('hidden', typeSelect.value !== 'wspolpraca');
                    // Kafelki dostępne dla każdego typu strony
                    if (contentField) contentField.classList.toggle('hidden', ['about', 'bip_move', 'wspolpraca', 'contact'].includes(typeSelect.value));
                    document.querySelectorAll('[data-hide-for]').forEach((el) => el.classList.toggle('hidden', el.dataset.hideFor === typeSelect.value));
                    const contactFields = document.querySelector('[data-contact-fields]');
                    if (contactFields) contactFields.classList.toggle('hidden', typeSelect.value !== 'contact');
                    document.querySelectorAll('[data-wspolpraca-tab]').forEach(function (btn) {
                        const isWspolpraca = typeSelect.value === 'wspolpraca';
                        btn.classList.toggle('hidden', isWspolpraca);
                        if (isWspolpraca && btn.getAttribute('aria-selected') === 'true') {
                            document.querySelector('[data-ftab-btn="tresc"]')?.click();
                        }
                    });
                    // Galeria „O organizacji" jest osobna — ukryj generyczny przełącznik dla tego typu.
                    document.querySelectorAll('[data-gallery-toggle]').forEach(function (el) {
                        el.classList.toggle('hidden', ['about', 'contact'].includes(typeSelect.value));
                    });
                });
            }

            // --- Generic repeaters (about-page sections) ------------------
            document.querySelectorAll('[data-repeater]').forEach(function (rep) {
                const rows = rep.querySelector('[data-repeater-rows]');
                const template = rep.querySelector('[data-repeater-template]');
                const addBtn = rep.querySelector('[data-repeater-add]');
                if (!rows || !template) return;
                let nextIndex = rows.querySelectorAll('[data-repeater-row]').length;

                if (addBtn) {
                    addBtn.addEventListener('click', function () {
                        const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = html.trim();
                        rows.appendChild(wrapper.firstElementChild);
                    });
                }
                // Przelicz indeksy nazw pól wg kolejności w DOM, aby zapisana
                // kolejność odpowiadała tej na ekranie (po przenoszeniu wierszy).
                const reindex = function () {
                    rows.querySelectorAll('[data-repeater-row]').forEach(function (row, n) {
                        row.querySelectorAll('[name]').forEach(function (el) {
                            el.name = el.name.replace(/^([^\[]+)\[[^\]]*\]/, '$1[' + n + ']');
                        });
                    });
                };

                rep.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-repeater-remove]');
                    if (remove) {
                        const row = remove.closest('[data-repeater-row]');
                        if (row) row.remove();
                        return;
                    }
                    const move = e.target.closest('[data-repeater-move]');
                    if (move) {
                        const row = move.closest('[data-repeater-row]');
                        if (!row) return;
                        if (move.dataset.repeaterMove === 'up' && row.previousElementSibling) {
                            rows.insertBefore(row, row.previousElementSibling);
                        } else if (move.dataset.repeaterMove === 'down' && row.nextElementSibling) {
                            rows.insertBefore(row.nextElementSibling, row);
                        }
                        reindex();
                    }
                });
            });

            // --- "Dodaj / zarządzaj plikami" → przełącz na zakładkę Pliki ---
            const gotoFiles = document.querySelector('[data-goto-files]');
            if (gotoFiles) {
                gotoFiles.addEventListener('click', function () {
                    const btn = document.querySelector('[data-ftab-btn="pliki"]');
                    if (btn) btn.click();
                });
            }

            // --- About section order (move up / down) ---------------------
            const orderList = document.getElementById('about-section-order-list');
            if (orderList) {
                const renumber = function () {
                    [...orderList.children].forEach(function (li, index) {
                        const input = li.querySelector('input[type="hidden"]');
                        if (input) input.value = index;
                    });
                };
                orderList.addEventListener('click', function (event) {
                    const button = event.target.closest('[data-move]');
                    if (!button) return;
                    const li = button.closest('li');
                    const sibling = button.dataset.move === 'up' ? li.previousElementSibling : li.nextElementSibling;
                    if (sibling) {
                        if (button.dataset.move === 'up') {
                            orderList.insertBefore(li, sibling);
                        } else {
                            orderList.insertBefore(sibling, li);
                        }
                        renumber();
                    }
                });
                renumber();
            }

            // --- Repeatable schedule rows ---------------------------------
            if (scheduleFields) {
                const rows = scheduleFields.querySelector('[data-schedule-rows]');
                const template = scheduleFields.querySelector('[data-schedule-template]');
                const addBtn = scheduleFields.querySelector('[data-schedule-add]');
                let nextIndex = rows ? rows.querySelectorAll('[data-schedule-row]').length : 0;

                const addRow = function () {
                    const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    rows.appendChild(wrapper.firstElementChild);
                };

                if (addBtn) addBtn.addEventListener('click', addRow);

                scheduleFields.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-schedule-remove]');
                    if (remove) {
                        const row = remove.closest('[data-schedule-row]');
                        if (row) row.remove();
                    }
                });

                if (rows && rows.querySelectorAll('[data-schedule-row]').length === 0) {
                    addRow();
                }

                // Highlight the "not published yet" box while its toggle is on.
                const pendingToggle = scheduleFields.querySelector('[data-schedule-pending-toggle]');
                const pendingBox = scheduleFields.querySelector('[data-schedule-pending-box]');
                if (pendingToggle && pendingBox) {
                    const syncPending = function () {
                        pendingBox.classList.toggle('border-amber-300', pendingToggle.checked);
                        pendingBox.classList.toggle('bg-amber-50', pendingToggle.checked);
                        pendingBox.classList.toggle('border-gray-200', !pendingToggle.checked);
                        pendingBox.classList.toggle('bg-gray-50', !pendingToggle.checked);
                    };
                    pendingToggle.addEventListener('change', syncPending);
                    syncPending();
                }
            }

            // --- Availability: reveal each message box only when active ----
            const disableToggle = document.querySelector('[data-disable-toggle]');
            const disableMessage = document.querySelector('[data-disable-message]');
            if (disableToggle && disableMessage) {
                const syncDisable = function () { disableMessage.classList.toggle('hidden', !disableToggle.checked); };
                disableToggle.addEventListener('change', syncDisable);
                syncDisable();
            }

            const wipMessage = document.querySelector('[data-wip-message]');
            const wipRadios = document.querySelectorAll('[data-wip-modes] input[name="wip_mode"]');
            if (wipMessage && wipRadios.length) {
                const syncWip = function () {
                    const selected = document.querySelector('[data-wip-modes] input[name="wip_mode"]:checked');
                    wipMessage.classList.toggle('hidden', !selected || selected.value === '');
                };
                wipRadios.forEach(function (r) { r.addEventListener('change', syncWip); });
                syncWip();
            }

            // --- Show the project-display choice only when a project is set -
            const projectSelect = document.querySelector('[data-project-select]');
            const displayWrap = document.querySelector('[data-project-display-wrap]');
            if (projectSelect && displayWrap) {
                projectSelect.addEventListener('change', function () {
                    displayWrap.classList.toggle('hidden', projectSelect.value === '');
                });
            }

            // --- Form tabs -------------------------------------------------
            const wrap = document.querySelector('[data-page-form-tabs]');
            if (!wrap) return;
            const buttons = Array.prototype.slice.call(document.querySelectorAll('[data-ftab-btn]'));
            const panels = Array.prototype.slice.call(document.querySelectorAll('[data-ftab-panel]'));

            const mainFormActions = document.querySelector('[data-main-form-actions]');
            const externalTabs = ['pliki', 'galeria', 'etr'];

            function activate(key) {
                buttons.forEach(function (b) {
                    const active = b.dataset.ftabBtn === key;
                    b.classList.toggle('bg-white', active);
                    b.classList.toggle('shadow-sm', active);
                    b.classList.toggle('text-brand', active);
                    b.classList.toggle('text-gray-500', !active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    p.classList.toggle('hidden', p.dataset.ftabPanel !== key);
                });
                if (mainFormActions) {
                    mainFormActions.classList.toggle('hidden', externalTabs.includes(key));
                }
                // A rich editor initialised inside a hidden panel can render
                // blank; toggling it re-lays it out once its tab is visible.
                const shown = panels.find(function (p) { return p.dataset.ftabPanel === key; });
                if (shown && window.tinymce) {
                    shown.querySelectorAll('textarea').forEach(function (ta) {
                        const ed = window.tinymce.get(ta.id);
                        if (ed) { ed.hide(); ed.show(); }
                    });
                }
                window.dispatchEvent(new Event('resize'));
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () { activate(btn.dataset.ftabBtn); });
            });

            // Flag tabs that contain validation errors and jump to the first one.
            let firstErrorKey = null;
            panels.forEach(function (p) {
                if (!p.querySelector('.text-red-600')) return;
                const key = p.dataset.ftabPanel;
                const btn = buttons.find(function (b) { return b.dataset.ftabBtn === key; });
                if (btn && !btn.querySelector('[data-ftab-error]')) {
                    const dot = document.createElement('span');
                    dot.setAttribute('data-ftab-error', '');
                    dot.className = 'ml-1.5 inline-block h-2 w-2 rounded-full bg-red-500 align-middle';
                    btn.appendChild(dot);
                }
                if (!firstErrorKey) firstErrorKey = key;
            });
            if (firstErrorKey) activate(firstErrorKey);
        })();
    </script>

    {{-- Lekki WYSIWYG (TinyMCE) na odpowiedziach FAQ — także dla nowo dodanych wierszy. --}}
    <script>
        (function () {
            const container = document.querySelector('[data-faq-fields]');
            if (!container) return;

            function initOne(ta) {
                if (ta.dataset.mceReady) return;
                ta.dataset.mceReady = '1';
                window.tinymce.init({
                    target: ta,
                    license_key: 'gpl',
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    convert_urls: false,
                    height: 200,
                    plugins: 'link lists autolink',
                    toolbar: 'bold italic | bullist numlist | link unlink | removeformat',
                    link_default_target: '_blank',
                    link_assume_external_targets: true,
                    setup: function (ed) { ed.on('change keyup', function () { ed.save(); }); },
                });
            }

            function initAll() {
                container.querySelectorAll('textarea[data-faq-answer]').forEach(initOne);
            }

            function withTiny(cb) {
                if (window.tinymce) return cb();
                window.__tinymceInitQueue = window.__tinymceInitQueue || [];
                window.__tinymceInitQueue.push(cb);
                if (!window.__tinymceLoading) {
                    window.__tinymceLoading = true;
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
                    s.referrerPolicy = 'origin';
                    s.onload = function () { (window.__tinymceInitQueue || []).forEach(function (f) { f(); }); window.__tinymceInitQueue = []; };
                    document.head.appendChild(s);
                }
            }

            withTiny(function () {
                initAll();
                const rows = container.querySelector('[data-repeater-rows]');
                if (rows && window.MutationObserver) {
                    new MutationObserver(initAll).observe(rows, { childList: true });
                }
            });

            // Wstawianie linku do podstrony w serwisie w aktywne pole odpowiedzi.
            const pageLink = container.querySelector('[data-faq-page-link]');
            if (pageLink) {
                pageLink.addEventListener('change', function () {
                    var url = this.value;
                    var title = this.selectedOptions[0] ? this.selectedOptions[0].dataset.title : url;
                    this.selectedIndex = 0;
                    if (!url || !window.tinymce) return;

                    var ed = window.tinymce.activeEditor;
                    if (ed && ed.targetElm && ed.targetElm.matches && ed.targetElm.matches('textarea[data-faq-answer]')) {
                        ed.insertContent('<a href="' + url + '">' + title + '</a>');
                        ed.save();
                    } else {
                        alert('Najpierw kliknij w treść odpowiedzi, w której chcesz wstawić link.');
                    }
                });
            }

            // Przepisz treść edytorów do textarea tuż przed wysłaniem formularza.
            const form = container.closest('form');
            if (form) form.addEventListener('submit', function () { if (window.tinymce) window.tinymce.triggerSave(); });
        })();

    </script>
@endsection
