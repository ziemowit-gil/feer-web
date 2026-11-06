{{--
    Nagłówek szablonu „elearning”: granatowy pasek z logo, menu głównym, linkiem powrotu do witryny nadrzędnej (gdy jest),
    A+, kontrastem i panelem dostępności. Style: partials/base-css (prefiks el-).
--}}
@php
    $navItems ??= collect();
    $elItems = \App\Support\HeaderNav::items($navItems);
    $elParent = $siteSettings->parent_site_id ? $siteSettings->parentSite : null;
    $elBack = $elParent ? ($elParent->domain ? 'https://'.$elParent->domain : url('/')) : null;
@endphp

@include('templates.elearning.partials.base-css')

<header class="el el-header"
        x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
        x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
        @keydown.escape.window="if (open) { open = false; $refs.a11yToggle.focus() }"
        @a11y-open.window="open = true; $nextTick(() => $refs.a11yToggle.focus())">
    <div class="el-wrap el-header-in">
        <a class="el-brand" href="{{ site_home_url() }}" aria-label="{{ $siteSettings->site_name }} — strona główna">
            @if ($siteSettings->logoUrl())<img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}">@endif
            @unless ($siteSettings->showLogoOnly())<span>{{ $siteSettings->site_name }}</span>@endunless
        </a>

        <nav class="el-nav" aria-label="Nawigacja główna">
            <ul>
                @foreach ($elItems as $item)
                    <li><a href="{{ $item->url }}" @if ($item->current) aria-current="page" @endif>{{ $item->label }}</a></li>
                @endforeach
                @if ($elItems->isEmpty() && $siteSettings->isModuleEnabled('courses') && \Illuminate\Support\Facades\Route::has('courses.index'))
                    <li><a href="{{ site_route('courses.index') }}">Kursy</a></li>
                @endif
            </ul>
        </nav>

        <div class="el-tools">
            <button type="button" class="el-tool" data-a11y-font="up" aria-label="Powiększ tekst">A+</button>
            <button type="button" class="el-tool" data-a11y-contrast="contrast" aria-pressed="false" aria-label="Wysoki kontrast"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></button>
            <button type="button" class="el-tool" x-ref="a11yToggle" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="a11y-panel"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Dostępność</span></button>
            @if ($elBack)
                <a class="el-back" href="{{ $elBack }}">Powrót do {{ $elParent->site_name }}</a>
            @endif
        </div>
    </div>
    @include('partials.a11y-panel')
</header>
