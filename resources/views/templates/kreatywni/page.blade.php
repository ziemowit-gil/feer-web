{{--
    Podstrona w szablonie „kreatywni” (układ wizytówki fundacji): kremowy pas z napisem (strona nadrzędna albo nazwa
    witryny), tytułem i zajawką (opis meta), pod nim treść w jednej czytelnej kolumnie (nagłówki H2/H3 dostają kotwice
    z App\Support\TableOfContents — do nich prowadzą sekcje w mega menu), galeria, podstrony jako karty i pliki; na dole pas „Bądźmy w kontakcie” z profilami social media i kontaktem.
    Typy stron z własnym widokiem (np. „O organizacji”) renderuje page.partials.typed-content na pełną szerokość.
    Edycja na żywo i wersja ETR działają jak w zwykłym widoku strony; warianty page_template się tu nie stosują.
--}}
@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: meta_excerpt($page->content))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        ...($page->project ? ($page->project->category ? $page->project->category->breadcrumbTrail() : [['label' => 'Działania', 'url' => route('projects.index')]]) : []),
        $page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)] : null,
        ...($page->ancestors()->isNotEmpty() ? [] : $page->menuTrail()),
        ...$page->rootlineCrumbs(),
        ['label' => $page->navLabel(), 'url' => null],
    ])])
@endsection

@php
    $kcParent = $page->parent_id ? $page->parent : null;
    $kcEyebrow = $kcParent?->navLabel() ?: $siteSettings->site_name;
    $kcLead = trim((string) $page->meta_description);
    $kcStandard = $page->usesStandardLayout();
    $kcSocial = $siteSettings->socialLinks();
    $kcContactUrl = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
@endphp

@section('content')
    @include('templates.kreatywni.partials.base-css')
    <div class="kc">
        @if ($page->showsPlaceholder())
            <div class="kc-wrap kc-sec">@include('partials.unavailable-notice', ['entity' => $page])</div>
        @elseif (! $kcStandard)
            {{-- Typ strony z własnym widokiem (np. „O organizacji”): pas z tytułem i pełna szerokość. --}}
            <section class="kc-page-head" aria-labelledby="kc-page-title">
                <div class="kc-wrap">
                    <p class="kc-eyebrow kc-hand">{{ $kcEyebrow }}</p>
                    <h1 id="kc-page-title">{{ $page->title }}</h1>
                    @if ($kcLead !== '')<p class="kc-lead">{{ $kcLead }}</p>@endif
                </div>
            </section>
            <div class="kc-typed">
                @include('page.partials.typed-content')
            </div>
        @else
            @php
                $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
                $contentHasShortcode = \App\Support\ShortcodeParser::has($page->content);
                // Kotwice nagłówków (te same, co sekcje w mega menu — App\Support\KreatywniMegaMenu).
                [$contentHtml] = \App\Support\TableOfContents::inject(\App\Support\ShortcodeParser::render($page->content), 1);
                $kcChildren = $page->menuChildren()->get();
            @endphp
            <article @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
                <header class="kc-page-head">
                    <div class="kc-wrap">
                        @if ($canInlineEdit)
                            @include('partials.inline-edit-bar')
                        @endif
                        <p class="kc-eyebrow kc-hand">{{ $kcEyebrow }}</p>
                        @if ($canInlineEdit)
                            <h1 data-inline-field="title" data-inline-kind="text" :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''">{{ $page->title }}</h1>
                        @else
                            <h1>{{ $page->title }}</h1>
                        @endif
                        @if ($kcLead !== '')<p class="kc-lead">{{ $kcLead }}</p>@endif
                    </div>
                </header>

                {{-- Strona „Oferta” (slug oferta / oferta-2…): na górze blok sprzedażowy, niżej opisy warsztatów z treści. --}}
                @if (preg_match('/^oferta(-\d+)?$/', (string) $page->slug))
                    <div class="kc-wrap kc-offer-page">
                        @include('templates.kreatywni.partials.offer-sales', ['texts' => \App\Support\KreatywniTexts::for($siteSettings), 'kcHeadingLevel' => 2])
                    </div>
                @endif

                <div class="kc-wrap kc-page-grid">
                    <div class="kc-page-main" x-data="{ etr: false }">
                        @include('partials.etr-toggle', ['etr' => $page->etr, 'title' => $page->title])
                        @include('partials.pjm-video', ['url' => $page->pjm_video_url, 'title' => $page->title])

                        <div x-show="!etr">
                            @if ($page->is_archived)
                                @include('partials.archival-notice', ['date' => $page->created_at, 'class' => 'mb-6'])
                            @endif

                            @include('partials.page-content-image')

                            @if ($canInlineEdit && ! $contentHasShortcode)
                                <div id="page-text" class="kc-prose prose max-w-none" data-inline-field="content" data-inline-kind="rich" :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''">{!! $contentHtml !!}</div>
                            @else
                                <div id="page-text" class="kc-prose prose max-w-none">{!! $contentHtml !!}</div>
                            @endif

                            @include('partials.page-gallery', ['page' => $page])

                            @if ($kcChildren->isNotEmpty() && ! $contentHasShortcode)
                                <ul class="kc-grid kc-grid-2 kc-child-cards">
                                    @foreach ($kcChildren as $child)
                                        <li>
                                            <a href="{{ $child->navUrl() }}" class="kc-card kc-child-card">
                                                <span class="kc-child-title">{{ $child->navLabel() }}</span>
                                                @if (filled($child->meta_description))<span class="kc-child-text">{{ $child->meta_description }}</span>@endif
                                                <span class="kc-child-more" aria-hidden="true">Czytaj dalej →</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @include('partials.attachments-list', ['attachments' => $page->attachments])
                        </div>
                    </div>
                </div>
            </article>
        @endif

        @if ($kcSocial !== [] || $kcContactUrl)
            <section class="kc-page-cta kc-dark" aria-labelledby="kc-page-cta-h">
                <div class="kc-wrap kc-page-cta-in">
                    <div>
                        <p class="kc-eyebrow kc-hand">{{ $siteSettings->site_name }}</p>
                        <h2 id="kc-page-cta-h" class="kc-h2">Bądźmy w kontakcie</h2>
                        <p class="kc-lead">Relacje z warsztatów publikujemy na bieżąco w mediach społecznościowych. Chcesz zamówić warsztaty albo nas wesprzeć? Napisz do nas.</p>
                    </div>
                    <div class="kc-actions">
                        @foreach ($kcSocial as [$sUrl, $sIcon, $sLabel])
                            <a href="{{ $sUrl }}" class="kc-btn kc-btn-sun" target="_blank" rel="noopener"><i class="{{ $sIcon }}" aria-hidden="true"></i>{{ $sLabel }}<span class="kc-sr"> (otwiera się w nowej karcie)</span></a>
                        @endforeach
                        @if ($kcContactUrl)
                            <a href="{{ $kcContactUrl }}" class="kc-btn kc-btn-outline-light">Napisz do nas</a>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
