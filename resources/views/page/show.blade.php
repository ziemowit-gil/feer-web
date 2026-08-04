@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', $page->content))), 160))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        $page->project ? ['label' => 'Projekty', 'url' => route('projects.index')] : null,
        $page->project && $page->project->category ? ['label' => $page->project->category->name, 'url' => route('categories.show', $page->project->category)] : null,
        $page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)]
            : ($page->parent ? ['label' => $page->parent->title, 'url' => route('page.show', $page->parent)] : null),
        ['label' => $page->title, 'url' => null],
    ])])
@endsection

@section('content')
    @if ($page->isAccessRestricted() && $page->access_mode === 'microsoft' && auth('member')->check())
        <div class="border-b border-brand/20 bg-brand-light">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
                <span class="flex items-center gap-2 text-brand-dark">
                    <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                    Strefa wewnętrzna — zalogowano jako <strong>{{ auth('member')->user()->email }}</strong>
                </span>
                <form method="POST" action="{{ route('member.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 font-medium text-brand transition hover:text-brand-dark">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Wyloguj ze strefy
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($page->showsPlaceholder())
    @include('partials.unavailable-notice', ['entity' => $page])
    @else
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
        $aboutTeam = collect($page->about_team ?? []);
        $aboutImages = $page->images->filter(fn ($img) => $img->image_url)->values();
        // Pierwsze 2–3 zdjęcia jako kolaż obok wstępu; reszta trafia do galerii.
        $introPhotos = $aboutImages->take(3)->values();
        $galleryPhotos = $aboutImages->slice(3)->values();
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

        @foreach ($page->orderedAboutSections() as $aboutSection)
        @switch($aboutSection)

        @case('intro')
        {{-- Wstęp (pole) + kolaż 2–3 zdjęć obok --}}
        @if (filled($page->about_intro) || $page->content || $introPhotos->isNotEmpty())
            <section class="mx-auto max-w-6xl px-4 py-16">
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
            <section class="relative overflow-hidden bg-gradient-to-br from-brand to-brand-dark px-4 py-12 text-white" aria-label="W liczbach" data-countup>
                <i class="fa-solid fa-arrow-trend-up pointer-events-none absolute -bottom-10 -right-2 text-[11rem] text-white/10" aria-hidden="true"></i>
                <dl class="relative mx-auto grid max-w-4xl grid-cols-2 gap-6 md:grid-cols-4 md:divide-x md:divide-white/20">
                    @foreach ($aboutStats as $stat)
                        <div class="flex flex-col items-center px-4 text-center">
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
            <section class="mx-auto max-w-6xl px-4 py-16">
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
            <section class="bg-gray-50 px-4 py-16" aria-label="Nasze wartości">
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
            <section class="mx-auto max-w-3xl px-4 py-16" aria-label="Nasza historia">
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
            <section class="bg-gray-50 px-4 py-16" aria-label="Nasz zespół">
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
            <section class="bg-gray-50 px-4 py-16" aria-label="Nasi partnerzy">
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
            <section class="mx-auto max-w-6xl px-4 py-16" aria-label="Dokumenty i sprawozdania">
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
            <section class="bg-gray-50 px-4 py-16" aria-label="My w mediach">
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
            <section class="mx-auto max-w-4xl px-4 py-12">
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
    @else
    @php
        $menuSiblings = $page->menuSiblings();
        $showSideNav = ($page->show_side_nav ?? true) && $menuSiblings->isNotEmpty();
    @endphp

    <section class="mx-auto max-w-5xl px-4 py-12">
        <div class="grid gap-10 {{ $showSideNav ? 'md:grid-cols-[1fr_220px]' : '' }}">
            <div>
                <h1 class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>

                @include('partials.page-content-image')

                <div class="prose max-w-none text-ink">{!! $page->content !!}</div>

                @include('partials.page-gallery', ['page' => $page])

        @include('partials.attachments-list', ['attachments' => $page->attachments])
            </div>

            @if ($showSideNav)
                @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
            @endif
        </div>
    </section>
    @endif
    @endif
@endsection
