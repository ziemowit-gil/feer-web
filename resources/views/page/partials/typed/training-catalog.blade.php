{{--
    Typ „Katalog szkoleń”. Nad listą slider wyróżnionych szkoleń (jak na stronie głównej FEER, ten sam partial),
    filtr kategorii (?kategoria=…) i karty szkoleń z podstron o typie „Szkolenie”.
--}}
@php
    $td = $page->typeData();
    $modeLabels = ['stationary' => 'Stacjonarnie', 'online' => 'Online', 'hybrid' => 'Hybrydowo'];
    $offers = $page->publishedChildren()->get()->where('type', 'training_offer')->values();
    $categories = $offers->map(fn ($o) => $o->typeData()['category'] ?? null)->filter()->unique()->sort()->values();
    $activeCategory = (string) request('kategoria', '');
    $activeMode = array_key_exists((string) request('forma', ''), $modeLabels) ? (string) request('forma') : '';
    $visible = $offers
        ->when($activeCategory !== '', fn ($c) => $c->filter(fn ($o) => ($o->typeData()['category'] ?? null) === $activeCategory))
        ->when($activeMode !== '', fn ($c) => $c->filter(fn ($o) => ($o->typeData()['mode'] ?? null) === $activeMode))
        ->values();
    $presentModes = $offers->map(fn ($o) => $o->typeData()['mode'] ?? null)->filter()->unique()->values();
    $filterUrl = fn (string $category, string $mode) => $page->publicUrl().(($category !== '' || $mode !== '') ? '?'.http_build_query(array_filter(['kategoria' => $category, 'forma' => $mode], fn ($v) => $v !== '')) : '');

    $featured = $offers->filter(fn ($o) => ($o->typeData()['featured'] ?? '0') == '1')->values();
    $slides = ($td['show_slider'] ?? '1') == '1'
        ? $featured->map(fn ($o) => (object) [
            'title' => $o->title, 'text' => $o->typeData()['lead'] ?? $o->meta_description, 'button_url' => $o->publicUrl(),
            'button_label' => 'Zobacz szkolenie', 'image_url' => $o->content_image ?: null, 'image_alt' => $o->content_image_alt ?? '',
        ])
        : collect();
@endphp

@if ($slides->isNotEmpty())
    @include('templates.feer.partials.hero', ['slides' => $slides, 'sliderLabel' => 'Wyróżnione szkolenia'])
@endif

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="training_catalog">
    @include('page.partials.typed._head', ['print' => false])
    @include('partials.page-content-image')

    @php $chip = 'inline-flex min-h-10 items-center rounded-md px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark'; @endphp
    @if ($categories->count() > 1)
        <nav aria-labelledby="tc-cat-label" class="mb-6">
            <p id="tc-cat-label" class="mb-2 text-sm font-bold text-ink">Kategoria</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $filterUrl('', $activeMode) }}" @if ($activeCategory === '') aria-current="true" @endif class="{{ $chip }} {{ $activeCategory === '' ? 'bg-brand text-white' : 'bg-gray-100 text-ink hover:bg-brand-light' }}">Wszystkie</a>
                @foreach ($categories as $cat)
                    <a href="{{ $filterUrl($cat, $activeMode) }}" @if ($activeCategory === $cat) aria-current="true" @endif class="{{ $chip }} {{ $activeCategory === $cat ? 'bg-brand text-white' : 'bg-gray-100 text-ink hover:bg-brand-light' }}">{{ $cat }}</a>
                @endforeach
            </div>
        </nav>
    @endif
    @if ($presentModes->count() > 1)
        <nav aria-labelledby="tc-mode-label" class="mb-8">
            <p id="tc-mode-label" class="mb-2 text-sm font-bold text-ink">Forma</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $filterUrl($activeCategory, '') }}" @if ($activeMode === '') aria-current="true" @endif class="{{ $chip }} {{ $activeMode === '' ? 'bg-brand text-white' : 'bg-gray-100 text-ink hover:bg-brand-light' }}">Każda</a>
                @foreach ($presentModes as $mode)
                    <a href="{{ $filterUrl($activeCategory, $mode) }}" @if ($activeMode === $mode) aria-current="true" @endif class="{{ $chip }} {{ $activeMode === $mode ? 'bg-brand text-white' : 'bg-gray-100 text-ink hover:bg-brand-light' }}">{{ $modeLabels[$mode] }}</a>
                @endforeach
            </div>
        </nav>
    @endif

    @if ($visible->isEmpty())
        <p class="rounded-md bg-gray-50 p-6 text-center text-ink">Nie znaleźliśmy szkoleń dla wybranych filtrów. <a href="{{ $page->publicUrl() }}" class="font-bold text-brand-dark underline underline-offset-4">Pokaż wszystkie</a>.</p>
    @else
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($visible as $offer)
                @php $o = $offer->typeData(); @endphp
                <li>
                    <article class="group relative flex h-full flex-col gap-2 rounded-md bg-gray-50 p-6 focus-within:ring-2 focus-within:ring-brand-dark">
                        @if (filled($o['category'] ?? null))<span class="text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $o['category'] }}</span>@endif
                        <h2 class="text-lg font-bold leading-snug"><a href="{{ $offer->publicUrl() }}" class="stretched-link text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none">{{ $offer->title }}</a></h2>
                        @if (filled($o['lead'] ?? null))<p class="text-sm leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($o['lead'], 150) }}</p>@endif
                        <p class="mt-auto pt-3 text-sm font-semibold text-ink">{{ collect([$o['duration'] ?? null, $modeLabels[$o['mode'] ?? ''] ?? null, $o['price'] ?? null])->filter()->implode(' · ') }}</p>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($page->content)<div class="prose mt-10 max-w-none text-ink">@shortcodes($page->content)</div>@endif
    @include('partials.attachments-list', ['attachments' => $page->attachments])
</section>
