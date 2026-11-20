{{--
    Nagłówek szablonu „kreatywni” (wizytówka fundacji), dwie części:
    1) pasek dostępności na samej górze (przewija się ze stroną; na telefonie tylko „Dostępność” i telefon): rozmiar tekstu A− / bieżący / A+, kontrast,
       „Więcej ustawień” (otwiera wspólny panel ułatwień partials/a11y-panel), telefon i e-mail;
       przyciski data-a11y-* obsługuje resources/js/app.js tak samo jak w panelu;
    2) przyklejona belka: logo i nazwa, mega menu z Menu nawigacji (App\Support\KreatywniMegaMenu: pozycja z
       podmenu — podstrony i sekcje strony, czyli dawny spis „Na tej stronie” — otwiera panel na całą szerokość po
       kliknięciu, Enter albo najechaniu; Esc zamyka i wraca na przycisk, klik obok i wyjście fokusem zamykają) wyśrodkowane,
       bez tła (bieżąca/otwarta pozycja — pogrubienie i limonkowa kropka pod napisem), po prawej wyróżnione profile
       (Facebook, Instagram — przyciski z nazwą i znakiem w kolorach serwisu; na węższym ekranie sam znak) i „Kontakt”; na wąskim ekranie „Menu” rozwija listę pozycji z podstronami
       i sekcjami H2. Style: partials/base-css (prefiks kc-).
--}}
@php
    $navItems ??= collect();
    $kcItems = \App\Support\KreatywniMegaMenu::items($navItems);
    $kcLogo = $siteSettings->logoUrl();
    $kcSocial = $siteSettings->socialLinks(3);
    // Serwis profilu po klasie ikony — do koloru znaku (Facebook, Instagram, YouTube); reszta w kolorach szablonu.
    $kcNet = fn (string $icon) => str_contains($icon, 'facebook') ? 'fb' : (str_contains($icon, 'instagram') ? 'ig' : (str_contains($icon, 'youtube') ? 'yt' : 'other'));
    $kcContactUrl = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    // Gdy „Kontakt” jest już pozycją menu, w menu mobilnym nie dopisujemy go drugi raz.
    $kcPath = fn ($u) => rtrim((string) parse_url((string) $u, PHP_URL_PATH), '/');
    $kcContactInMenu = $kcContactUrl && $kcItems->contains(fn ($it) => $it->hasUrl && $kcPath($it->url) === $kcPath($kcContactUrl));
    $kcPhone = trim((string) $siteSettings->contact_phone);
    $kcEmail = trim((string) $siteSettings->contact_email);
    $kcName = $siteSettings->site_name;
    // „Fundacja „Kreatywni na co dzień”” → mały napis „Fundacja” i duża nazwa (bez cudzysłowów); inna nazwa — w całości.
    $kcKind = null;
    if (preg_match('/^(Fundacja|Stowarzyszenie)\s+(.+)$/u', (string) $kcName, $m)) {
        $kcKind = $m[1];
        $kcName = trim($m[2], " \"'„”“");
    }
@endphp

@include('templates.kreatywni.partials.base-css')

<div class="kc kc-topbar"
     x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
     x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
     @keydown.escape.window="if (open) { open = false; $refs.a11yToggle.focus() }"
     @a11y-open.window="open = true; $nextTick(() => $refs.a11yToggle.focus())">
    <div class="kc-wrap kc-topbar-in">
        <div class="kc-topbar-group kc-hide-xs" role="group" aria-label="Rozmiar tekstu">
            <span class="kc-topbar-label" aria-hidden="true">Tekst</span>
            <button type="button" class="kc-top-btn" data-a11y-font="down" aria-label="Zmniejsz tekst">A<sup aria-hidden="true">−</sup></button>
            <button type="button" class="kc-top-btn kc-top-btn-wide" data-a11y-font="reset" aria-label="Rozmiar tekstu 100% — przywróć domyślny"><span data-a11y-font-value>100%</span></button>
            <button type="button" class="kc-top-btn" data-a11y-font="up" aria-label="Powiększ tekst">A<sup aria-hidden="true">+</sup></button>
        </div>
        <button type="button" class="kc-top-btn kc-top-btn-wide kc-hide-xs" data-a11y-contrast="contrast" aria-pressed="false">
            <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i><span>Kontrast</span>
        </button>
        <button type="button" class="kc-top-btn kc-top-btn-wide" x-ref="a11yToggle" @click="open = ! open"
                aria-expanded="false" :aria-expanded="open.toString()" aria-controls="a11y-panel">
            <i class="fa-solid fa-universal-access" aria-hidden="true"></i><span class="kc-lbl-long">Więcej ustawień<span class="kc-sr"> dostępności</span></span><span class="kc-lbl-short">Dostępność</span>
        </button>

        @if ($kcPhone !== '' || $kcEmail !== '')
            <ul class="kc-topbar-contact" aria-label="Kontakt">
                @if ($kcPhone !== '')
                    <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $kcPhone) }}"><i class="fa-solid fa-phone" aria-hidden="true"></i>{{ $kcPhone }}</a></li>
                @endif
                @if ($kcEmail !== '')
                    <li class="kc-hide-md"><a href="mailto:{{ $kcEmail }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $kcEmail }}</a></li>
                @endif
            </ul>
        @endif
    </div>
    @include('partials.a11y-panel')
