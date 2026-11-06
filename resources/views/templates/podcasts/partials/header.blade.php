{{--
    Nagłówek szablonu „podcasts”: ciemny pasek z logo, menu głównym, linkiem powrotu do witryny nadrzędnej (gdy jest),
    A+, kontrastem i panelem dostępności. Style: partials/base-css (prefiks pc-).
--}}
@php
    $navItems ??= collect();
    $elItems = \App\Support\HeaderNav::items($navItems);
    $elParent = $siteSettings->parent_site_id ? $siteSettings->parentSite : null;
    $elBack = $elParent ? ($elParent->domain ? 'https://'.$elParent->domain : url('/')) : null;
@endphp

@include('templates.podcasts.partials.base-css')

<header class="pc pc-header"
        x-data="{ open: (function () { try { return localStorage.getItem('a11y-panpc-open') === '1' } catch (e) { return false } })() }"
        x-effect="(() => { try { localStorage.setItem('a11y-panpc-open', open ? '1' : '0') } catch (e) {} })()"
        @keydown.escape.window="if (open) { open = false; $refs.a11yToggle.focus() }"
        @a11y-open.window="open = true; $nextTick(() => $refs.a11yToggle.focus())">
    <div class="pc-wrap pc-header-in">
        <a class="pc-brand" href="{{ site_home_url() }}" aria-label="{{ $siteSettings->site_name }} — strona główna">
            @if ($siteSettings->logoUrl())<img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}">@endif
            @unless ($siteSettings->showLogoOnly())<span>{{ $siteSettings->site_name }}</span>@endunless
        </a>

        <nav class="pc-nav" aria-label="Nawigacja główna">
            <ul>
                @foreach ($elItems as $item)
                    <li><a href="{{ $item->url }}" @if ($item->current) aria-current="page" @endif>{{ $item->label }}</a></li>
                @endforeach
                @if ($elItems->isEmpty() && \Illuminate\Support\Facades\Route::has('podcasts.index'))
                    <li><a href="{{ site_route('podcasts.index') }}">Odcinki</a></li>
                @endif
            </ul>
        </nav>

        <div class="pc-tools">
            <button type="button" class="pc-tool" data-a11y-font="up" aria-label="Powiększ tekst">A+</button>
            <button type="button" class="pc-tool" data-a11y-contrast="contrast" aria-pressed="false" aria-label="Wysoki kontrast"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></button>
            <button type="button" class="pc-tool" x-ref="a11yToggle" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="a11y-panel"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Dostępność</span></button>
            @if ($elBack)
                <a class="pc-back" href="{{ $elBack }}">Powrót do {{ $elParent->site_name }}</a>
            @endif
        </div>
    </div>
    @include('partials.a11y-panel')
</header>
