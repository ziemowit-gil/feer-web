@extends('layouts.site')

@section('title', ($project->meta_title ?: $project->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $project->meta_description ?: $project->excerpt)
@if ($project->image_url)
    @section('og_image', $project->image_url)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => route('projects.index')],
        ['label' => $project->category->name, 'url' => route('categories.show', $project->category)],
        ['label' => $project->title, 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('projects');
        $customSections = collect($project->custom_sections ?? [])
            ->filter(fn ($s) => ! empty($s['title']) || ! empty($s['content']));
        $featuredSections = $customSections->filter(fn ($s) => ! empty($s['featured']));
        $regularSections = $customSections->reject(fn ($s) => ! empty($s['featured']));

        // Subpages attached to this project, grouped by how they should appear:
        // inline sections in the body, tabs, or just links in the sidebar.
        $tabPages = $project->publishedPages->where('project_display', 'tab')->values();
        // Jedna „zakładka” nie ma po co przełączać — pokazujemy ją jak zwykłą sekcję w treści.
        $inlinePages = $project->publishedPages->filter(fn ($p) => $p->project_display === 'inline' || ($p->project_display === 'tab' && $tabPages->count() === 1))->values();
        $linkPages = $project->publishedPages->whereNotIn('project_display', ['inline', 'tab'])->values();

        // A schedule ("harmonogram") page attached to this project — surfaced as a
        // call-to-action near the top; the button jumps to the inline section when
        // embedded, otherwise it opens the schedule's own page.
        $schedulePage = $project->publishedPages->first(fn ($p) => $p->isSchedule());
        $scheduleHref = $schedulePage
            ? ($schedulePage->project_display === 'inline' ? '#harmonogram-'.$schedulePage->id : route('page.show', $schedulePage))
            : null;
    @endphp
    <div @if ($canInlineEdit) x-data="inlineContentEditor('project', {{ $project->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
    @if ($canInlineEdit)
        @include('partials.inline-edit-bar')
    @endif
        @php
            $catName = trim($project->category->name);
            $forWhom = trim((string) $project->for_whom);
            // „Dla kogo" bywa tym samym, co kategoria (taksonomia projektów jest wg
            // odbiorcy, np. „Dla NGO"), więc pokazujemy je tylko, gdy wnosi coś ponad
            // nazwę kategorii — inaczej grupa docelowa dublowałaby się z plakietką.
            $showForWhom = $forWhom !== ''
                && mb_strtolower($forWhom) !== mb_strtolower($catName)
                && mb_strtolower($forWhom) !== mb_strtolower(trim(\Illuminate\Support\Str::after($catName, 'Dla ')));
        @endphp

    {{-- ══ HERO: kategoria, tytuł, status, zajawka, kluczowe fakty i zdjęcie ══ --}}
    <section class="border-b border-gray-100 bg-gray-50">
        <div class="relative mx-auto grid max-w-6xl items-center gap-8 px-4 py-12 sm:py-16 {{ $project->image_url ? 'lg:grid-cols-[minmax(0,1fr)_26rem]' : '' }}">
            <div class="min-w-0">
                <a href="{{ route('categories.show', $project->category) }}" class="inline-block text-xs font-bold uppercase tracking-widest text-brand hover:text-brand-dark">
                    {{ $project->category->name }}
                </a>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl" @if ($canInlineEdit) data-inline-field="title" data-inline-kind="text" @endif>{{ $project->title }}</h1>
                @if ($project->excerpt)
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink/80">{{ $project->excerpt }}</p>
                @endif

            </div>

            @if ($project->image_url)
                <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: 'Zdjęcie ilustracyjne: '.$project->title }}" data-lightbox
                    class="aspect-[4/3] w-full rounded-lg object-cover shadow-sm ring-1 ring-gray-200">
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12">
        <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="min-w-0">
                @if ($project->sections_as_tabs && $customSections->count() > 1)
                    @php $sectionTabItems = $customSections->values()->map(fn ($sec, $i) => ['id' => 'sekcja-'.$i, 'label' => $sec['title'] ?: 'Sekcja '.($i + 1)])->all(); @endphp
                    <div class="mb-8" x-data="{
                            tabs: @js(array_column($sectionTabItems, 'id')),
                            tab: @js($sectionTabItems[0]['id']),
                            move(step) { const i = this.tabs.indexOf(this.tab); this.tab = this.tabs[(i + step + this.tabs.length) % this.tabs.length]; this.focusActive(); },
                            jump(id) { this.tab = id; this.focusActive(); },
                            focusActive() { this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus()); },
                        }">
                        @include('partials.tab-strip', ['tabItems' => $sectionTabItems, 'tabsLabel' => 'Sekcje projektu'])
                        <div class="pt-6">
                            @foreach ($customSections->values() as $i => $section)
                                <div id="panel-sekcja-{{ $i }}" role="tabpanel" aria-labelledby="tab-sekcja-{{ $i }}" tabindex="0" @unless ($loop->first) x-cloak @endunless
                                     x-show="tab === 'sekcja-{{ $i }}'" class="prose max-w-none text-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                                    {!! $section['content'] !!}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    @foreach ($featuredSections as $section)
                        <div class="mb-8 rounded-lg border border-brand/20 border-l-4 border-l-brand bg-brand-light/50 p-6">
                            @if (! empty($section['title']))
                                <h2 class="mb-3 text-xl font-bold text-ink">{{ $section['title'] }}</h2>
                            @endif
                            @if (! empty($section['content']))
                                <div class="prose max-w-none text-ink">{!! $section['content'] !!}</div>
                            @endif
                        </div>
                    @endforeach
                @endif

                @if ($project->show_legacy_box)
                    <div class="mb-8 rounded-lg border-l-4 border-brand bg-brand-light p-5">
                        <div class="flex items-start gap-4">
                            <div>
                                <p class="text-base font-bold text-ink">To działanie realizowaliśmy przed uruchomieniem nowej strony.</p>
                                @if ($project->legacy_url)
                                    <a href="{{ $project->legacy_url }}" target="_blank" rel="noopener"
                                        class="mt-3 inline-flex items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-dark">
                                        Zobacz informacje o projekcie
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if ($project->content)
                    <h2 class="mb-3 flex items-center gap-2 text-xl font-bold text-ink">
                        Opis projektu
                    </h2>
                    <div class="prose mb-8 max-w-none text-ink" @if ($canInlineEdit) data-inline-field="content" data-inline-kind="rich" @endif>{!! $project->content !!}</div>
                @endif

                @if ($project->why)
                    <h2 class="mb-3 flex items-center gap-2 text-xl font-bold text-ink">
                        Dlaczego to robimy
                    </h2>
                    <div class="prose max-w-none text-ink">{{ $project->why }}</div>
                @endif

                @unless ($project->sections_as_tabs && $customSections->count() > 1)
                    @foreach ($regularSections as $customSection)
                        <div class="mt-8">
                            @if (! empty($customSection['title']))
                                <h2 class="mb-3 text-xl font-bold text-ink">{{ $customSection['title'] }}</h2>
                            @endif
                            @if (! empty($customSection['content']))
                                <div class="prose max-w-none text-ink">{!! $customSection['content'] !!}</div>
                            @endif
                        </div>
                    @endforeach
                @endunless

                {{-- Project subpages shown inline as sections in the body --}}
                @foreach ($inlinePages as $subpage)
                    @php
                        $anchor = $subpage->isSchedule() ? 'harmonogram-'.$subpage->id : ($subpage->isFaq() ? 'faq-'.$subpage->id : null);
                        $subIcon = $subpage->isSchedule() ? 'fa-calendar-days' : ($subpage->isFaq() ? 'fa-circle-question' : 'fa-file-lines');
                    @endphp
                    <section @if ($anchor) id="{{ $anchor }}" @endif class="mt-8 scroll-mt-24 rounded-lg border border-gray-200 p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
                            {{ $subpage->title }}
                        </h2>
                        @if ($subpage->content)
                            <div class="prose max-w-none text-ink">{!! $subpage->content !!}</div>
                        @endif
                        @if ($subpage->isSchedule())
                            @include('partials.schedule', ['page' => $subpage, 'showHeading' => false])
                        @elseif ($subpage->isFaq())
                            @include('partials.faq', ['page' => $subpage])
                        @endif
                        <a href="{{ route('page.show', $subpage) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                            Otwórz jako osobną stronę                         </a>
                    </section>
                @endforeach

                {{-- Project subpages shown as tabs --}}
                @if ($tabPages->count() > 1)
                    @php $subTabItems = $tabPages->map(fn ($sp, $i) => ['id' => 'podstrona-'.$i, 'label' => $sp->title])->all(); @endphp
                    <div class="mt-8" x-data="{
                            tabs: @js(array_column($subTabItems, 'id')),
                            tab: @js($subTabItems[0]['id']),
                            move(step) { const i = this.tabs.indexOf(this.tab); this.tab = this.tabs[(i + step + this.tabs.length) % this.tabs.length]; this.focusActive(); },
                            jump(id) { this.tab = id; this.focusActive(); },
                            focusActive() { this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus()); },
                        }">
                        @include('partials.tab-strip', ['tabItems' => $subTabItems, 'tabsLabel' => 'Strony projektu'])
                        <div class="pt-6">
                            @foreach ($tabPages as $i => $subpage)
                                <div id="panel-podstrona-{{ $i }}" role="tabpanel" aria-labelledby="tab-podstrona-{{ $i }}" tabindex="0" @unless ($loop->first) x-cloak @endunless
                                     x-show="tab === 'podstrona-{{ $i }}'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                                @if ($subpage->content)
                                    <div class="prose max-w-none text-ink">{!! $subpage->content !!}</div>
                                @endif
                                @if ($subpage->isSchedule())
                                    @include('partials.schedule', ['page' => $subpage, 'showHeading' => false])
                                @elseif ($subpage->isFaq())
                                    @include('partials.faq', ['page' => $subpage])
                                @endif
                                <a href="{{ route('page.show', $subpage) }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                                    Otwórz jako osobną stronę                                 </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($project->outcomes)
                    <div class="mt-8 rounded-lg border border-emerald-200 bg-emerald-50/60 p-6">
                        <h2 class="mb-3 flex items-center gap-2 text-xl font-bold text-ink">
                            Co udało się osiągnąć
                        </h2>
                        <div class="prose max-w-none text-ink">{!! $project->outcomes !!}</div>
                    </div>
                @endif

                @php $pricing = collect($project->pricing ?? [])->filter(fn ($p) => filled($p['item'] ?? null) || filled($p['price'] ?? null)); @endphp
                @if ($project->is_paid && $pricing->isNotEmpty())
                    <div class="mt-8 rounded-lg border border-gray-200 p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
                            Cennik
                        </h2>
                        <ul class="divide-y divide-gray-100">
                            @foreach ($pricing as $row)
                                <li class="flex items-baseline justify-between gap-4 py-2.5">
                                    <span class="min-w-0">
                                        <span class="font-medium text-ink">{{ $row['item'] }}</span>
                                        @if (filled($row['note'] ?? null))
                                            <span class="block text-sm text-muted">{{ $row['note'] }}</span>
                                        @endif
                                    </span>
                                    @if (filled($row['price'] ?? null))
                                        <span class="shrink-0 font-bold text-brand">{{ $row['price'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($siteSettings->isModuleEnabled('news') && $project->publishedNews->isNotEmpty())
                    <div class="mt-10 border-t border-gray-200 pt-8">
                        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
                            Aktualności projektu
                        </h2>
                        <ul class="space-y-4">
                            @foreach ($project->publishedNews as $item)
                                <li>
                                    <a href="{{ route('news.show', $item) }}"
                                        @class([
                                            'group flex gap-4 rounded-lg border p-4 transition hover:shadow-sm',
                                            'border-gray-200 hover:border-brand/40' => ! $item->is_featured,
                                            'border-2 border-amber-400 bg-amber-50/50' => $item->is_featured,
                                        ])>
                                        @php $itemImg = $item->imageUrlOrDefault(); @endphp
                                        @if ($itemImg)
                                            <img src="{{ $itemImg }}" alt="" loading="lazy" class="h-16 w-24 flex-none rounded object-cover">
                                        @endif
                                        <div class="min-w-0">
                                            @if ($item->is_featured)
                                                <span class="mb-1 inline-flex items-center gap-1 rounded-md bg-amber-400/20 px-2 py-0.5 text-xs font-bold text-amber-700">
                                                    Wyróżnione
                                                </span>
                                            @endif
                                            <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ $item->published_at->format('d.m.Y') }}</p>
                                            <p class="font-bold text-ink group-hover:text-brand">{{ $item->title }}</p>
                                            @if ($item->excerpt)
                                                <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $item->excerpt }}</p>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- ══ PANEL BOCZNY: harmonogram, kontakt, strony projektu, powrót ══ --}}
            <aside class="space-y-5 lg:sticky lg:top-6" aria-label="Informacje o projekcie">
                {{-- Karta faktów: zawsze wypełnia panel boczny, także gdy projekt nie ma kontaktu ani harmonogramu --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <h2 class="mb-3 flex items-center gap-2 text-base font-bold text-ink">
                        O projekcie
                    </h2>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-muted">Kategoria</dt>
                            <dd><a href="{{ route('categories.show', $project->category) }}" class="font-medium text-brand hover:text-brand-dark">{{ $project->category->name }}</a></dd>
                        </div>
                        @if ($project->since)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Od kiedy</dt>
                                <dd class="font-medium text-ink">{{ $project->since }}</dd>
                            </div>
                        @endif
                        @if ($showForWhom)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Dla kogo</dt>
                                <dd class="font-medium text-ink" @if ($canInlineEdit) data-inline-field="for_whom" data-inline-kind="text" data-inline-multiline @endif>{{ $project->for_whom }}</dd>
                            </div>
                        @endif
                        @if ($project->is_paid)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Dostęp</dt>
                                <dd class="font-medium text-ink">Płatny</dd>
                            </div>
                        @endif
                    </dl>
                </div>

        @if ($schedulePage)
            <div class="flex flex-col gap-3 rounded-lg border border-brand/20 bg-brand-light p-5">
                <div class="flex items-start gap-3">
                                        <div>
                        <p class="font-bold text-ink">{{ $schedulePage->title }}</p>
                        <p class="text-sm text-muted">Sprawdź terminy zajęć i spotkań w ramach tego projektu.</p>
                    </div>
                </div>
                <a href="{{ $scheduleHref }}"
                    class="inline-flex flex-none items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Zobacz harmonogram                 </a>
            </div>
        @endif

                {{-- Kontakt w sprawie projektu — jako zwykła sekcja treści (jak „Opis
                     projektu"), w głównym nurcie i pełną szerokością, nie jako kafelek z boku. --}}
                @if (! $project->is_completed && $project->showsCoordinator())
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-base font-bold text-ink">
                            Kontakt w sprawie projektu
                        </h2>
                        <div class="space-y-1.5 text-ink">
                            @if ($project->coordinator_name)
                                <p class="font-medium">{{ $project->coordinator_name }}</p>
                            @endif
                            <p>
                                                                <a href="mailto:{{ $project->contactEmail() }}" class="break-all font-medium text-brand hover:text-brand-dark">{{ $project->contactEmail() }}</a>
                            </p>
                            @if ($project->coordinator_phone)
                                <p>
                                                                        <a href="tel:{{ $project->coordinator_phone }}" class="font-medium text-brand hover:text-brand-dark">{{ $project->coordinator_phone }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($linkPages->isNotEmpty())
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-base font-bold text-ink">
                            Strony projektu
                        </h2>
                        <ul class="flex flex-col gap-2 text-sm">
                            @foreach ($linkPages as $projectPage)
                                <li>
                                    @if ($projectPage->isSchedule())
                                        {{-- Harmonogram wyróżnia się jako przycisk-wezwanie do działania. --}}
                                        <a href="{{ route('page.show', $projectPage) }}"
                                            class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            {{ $projectPage->title }}
                                        </a>
                                    @else
                                        <a href="{{ route('page.show', $projectPage) }}"
                                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 font-medium text-ink transition hover:border-brand/40 hover:text-brand">
                                            {{ $projectPage->title }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                    Wszystkie projekty
                </a>
            </aside>
        </div>
    </section>

    </div>
@endsection