</div>

<header class="kc kc-header"
        x-data="{
            mnav: false, drop: null, timer: null, byHover: false,
            hasHover: window.matchMedia('(hover: hover)').matches,
            enter(i) { if (! this.hasHover) return; clearTimeout(this.timer); if (this.drop !== i) { this.drop = i; this.byHover = true } },
            leave() { if (! this.hasHover) return; clearTimeout(this.timer); this.timer = setTimeout(() => { if (this.byHover) this.drop = null }, 300) },
            toggle(i) { clearTimeout(this.timer); this.drop = (this.drop === i && ! this.byHover) ? null : i; this.byHover = false },
        }"
        @keydown.escape="if (drop !== null) { const d = drop; drop = null; $refs['d' + d]?.focus() } else if (mnav) { mnav = false; $refs.mnavToggle.focus() }">
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
            <nav class="kc-nav" aria-label="Menu główne" @click.outside="drop = null"
                 @focusout="$nextTick(() => { if (! $el.contains(document.activeElement)) drop = null })">
                <ul>
                    @foreach ($kcItems as $i => $item)
                        <li class="kc-nav-item" @if ($item->hasPanel) @mouseenter="enter({{ $i }})" @mouseleave="leave()" @endif>
                            @if ($item->hasPanel)
                                <button type="button" class="kc-nav-link @if ($item->current) is-here @endif" x-ref="d{{ $i }}"
                                        @click="toggle({{ $i }})" aria-expanded="false"
                                        :aria-expanded="(drop === {{ $i }}).toString()" aria-controls="kc-mega-{{ $i }}">
                                    {{ $item->label }}<span class="kc-chev" aria-hidden="true"></span>
                                </button>
                                <div id="kc-mega-{{ $i }}" class="kc-mega" x-show="drop === {{ $i }}" x-cloak>
                                    <div class="kc-wrap kc-mega-in">
                                        <div class="kc-mega-intro">
                                            <p class="kc-mega-title">{{ $item->label }}</p>
                                            @if ($item->description !== '')<p>{{ $item->description }}</p>@endif
                                            @if ($item->hasUrl)
                                                <a href="{{ $item->url }}" class="kc-btn" @if ($item->current) aria-current="page" @endif>Przejdź do strony<span class="kc-sr"> „{{ $item->label }}”</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                                            @endif
                                        </div>
                                        <div class="kc-mega-groups">
                                            @foreach ($item->groups as $group)
                                                <div @class(['kc-mega-group', 'is-long' => count($group['links']) > 6])>
                                                    @if ($group['heading'])
                                                        <a href="{{ $group['heading']['url'] }}" class="kc-mega-head">{{ $group['heading']['label'] }}</a>
                                                    @endif
                                                    @if ($group['links'] !== [])
                                                        <ul>
                                                            @foreach ($group['links'] as $link)
                                                                <li><a href="{{ $link['url'] }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
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
                <a href="{{ $sUrl }}" class="kc-net-pill" target="_blank" rel="noopener">
                    <span class="kc-net kc-net-{{ $kcNet($sIcon) }}" aria-hidden="true"><i class="{{ $sIcon }}"></i></span>
                    <span class="kc-net-label">{{ $sLabel }}</span><span class="kc-sr"> (otwiera się w nowej karcie)</span>
                </a>
            @endforeach
            @if ($kcContactUrl)
                <a href="{{ $kcContactUrl }}" class="kc-btn kc-hide-sm" @if (request()->routeIs('contact.*')) aria-current="page" @endif><i class="fa-solid fa-envelope" aria-hidden="true"></i>Kontakt</a>
            @endif
            <button type="button" class="kc-icon-btn kc-burger" x-ref="mnavToggle" @click="mnav = ! mnav"
                    aria-expanded="false" :aria-expanded="mnav.toString()" aria-controls="kc-mnav">
                <i class="fa-solid fa-bars" aria-hidden="true" x-show="! mnav"></i>
                <i class="fa-solid fa-xmark" aria-hidden="true" x-show="mnav" x-cloak></i>
                <span class="kc-sr" x-text="mnav ? 'Zamknij menu' : 'Otwórz menu'">Otwórz menu</span>
            </button>
        </div>
    </div>

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
                        @php
                            $kcMobileLinks = collect($item->groups)->flatMap(fn ($g) => $g['heading'] ? [$g['heading']] : $g['links'])->values();
                        @endphp
                        @if ($kcMobileLinks->isNotEmpty())
                            <ul>
                                @foreach ($kcMobileLinks as $link)
                                    <li><a href="{{ $link['url'] }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a></li>
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
                        <a href="{{ $sUrl }}" class="kc-net-pill" target="_blank" rel="noopener">
                            <span class="kc-net kc-net-{{ $kcNet($sIcon) }}" aria-hidden="true"><i class="{{ $sIcon }}"></i></span>
                            <span>{{ $sLabel }}</span><span class="kc-sr"> (otwiera się w nowej karcie)</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </nav>
    </div>
</header>
