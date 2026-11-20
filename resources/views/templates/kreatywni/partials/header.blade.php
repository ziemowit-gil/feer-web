{{--
    Nagłówek szablonu „kreatywni” (wizytówka fundacji): logo i nazwa, menu z Menu nawigacji (pozycja z podpozycjami
    rozwija listę po kliknięciu, Esc zamyka i wraca na przycisk), ikony social media (do trzech profili z ustawień),
    przycisk „Dostępność” (pasek ułatwień), „Kontakt”. Na wąskim ekranie przycisk „Menu” rozwija pełną listę z
    podpozycjami i profilami. Przyklejony do góry przy przewijaniu. Style: partials/base-css (prefiks kc-).
--}}
@php
    $navItems ??= collect();
    $kcItems = \App\Support\HeaderNav::items($navItems);
    $kcLogo = $siteSettings->logoUrl();
    $kcSocial = $siteSettings->socialLinks(3);
    $kcContactUrl = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    // Gdy „Kontakt” jest już pozycją menu, w menu mobilnym nie dopisujemy go drugi raz.
    $kcPath = fn ($u) => rtrim((string) parse_url((string) $u, PHP_URL_PATH), '/');
    $kcContactInMenu = $kcContactUrl && $kcItems->contains(fn ($it) => $it->hasUrl && $kcPath($it->url) === $kcPath($kcContactUrl));
    $kcName = $siteSettings->site_name;
    // „Fundacja „Kreatywni na co dzień”” → mały napis „Fundacja” i duża nazwa (bez cudzysłowów); inna nazwa — w całości.
    $kcKind = null;
    if (preg_match('/^(Fundacja|Stowarzyszenie)\s+(.+)$/u', (string) $kcName, $m)) {
        $kcKind = $m[1];
        $kcName = trim($m[2], " \"'„”“");
    }
@endphp

@include('templates.kreatywni.partials.base-css')

<header class="kc kc-header"
        x-data="{
            open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })(),
            mnav: false,
            drop: null,
        }"
        x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
        @keydown.escape="if (drop !== null) { const d = drop; drop = null; $refs['d' + d]?.focus() } else if (open) { open = false; $refs.a11yToggle.focus() } else if (mnav) { mnav = false; $refs.mnavToggle.focus() }"
        @a11y-open.window="open = true; $nextTick(() => $refs.a11yToggle.focus())">
    <div class="kc-wrap kc-top">
        <a class="kc-brand" href="{{ site_home_url() }}">
            @if ($kcLogo)
                <img src="{{ $kcLogo }}" alt="" width="56" height="56">
            @endif
            <span class="kc-brand-text">
                @if ($kcKind)<span class="kc-brand-kind">{{ $kcKind }}</span>@endif
                <span class="kc-brand-name">{{ $kcName }}</span>
                <span class="kc-sr"> — strona główna</span>
            </span>
        </a>

        @if ($kcItems->isNotEmpty())
            <nav class="kc-nav" aria-label="Menu główne" @click.outside="drop = null">
                <ul>
                    @foreach ($kcItems as $i => $item)
                        <li class="kc-nav-item">
                            @if ($item->flat !== [])
                                <button type="button" class="kc-nav-link @if ($item->current) is-here @endif" x-ref="d{{ $i }}"
                                        @click="drop = drop === {{ $i }} ? null : {{ $i }}" aria-expanded="false"
                                        :aria-expanded="(drop === {{ $i }}).toString()" aria-controls="kc-drop-{{ $i }}">
                                    {{ $item->label }}<span class="kc-chev" aria-hidden="true"></span>
                                </button>
                                <ul id="kc-drop-{{ $i }}" class="kc-drop" x-show="drop === {{ $i }}" x-cloak>
                                    @if ($item->hasUrl)
                                        <li><a href="{{ $item->url }}">{{ $item->label }} — wszystko</a></li>
                                    @endif
                                    @foreach ($item->flat as $link)
                                        @if (filled($link['url']))
                                            <li><a href="{{ $link['url'] }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a></li>
                                        @endif
                                    @endforeach
                                </ul>
                            @elseif ($item->hasUrl)
                                <a href="{{ $item->url }}" class="kc-nav-link" @if ($item->current) aria-current="page" @endif>{{ $item->label }}</a>
                            @else
                                <span class="kc-nav-link">{{ $item->label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div class="kc-tools">
            @foreach ($kcSocial as [$sUrl, $sIcon, $sLabel])
                <a href="{{ $sUrl }}" class="kc-icon-btn kc-hide-sm" target="_blank" rel="noopener">
                    <i class="{{ $sIcon }}" aria-hidden="true"></i><span class="kc-sr">{{ $sLabel }} (otwiera się w nowej karcie)</span>
                </a>
            @endforeach
            <button type="button" class="kc-icon-btn" x-ref="a11yToggle" @click="open = ! open"
                    aria-expanded="false" :aria-expanded="open.toString()" aria-controls="a11y-panel">
                <i class="fa-solid fa-universal-access" aria-hidden="true"></i><span class="kc-sr">Ustawienia dostępności: rozmiar tekstu i kontrast</span>
            </button>
            @if ($kcContactUrl)
                <a href="{{ $kcContactUrl }}" class="kc-btn kc-hide-sm" @if (request()->routeIs('contact.*')) aria-current="page" @endif>Kontakt</a>
            @endif
            <button type="button" class="kc-icon-btn kc-burger" x-ref="mnavToggle" @click="mnav = ! mnav"
                    aria-expanded="false" :aria-expanded="mnav.toString()" aria-controls="kc-mnav">
                <i class="fa-solid fa-bars" aria-hidden="true" x-show="! mnav"></i>
                <i class="fa-solid fa-xmark" aria-hidden="true" x-show="mnav" x-cloak></i>
                <span class="kc-sr" x-text="mnav ? 'Zamknij menu' : 'Otwórz menu'">Otwórz menu</span>
            </button>
        </div>
    </div>

    @include('partials.a11y-panel')

    <div id="kc-mnav" class="kc-mnav" x-show="mnav" x-cloak>
        <nav class="kc-wrap" aria-label="Menu główne (wersja mobilna)">
            <ul>
                @foreach ($kcItems as $item)
                    <li>
                        @if ($item->hasUrl)
                            <a href="{{ $item->url }}" @if ($item->current) aria-current="page" @endif>{{ $item->label }}</a>
                        @else
                            <span class="kc-sr">{{ $item->label }}</span>
                        @endif
                        @if ($item->flat !== [])
                            <ul>
                                @foreach ($item->flat as $link)
                                    @if (filled($link['url']))
                                        <li><a href="{{ $link['url'] }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
                @if ($kcContactUrl && ! $kcContactInMenu)
                    <li><a href="{{ $kcContactUrl }}">Kontakt</a></li>
                @endif
            </ul>
            @if ($kcSocial !== [])
                <div class="kc-mnav-social">
                    @foreach ($kcSocial as [$sUrl, $sIcon, $sLabel])
                        <a href="{{ $sUrl }}" class="kc-icon-btn" target="_blank" rel="noopener">
                            <i class="{{ $sIcon }}" aria-hidden="true"></i><span class="kc-sr">{{ $sLabel }} (otwiera się w nowej karcie)</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </nav>
    </div>
</header>
