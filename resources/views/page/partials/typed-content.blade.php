    @if ($page->wipIsNotice())
        <div class="px-4 pt-8">
            @include('partials.page-wip-notice', ['message' => $page->wipMessage()])
        </div>
    @endif

    @if ($page->is_archived)
        <div class="mx-auto max-w-4xl px-4 pt-8">
            @include('partials.archival-notice', ['date' => $page->created_at])
        </div>
    @endif

    @if ($page->isEvent())
        <section class="mx-auto max-w-4xl px-4 py-12">
            <div class="mb-5 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Wydarzenie
                </span>
                @if ($page->eventModeLabel())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-sm font-bold text-muted">
                        <i class="fa-solid {{ $page->event_mode === 'online' ? 'fa-video' : 'fa-location-dot' }}" aria-hidden="true"></i> {{ $page->eventModeLabel() }}
                    </span>
                @endif
            </div>

            <h1 class="mb-8 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>

            <div class="grid gap-10 md:grid-cols-[1fr_300px]">
                <div class="min-w-0">
                    @include('partials.page-content-image')

                    @if ($page->content)
                        <div class="prose max-w-none text-ink">{!! $page->content !!}</div>
                    @endif

                    @if ($page->event_how_to_join)
                        <div class="mt-8 rounded-lg border border-gray-200 p-6">
                            <h2 class="mb-3 flex items-center gap-2 text-xl font-bold text-ink">
                                <i class="fa-solid fa-door-open text-brand" aria-hidden="true"></i> Jak dołączyć
                            </h2>
                            <div class="prose max-w-none text-ink">{!! nl2br(e($page->event_how_to_join)) !!}</div>
                        </div>
                    @endif

                    @include('partials.page-gallery', ['page' => $page])

        @include('partials.attachments-list', ['attachments' => $page->attachments])
                </div>

                @if ($page->event_when || $page->event_location || $page->event_registration_url)
                    <aside aria-label="Szczegóły wydarzenia">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                            <p class="mb-4 text-xs font-bold uppercase tracking-wide text-muted">Gdzie, kiedy</p>
                            <ul class="space-y-4 text-sm">
                                @if ($page->event_when)
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold uppercase tracking-wide text-muted">Termin</span>
                                            <span class="font-medium text-ink">{{ $page->event_when }}</span>
                                        </span>
                                    </li>
                                @endif
                                @if ($page->event_location)
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true"><i class="fa-solid {{ $page->event_mode === 'online' ? 'fa-video' : 'fa-location-dot' }}"></i></span>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold uppercase tracking-wide text-muted">{{ $page->event_mode === 'online' ? 'Platforma' : 'Miejsce' }}</span>
                                            <span class="font-medium text-ink">{{ $page->event_location }}</span>
                                        </span>
                                    </li>
                                @endif
                            </ul>

                            @if ($page->event_registration_url)
                                <a href="{{ $page->event_registration_url }}" target="_blank" rel="noopener"
                                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded bg-brand px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-dark">
                                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Zarejestruj się
                                </a>
                            @endif
                        </div>
                    </aside>
                @endif
            </div>
        </section>
    @elseif ($page->isSchedule())
    @php
        $menuSiblings = $page->menuSiblings();
        $scheduleItems = collect($page->schedule_items ?? []);
        $formatDate = function ($d) {
            if (! $d) {
                return null;
            }
            try {
                return \Illuminate\Support\Carbon::parse($d)->format('d.m.Y');
            } catch (\Throwable $e) {
                return $d;
            }
        };
    @endphp

    <section class="mx-auto max-w-5xl px-4 py-12">
        <div class="grid gap-10 {{ $menuSiblings->isNotEmpty() ? 'md:grid-cols-[1fr_220px]' : '' }}">
            <div class="min-w-0">
                <div class="mb-5">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Harmonogram
                    </span>
                </div>

                <h1 class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>

                @include('partials.page-content-image')

                @if ($page->content)
                    <div class="prose max-w-none text-ink">{!! $page->content !!}</div>
                @endif

                @include('partials.schedule', ['page' => $page, 'showHeading' => true])

                @include('partials.page-gallery', ['page' => $page])

        @include('partials.attachments-list', ['attachments' => $page->attachments])
            </div>

            @if ($menuSiblings->isNotEmpty())
                @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
            @endif
        </div>
    </section>
    @elseif ($page->isAbout())
    @php
        $aboutStats = collect($page->about_stats ?? []);
        $aboutTimeline = collect($page->about_timeline ?? []);
        $aboutValues = collect($page->about_values ?? []);
        $aboutTeam = $page->children()->where('type', 'about_person')->where('is_published', true)->orderBy('order')->orderBy('title')->get();
        $aboutImages = $page->images->filter(fn ($img) => $img->image_url)->values();
        // Pierwsze 2–3 zdjęcia jako kolaż obok wstępu; reszta trafia do galerii.
        $introPhotos = $aboutImages->take(3)->values();
        $galleryPhotos = $aboutImages->slice(3)->values();

        $aboutPressItems = collect($page->about_press ?? [])->filter(fn ($p) => ! empty($p['url']) || ! empty($p['title']));
        $aboutSectionLabels = ['intro' => 'O nas', 'stats' => 'W liczbach', 'values' => 'Wartości', 'timeline' => 'Historia', 'team' => 'Zespół', 'gallery' => 'Galeria', 'partners' => 'Partnerzy', 'press' => 'Media', 'documents' => 'Dokumenty', 'faq' => 'FAQ'];
        $activeAboutSections = [];
        foreach ($page->orderedAboutSections() as $_s) {
            $has = match ($_s) {
                'intro'     => filled($page->about_intro) || $page->content || $introPhotos->isNotEmpty(),
                'stats'     => $aboutStats->isNotEmpty(),
                'values'    => $aboutValues->isNotEmpty(),
                'timeline'  => $aboutTimeline->isNotEmpty(),
                'team'      => $aboutTeam->isNotEmpty(),
                'gallery'   => $galleryPhotos->isNotEmpty(),
                'partners'  => ! empty($page->about_partner_ids),
                'documents' => $page->attachments->isNotEmpty() || filled($page->about_documents_intro),
                'press'     => $aboutPressItems->isNotEmpty() || filled($page->about_press_intro),
                'faq'       => (bool) $page->about_faq_visible,
                default     => false,
            };
            if ($has) $activeAboutSections[$_s] = $aboutSectionLabels[$_s] ?? $_s;
        }
    @endphp

    <article>
        {{-- Hero: tytuł + motto na gradiencie marki --}}
        <header class="relative overflow-hidden bg-gradient-to-br from-brand to-brand-dark px-4 py-10 text-center text-white md:py-16">
            <i class="fa-solid fa-quote-right pointer-events-none absolute -right-4 -top-4 text-[9rem] text-white/10 md:text-[14rem]" aria-hidden="true"></i>
            <div class="relative mx-auto max-w-4xl">
                <h1 class="text-3xl font-bold leading-tight md:text-5xl">{{ $page->title }}</h1>
                @if ($page->about_motto)
                    <p class="mx-auto mt-6 max-w-3xl text-xl font-medium leading-relaxed text-white/90 md:text-2xl">
                        „{{ $page->about_motto }}"
                    </p>
                    @if ($page->about_motto_author)
                        <p class="mt-4 text-sm font-bold uppercase tracking-widest text-white/70">— {{ $page->about_motto_author }}</p>
                    @endif
                @endif
            </div>
        </header>

        @if (count($activeAboutSections) > 1)
        <nav aria-label="Sekcje na stronie" class="sticky top-0 z-20 border-b border-gray-200 bg-white/95 backdrop-blur-sm">
            <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 py-2" style="scrollbar-width:none">
                @foreach ($activeAboutSections as $navKey => $navLabel)
                    <a href="#sekcja-{{ $navKey }}"
                       class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand
                              {{ $navKey === 'team' ? 'bg-brand text-white hover:bg-brand-dark' : 'text-muted hover:bg-gray-100 hover:text-ink' }}">
                        {{ $navLabel }}
                    </a>
                @endforeach
            </div>
        </nav>
        @endif

        @foreach ($page->orderedAboutSections() as $aboutSection)
        @switch($aboutSection)

        @case('intro')
        {{-- Wstęp (pole) + kolaż 2–3 zdjęć obok --}}
        @if (filled($page->about_intro) || $page->content || $introPhotos->isNotEmpty())
            <section id="sekcja-intro" class="mx-auto max-w-6xl px-4 py-16">
                <div class="grid items-center gap-10 {{ $introPhotos->isNotEmpty() ? 'lg:grid-cols-2' : '' }}">
                    <div>
                        @if (filled($page->about_intro))
                            <div class="prose prose-lg max-w-none text-ink">{!! nl2br(e($page->about_intro)) !!}</div>
                        @elseif ($page->content)
                            <div class="prose prose-lg max-w-none text-ink">{!! $page->content !!}</div>
                        @endif
                    </div>

                    @if ($introPhotos->isNotEmpty())
                        <div class="grid grid-cols-2 gap-3" data-lightbox>
                            @foreach ($introPhotos as $photo)
                                @php $spanFull = $introPhotos->count() === 1 || ($introPhotos->count() === 3 && $loop->first); @endphp
                                <figure class="overflow-hidden rounded-2xl shadow-sm {{ $spanFull ? 'col-span-2' : '' }}">
                                    <img src="{{ $photo->image_url }}" alt="{{ $photo->alt }}" loading="lazy"
                                        class="w-full object-cover {{ $spanFull ? 'h-64' : 'h-48' }}">
                                    @if ($photo->caption)
                                        <figcaption class="bg-gray-50 px-3 py-1.5 text-xs text-muted">{{ $photo->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif
        @break

        @case('stats')
        {{-- Statystyki: pas w gradiencie marki, dzielniki, animacja liczenia --}}
        @if ($aboutStats->isNotEmpty())
            <section id="sekcja-stats" class="relative overflow-hidden bg-gradient-to-br from-brand to-brand-dark px-4 py-12 text-white" aria-label="W liczbach" data-countup>
                <i class="fa-solid fa-arrow-trend-up pointer-events-none absolute -bottom-10 -right-2 text-[11rem] text-white/10" aria-hidden="true"></i>
                <dl class="relative mx-auto flex max-w-4xl items-center justify-center">
                    @foreach ($aboutStats as $stat)
                        <div class="flex flex-1 flex-col items-center px-6 text-center {{ !$loop->first ? 'border-l border-white/20' : '' }}">
                            <dt class="sr-only">{{ $stat['label'] ?? '' }}</dt>
                            <dd class="flex flex-col items-center">
                                <span class="block text-3xl font-extrabold leading-none tracking-tight md:text-4xl" data-countup-value>{{ $stat['value'] ?? '' }}</span>
                                <span class="mx-auto mt-3 block h-px w-8 rounded-full bg-white/40" aria-hidden="true"></span>
                                <span class="mt-3 block text-xs font-medium uppercase tracking-wider text-white/75">{{ $stat['label'] ?? '' }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif
        @break

        @case('gallery')
        {{-- Galeria: pozostałe zdjęcia w układzie bento (pierwsze duże) --}}
        @if ($galleryPhotos->isNotEmpty())
            <section id="sekcja-gallery" class="mx-auto max-w-6xl px-4 py-16">
                <h2 class="mb-8 text-center text-2xl font-bold text-ink md:text-3xl">Galeria</h2>
                <div class="grid auto-rows-[160px] grid-cols-2 gap-3 md:auto-rows-[220px] md:grid-cols-4" data-lightbox>
                    @foreach ($galleryPhotos as $image)
                        <figure class="group relative overflow-hidden rounded-2xl {{ $loop->first ? 'col-span-2 row-span-2' : '' }}">
                            <img src="{{ $image->image_url }}" alt="{{ $image->alt }}" loading="lazy"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @if ($image->caption)
                                <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 text-sm font-medium text-white">
                                    {{ $image->caption }}
                                </figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif
        @break

        @case('values')
        {{-- Wartości: poziome wiersze z okrągłą ikoną --}}
        @if ($aboutValues->isNotEmpty())
            <section id="sekcja-values" class="bg-gray-50 px-4 py-16" aria-label="Nasze wartości">
                <div class="mx-auto max-w-4xl">
                    <h2 class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Nasze wartości</h2>
                    <div class="grid gap-x-12 gap-y-9 sm:grid-cols-2">
                        @foreach ($aboutValues as $value)
                            <div class="flex items-start gap-4">
                                @if (! empty($value['icon']))
                                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-brand text-xl text-white" aria-hidden="true">
                                        <i class="{{ $value['icon'] }}"></i>
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    @if (! empty($value['title']))
                                        <h3 class="text-lg font-bold text-ink">{{ $value['title'] }}</h3>
                                    @endif
                                    @if (! empty($value['text']))
                                        <p class="mt-1 text-sm leading-relaxed text-muted">{{ $value['text'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
        @break

        @case('timeline')
        {{-- Oś czasu --}}
        @if ($aboutTimeline->isNotEmpty())
            <section id="sekcja-timeline" class="mx-auto max-w-3xl px-4 py-16" aria-label="Nasza historia">
                <h2 class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Nasza historia</h2>
                <ol class="relative ml-3 space-y-8 border-l-2 border-brand/30 pl-8">
                    @foreach ($aboutTimeline as $entry)
                        @php
                            $tlColor = \App\Support\Color::isValid($entry['color'] ?? null) ? $entry['color'] : null;
                            $tlYearColor = $tlColor ? $siteSettings->contrastSafeColor($tlColor) : null;
                        @endphp
                        <li class="relative">
                            <span class="absolute -left-[41px] top-1 flex h-5 w-5 items-center justify-center rounded-full border-4 border-white {{ $tlColor ? '' : 'bg-brand' }}" aria-hidden="true" @if ($tlColor) style="background-color: {{ $tlColor }};" @endif></span>
                            @if (! empty($entry['year']))
                                <p class="text-sm font-bold uppercase tracking-widest {{ $tlYearColor ? '' : 'text-brand' }}" @if ($tlYearColor) style="color: {{ $tlYearColor }};" @endif>{{ $entry['year'] }}</p>
                            @endif
                            @if (! empty($entry['text']))
                                <p class="mt-1 leading-relaxed text-ink">{{ $entry['text'] }}</p>
                            @endif
                            @php
                                $tlLinks = collect([
                                    ['url' => $entry['url'] ?? null, 'label' => $entry['label'] ?? null],
                                    ['url' => $entry['url2'] ?? null, 'label' => $entry['label2'] ?? null],
                                    ['url' => $entry['url3'] ?? null, 'label' => $entry['label3'] ?? null],
                                ])->filter(fn ($l) => ! empty($l['url']));
                            @endphp
                            @if ($tlLinks->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                                    @foreach ($tlLinks as $link)
                                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-1.5 text-sm font-bold text-brand hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                            {{ $link['label'] ?: 'Zobacz więcej' }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
        @break

        @case('team')
        {{-- Zespół — kolorowe karty --}}
        @if ($aboutTeam->isNotEmpty())
            <section id="sekcja-team" class="bg-gray-50 px-4 py-16" aria-label="Nasz zespół">
                <div class="mx-auto max-w-5xl">
                    <h2 class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Nasz zespół</h2>
                    @include('partials.team', ['members' => $aboutTeam])
                </div>
            </section>
        @endif
        @break

        @case('partners')
        {{-- Nasi partnerzy — wybrane logotypy --}}
        @php $aboutPartners = $page->aboutPartners(); @endphp
        @if ($aboutPartners->isNotEmpty())
            <section id="sekcja-partners" class="bg-gray-50 px-4 py-16" aria-label="Nasi partnerzy">
                <div class="mx-auto max-w-5xl text-center">
                    <h2 class="mb-2 text-2xl font-bold text-ink md:text-3xl">Nasi partnerzy — wspierają nas</h2>
                    <p class="mb-10 text-muted">Dziękujemy organizacjom i instytucjom, które nas wspierają.</p>
                    <ul class="flex flex-wrap items-center justify-center gap-x-12 gap-y-8">
                        @foreach ($aboutPartners as $partner)
                            <li>
                                @php
                                    $logo = $partner->logo_url
                                        ? '<img src="'.e($partner->logo_url).'" alt="'.e($partner->name).'" loading="lazy" class="h-16 w-auto max-w-[180px] object-contain">'
                                        : '<span class="text-lg font-bold text-ink">'.e($partner->name).'</span>';
                                @endphp
                                @if ($partner->url)
                                    <a href="{{ $partner->url }}" target="_blank" rel="noopener"
                                        class="block transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                        title="{{ $partner->name }}">{!! $logo !!}</a>
                                @else
                                    {!! $logo !!}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
        @break

        @case('documents')
        {{-- Dokumenty i sprawozdania: wybrane pliki + odnośnik do BIP po resztę --}}
        @if ($page->attachments->isNotEmpty() || filled($page->about_documents_intro))
            <section id="sekcja-documents" class="mx-auto max-w-6xl px-4 py-16" aria-label="Dokumenty i sprawozdania">
                <h2 class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Dokumenty i sprawozdania</h2>
                <div class="grid gap-10 lg:grid-cols-2">
                    <div class="prose max-w-none text-ink">
                        @if (filled($page->about_documents_intro))
                            {!! nl2br(e($page->about_documents_intro)) !!}
                        @endif
                        <p class="mt-4 text-sm text-muted">Pozostałe dokumenty publikujemy w <a href="{{ route('bip') }}" class="font-bold text-brand hover:text-brand-dark">Biuletynie Informacji Publicznej</a>.</p>
                    </div>
                    <div>
                        @if ($page->attachments->isNotEmpty())
                            <ul class="divide-y divide-gray-100">
                                @foreach ($page->attachments as $doc)
                                    <li class="flex items-center justify-between gap-4 py-4">
                                        <span class="min-w-0 font-medium text-ink">{{ $doc->label }}</span>
                                        <a href="{{ $doc->file_url }}" download aria-label="Pobierz: {{ $doc->label }}"
                                            class="inline-flex flex-none items-center gap-2 font-bold text-brand hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            Pobierz <i class="fa-solid fa-download" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-muted">Nie dodano jeszcze dokumentów. Wgraj je w zakładce „Pliki do pobrania".</p>
                        @endif
                        <a href="{{ route('bip') }}"
                            class="mt-6 flex items-center justify-center gap-2 rounded-full border-2 border-ink px-6 py-3 font-bold text-ink transition hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            Zobacz wszystkie <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </section>
        @endif
        @break

        @case('press')
        {{-- Piszą o nas — wzmianki prasowe z obrazkiem (og:image) --}}
        @php $aboutPress = collect($page->about_press ?? [])->filter(fn ($p) => ! empty($p['url']) || ! empty($p['title'])); @endphp
        @if ($aboutPress->isNotEmpty() || filled($page->about_press_intro))
            <section id="sekcja-press" class="bg-gray-50 px-4 py-16" aria-label="My w mediach">
                <div class="mx-auto max-w-5xl">
                    <h2 class="mb-3 text-center text-2xl font-bold text-ink md:text-3xl">My w mediach</h2>
                    @if (filled($page->about_press_intro))
                        <p class="mx-auto mb-10 max-w-2xl text-center text-muted">{{ $page->about_press_intro }}</p>
                    @endif
                    @if ($aboutPress->isNotEmpty())
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($aboutPress as $item)
                                <a href="{{ $item['url'] ?: '#' }}" @if (! empty($item['url'])) target="_blank" rel="noopener" @endif
                                    class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    @if (! empty($item['image']))
                                        <img src="{{ $item['image'] }}" alt="" loading="lazy" class="h-40 w-full object-cover">
                                    @else
                                        <span class="flex h-40 w-full items-center justify-center bg-brand-light text-3xl text-brand" aria-hidden="true"><i class="fa-solid fa-newspaper"></i></span>
                                    @endif
                                    <div class="flex flex-1 flex-col p-5">
                                        @if (! empty($item['source']))
                                            <span class="mb-1 text-xs font-bold uppercase tracking-widest text-brand">{{ $item['source'] }}</span>
                                        @endif
                                        <span class="font-bold text-ink group-hover:text-brand">{{ $item['title'] ?: $item['url'] }}</span>
                                        <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-brand">Czytaj <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif
        @break

        @case('faq')
        {{-- Odnośnik do FAQ — zawsze prowadzi do /faq --}}
        @if ($page->about_faq_visible)
            <section id="sekcja-faq" class="mx-auto max-w-4xl px-4 py-12">
                <div class="flex flex-col items-center gap-4 rounded-2xl bg-brand-light p-8 text-center sm:flex-row sm:justify-between sm:text-left">
                    <div>
                        <h2 class="flex items-center gap-2 text-xl font-bold text-ink">
                            <i class="fa-solid fa-circle-question text-brand" aria-hidden="true"></i> Masz pytania?
                        </h2>
                        <p class="mt-1 text-muted">Odpowiedzi na najczęstsze pytania zebraliśmy w jednym miejscu.</p>
                    </div>
                    <a href="{{ url('/faq') }}"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-brand px-6 py-3 font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        Najczęściej zadawane pytania <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </section>
        @endif
        @break

        @endswitch
        @endforeach
    </article>

    <script>
        (function () {
            var band = document.querySelector('[data-countup]');
            if (!band || !('IntersectionObserver' in window)) return;
            var done = false;

            function run() {
                if (done) return;
                done = true;
                band.querySelectorAll('[data-countup-value]').forEach(function (el) {
                    var raw = el.textContent.trim();
                    var m = raw.match(/\d[\d\s.,]*/);
                    if (!m) return;
                    var numStr = m[0].replace(/\s/g, '').replace(',', '.');
                    var target = parseFloat(numStr);
                    if (isNaN(target)) return;
                    var isInt = numStr.indexOf('.') === -1;
                    var prefix = raw.slice(0, m.index);
                    var suffix = raw.slice(m.index + m[0].length);
                    var start = null;
                    var duration = 1300;
                    function step(ts) {
                        if (!start) start = ts;
                        var p = Math.min((ts - start) / duration, 1);
                        var eased = 1 - Math.pow(1 - p, 3);
                        var cur = target * eased;
                        el.textContent = prefix + (isInt ? Math.round(cur).toLocaleString('pl-PL') : cur.toFixed(1)) + suffix;
                        if (p < 1) requestAnimationFrame(step);
                        else el.textContent = raw;
                    }
                    requestAnimationFrame(step);
                });
            }

            new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { if (e.isIntersecting) run(); });
            }, { threshold: 0.3 }).observe(band);
        })();
    </script>

    @elseif ($page->isFaq())
    <section class="mx-auto max-w-3xl px-4 py-12">
        <div class="mb-5">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
                <i class="fa-solid fa-circle-question" aria-hidden="true"></i> Najczęściej zadawane pytania
            </span>
        </div>

        <h1 class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>

        @include('partials.page-content-image')

        @if ($page->content)
            <div class="prose mb-6 max-w-none text-ink">{!! $page->content !!}</div>
        @endif

        @include('partials.faq', ['page' => $page, 'faqLdJson' => true])

        @include('partials.page-gallery', ['page' => $page])

        @include('partials.attachments-list', ['attachments' => $page->attachments])
    </section>
    @elseif ($page->isBipMove())
    @php $bipUrl = $page->bip_move_url ?: $siteSettings->bip_url; @endphp

    <section class="mx-auto max-w-2xl px-4 py-16">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm md:p-12">
            <img src="{{ asset('img/bip-logo.svg') }}" alt="Logo Biuletynu Informacji Publicznej (BIP)"
                class="mx-auto mb-6 h-20 w-auto md:h-24">

            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-brand">Biuletyn Informacji Publicznej</p>
            <h1 class="mb-4 text-2xl font-bold text-ink md:text-3xl">{{ $page->title }}</h1>

            @if ($page->content)
                <div class="prose mx-auto mb-6 max-w-none text-ink">{!! $page->content !!}</div>
            @endif

            <p class="mb-6 leading-relaxed text-muted">
                Dokumenty i sprawozdania naszej fundacji publikujemy w <strong class="font-bold text-ink">Biuletynie
                Informacji Publicznej (BIP)</strong> — oficjalnym, uporządkowanym miejscu gromadzącym najważniejsze
                informacje o naszej działalności. Dzięki temu na tej stronie możemy skupić się na przedstawianiu tego,
                co i dla kogo robimy. Nasz Biuletyn wciąż się rozwija — serdecznie zachęcamy do regularnego odwiedzania
                i dziękujemy za wyrozumiałość.
            </p>

            @if (filled($page->bip_move_note))
                <p class="mx-auto mb-6 max-w-md rounded-lg bg-gray-50 px-4 py-3 text-sm leading-relaxed text-muted">{!! nl2br(e($page->bip_move_note)) !!}</p>
            @endif

            @if ($bipUrl)
                <a href="{{ $bipUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-6 py-3 font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Przejdź do BIP
                </a>
            @else
                <p class="text-sm text-muted">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Adres BIP zostanie udostępniony wkrótce.
                </p>
            @endif
        </div>
    </section>
    @elseif ($page->isInternalHub())
    @php $hubLinks = collect($page->hub_links ?? [])->filter(fn ($l) => filled($l['label'] ?? null) && filled($l['url'] ?? null)); @endphp

    <section class="relative overflow-hidden {{ $page->hub_hero ? 'text-white' : 'bg-brand text-white' }}"
        @if ($page->hub_hero) style="background-image: linear-gradient(0deg, rgba(0,0,0,.65), rgba(0,0,0,.35)), url('{{ $page->hub_hero }}'); background-size: cover; background-position: center;" @endif>
        <div class="mx-auto max-w-5xl px-4 py-20 text-center md:py-28">
            <span class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-sm font-bold backdrop-blur">
                <i class="fa-solid fa-users-gear" aria-hidden="true"></i> Strefa współpracownika
            </span>
            <h1 class="text-3xl font-bold md:text-4xl">{{ $page->title }}</h1>
            @if (filled($page->hub_intro))
                <p class="mx-auto mt-3 max-w-2xl text-white/90">{{ $page->hub_intro }}</p>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 py-12">
        @isset($szoKomunikaty)
            @include('partials.strefa-notice', ['szoPanelUrl' => $szoPanelUrl ?? null])
        @endisset

        @include('partials.strefa-tozsamosc')

        @if ($page->content)
            <div class="prose mx-auto mb-10 max-w-none text-ink">{!! $page->content !!}</div>
        @endif

        @isset($szoKomunikaty)
            @include('partials.strefa-komunikaty', ['szoKomunikaty' => $szoKomunikaty])
        @endisset

        @if ($hubLinks->isNotEmpty())
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($hubLinks as $link)
                    <li>
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                            class="group flex h-full items-start gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-light text-lg text-brand" aria-hidden="true">
                                <i class="{{ filled($link['icon'] ?? null) ? $link['icon'] : 'fa-solid fa-arrow-up-right-from-square' }}"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block font-bold text-ink group-hover:text-brand">{{ $link['label'] }}</span>
                                @if (filled($link['description'] ?? null))
                                    <span class="mt-0.5 block text-sm text-muted">{{ $link['description'] }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-center text-muted">Brak dodanych linków. Dodaj je w panelu (edycja strony → Strefa współpracownika).</p>
        @endif
    </section>
    @elseif ($page->isLinksHub())
    @php
        $hubLinks = collect($page->hub_links ?? [])->filter(fn ($l) => filled($l['label'] ?? null) && filled($l['url'] ?? null))->values();
        $metroGradientMap = [
            'blue'   => 'linear-gradient(135deg, #1a56a4 0%, #2563eb 100%)',
            'dark'   => 'linear-gradient(135deg, #1f2937 0%, #374151 100%)',
            'green'  => 'linear-gradient(135deg, #166534 0%, #16a34a 100%)',
            'purple' => 'linear-gradient(135deg, #581c87 0%, #7e22ce 100%)',
            'orange' => 'linear-gradient(135deg, #c2410c 0%, #f97316 100%)',
            'red'    => 'linear-gradient(135deg, #991b1b 0%, #ef4444 100%)',
        ];
        $metroGradientFallback = array_values($metroGradientMap);
    @endphp

    {{-- Hero --}}
    <section class="bg-brand text-white"
        @if ($page->hub_hero) style="background-image: linear-gradient(0deg, rgba(0,0,0,.55), rgba(0,0,0,.35)), url('{{ $page->hub_hero }}'); background-size: cover; background-position: center;" @endif>
        <div class="mx-auto max-w-5xl px-4 py-20 text-center md:py-28">
            <h1 class="text-4xl font-extrabold leading-tight tracking-tight md:text-5xl">{{ $page->title }}</h1>
            @if (filled($page->hub_intro))
                <p class="mx-auto mt-5 max-w-2xl text-lg text-white/85">{{ $page->hub_intro }}</p>
            @endif
        </div>
    </section>

    {{-- Metro kafelki --}}
    <section class="mx-auto max-w-5xl px-4 py-14" aria-label="{{ $page->title }}">
        @if ($page->content)
            <div class="prose mx-auto mb-10 max-w-none text-ink">{!! $page->content !!}</div>
        @endif

        @if ($hubLinks->isNotEmpty())
            <ul class="grid gap-5 {{ $hubLinks->count() === 2 ? 'sm:grid-cols-2' : ($hubLinks->count() === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4') }}" role="list">
                @foreach ($hubLinks as $i => $link)
                    @php
                        $colorKey = $link['color'] ?? null;
                        $grad = isset($colorKey) && isset($metroGradientMap[$colorKey])
                            ? $metroGradientMap[$colorKey]
                            : $metroGradientFallback[$i % count($metroGradientFallback)];
                        $ctaLabel = filled($link['cta_label'] ?? null) ? $link['cta_label'] : 'Dowiedz się więcej';
                    @endphp
                    <li>
                        <a href="{{ $link['url'] }}"
                           class="group relative flex min-h-52 flex-col justify-end overflow-hidden rounded-2xl p-8 text-white shadow-md transition hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                           style="background: {{ $grad }}">
                            <span class="relative z-10">
                                @if (filled($link['icon'] ?? null))
                                    <span class="mb-3 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur" aria-hidden="true">
                                        <i class="{{ $link['icon'] }} text-xl text-white"></i>
                                    </span>
                                @endif
                                <span class="block text-2xl font-extrabold leading-tight">{{ $link['label'] }}</span>
                                @if (filled($link['description'] ?? null))
                                    <span class="mt-1 block text-sm text-white/80">{{ $link['description'] }}</span>
                                @endif
                                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-white/90 transition group-hover:gap-3">
                                    {{ $ctaLabel }} <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
    @elseif ($page->isCooperation())
    @php
        $cd      = $page->cooperation_data ?? [];
        $cdStats = collect($cd['stats'] ?? [])->filter(fn($s) => filled($s['value'] ?? null) && filled($s['label'] ?? null));
        $cdSectors = $cd['sectors'] ?? [
            ['icon' => 'fa-solid fa-building',    'color' => 'blue',   'title' => 'Biznes',               'text' => '', 'tag1' => 'CSR / ESG', 'tag2' => 'Wolontariat pracowniczy', 'tag3' => 'Wizerunek marki'],
            ['icon' => 'fa-solid fa-landmark',    'color' => 'green',  'title' => 'Samorząd i instytucje','text' => '', 'tag1' => 'Dialog obywatelski', 'tag2' => 'Polityka społeczna', 'tag3' => 'Aktywizacja lokalna'],
            ['icon' => 'fa-solid fa-flask',       'color' => 'purple', 'title' => 'Nauka i edukacja',     'text' => '', 'tag1' => 'Innowacje społeczne', 'tag2' => 'Praktyki i badania', 'tag3' => 'Transfer wiedzy'],
            ['icon' => 'fa-solid fa-people-group','color' => 'orange', 'title' => 'Inne NGO',             'text' => '', 'tag1' => 'Koalicje i synergia', 'tag2' => 'Wymiana zasobów', 'tag3' => 'Wspólny advocacy'],
        ];
        $cdForms = $cd['forms'] ?? [
            ['icon' => 'fa-solid fa-star',                  'title' => 'Partnerstwo strategiczne',  'text' => ''],
            ['icon' => 'fa-solid fa-circle-dollar-to-slot', 'title' => 'Sponsoring',               'text' => ''],
            ['icon' => 'fa-solid fa-user-gear',             'title' => 'Wolontariat kompetencyjny','text' => ''],
            ['icon' => 'fa-solid fa-sitemap',               'title' => 'Koalicje i sieci',         'text' => ''],
        ];
        $colorMap = [
            'blue'   => ['bg' => 'bg-blue-50',    'text' => 'text-blue-600',   'pill' => 'bg-blue-50 text-blue-700',    'border' => 'border-blue-200'],
            'green'  => ['bg' => 'bg-green-50',   'text' => 'text-green-600',  'pill' => 'bg-green-50 text-green-700',  'border' => 'border-green-200'],
            'purple' => ['bg' => 'bg-purple-50',  'text' => 'text-purple-600', 'pill' => 'bg-purple-50 text-purple-700','border' => 'border-purple-200'],
            'orange' => ['bg' => 'bg-orange-50',  'text' => 'text-orange-600', 'pill' => 'bg-orange-50 text-orange-700','border' => 'border-orange-200'],
            'brand'  => ['bg' => 'bg-brand-light','text' => 'text-brand',      'pill' => 'bg-brand-light text-brand',   'border' => 'border-brand/20'],
        ];
        $partners = \App\Models\Partner::orderBy('order')->orderBy('name')->with('media')->get();
    @endphp

    {{-- ══ HERO ══ --}}
    <section class="relative overflow-hidden bg-brand text-white">
        {{-- Dekoracja tła --}}
        <span class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/5 blur-3xl" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-white/5 blur-2xl" aria-hidden="true"></span>

        <div class="relative mx-auto max-w-5xl px-4 py-20 md:py-28">
            <div class="mx-auto max-w-3xl text-center">
                @if (filled($cd['hero_badge'] ?? null))
                    <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-bold backdrop-blur-sm">
                        <i class="fa-solid fa-handshake text-white/70" aria-hidden="true"></i>
                        {{ $cd['hero_badge'] }}
                    </span>
                @endif
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight md:text-5xl">{{ $page->title }}</h1>
                @if (filled($cd['hero_subtitle'] ?? null))
                    <p class="mt-5 text-lg leading-relaxed text-white/80">{{ $cd['hero_subtitle'] }}</p>
                @endif
                <div class="mt-10 flex flex-wrap justify-center gap-3">
                    @if (filled($cd['hero_cta1_label'] ?? null))
                        <a href="{{ $cd['hero_cta1_url'] ?? route('contact.show') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 font-bold text-brand shadow-md transition hover:bg-brand-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                            {{ $cd['hero_cta1_label'] }}
                        </a>
                    @endif
                    @if (filled($cd['hero_cta2_label'] ?? null))
                        <a href="#formy-wspolpracy"
                           class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-7 py-3.5 font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <i class="fa-solid fa-chevron-down text-sm" aria-hidden="true"></i>
                            {{ $cd['hero_cta2_label'] }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

    </section>

    {{-- ══ STATYSTYKI ══ --}}
    @if ($cdStats->isNotEmpty())
    <section class="bg-gray-50" aria-label="Kluczowe liczby">
        <ul class="mx-auto flex max-w-5xl flex-wrap divide-x divide-gray-200" role="list">
            @foreach ($cdStats as $stat)
            <li class="flex flex-1 flex-col items-center justify-center px-8 py-10 text-center" style="min-width:140px">
                <span class="text-3xl font-extrabold text-brand md:text-4xl">{{ $stat['value'] }}</span>
                <span class="mt-1.5 text-sm font-medium text-muted">{{ $stat['label'] }}</span>
            </li>
            @endforeach
        </ul>
    </section>
    @endif

    {{-- ══ SEKTORY ══ --}}
    @if (!empty($cdSectors))
    <section id="dla-firm" class="bg-white py-16 md:py-24" aria-labelledby="sectors-heading">
        <div class="mx-auto max-w-5xl px-4">
            <div class="mb-12 text-center">
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-brand">Dla kogo</p>
                <h2 id="sectors-heading" class="text-2xl font-bold text-ink md:text-3xl">{{ $cd['sectors_heading'] ?? 'Dlaczego warto z nami współpracować?' }}</h2>
                @if (filled($cd['sectors_subtitle'] ?? null))
                    <p class="mx-auto mt-3 max-w-xl text-muted">{{ $cd['sectors_subtitle'] }}</p>
                @endif
            </div>

            <ul class="grid gap-5 sm:grid-cols-2" role="list">
                @foreach ($cdSectors as $sector)
                    @php
                        $colors = $colorMap[$sector['color'] ?? 'blue'] ?? $colorMap['blue'];
                        $tags   = array_filter([$sector['tag1'] ?? null, $sector['tag2'] ?? null, $sector['tag3'] ?? null]);
                    @endphp
                    <li class="flex flex-col rounded-2xl border-l-4 {{ $colors['border'] }} bg-white p-6 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $colors['bg'] }} {{ $colors['text'] }}" aria-hidden="true">
                                <i class="{{ $sector['icon'] ?? 'fa-solid fa-circle' }}"></i>
                            </span>
                            <h3 class="text-base font-bold text-ink">{{ $sector['title'] ?? '' }}</h3>
                        </div>
                        @if (filled($sector['text'] ?? null))
                            <p class="mb-4 text-sm leading-relaxed text-muted">{{ $sector['text'] }}</p>
                        @endif
                        @if ($tags)
                            <ul class="mt-auto flex flex-wrap gap-1.5" aria-label="Obszary">
                                @foreach ($tags as $tag)
                                    <li class="rounded-full {{ $colors['pill'] }} px-2.5 py-0.5 text-xs font-medium">{{ $tag }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
    @endif

    {{-- ══ FORMY WSPÓŁPRACY ══ --}}
    <section id="formy-wspolpracy" class="bg-gray-50 py-16 md:py-24" aria-labelledby="formy-heading">
        <div class="mx-auto max-w-5xl px-4">
            <div class="mb-12 text-center">
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-brand">Jak działamy razem</p>
                <h2 id="formy-heading" class="text-2xl font-bold text-ink md:text-3xl">{{ $cd['forms_heading'] ?? 'Formy współpracy' }}</h2>
                @if (filled($cd['forms_subtitle'] ?? null))
                    <p class="mx-auto mt-3 max-w-xl text-muted">{{ $cd['forms_subtitle'] }}</p>
                @endif
            </div>
            <ul class="grid gap-4 sm:grid-cols-2" role="list">
                @foreach ($cdForms as $idx => $form)
                    <li class="group flex items-start gap-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-brand/40 hover:shadow-md">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand text-white shadow-sm" aria-hidden="true">
                            <i class="{{ $form['icon'] ?? 'fa-solid fa-circle' }}"></i>
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-bold text-ink">{{ $form['title'] ?? '' }}</h3>
                            @if (filled($form['text'] ?? null))
                                <p class="mt-1 text-sm leading-relaxed text-muted">{{ $form['text'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ══ PARTNERZY ══ --}}
    @if ($partners->isNotEmpty())
    <section class="border-t border-gray-100 bg-white py-14" aria-labelledby="partnerzy-heading">
        <div class="mx-auto max-w-5xl px-4">
            <p id="partnerzy-heading" class="mb-10 text-center text-xs font-bold uppercase tracking-widest text-muted">Nasi partnerzy i współpracownicy</p>
            <ul class="flex flex-wrap items-center justify-center gap-10" role="list" aria-label="Logotypy partnerów">
                @foreach ($partners as $partner)
                    @php $logo = $partner->logo_url; @endphp
                    <li>
                        @if ($logo)
                            @if ($partner->url)
                                <a href="{{ $partner->url }}" target="_blank" rel="noopener noreferrer"
                                   class="block rounded-lg transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                   title="{{ $partner->name }}">
                                    <img src="{{ $logo }}" alt="{{ $partner->name }}" loading="lazy"
                                         class="h-10 w-auto object-contain opacity-50 grayscale transition hover:opacity-100 hover:grayscale-0">
                                </a>
                            @else
                                <img src="{{ $logo }}" alt="{{ $partner->name }}" loading="lazy"
                                     class="h-10 w-auto object-contain opacity-50 grayscale">
                            @endif
                        @else
                            <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-muted">{{ $partner->name }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
    @endif

    {{-- ══ DOŁĄCZ DO FEER ══ --}}
    <section class="bg-gray-50 py-16 md:py-20" aria-labelledby="dolacz-heading">
        <div class="mx-auto max-w-5xl px-4">
            <div class="mb-10 text-center">
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">Zaangażuj się osobiście</p>
                <h2 id="dolacz-heading" class="text-2xl font-bold text-ink md:text-3xl">Dołącz do FEER</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('volunteer.index') }}"
                   class="group relative flex min-h-52 flex-col justify-end overflow-hidden rounded-2xl bg-brand p-8 text-white shadow-md transition hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    <span class="pointer-events-none absolute -right-6 -top-6 h-40 w-40 rounded-full bg-white/10 transition-transform group-hover:scale-110" aria-hidden="true"></span>
                    <span class="relative z-10">
                        <span class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <i class="fa-solid fa-hands-helping text-white" aria-hidden="true"></i>
                        </span>
                        <span class="block text-xl font-extrabold leading-tight">Wolontariat</span>
                        <span class="mt-1 block text-sm text-white/75">Angażuj czas i umiejętności — każda pomoc ma znaczenie.</span>
                        <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-white/90 transition group-hover:gap-3">
                            Dowiedz się więcej <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </span>
                    </span>
                </a>
                <a href="{{ route('praca.index') }}"
                   class="group relative flex min-h-52 flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 p-8 text-white shadow-md transition hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900">
                    <span class="pointer-events-none absolute -right-6 -top-6 h-40 w-40 rounded-full bg-white/5 transition-transform group-hover:scale-110" aria-hidden="true"></span>
                    <span class="relative z-10">
                        <span class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                            <i class="fa-solid fa-briefcase text-white" aria-hidden="true"></i>
                        </span>
                        <span class="block text-xl font-extrabold leading-tight">Praca</span>
                        <span class="mt-1 block text-sm text-white/75">Sprawdź oferty — buduj karierę z misją.</span>
                        <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-white/90 transition group-hover:gap-3">
                            Zobacz oferty <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </span>
                    </span>
                </a>
            </div>
        </div>
    </section>

    {{-- ══ CTA ══ --}}
    <section class="bg-brand py-16 text-white md:py-24" aria-labelledby="cta-heading">
        <div class="mx-auto max-w-5xl px-4">
            <div class="flex flex-col items-center gap-10 md:flex-row md:items-start md:gap-16">
                {{-- Lewa: tekst + przycisk --}}
                <div class="flex-1 text-center md:text-left">
                    <span class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white/15">
                        <i class="fa-solid fa-handshake text-xl" aria-hidden="true"></i>
                    </span>
                    <h2 id="cta-heading" class="mt-2 text-2xl font-bold md:text-3xl">{{ $cd['cta_heading'] ?? 'Zacznijmy rozmowę' }}</h2>
                    @if (filled($cd['cta_text'] ?? null))
                        <p class="mt-4 max-w-lg leading-relaxed text-white/80">{{ $cd['cta_text'] }}</p>
                    @endif
                    @if (! empty($cd['form_enabled']))
                        <a href="{{ route('cooperation.form.show', $page) }}"
                           class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 font-bold text-brand shadow-md transition hover:bg-brand-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            {{ $cd['cta_button_label'] ?? 'Wyślij zgłoszenie' }}
                        </a>
                    @elseif (filled($cd['cta_button_label'] ?? null))
                        <a href="{{ $cd['cta_button_url'] ?? route('contact.show') }}"
                           class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 font-bold text-brand shadow-md transition hover:bg-brand-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            {{ $cd['cta_button_label'] }}
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
                {{-- Prawa: social proof --}}
                <div class="w-full shrink-0 rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-sm md:w-72">
                    <p class="text-xs font-bold uppercase tracking-widest text-white/60">Szybki kontakt</p>
                    <ul class="mt-4 space-y-3 text-sm text-white/85">
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-clock w-4 text-center text-white/50" aria-hidden="true"></i>
                            Odpowiadamy w ciągu 2 dni roboczych
                        </li>
                        @if (filled($siteSettings->contact_email ?? null))
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-envelope w-4 text-center text-white/50" aria-hidden="true"></i>
                            <a href="mailto:{{ $siteSettings->contact_email }}"
                               class="hover:text-white focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-white">
                                {{ $siteSettings->contact_email }}
                            </a>
                        </li>
                        @endif
                        @if (filled($siteSettings->contact_phone ?? null))
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-phone w-4 text-center text-white/50" aria-hidden="true"></i>
                            <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}"
                               class="hover:text-white focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-white">
                                {{ $siteSettings->contact_phone }}
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>
    @elseif ($page->isTrainingInstitution())
    <section class="border-b border-gray-200 bg-gray-50">
        <div class="mx-auto max-w-4xl px-4 py-12 md:py-16">
            <span class="mb-3 inline-flex items-center gap-2 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
                <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Instytucja szkoleniowa
            </span>
            <h1 class="mt-3 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-12">
        <div class="space-y-8">
            {{-- Dane kierownika --}}
            @if (filled($page->training_manager_name))
                <div class="flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid fa-user-tie text-lg"></i>
                    </span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted">Kierownik szkolenia</p>
                        <p class="mt-1 text-lg font-bold text-ink">{{ $page->training_manager_name }}</p>
                        @if (filled($page->training_manager_title))
                            <p class="text-sm text-muted">{{ $page->training_manager_title }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Numery rejestracyjne RIS / BUR --}}
            @if (filled($page->training_ris_number) || filled($page->training_bur_number))
                <div class="grid gap-4 sm:grid-cols-2">
                    @if (filled($page->training_ris_number))
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">Numer wpisu RIS</p>
                            <p class="mt-2 font-mono text-lg font-bold text-ink">{{ $page->training_ris_number }}</p>
                        </div>
                    @endif
                    @if (filled($page->training_bur_number))
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">Numer wpisu BUR</p>
                            <p class="mt-2 font-mono text-lg font-bold text-ink">{{ $page->training_bur_number }}</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Wyjaśnienie braku wpisu w BUR --}}
            @if (filled($page->training_bur_note))
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4" role="note">
                    <i class="fa-solid fa-circle-info mt-0.5 shrink-0 text-amber-500" aria-hidden="true"></i>
                    <p class="text-sm leading-relaxed text-amber-900">{!! nl2br(e($page->training_bur_note)) !!}</p>
                </div>
            @endif

            {{-- Treść i dodatkowe informacje --}}
            @include('partials.page-content-image')
            @if ($page->content)
                <div class="prose max-w-none text-ink">{!! $page->content !!}</div>
            @endif

            @if (filled($page->training_extra_info))
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-3 text-base font-bold text-ink">Dodatkowe informacje</h2>
                    <div class="prose max-w-none text-ink">{!! nl2br(e($page->training_extra_info)) !!}</div>
                </div>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </section>
    @elseif ($page->isBrandAssets())
    {{-- Marka: nagłówek + przycisk wylogowania --}}
    <section class="border-b border-gray-200 bg-gray-50">
        <div class="mx-auto max-w-5xl px-4 py-10 md:py-14">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="mb-2 inline-flex items-center gap-2 rounded-full bg-brand-light px-3 py-1 text-xs font-bold uppercase tracking-wide text-brand">
                        <i class="fa-solid fa-palette" aria-hidden="true"></i> Identyfikacja wizualna
                    </span>
                    <h1 class="text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
                    @if ($page->content)
                        <div class="prose mt-3 max-w-2xl text-muted">{!! $page->content !!}</div>
                    @endif
                </div>
                <form method="POST" action="{{ route('page.brand-logout', $page) }}" class="flex-none">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-muted hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                        Wyloguj się
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Brandbook --}}
    @if (filled($page->brand_brandbook_url))
    <section class="border-b border-gray-100 bg-brand-light">
        <div class="mx-auto max-w-5xl px-4 py-4">
            <a href="{{ $page->brand_brandbook_url }}"
               target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2 font-bold text-brand hover:text-brand-dark hover:underline">
                <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                Pobierz brandbook
                <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </section>
    @endif

    {{-- Sekcje plików --}}
    <section class="mx-auto max-w-5xl px-4 py-12 space-y-12">
        @php
            $sections   = collect($page->brand_sections ?? []);
            $attachments = $page->attachments->load('media');
        @endphp

        @forelse ($sections as $section)
            @php
                $sectionFiles = $attachments->filter(fn ($a) => $a->group === $section['key']);
            @endphp
            <div>
                <h2 class="mb-4 text-xl font-bold text-ink">{{ $section['title'] }}</h2>

                @if ($sectionFiles->isNotEmpty())
                    <div class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                        @foreach ($sectionFiles as $attachment)
                            @php
                                $ext = strtolower($attachment->file_extension ?? '');
                                $fileIcon = match(true) {
                                    $ext === 'pdf'                                    => 'fa-file-pdf',
                                    in_array($ext, ['doc','docx'])                    => 'fa-file-word',
                                    in_array($ext, ['xls','xlsx'])                    => 'fa-file-excel',
                                    in_array($ext, ['zip','rar','7z'])                => 'fa-file-zipper',
                                    in_array($ext, ['jpg','jpeg','png','gif','webp']) => 'fa-file-image',
                                    in_array($ext, ['ai','eps','svg'])                => 'fa-file-image',
                                    default                                           => 'fa-file-arrow-down',
                                };
                            @endphp
                            <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded border border-brand text-brand" aria-hidden="true">
                                        <i class="fa-solid {{ $fileIcon }}"></i>
                                    </span>
                                    <div>
                                        <p class="truncate font-bold text-ink">{{ $attachment->label }}</p>
                                        <p class="text-xs text-muted">{{ $attachment->file_extension }} &middot; {{ $attachment->file_size }}</p>
                                    </div>
                                </div>
                                <a href="{{ $attachment->file_url }}" download
                                    class="flex-none rounded bg-brand px-5 py-2 text-sm font-bold uppercase text-white transition hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    Pobierz
                                    <span class="sr-only">— {{ $attachment->label }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-muted italic">Brak plików w tej sekcji.</p>
                @endif
            </div>
        @empty
            {{-- Brak sekcji — wyświetl wszystkie pliki bez podziału --}}
            @include('partials.attachments-list', ['attachments' => $attachments])
        @endforelse
    </section>

    @elseif ($page->isLegacy())
    <section class="border-b border-gray-200 bg-gray-50">
        <div class="mx-auto max-w-4xl px-4 py-12 text-center md:py-16">
            <span class="mb-3 inline-flex items-center gap-2 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Kontynuujemy tę działalność
            </span>
            <h1 class="text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
            @if (filled($page->legacy_name))
                <p class="mt-2 text-lg font-bold text-muted">{{ $page->legacy_name }}</p>
            @endif
            @if (filled($page->legacy_intro))
                <p class="mx-auto mt-3 max-w-2xl text-muted">{{ $page->legacy_intro }}</p>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-12">
        @include('partials.page-content-image')
        @if ($page->content)
            <div class="prose max-w-none text-ink">{!! $page->content !!}</div>
        @endif
        @include('partials.page-gallery', ['page' => $page])
        @include('partials.attachments-list', ['attachments' => $page->attachments])
    </section>
    @elseif ($page->isAboutPerson())
    @auth
    @if (auth()->user()->isAdmin() || auth()->user()->user_group_id)
    <div class="border-b border-amber-300 bg-amber-50 px-4 py-3">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3">
            <p class="flex items-center gap-2 text-sm font-bold text-amber-800">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Panel administracyjny
            </p>
            <a href="{{ route('admin.podstrony.edit', $page) }}"
                class="inline-flex items-center gap-2 rounded bg-amber-700 px-4 py-1.5 text-sm font-bold text-white hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj stronę osoby
            </a>
        </div>
    </div>
    @endif
    @endauth
    @php
        $pSocial = array_filter($page->person_social ?? []);
        $personInitials = \Illuminate\Support\Str::of($page->title)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    @endphp

    {{-- Hero: zdjęcie + imię + rola + social --}}
    <section class="border-b border-gray-100 py-14">
        <div class="mx-auto flex max-w-4xl flex-col items-center gap-8 px-4 sm:flex-row sm:items-start">
            {{-- Zdjęcie lub inicjały --}}
            @if (filled($page->content_image))
                <img src="{{ $page->content_image }}"
                    alt="{{ $page->content_image_alt ?: $page->title }}"
                    class="h-40 w-40 shrink-0 rounded-2xl object-cover shadow-sm">
            @else
                <span class="flex h-40 w-40 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-4xl font-bold text-brand" aria-hidden="true">{{ $personInitials }}</span>
            @endif

            {{-- Dane + social --}}
            <div class="min-w-0 text-center sm:text-left">
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-brand">{{ $page->person_member_label ?: 'Członek zespołu FEER' }}</p>
                <h1 class="text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
                @if (filled($page->person_role))
                    <p class="mt-2 text-lg text-muted">{{ $page->person_role }}</p>
                @endif

                {{-- Kontakt —  bez karty --}}
                @if (filled($page->person_phone) || filled($page->person_email))
                    <div class="mt-4 flex flex-col gap-1.5 text-sm">
                        @if (filled($page->person_phone))
                            <a href="tel:{{ preg_replace('/\s+/', '', $page->person_phone) }}"
                                class="inline-flex items-center gap-2 text-muted hover:text-brand focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                <i class="fa-solid fa-phone w-4 text-center" aria-hidden="true"></i>
                                {{ $page->person_phone }}
                            </a>
                        @endif
                        @if (filled($page->person_email))
                            <a href="mailto:{{ $page->person_email }}"
                                class="inline-flex items-center gap-2 break-all text-muted hover:text-brand focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                <i class="fa-solid fa-envelope w-4 text-center" aria-hidden="true"></i>
                                {{ $page->person_email }}
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Social media — bez obramówki --}}
                @if (! empty($pSocial))
                    <div class="mt-4 flex items-center gap-2 justify-center sm:justify-start">
                        @if (! empty($pSocial['facebook']))
                            <a href="{{ $pSocial['facebook'] }}" target="_blank" rel="noopener noreferrer"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1877f2] text-white hover:opacity-80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                aria-label="Facebook — {{ $page->title }}">
                                <i class="fa-brands fa-facebook-f text-sm" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if (! empty($pSocial['instagram']))
                            <a href="{{ $pSocial['instagram'] }}" target="_blank" rel="noopener noreferrer"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-[#f09433] via-[#e6683c] to-[#bc1888] text-white hover:opacity-80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                aria-label="Instagram — {{ $page->title }}">
                                <i class="fa-brands fa-instagram text-sm" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if (! empty($pSocial['linkedin']))
                            <a href="{{ $pSocial['linkedin'] }}" target="_blank" rel="noopener noreferrer"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-[#0a66c2] text-white hover:opacity-80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                aria-label="LinkedIn — {{ $page->title }}">
                                <i class="fa-brands fa-linkedin-in text-sm" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if (! empty($pSocial['website']))
                            <a href="{{ $pSocial['website'] }}" target="_blank" rel="noopener noreferrer"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-600 text-white hover:opacity-80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                aria-label="Strona internetowa — {{ $page->title }}">
                                <i class="fa-solid fa-globe text-sm" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Treść --}}
    <section class="mx-auto max-w-4xl px-4 py-12">
        @if (filled($page->person_bio))
            <p class="mb-8 text-lg leading-relaxed text-ink">{{ $page->person_bio }}</p>
        @endif
        @if ($page->content)
            <div class="prose max-w-none text-ink">{!! $page->content !!}</div>
        @endif
        @include('partials.page-gallery', ['page' => $page])
        @include('partials.attachments-list', ['attachments' => $page->attachments])
    </section>
    @endif
