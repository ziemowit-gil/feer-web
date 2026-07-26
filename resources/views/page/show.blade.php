@extends('layouts.site')

@section('title', $page->title . ' — ' . $siteSettings->site_name)
@section('meta_description', \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', $page->content))), 160))

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
    @if ($page->showsPlaceholder())
    @include('partials.unavailable-notice', ['entity' => $page])
    @else
    @if ($page->wipIsNotice())
        <div class="px-4 pt-8">
            @include('partials.page-wip-notice', ['message' => $page->wipMessage()])
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
        <header class="relative overflow-hidden bg-gradient-to-br from-brand to-brand-dark px-4 py-20 text-center text-white md:py-28">
            <i class="fa-solid fa-quote-right pointer-events-none absolute -right-4 -top-4 text-[9rem] text-white/10 md:text-[14rem]" aria-hidden="true"></i>
            <div class="relative mx-auto max-w-4xl">
                <h1 class="text-3xl font-bold leading-tight md:text-5xl">{{ $page->title }}</h1>
                @if ($page->about_motto)
                    <p class="mx-auto mt-6 max-w-3xl text-xl font-medium leading-relaxed text-white/90 md:text-2xl">
                        „{{ $page->about_motto }}”
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
                        <div class="grid grid-cols-2 gap-3">
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
            <section class="relative overflow-hidden bg-gradient-to-br from-brand to-brand-dark px-4 py-16 text-white" aria-label="W liczbach" data-countup>
                <i class="fa-solid fa-arrow-trend-up pointer-events-none absolute -bottom-10 -right-2 text-[11rem] text-white/10" aria-hidden="true"></i>
                <dl class="relative mx-auto grid max-w-5xl grid-cols-2 gap-y-10 md:grid-cols-4 md:divide-x md:divide-white/20">
                    @foreach ($aboutStats as $stat)
                        <div class="px-6 text-center">
                            <dt class="sr-only">{{ $stat['label'] ?? '' }}</dt>
                            <dd>
                                <span class="block text-5xl font-extrabold leading-none tracking-tight md:text-6xl" data-countup-value>{{ $stat['value'] ?? '' }}</span>
                                <span class="mx-auto mt-4 block h-1 w-10 rounded-full bg-white/50" aria-hidden="true"></span>
                                <span class="mt-4 block text-sm font-medium uppercase tracking-widest text-white/80">{{ $stat['label'] ?? '' }}</span>
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
                <div class="grid auto-rows-[160px] grid-cols-2 gap-3 md:auto-rows-[220px] md:grid-cols-4">
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
                        <li class="relative">
                            <span class="absolute -left-[41px] top-1 flex h-5 w-5 items-center justify-center rounded-full border-4 border-white bg-brand ring-1 ring-brand/30" aria-hidden="true"></span>
                            @if (! empty($entry['year']))
                                <p class="text-sm font-bold uppercase tracking-widest text-brand">{{ $entry['year'] }}</p>
                            @endif
                            @if (! empty($entry['text']))
                                <p class="mt-1 leading-relaxed text-ink">{{ $entry['text'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
        @break

        @case('team')
        {{-- Zespół --}}
        @if ($aboutTeam->isNotEmpty())
            <section class="bg-gray-50 px-4 py-16" aria-label="Nasz zespół">
                <div class="mx-auto max-w-5xl">
                    <h2 class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Nasz zespół</h2>
                    <ul class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($aboutTeam as $person)
                            <li class="text-center">
                                @if (! empty($person['photo']))
                                    <img src="{{ $person['photo'] }}" alt="{{ $person['name'] ?? '' }}" loading="lazy"
                                        class="mx-auto mb-4 h-32 w-32 rounded-full object-cover ring-4 ring-white shadow-sm">
                                @else
                                    <span class="mx-auto mb-4 flex h-32 w-32 items-center justify-center rounded-full bg-brand text-3xl font-bold text-white ring-4 ring-white shadow-sm" aria-hidden="true">
                                        {{ \Illuminate\Support\Str::of($person['name'] ?? '?')->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                    </span>
                                @endif
                                @if (! empty($person['name']))
                                    <p class="font-bold text-ink">{{ $person['name'] }}</p>
                                @endif
                                @if (! empty($person['role']))
                                    <p class="text-sm text-muted">{{ $person['role'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
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

        @endswitch
        @endforeach

        @if ($page->attachments->isNotEmpty())
            <div class="mx-auto max-w-3xl px-4 py-8">
                @include('partials.page-gallery', ['page' => $page])

        @include('partials.attachments-list', ['attachments' => $page->attachments])
            </div>
        @endif
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
                Szukasz dokumentów albo sprawozdań naszej fundacji? Zebraliśmy je
                w <strong class="font-bold text-ink">Biuletynie Informacji Publicznej (BIP)</strong>. Dzięki temu tutaj
                możemy po prostu opowiadać o tym, co robimy i dla kogo, a papiery masz pod ręką w jednym, uporządkowanym
                miejscu. Nasz BIP dopiero się rozkręca — zaglądaj śmiało i dziękujemy za wyrozumiałość.
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
    @else
    @php $menuSiblings = $page->menuSiblings(); @endphp

    <section class="mx-auto max-w-5xl px-4 py-12">
        <div class="grid gap-10 {{ $menuSiblings->isNotEmpty() ? 'md:grid-cols-[1fr_220px]' : '' }}">
            <div>
                <h1 class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>
                <div class="prose max-w-none text-ink">{!! $page->content !!}</div>

                @include('partials.page-gallery', ['page' => $page])

        @include('partials.attachments-list', ['attachments' => $page->attachments])
            </div>

            @if ($menuSiblings->isNotEmpty())
                @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
            @endif
        </div>
    </section>
    @endif
    @endif
@endsection
