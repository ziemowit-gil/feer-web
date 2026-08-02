@extends('admin.layout')

@section('title', 'Ustawienia strony')

@section('content')
    @php
        $initialTab = in_array(request('tab'), array_keys(\App\Models\SiteSetting::SETTINGS_TABS), true)
            ? request('tab')
            : 'general';
    @endphp
    <div x-data="{ tab: '{{ $initialTab }}', wm_layout: '{{ old('header_layout', $settings->header_layout ?? 'classic') }}', wideModal: {{ $errors->has('wide_activation_code') && old('header_layout') === 'wide_mission' ? 'true' : 'false' }}, wideCode: '', wideCodeError: false, prevLayout: '{{ old('header_layout', $settings->header_layout ?? 'classic') }}' }"
        x-init="$watch('tab', value => history.replaceState(null, '', '?tab=' + value))"
        class="max-w-3xl space-y-6">
    @if (! empty($strefaConflict))
        <div role="alert" class="rounded-lg border border-amber-300 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 text-lg text-amber-600" aria-hidden="true"></i>
                <div class="flex-1">
                    <h2 class=”font-bold text-amber-900”>Adres <code>/strefa-wspolpracownika-feer</code> jest już zajęty</h2>
                    <p class=”mt-1 text-sm text-amber-800”>
                        Ten adres zajmuje strona „<strong>{{ $strefaConflict->title }}</strong>”
                        (typ: {{ \App\Models\Page::TYPES[$strefaConflict->type] ?? $strefaConflict->type }}), więc
                        strefa współpracownika nie działa. Potwierdź, aby ją nadpisać — strona zostanie przełączona na
                        wewnętrzną z logowaniem Microsoft 365. Dotychczasowa treść zostanie zachowana.
                    </p>
                    <form method=”POST” action=”{{ route('admin.strefa.overwrite') }}” class=”mt-3”
                        onsubmit=”return confirm('Nadpisać stronę /strefa-wspolpracownika-feer jako strefę współpracownika? Strona „{{ $strefaConflict->title }}” stanie się stroną wewnętrzną (logowanie MS365).')”>
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded bg-amber-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                            <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Potwierdź i nadpisz
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <form method="POST" action="{{ route('admin.ustawienia.update') }}" enctype="multipart/form-data"
        class="rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @method('PUT')

        {{-- Zakładki ustawień są w bocznym menu panelu (Ustawienia → …).
             Sekcje przełączamy przez ?tab=… ustawiane z menu bocznego. --}}
        <p class="mb-6 text-sm text-muted">
            Wybierz sekcję ustawień z menu po lewej. Zmiany zapisz przyciskiem na dole — dotyczą wszystkich sekcji.
        </p>

        <div x-show="tab === 'general'" x-cloak class="space-y-6">
        <div>
            <label for="site_name" class="mb-1 block text-sm font-bold">Nazwa strony</label>
            <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('site_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tagline" class="mb-1 block text-sm font-bold">Podtytuł (tagline)</label>
            <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $settings->tagline) }}"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Wyświetlany w nagłówku pod nazwą strony.</p>
            @error('tagline') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="content_editor" class="mb-1 block text-sm font-bold">Edytor treści (strony, aktualności, projekty)</label>
            <select id="content_editor" name="content_editor" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @foreach (\App\Models\SiteSetting::EDITORS as $value => $label)
                    <option value="{{ $value }}" {{ old('content_editor', $settings->content_editor) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('content_editor') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="unsplash_access_key" class="mb-1 block text-sm font-bold">Klucz Unsplash (Access Key)</label>
            <input type="password" id="unsplash_access_key" name="unsplash_access_key" autocomplete="new-password"
                placeholder="{{ $settings->unsplash_access_key ? '•••••••• (zapisany — zostaw puste, aby nie zmieniać)' : 'Wklej Access Key z unsplash.com/developers' }}"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Umożliwia wyszukiwanie i import zdjęć z Unsplash w module Multimedia. Darmowy klucz: <a href="https://unsplash.com/developers" target="_blank" rel="noopener" class="text-brand underline">unsplash.com/developers</a>. Puste = wartość z pliku <code>.env</code>.</p>
            @error('unsplash_access_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <label class="flex items-center gap-2">
                <input type="hidden" name="cookie_banner_enabled" value="0">
                <input type="checkbox" name="cookie_banner_enabled" value="1" {{ old('cookie_banner_enabled', $settings->cookie_banner_enabled ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-sm font-bold">Pokaż baner o plikach cookies</span>
            </label>
            <div>
                <label for="cookie_banner_text" class="mb-1 block text-sm font-bold">Tekst banera <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <textarea id="cookie_banner_text" name="cookie_banner_text" rows="2"
                    placeholder="{{ \App\Models\SiteSetting::DEFAULT_COOKIE_BANNER_TEXT }}"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('cookie_banner_text', $settings->cookie_banner_text) }}</textarea>
                <p class="mt-1 text-xs text-muted">Baner z przyciskiem „Akceptuję" i linkiem do polityki prywatności. Puste = tekst domyślny.</p>
                @error('cookie_banner_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <input type="hidden" name="show_cms_credit" value="0">
            <input type="checkbox" name="show_cms_credit" value="1" {{ old('show_cms_credit', $settings->show_cms_credit ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Pokaż nazwę CMS w stopce („weCMS · autor…")</span>
        </label>

        <div>
            <p class="mb-1 text-sm font-bold">Logo</p>
            @if ($settings->logoUrl())
                <div class="mb-2 flex items-center gap-3">
                    <img src="{{ $settings->logoUrl() }}" alt="Logo" class="h-14 w-14 rounded object-contain">
                    <label class="flex items-center gap-2 text-sm text-muted">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                        Usuń logo (wróć do domyślnego znaczka)
                    </label>
                </div>
            @endif
            <input type="file" name="logo" accept="image/*" class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="mt-3">
                <label for="logo_alt" class="mb-1 block text-sm font-bold">Tekst alternatywny logo <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="logo_alt" name="logo_alt" value="{{ old('logo_alt', $settings->logo_alt) }}"
                    placeholder="{{ $settings->site_name }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Opis logo dla czytników ekranu (WCAG). Puste = nazwa strony.</p>
                @error('logo_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="mt-3 flex items-start gap-2 text-sm">
                <input type="checkbox" name="logo_only" value="1" @checked(old('logo_only', $settings->logo_only)) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                <span>
                    <span class="font-bold">Pokazuj samo logo</span>
                    <span class="block text-xs text-muted">Ukrywa nazwę strony i podtytuł w nagłówku. Działa tylko, gdy logo jest wgrane.</span>
                </span>
            </label>
        </div>
        </div>

        <div x-show="tab === 'header'" x-cloak class="space-y-6">
            <div>
                <p class="mb-3 text-sm font-bold">Układ nagłówka strony</p>
                @php $isFeer = str_contains(request()->getHost(), 'feer.org.pl'); @endphp
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\SiteSetting::HEADER_LAYOUTS as $layoutValue => $layoutLabel)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition has-[:checked]:border-brand has-[:checked]:bg-brand-light">
                        @if ($layoutValue === 'wide_mission')
                        <input type="radio" name="header_layout" value="wide_mission"
                            :checked="wm_layout === 'wide_mission'"
                            @click.prevent="if (wm_layout !== 'wide_mission') { prevLayout = wm_layout; wideCode = ''; wideCodeError = false; wideModal = true; }"
                            class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                        @else
                        <input type="radio" name="header_layout" value="{{ $layoutValue }}"
                            {{ old('header_layout', $settings->header_layout ?? 'classic') === $layoutValue ? 'checked' : '' }}
                            x-model="wm_layout"
                            class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                        @endif
                        <span class="text-sm leading-snug">
                            {{ $layoutLabel }}
                            @if ($layoutValue === 'wide_mission' && !$isFeer)
                            <span class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-[0.65rem] font-semibold text-gray-600">🔒 Tylko FEER</span>
                            <span class="mt-1 block text-xs text-muted">weCMS powstał jako autorskie rozwiązanie dla FEER. Jest teraz publicznie dostępny, ale niektóre opcje pozostają zarezerwowane.</span>
                            @endif
                        </span>
                    </label>
                    @endforeach
                </div>
                <input type="hidden" name="wide_activation_code" x-bind:value="wideCode">
                @error('wide_activation_code')
                <p class="mt-2 text-sm text-red-600">
                    {{ $message }}
                    <button type="button" @click="wideCode = ''; wideModal = true"
                            class="ml-2 underline hover:no-underline focus:outline-none">Wprowadź ponownie</button>
                </p>
                @enderror
                @error('header_layout') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Modal: sekretny kod do aktywacji stylu Wide --}}
            <div x-show="wideModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                 @keydown.escape.window="wm_layout = prevLayout; wideModal = false">
                <div class="w-80 rounded-xl bg-white p-6 shadow-xl" @click.stop>
                    <h3 class="mb-1 text-base font-bold">Styl Wide — tylko dla FER</h3>
                    <p class="mb-4 text-sm text-muted">Ten układ jest zarezerwowany. Podaj sekretny kod, aby go aktywować.</p>
                    <input type="text" x-model="wideCode" x-ref="wideCodeInput"
                           x-init="$watch('wideModal', v => { if (v) $nextTick(() => $refs.wideCodeInput.focus()) })"
                           @keydown.enter="wm_layout = 'wide_mission'; wideModal = false"
                           placeholder="Sekretny kod"
                           autocomplete="off"
                           class="mb-2 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <div class="flex gap-2">
                        <button type="button"
                                @click="wm_layout = 'wide_mission'; wideModal = false"
                                class="flex-1 rounded bg-brand px-3 py-2 text-sm font-bold text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-1">
                            Aktywuj
                        </button>
                        <button type="button"
                                @click="wm_layout = prevLayout; wideModal = false; wideCode = ''; wideCodeError = false"
                                class="flex-1 rounded bg-gray-100 px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-1">
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="wm_layout === 'wide_mission'" x-cloak
                class="rounded-xl border border-brand-light bg-brand-light/30 p-5 space-y-5">
                <p class="text-sm font-bold text-brand">Ustawienia nagłówka WOŚP</p>

                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                    <input type="hidden" name="wide_mission_show_mission" value="0">
                    <input type="checkbox" name="wide_mission_show_mission" value="1"
                        {{ old('wide_mission_show_mission', $settings->wide_mission_show_mission ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Pokazuj misję <span class="font-normal text-muted">(pobiera motto ze strony „O organizacji")</span></span>
                </label>

                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                    <input type="hidden" name="wide_mission_highlight_account" value="0">
                    <input type="checkbox" name="wide_mission_highlight_account" value="1"
                        {{ old('wide_mission_highlight_account', $settings->wide_mission_highlight_account ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Podświetlaj nr konta <span class="font-normal text-muted">(wyświetla numer w kolorze marki)</span></span>
                </label>

                <div>
                    <p class="mb-2 text-sm font-bold">Wyrównanie menu</p>
                    <div class="flex gap-4">
                        @foreach(['left' => 'Do lewej', 'center' => 'Na środku'] as $alignVal => $alignLabel)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" name="wide_mission_nav_align" value="{{ $alignVal }}"
                                {{ old('wide_mission_nav_align', $settings->wide_mission_nav_align ?? 'left') === $alignVal ? 'checked' : '' }}
                                class="border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm">{{ $alignLabel }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                    <input type="hidden" name="wide_mission_search_in_nav" value="0">
                    <input type="checkbox" name="wide_mission_search_in_nav" value="1"
                        {{ old('wide_mission_search_in_nav', $settings->wide_mission_search_in_nav ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Wyszukiwarka w menu <span class="font-normal text-muted">(przenosi search z górnego paska na koniec paska nav)</span></span>
                </label>

                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                    <input type="hidden" name="wide_mission_sidebar" value="0">
                    <input type="checkbox" name="wide_mission_sidebar" value="1"
                        {{ old('wide_mission_sidebar', $settings->wide_mission_sidebar ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Sidebar obok slidera <span class="font-normal text-muted">(misja organizacji lub skróty obok slidera; slider przyjmuje mniejsze wymiary)</span></span>
                </label>

                <div>
                    <label class="mb-1 block text-sm font-bold">Zawartość sidebara</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach (['mission' => 'Misja organizacji', 'colored' => 'Kolorowe kafle (skróty)', 'cards' => 'Białe karty (skróty)'] as $sv => $sl)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 has-[:checked]:border-brand has-[:checked]:bg-brand-light">
                                <input type="radio" name="wide_mission_sidebar_style" value="{{ $sv }}"
                                    {{ old('wide_mission_sidebar_style', $settings->wide_mission_sidebar_style ?? 'colored') === $sv ? 'checked' : '' }}
                                    class="text-brand focus:ring-brand">
                                <span class="text-sm font-bold">{{ $sl }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold">Substyl paska nawigacji</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach (['brand_bar' => 'Pasek koloru marki (domyślny)', 'icons_white' => 'Biały pasek — ikony + etykiety'] as $nv => $nl)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 has-[:checked]:border-brand has-[:checked]:bg-brand-light">
                                <input type="radio" name="wide_mission_nav_style" value="{{ $nv }}"
                                    {{ old('wide_mission_nav_style', $settings->wide_mission_nav_style ?? 'brand_bar') === $nv ? 'checked' : '' }}
                                    class="text-brand focus:ring-brand">
                                <span class="text-sm font-bold">{{ $nl }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-muted">Biały pasek wymaga ustawienia ikon w pozycjach menu (zakładka Menu → pole Ikona).</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-bold">Social media 1</label>
                        <select name="wide_mission_social_1" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <option value="">— brak —</option>
                            @foreach (\App\Models\SiteSetting::SOCIAL_KEYS as $smKey => $smMeta)
                                <option value="{{ $smKey }}" {{ old('wide_mission_social_1', $settings->wide_mission_social_1) === $smKey ? 'selected' : '' }}>{{ $smMeta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold">Social media 2</label>
                        <select name="wide_mission_social_2" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <option value="">— brak —</option>
                            @foreach (\App\Models\SiteSetting::SOCIAL_KEYS as $smKey => $smMeta)
                                <option value="{{ $smKey }}" {{ old('wide_mission_social_2', $settings->wide_mission_social_2) === $smKey ? 'selected' : '' }}>{{ $smMeta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="wide_mission_cta_label" class="mb-1 block text-sm font-bold">
                        Etykieta przycisku CTA
                        <span class="font-normal text-muted">(opcjonalnie)</span>
                    </label>
                    <input type="text" id="wide_mission_cta_label" name="wide_mission_cta_label"
                        value="{{ old('wide_mission_cta_label', $settings->wide_mission_cta_label) }}"
                        placeholder="np. Wolontariat" maxlength="80"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label for="wide_mission_cta_url" class="mb-1 block text-sm font-bold">
                        Link przycisku CTA
                        <span class="font-normal text-muted">(opcjonalnie)</span>
                    </label>
                    <input type="text" id="wide_mission_cta_url" name="wide_mission_cta_url"
                        value="{{ old('wide_mission_cta_url', $settings->wide_mission_cta_url) }}"
                        placeholder="https://..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Przycisk pojawi się obok ikon social media w górnym pasku nagłówka.</p>
                </div>
            </div>
        </div>

        <div x-show="tab === 'colors'" x-cloak class="space-y-6">
            @php $subBrands = array_values((array) old('sub_brands', $settings->sub_brands ?? [])); @endphp
            <p class="text-xs text-muted">Kolor przewodni strony oraz nazwane kolory submarek dla różnych treści (projektów i aktualności). Każdy kolor jest przy zapisie przyciemniany do kontrastu WCAG AA wobec bieli.</p>

            <div>
                <label for="brand_color" class="mb-1 block text-sm font-bold">Kolor przewodni (marka)</label>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="color" id="brand_color" name="brand_color" value="{{ old('brand_color', $settings->brand_color) }}"
                        class="h-10 w-16 rounded border-gray-300">
                    <input type="text" id="brand_color_text" value="{{ old('brand_color', $settings->brand_color) }}"
                        oninput="document.getElementById('brand_color').value = this.value"
                        class="w-32 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                    <span id="contrast-badge" class="rounded-full px-3 py-1 text-xs font-bold"></span>
                    <button type="button" id="contrast-fix-button" hidden
                        class="rounded border border-brand px-3 py-1 text-xs font-bold text-brand hover:bg-brand-light">
                        Dostosuj automatycznie
                    </button>
                </div>
                <p class="mt-1 text-xs text-muted">Ciemniejszy i jaśniejszy odcień zostaną obliczone automatycznie. Kontrast liczony jest wobec bieli — tak jak kolor jest używany na przyciskach i linkach.</p>
                @error('brand_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-1 text-sm font-bold">Dodatkowe kolory marki (identyfikacja)</p>
                <p class="mb-3 text-xs text-muted">Kolory 2–4 z identyfikacji wizualnej, korespondujące z kolorem przewodnim. Dostępne w kodzie jako <code class="rounded bg-gray-100 px-1">brand-2/3/4</code> i użyte jako pasek akcentu pod nagłówkiem. Puste = niewykorzystane. Zbyt jasny kolor zostanie przyciemniony dla kontrastu (WCAG).</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([2, 3, 4] as $n)
                        @php $val = old('brand_color_'.$n, $settings->{'brand_color_'.$n}); @endphp
                        <div>
                            <label for="brand_color_{{ $n }}_text" class="mb-1 block text-xs font-bold text-muted">Kolor {{ $n }}</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="brand_color_{{ $n }}_picker" value="{{ $val ?: $settings->brand_color }}"
                                    oninput="document.getElementById('brand_color_{{ $n }}_text').value = this.value"
                                    class="h-10 w-12 flex-none rounded border-gray-300" aria-label="Wybierz kolor {{ $n }} marki">
                                <input type="text" id="brand_color_{{ $n }}_text" name="brand_color_{{ $n }}" value="{{ $val }}"
                                    placeholder="#RRGGBB" pattern="^#[0-9a-fA-F]{6}$"
                                    oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('brand_color_{{ $n }}_picker').value = this.value"
                                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            </div>
                            @error('brand_color_'.$n) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <label for="ngo_color_text" class="mb-1 block text-sm font-bold">Kolor submarki „NGO” <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="color" id="ngo_color_picker" value="{{ old('ngo_color', $settings->ngo_color ?: '#1f6feb') }}"
                        oninput="document.getElementById('ngo_color_text').value = this.value"
                        class="h-10 w-16 rounded border-gray-300" aria-label="Wybierz kolor NGO">
                    <input type="text" id="ngo_color_text" name="ngo_color" value="{{ old('ngo_color', $settings->ngo_color) }}"
                        placeholder="np. #1f6feb — puste = brak"
                        oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('ngo_color_picker').value = this.value"
                        class="w-48 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                </div>
                <p class="mt-1 text-xs text-muted">Wbudowana submarka „NGO”, wybierana przy projektach i aktualnościach. Zostaw puste, aby używać koloru przewodniego.</p>
                @error('ngo_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-5" data-subbrands>
                <p class="mb-1 text-sm font-bold">Kolory submarek</p>
                <p class="mb-3 text-xs text-muted">Zdefiniuj nazwane kolory dla różnych rodzajów treści (np. „Seniorzy”, „Szkoły”, „Wolontariat”). Pojawią się do wyboru w polu „Grupa docelowa (kolorystyka)” przy projektach i aktualnościach. Puste wiersze są pomijane.</p>
                <div data-subbrands-rows class="space-y-2">
                    @foreach ($subBrands as $i => $sb)
                        <div data-subbrands-row class="flex flex-wrap items-center gap-2">
                            <input type="color" value="{{ $sb['color'] ?? '#1f6feb' }}" oninput="this.nextElementSibling.value = this.value"
                                class="h-10 w-14 flex-none rounded border-gray-300" aria-label="Kolor submarki {{ $i + 1 }}">
                            <input type="text" name="sub_brands[{{ $i }}][color]" value="{{ $sb['color'] ?? '' }}" placeholder="#1f6feb"
                                oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) this.previousElementSibling.value = this.value"
                                class="w-32 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand" aria-label="Kod koloru submarki {{ $i + 1 }}">
                            <input type="text" name="sub_brands[{{ $i }}][name]" value="{{ $sb['name'] ?? '' }}" placeholder="Nazwa, np. Seniorzy"
                                class="min-w-0 flex-1 rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Nazwa submarki {{ $i + 1 }}">
                            <button type="button" data-subbrands-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń submarkę {{ $i + 1 }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                        </div>
                    @endforeach
                </div>
                <button type="button" data-subbrands-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus"></i> Dodaj kolor submarki</button>
                <template data-subbrands-template>
                    <div data-subbrands-row class="flex flex-wrap items-center gap-2">
                        <input type="color" value="#1f6feb" oninput="this.nextElementSibling.value = this.value"
                            class="h-10 w-14 flex-none rounded border-gray-300" aria-label="Kolor submarki">
                        <input type="text" name="sub_brands[__INDEX__][color]" placeholder="#1f6feb"
                            oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) this.previousElementSibling.value = this.value"
                            class="w-32 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand" aria-label="Kod koloru submarki">
                        <input type="text" name="sub_brands[__INDEX__][name]" placeholder="Nazwa, np. Seniorzy"
                            class="min-w-0 flex-1 rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Nazwa submarki">
                        <button type="button" data-subbrands-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń submarkę"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="tab === 'maintenance'" x-cloak class="space-y-6" x-data="{ maintenance: {{ old('maintenance_mode', $settings->maintenance_mode) ? 'true' : 'false' }} }">
            <div>
                <label for="site_url" class="mb-1 block text-sm font-bold">Główny adres strony (Site URL)</label>
                <input type="url" id="site_url" name="site_url" value="{{ old('site_url', $settings->site_url) }}"
                    placeholder="{{ config('app.url') }}"
                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Adres używany do budowania linków bezwzględnych (mapa strony, e-maile, powiadomienia, adres powrotny logowania Microsoft). Puste = wartość <code>APP_URL</code> z pliku <code>.env</code>. Zmieniaj tylko, gdy wiesz, że domena serwisu jest inna niż wykrywana automatycznie.</p>
                @error('site_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-6">
                <h2 class="text-base font-bold text-ink">Tryb konserwacji (przerwa techniczna)</h2>
                <p class="mt-1 text-xs text-muted">Po włączeniu zwykli użytkownicy zobaczą komunikat o przerwie technicznej. Zalogowani administratorzy dalej mają dostęp do panelu i strony, aby móc wyłączyć tryb.</p>

                <label class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm">
                    <input type="hidden" name="maintenance_mode" value="0">
                    <input type="checkbox" name="maintenance_mode" value="1" x-model="maintenance"
                        @checked(old('maintenance_mode', $settings->maintenance_mode))
                        class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span>
                        <span class="font-bold text-amber-900">Włącz tryb konserwacji</span>
                        <span class="block text-xs text-amber-800">Strona publiczna zostanie zastąpiona komunikatem o przerwie technicznej (HTTP 503).</span>
                    </span>
                </label>

                <div class="mt-4" x-show="maintenance" x-cloak>
                    <label for="maintenance_message" class="mb-1 block text-sm font-bold">Komunikat dla użytkowników</label>
                    <textarea id="maintenance_message" name="maintenance_message" rows="3"
                        placeholder="{{ \App\Models\SiteSetting::DEFAULT_MAINTENANCE_MESSAGE }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('maintenance_message', $settings->maintenance_message) }}</textarea>
                    <p class="mt-1 text-xs text-muted">Puste = komunikat domyślny (widoczny jako podpowiedź).</p>
                    @error('maintenance_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <p class="mt-4 flex items-start gap-2 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800" x-show="maintenance" x-cloak>
                    <i class="fa-solid fa-shield-halved mt-0.5" aria-hidden="true"></i>
                    <span>W trybie konserwacji <strong>logowanie przez Microsoft 365 jest całkowicie zablokowane</strong>. Zaloguj się loginem i hasłem, aby wyłączyć tryb.</span>
                </p>
            </div>
        </div>

        <div x-show="tab === 'seo'" x-cloak class="space-y-6">
            <div class="mb-5">
                <label for="meta_description" class="mb-1 block text-sm font-bold">Domyślny opis strony (meta description)</label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="300"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('meta_description', $settings->meta_description) }}</textarea>
                <p class="mt-1 text-xs text-muted">Używany, gdy strona/artykuł nie ma własnego opisu. Widoczny w wynikach Google i podglądach linków.</p>
                @error('meta_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mb-5">
                <p class="mb-1 text-sm font-bold">Domyślny obrazek udostępniania (Open Graph)</p>
                @if ($settings->ogImageUrl())
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ $settings->ogImageUrl() }}" alt="Obrazek OG" class="h-14 w-24 rounded object-cover">
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remove_og_image" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                            Usuń obrazek
                        </label>
                    </div>
                @endif
                <input type="file" name="og_image" accept="image/*" class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                <p class="mt-1 text-xs text-muted">Pojawia się w podglądzie linku na Facebooku, LinkedInie i Messengerze (zalecane 1200×630 px).</p>
                @error('og_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="allow_indexing" value="1" {{ old('allow_indexing', $settings->allow_indexing) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                Zezwól wyszukiwarkom na indeksowanie strony
            </label>
            <p class="mt-1 text-xs text-muted">Odznacz, aby ukryć całą stronę przed Google (np. na czas prac testowych).</p>

            <div class="mt-5 border-t border-gray-100 pt-5">
                <label for="ga_measurement_id" class="mb-1 block text-sm font-bold">Google Analytics 4 — identyfikator pomiaru</label>
                <input type="text" id="ga_measurement_id" name="ga_measurement_id"
                    value="{{ old('ga_measurement_id', $settings->ga_measurement_id) }}"
                    placeholder="G-XXXXXXXXXX" pattern="G-[A-Za-z0-9]+"
                    class="w-full max-w-xs rounded border-gray-300 font-mono focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">
                    Zostaw puste, aby wyłączyć. Kod śledzenia ładuje się dopiero po akceptacji cookies (RODO), z anonimizacją IP.
                </p>
                @error('ga_measurement_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div x-show="tab === 'registry'" x-cloak>
            <p class="mb-4 text-xs text-muted">Wyświetlane w stopce strony. Puste pola zostają ukryte.</p>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="krs_number" class="mb-1 block text-sm font-bold">Numer KRS</label>
                    <input type="text" id="krs_number" name="krs_number" value="{{ old('krs_number', $settings->krs_number) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('krs_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nip_number" class="mb-1 block text-sm font-bold">NIP</label>
                    <input type="text" id="nip_number" name="nip_number" value="{{ old('nip_number', $settings->nip_number) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('nip_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="regon_number" class="mb-1 block text-sm font-bold">REGON</label>
                    <input type="text" id="regon_number" name="regon_number" value="{{ old('regon_number', $settings->regon_number) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('regon_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div x-show="tab === 'accessibility'" x-cloak class="space-y-6">
            <p class="text-xs text-muted">Zmienne części <a href="{{ route('accessibility.show') }}" target="_blank" rel="noopener" class="text-brand underline">deklaracji dostępności</a>. Stały, prawny szkielet deklaracji jest wbudowany — tu uzupełniasz tylko dane podmiotu, status i kontakt do zgłoszeń barier.</p>

            <div>
                <label for="accessibility_entity_name" class="mb-1 block text-sm font-bold">Nazwa podmiotu</label>
                <input type="text" id="accessibility_entity_name" name="accessibility_entity_name" value="{{ old('accessibility_entity_name', $settings->accessibility_entity_name) }}" placeholder="{{ $settings->site_name }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Puste = nazwa strony ({{ $settings->site_name }}).</p>
                @error('accessibility_entity_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="accessibility_status" class="mb-1 block text-sm font-bold">Status zgodności z ustawą</label>
                <select id="accessibility_status" name="accessibility_status" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @foreach (\App\Models\SiteSetting::ACCESSIBILITY_STATUSES as $value => $label)
                        <option value="{{ $value }}" {{ old('accessibility_status', $settings->accessibility_status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('accessibility_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="accessibility_status_note" class="mb-1 block text-sm font-bold">Uzasadnienie / wyłączenia <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <textarea id="accessibility_status_note" name="accessibility_status_note" rows="4" placeholder="Np. które elementy nie są jeszcze dostępne i dlaczego."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('accessibility_status_note', $settings->accessibility_status_note) }}</textarea>
                @error('accessibility_status_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="accessibility_page_published_at" class="mb-1 block text-sm font-bold">Data publikacji strony</label>
                    <input type="date" id="accessibility_page_published_at" name="accessibility_page_published_at" value="{{ old('accessibility_page_published_at', $settings->accessibility_page_published_at?->format('Y-m-d')) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('accessibility_page_published_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="accessibility_page_updated_at" class="mb-1 block text-sm font-bold">Ostatnia istotna aktualizacja</label>
                    <input type="date" id="accessibility_page_updated_at" name="accessibility_page_updated_at" value="{{ old('accessibility_page_updated_at', $settings->accessibility_page_updated_at?->format('Y-m-d')) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('accessibility_page_updated_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="accessibility_declaration_date" class="mb-1 block text-sm font-bold">Data sporządzenia deklaracji</label>
                    <input type="date" id="accessibility_declaration_date" name="accessibility_declaration_date" value="{{ old('accessibility_declaration_date', $settings->accessibility_declaration_date?->format('Y-m-d')) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('accessibility_declaration_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="accessibility_review_method" class="mb-1 block text-sm font-bold">Sposób sporządzenia deklaracji</label>
                <select id="accessibility_review_method" name="accessibility_review_method" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @foreach (\App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS as $value => $label)
                        <option value="{{ $value }}" {{ old('accessibility_review_method', $settings->accessibility_review_method) === $value ? 'selected' : '' }}>{{ ucfirst($label) }}</option>
                    @endforeach
                </select>
                @error('accessibility_review_method') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="accessibility_contact_name" class="mb-1 block text-sm font-bold">Osoba do kontaktu</label>
                    <input type="text" id="accessibility_contact_name" name="accessibility_contact_name" value="{{ old('accessibility_contact_name', $settings->accessibility_contact_name) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('accessibility_contact_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="accessibility_contact_email" class="mb-1 block text-sm font-bold">E-mail do zgłoszeń</label>
                    <input type="email" id="accessibility_contact_email" name="accessibility_contact_email" value="{{ old('accessibility_contact_email', $settings->accessibility_contact_email) }}" placeholder="{{ $settings->contact_email }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Puste = ogólny e-mail kontaktowy. Tu trafiają zgłoszenia barier.</p>
                    @error('accessibility_contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="accessibility_contact_phone" class="mb-1 block text-sm font-bold">Telefon do zgłoszeń</label>
                    <input type="text" id="accessibility_contact_phone" name="accessibility_contact_phone" value="{{ old('accessibility_contact_phone', $settings->accessibility_contact_phone) }}" placeholder="{{ $settings->contact_phone }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('accessibility_contact_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="accessibility_architectural" class="mb-1 block text-sm font-bold">Dostępność architektoniczna <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <textarea id="accessibility_architectural" name="accessibility_architectural" rows="5" placeholder="Opis dostępności budynku/siedziby: wejście, windy, toalety, miejsca parkingowe, pętla indukcyjna itp."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('accessibility_architectural', $settings->accessibility_architectural) }}</textarea>
                @error('accessibility_architectural') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div x-show="tab === 'support'" x-cloak class="space-y-5">
            @php $sd = \App\Models\SiteSetting::SUPPORT_DEFAULTS; @endphp
            <p class="text-xs text-muted">Wyświetlane na podstronie <a href="{{ route('support.show') }}" target="_blank" rel="noopener" class="text-brand underline">/wsparcie</a>. Puste pola pokazują tekst domyślny (widoczny jako podpowiedź).</p>

            <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <p class="text-sm font-bold text-ink">Zbiórka na cele FEER</p>
                <p class="-mt-2 text-xs text-muted">Podaj tytuł i cel (kwotę), aby na stronie pojawił się pasek postępu zbiórki. Pusty cel = brak bloku.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="support_fundraiser_title" class="mb-1 block text-sm font-bold">Tytuł zbiórki</label>
                        <input type="text" id="support_fundraiser_title" name="support_fundraiser_title" value="{{ old('support_fundraiser_title', $settings->support_fundraiser_title) }}"
                            placeholder="np. Wyposażenie pracowni cyfrowej" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_fundraiser_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_fundraiser_url" class="mb-1 block text-sm font-bold">Link do wpłaty na zbiórkę</label>
                        <input type="text" id="support_fundraiser_url" name="support_fundraiser_url" value="{{ old('support_fundraiser_url', $settings->support_fundraiser_url) }}"
                            placeholder="np. https://wplacam.ngo.pl/..." class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_fundraiser_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="support_fundraiser_text" class="mb-1 block text-sm font-bold">Opis <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <textarea id="support_fundraiser_text" name="support_fundraiser_text" rows="2"
                        placeholder="Na co zbieramy i dlaczego to ważne." class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_fundraiser_text', $settings->support_fundraiser_text) }}</textarea>
                    @error('support_fundraiser_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="support_fundraiser_goal" class="mb-1 block text-sm font-bold">Cel (zł)</label>
                        <input type="number" id="support_fundraiser_goal" name="support_fundraiser_goal" value="{{ old('support_fundraiser_goal', $settings->support_fundraiser_goal) }}" min="0"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_fundraiser_goal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_fundraiser_raised" class="mb-1 block text-sm font-bold">Zebrano (zł)</label>
                        <input type="number" id="support_fundraiser_raised" name="support_fundraiser_raised" value="{{ old('support_fundraiser_raised', $settings->support_fundraiser_raised) }}" min="0"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Aktualizuj ręcznie.</p>
                        @error('support_fundraiser_raised') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_fundraiser_cta_label" class="mb-1 block text-sm font-bold">Tekst przycisku</label>
                        <input type="text" id="support_fundraiser_cta_label" name="support_fundraiser_cta_label" value="{{ old('support_fundraiser_cta_label', $settings->support_fundraiser_cta_label) }}"
                            placeholder="Wesprzyj zbiórkę" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_fundraiser_cta_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <p class="text-sm font-bold text-ink">Social proof — cytat</p>
                <div>
                    <label for="support_testimonial_quote" class="mb-1 block text-sm font-bold">Cytat <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <textarea id="support_testimonial_quote" name="support_testimonial_quote" rows="2"
                        placeholder="np. „Dzięki FEER mama nauczyła się rozmawiać z wnukami przez wideo.”" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_testimonial_quote', $settings->support_testimonial_quote) }}</textarea>
                    @error('support_testimonial_quote') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="support_testimonial_author" class="mb-1 block text-sm font-bold">Autor</label>
                        <input type="text" id="support_testimonial_author" name="support_testimonial_author" value="{{ old('support_testimonial_author', $settings->support_testimonial_author) }}"
                            placeholder="np. Anna, uczestniczka warsztatów" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_testimonial_author') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_testimonial_role" class="mb-1 block text-sm font-bold">Rola / kontekst</label>
                        <input type="text" id="support_testimonial_role" name="support_testimonial_role" value="{{ old('support_testimonial_role', $settings->support_testimonial_role) }}"
                            placeholder="np. darczyńca, wolontariuszka" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_testimonial_role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <input type="checkbox" name="support_show_partners" value="1" {{ old('support_show_partners', $settings->support_show_partners) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-sm font-bold">Pokaż logotypy partnerów („Zaufali nam") na stronie wsparcia</span>
            </label>

            <div>
                <p class="mb-1 text-sm font-bold">Zdjęcie nagłówka <span class="font-normal text-muted">(opcjonalnie)</span></p>
                @if ($settings->supportImageUrl())
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ $settings->supportImageUrl() }}" alt="Zdjęcie nagłówka strony wsparcia" class="h-20 w-32 rounded object-cover">
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remove_support_image" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                            Usuń zdjęcie
                        </label>
                    </div>
                @endif
                <input type="file" name="support_image" accept="image/*" class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                <p class="mt-1 text-xs text-muted">Wyświetlane jako duże zdjęcie w tle nagłówka strony (zalecane min. 1600×500 px).</p>
                @error('support_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-1 text-sm font-bold">Galeria „działamy" (osobna od głównej galerii)</p>
                <p class="mb-3 text-xs text-muted">Zdjęcia w mozaikowym kolażu na stronie wsparcia — dowód realnych działań. Wyświetlanych jest do 7 pierwszych.</p>

                @if ($settings->supportGalleryImages()->isNotEmpty())
                    <div class="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($settings->supportGalleryImages() as $media)
                            <label class="group relative block cursor-pointer overflow-hidden rounded-lg border border-gray-200">
                                <img src="{{ $media->getUrl() }}" alt="" class="h-24 w-full object-cover">
                                <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1.5 bg-black/60 py-1 text-xs font-bold text-white opacity-0 transition group-hover:opacity-100">
                                    <input type="checkbox" name="remove_support_gallery[]" value="{{ $media->id }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Usuń
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mb-3 text-xs text-muted">Zaznacz zdjęcia do usunięcia i zapisz.</p>
                @endif

                <input type="file" name="support_gallery[]" accept="image/*" multiple
                    class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                <p class="mt-1 text-xs text-muted">Możesz wybrać kilka zdjęć naraz. Dodane pojawią się po zapisaniu.</p>
                @error('support_gallery.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold">Nagłówek strony</p>
                <div class="space-y-4">
                    <div>
                        <label for="support_hero_badge" class="mb-1 block text-sm font-bold">Etykieta (badge)</label>
                        <input type="text" id="support_hero_badge" name="support_hero_badge" value="{{ old('support_hero_badge', $settings->support_hero_badge) }}"
                            placeholder="{{ $sd['support_hero_badge'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_hero_badge') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_hero_title" class="mb-1 block text-sm font-bold">Tytuł (H1)</label>
                        <input type="text" id="support_hero_title" name="support_hero_title" value="{{ old('support_hero_title', $settings->support_hero_title) }}"
                            placeholder="{{ $sd['support_hero_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_hero_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_hero_subtitle" class="mb-1 block text-sm font-bold">Podtytuł</label>
                        <textarea id="support_hero_subtitle" name="support_hero_subtitle" rows="2"
                            placeholder="{{ $sd['support_hero_subtitle'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_hero_subtitle', $settings->support_hero_subtitle) }}</textarea>
                        @error('support_hero_subtitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_hero_cta_label" class="mb-1 block text-sm font-bold">Tekst przycisku „Wpłać teraz”</label>
                        <input type="text" id="support_hero_cta_label" name="support_hero_cta_label" value="{{ old('support_hero_cta_label', $settings->support_hero_cta_label) }}"
                            placeholder="{{ $sd['support_hero_cta_label'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Przycisk pojawia się tylko, gdy ustawiono link do szybkiego przelewu (poniżej).</p>
                        @error('support_hero_cta_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold">Sekcja „Dlaczego warto nas wspierać”</p>
                <div class="space-y-4">
                    <div>
                        <label for="support_benefits_title" class="mb-1 block text-sm font-bold">Tytuł sekcji</label>
                        <input type="text" id="support_benefits_title" name="support_benefits_title" value="{{ old('support_benefits_title', $settings->support_benefits_title) }}"
                            placeholder="{{ $sd['support_benefits_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_benefits_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_benefits_subtitle" class="mb-1 block text-sm font-bold">Opis sekcji</label>
                        <textarea id="support_benefits_subtitle" name="support_benefits_subtitle" rows="2"
                            placeholder="{{ $sd['support_benefits_subtitle'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_benefits_subtitle', $settings->support_benefits_subtitle) }}</textarea>
                        @error('support_benefits_subtitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @foreach (['1', '2', '3'] as $i)
                        <div class="rounded border border-gray-200 p-4">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Karta {{ $i }}</p>
                            <div class="grid gap-3 sm:grid-cols-[10rem_1fr]">
                                <div>
                                    <label for="support_benefit{{ $i }}_icon" class="mb-1 block text-sm font-bold">Ikona</label>
                                    <input type="text" id="support_benefit{{ $i }}_icon" name="support_benefit{{ $i }}_icon" value="{{ old('support_benefit'.$i.'_icon', $settings->{'support_benefit'.$i.'_icon'}) }}"
                                        placeholder="{{ $sd['support_benefit'.$i.'_icon'] }}" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                                    <p class="mt-1 text-xs text-muted">Klasa <a href="https://fontawesome.com/search?o=r&m=free" target="_blank" rel="noopener" class="text-brand underline">Font Awesome</a>.</p>
                                    @error('support_benefit'.$i.'_icon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="support_benefit{{ $i }}_title" class="mb-1 block text-sm font-bold">Tytuł</label>
                                    <input type="text" id="support_benefit{{ $i }}_title" name="support_benefit{{ $i }}_title" value="{{ old('support_benefit'.$i.'_title', $settings->{'support_benefit'.$i.'_title'}) }}"
                                        placeholder="{{ $sd['support_benefit'.$i.'_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                    @error('support_benefit'.$i.'_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="mt-3">
                                <label for="support_benefit{{ $i }}_text" class="mb-1 block text-sm font-bold">Opis</label>
                                <textarea id="support_benefit{{ $i }}_text" name="support_benefit{{ $i }}_text" rows="2"
                                    placeholder="{{ $sd['support_benefit'.$i.'_text'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_benefit'.$i.'_text', $settings->{'support_benefit'.$i.'_text'}) }}</textarea>
                                @error('support_benefit'.$i.'_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <label for="support_methods_title" class="mb-1 block text-sm font-bold">Tytuł sekcji „Jak możesz pomóc”</label>
                <input type="text" id="support_methods_title" name="support_methods_title" value="{{ old('support_methods_title', $settings->support_methods_title) }}"
                    placeholder="{{ $sd['support_methods_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('support_methods_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="editor-support_intro" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(opcjonalnie)</span></label>
                @include('admin.partials.editor', ['name' => 'support_intro', 'value' => old('support_intro', $settings->support_intro)])
                @error('support_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold">1. Darowizna na cele statutowe</p>
                <div class="space-y-4">
                    <div>
                        <label for="support_method1_title" class="mb-1 block text-sm font-bold">Tytuł karty</label>
                        <input type="text" id="support_method1_title" name="support_method1_title" value="{{ old('support_method1_title', $settings->support_method1_title) }}"
                            placeholder="{{ $sd['support_method1_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_method1_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="bank_account_number" class="mb-1 block text-sm font-bold">Numer konta <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $settings->bank_account_number) }}"
                                placeholder="PL00 0000 0000 0000 0000 0000 0000"
                                class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            @error('bank_account_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <label for="support_method1_account_label" class="mb-1 mt-2 block text-xs font-bold text-muted">Etykieta pola</label>
                            <input type="text" id="support_method1_account_label" name="support_method1_account_label" value="{{ old('support_method1_account_label', $settings->support_method1_account_label) }}"
                                placeholder="{{ $sd['support_method1_account_label'] }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('support_method1_account_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="bank_account_tax_number" class="mb-1 block text-sm font-bold">Konto na 1,5% podatku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="bank_account_tax_number" name="bank_account_tax_number" value="{{ old('bank_account_tax_number', $settings->bank_account_tax_number) }}"
                                placeholder="zostaw puste, jeśli takie samo jak wyżej"
                                class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            @error('bank_account_tax_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <label for="support_method1_tax_label" class="mb-1 mt-2 block text-xs font-bold text-muted">Etykieta pola</label>
                            <input type="text" id="support_method1_tax_label" name="support_method1_tax_label" value="{{ old('support_method1_tax_label', $settings->support_method1_tax_label) }}"
                                placeholder="{{ $sd['support_method1_tax_label'] }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('support_method1_tax_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="support_transfer_title" class="mb-1 block text-sm font-bold">Tytuł przelewu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="support_transfer_title" name="support_transfer_title" value="{{ old('support_transfer_title', $settings->support_transfer_title) }}"
                            placeholder="{{ $sd['support_transfer_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Sugerowany tytuł przelewu dla darczyńcy (z przyciskiem „Kopiuj tytuł"). Zostaw puste, aby ukryć.</p>
                        @error('support_transfer_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <label for="support_method1_transfer_label" class="mb-1 mt-2 block text-xs font-bold text-muted">Etykieta pola</label>
                        <input type="text" id="support_method1_transfer_label" name="support_method1_transfer_label" value="{{ old('support_method1_transfer_label', $settings->support_method1_transfer_label) }}"
                            placeholder="{{ $sd['support_method1_transfer_label'] }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        @error('support_method1_transfer_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold">2. Szybki przelew</p>
                <div class="space-y-4">
                    <div>
                        <label for="support_method2_title" class="mb-1 block text-sm font-bold">Tytuł karty</label>
                        <input type="text" id="support_method2_title" name="support_method2_title" value="{{ old('support_method2_title', $settings->support_method2_title) }}"
                            placeholder="{{ $sd['support_method2_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_method2_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_method2_text" class="mb-1 block text-sm font-bold">Opis</label>
                        <textarea id="support_method2_text" name="support_method2_text" rows="2"
                            placeholder="{{ $sd['support_method2_text'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_method2_text', $settings->support_method2_text) }}</textarea>
                        @error('support_method2_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_quick_transfer_url" class="mb-1 block text-sm font-bold">Link do szybkiego przelewu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="support_quick_transfer_url" name="support_quick_transfer_url" value="{{ old('support_quick_transfer_url', $settings->support_quick_transfer_url) }}"
                            placeholder="np. link do Przelewy24, Tpay lub PayPal.me"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_quick_transfer_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_method2_cta_label" class="mb-1 block text-sm font-bold">Tekst przycisku</label>
                        <input type="text" id="support_method2_cta_label" name="support_method2_cta_label" value="{{ old('support_method2_cta_label', $settings->support_method2_cta_label) }}"
                            placeholder="{{ $sd['support_method2_cta_label'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_method2_cta_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold">3. BuyCoffee</p>
                <div class="space-y-4">
                    <div>
                        <label for="support_method3_title" class="mb-1 block text-sm font-bold">Tytuł karty</label>
                        <input type="text" id="support_method3_title" name="support_method3_title" value="{{ old('support_method3_title', $settings->support_method3_title) }}"
                            placeholder="{{ $sd['support_method3_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_method3_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_method3_text" class="mb-1 block text-sm font-bold">Opis</label>
                        <textarea id="support_method3_text" name="support_method3_text" rows="2"
                            placeholder="{{ $sd['support_method3_text'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_method3_text', $settings->support_method3_text) }}</textarea>
                        @error('support_method3_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_buycoffee_url" class="mb-1 block text-sm font-bold">Link do profilu BuyCoffee <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="support_buycoffee_url" name="support_buycoffee_url" value="{{ old('support_buycoffee_url', $settings->support_buycoffee_url) }}"
                            placeholder="np. https://buycoffee.to/nazwa"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_buycoffee_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_method3_cta_label" class="mb-1 block text-sm font-bold">Tekst przycisku</label>
                        <input type="text" id="support_method3_cta_label" name="support_method3_cta_label" value="{{ old('support_method3_cta_label', $settings->support_method3_cta_label) }}"
                            placeholder="{{ $sd['support_method3_cta_label'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_method3_cta_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold">4. wpłacam.ngo.pl</p>
                <div class="space-y-4">
                    <div>
                        <label for="support_method4_title" class="mb-1 block text-sm font-bold">Tytuł karty</label>
                        <input type="text" id="support_method4_title" name="support_method4_title" value="{{ old('support_method4_title', $settings->support_method4_title) }}"
                            placeholder="{{ $sd['support_method4_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_method4_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_method4_text" class="mb-1 block text-sm font-bold">Opis</label>
                        <textarea id="support_method4_text" name="support_method4_text" rows="2"
                            placeholder="{{ $sd['support_method4_text'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('support_method4_text', $settings->support_method4_text) }}</textarea>
                        @error('support_method4_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_wplacam_url" class="mb-1 block text-sm font-bold">Link do wpłaty (wpłacam.ngo.pl) <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="support_wplacam_url" name="support_wplacam_url" value="{{ old('support_wplacam_url', $settings->support_wplacam_url) }}"
                            placeholder="np. https://wplacam.ngo.pl/wesprzyj/nazwa"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Karta pojawi się na stronie /wsparcie po podaniu linku.</p>
                        @error('support_wplacam_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_method4_cta_label" class="mb-1 block text-sm font-bold">Tekst przycisku</label>
                        <input type="text" id="support_method4_cta_label" name="support_method4_cta_label" value="{{ old('support_method4_cta_label', $settings->support_method4_cta_label) }}"
                            placeholder="{{ $sd['support_method4_cta_label'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_method4_cta_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-3 text-sm font-bold">Ramka na dole strony</p>
                <div class="space-y-4">
                    <div>
                        <label for="support_outro_title" class="mb-1 block text-sm font-bold">Tytuł</label>
                        <input type="text" id="support_outro_title" name="support_outro_title" value="{{ old('support_outro_title', $settings->support_outro_title) }}"
                            placeholder="{{ $sd['support_outro_title'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_outro_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support_outro_subtitle" class="mb-1 block text-sm font-bold">Podtytuł</label>
                        <input type="text" id="support_outro_subtitle" name="support_outro_subtitle" value="{{ old('support_outro_subtitle', $settings->support_outro_subtitle) }}"
                            placeholder="{{ $sd['support_outro_subtitle'] }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('support_outro_subtitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'contact'" x-cloak>
            <p class="mb-4 text-xs text-muted">Wyświetlane w sekcji „Kontakt” na dole strony głównej oraz na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>.</p>

            <div class="mb-4">
                <label for="editor-contact_intro" class="mb-1 block text-sm font-bold">Wstęp na stronie kontakt <span class="font-normal text-muted">(opcjonalnie)</span></label>
                @include('admin.partials.editor', ['name' => 'contact_intro', 'value' => old('contact_intro', $settings->contact_intro)])
                @error('contact_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="mb-4 flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                <input type="hidden" name="show_coordinators" value="0">
                <input type="checkbox" name="show_coordinators" value="1" @checked(old('show_coordinators', $settings->show_coordinators)) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                <span>
                    <span class="font-bold">Pokazuj koordynatorów projektów</span>
                    <span class="block text-xs text-muted">Główny wyłącznik danych koordynatorów na stronie „Kontakt” oraz na stronach projektów. Poszczególne projekty mają dodatkowo własny przełącznik.</span>
                </span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact_address" class="mb-1 block text-sm font-bold">Adres (ulica i numer)</label>
                    <input type="text" id="contact_address" name="contact_address" value="{{ old('contact_address', $settings->contact_address) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_city" class="mb-1 block text-sm font-bold">Kod pocztowy i miasto</label>
                    <input type="text" id="contact_city" name="contact_city" value="{{ old('contact_city', $settings->contact_city) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_email" class="mb-1 block text-sm font-bold">E-mail kontaktowy</label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_phone" class="mb-1 block text-sm font-bold">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" placeholder="np. +48 123 456 789"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="contact_edelivery_address" class="mb-1 block text-sm font-bold">Adres do e-Doręczeń <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="contact_edelivery_address" name="contact_edelivery_address" value="{{ old('contact_edelivery_address', $settings->contact_edelivery_address) }}"
                    placeholder="AE:PL-12345-67890-ABCDE-12"
                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Adres do doręczeń elektronicznych (ADE). Pokaże się w danych kontaktowych na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>. Zostaw puste, aby ukryć.</p>
                @error('contact_edelivery_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Przesyłki: paczka / list / paczkomat --}}
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <div>
                    <p class="text-sm font-bold text-ink">Przesyłki (paczka, list, paczkomat)</p>
                    <p class="mt-0.5 text-xs text-muted">Informacja na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a> o nadawaniu do nas przesyłek. Zostaw pola puste, aby ukryć cały blok.</p>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="contact_shipping_visible" value="1" {{ old('contact_shipping_visible', $settings->contact_shipping_visible ?? true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Pokaż sekcję „Wyślij do nas przesyłkę" na stronie kontaktu</span>
                </label>

                <div>
                    <label for="contact_shipping_note" class="mb-1 block text-sm font-bold">Tekst wstępny <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_shipping_note" name="contact_shipping_note" value="{{ old('contact_shipping_note', $settings->contact_shipping_note) }}"
                        placeholder="np. Możesz nadać do nas paczkę lub list — również na paczkomat."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_shipping_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded border border-gray-200 bg-white p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Paczkomat InPost</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="contact_paczkomat_code" class="mb-1 block text-sm font-bold">Kod paczkomatu</label>
                            <input type="text" id="contact_paczkomat_code" name="contact_paczkomat_code" value="{{ old('contact_paczkomat_code', $settings->contact_paczkomat_code) }}"
                                placeholder="np. NSA22M" class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            @error('contact_paczkomat_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact_shipping_phone" class="mb-1 block text-sm font-bold">Telefon do przesyłki</label>
                            <input type="text" id="contact_shipping_phone" name="contact_shipping_phone" value="{{ old('contact_shipping_phone', $settings->contact_shipping_phone) }}"
                                placeholder="np. 601 350 487" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('contact_shipping_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="contact_paczkomat_address" class="mb-1 block text-sm font-bold">Adres paczkomatu</label>
                            <input type="text" id="contact_paczkomat_address" name="contact_paczkomat_address" value="{{ old('contact_paczkomat_address', $settings->contact_paczkomat_address) }}"
                                placeholder="np. Barbackiego 81, 33-300 Nowy Sącz" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('contact_paczkomat_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="contact_paczkomat_location" class="mb-1 block text-sm font-bold">Lokalizacja / wskazówka dojścia <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="contact_paczkomat_location" name="contact_paczkomat_location" value="{{ old('contact_paczkomat_location', $settings->contact_paczkomat_location) }}"
                                placeholder="np. Boczna ściana sklepu przy parkingu" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('contact_paczkomat_location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rachunki bankowe pokazywane na podstronie /kontakt --}}
            @php
                $bankAccounts = old('contact_bank_accounts', $settings->contact_bank_accounts ?: []);
                if (empty($bankAccounts)) {
                    $bankAccounts = [['number' => '', 'purpose' => '']];
                }
            @endphp
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5"
                x-data="{ accounts: {{ \Illuminate\Support\Js::from(array_values($bankAccounts)) }} }">
                <div>
                    <p class="text-sm font-bold text-ink">Numery rachunków bankowych</p>
                    <p class="mt-0.5 text-xs text-muted">Lista rachunków pokazywana na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>. Przy każdym rachunku podaj opis — do czego służy i co można na niego wpłacić. Zostaw numer pusty, aby usunąć wiersz przy zapisie.</p>
                </div>

                <template x-for="(account, index) in accounts" :key="index">
                    <div class="rounded border border-gray-200 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted" x-text="'Rachunek ' + (index + 1)"></p>
                            <button type="button" @click="accounts.splice(index, 1)"
                                class="inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Usuń
                            </button>
                        </div>
                        <div class="mt-3 space-y-3">
                            <div>
                                <label :for="'contact_bank_account_number_' + index" class="mb-1 block text-sm font-bold">Numer konta</label>
                                <input type="text" :id="'contact_bank_account_number_' + index"
                                    :name="'contact_bank_accounts[' + index + '][number]'" x-model="account.number"
                                    placeholder="PL00 0000 0000 0000 0000 0000 0000"
                                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label :for="'contact_bank_account_purpose_' + index" class="mb-1 block text-sm font-bold">Do czego służy / co można wpłacić</label>
                                <textarea :id="'contact_bank_account_purpose_' + index"
                                    :name="'contact_bank_accounts[' + index + '][purpose]'" x-model="account.purpose" rows="2"
                                    placeholder="np. Darowizny na cele statutowe — wsparcie bieżących działań fundacji"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></textarea>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="accounts.push({ number: '', purpose: '' })"
                    class="inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand hover:text-white">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj rachunek
                </button>
                @error('contact_bank_accounts') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Sekcja „Spotkajmy się”: online (zalecane) + harmonogram stacjonarny --}}
            @php
                $schedule = old('contact_schedule', $settings->contact_schedule ?: []);
                if (empty($schedule)) {
                    $schedule = [['type' => 'date', 'date' => '', 'weekday' => 1, 'time' => '', 'where' => '', 'note' => '']];
                }
                $weekdayOptions = [1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela'];
            @endphp
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <div>
                    <p class="text-sm font-bold text-ink">Spotkajmy się (online i stacjonarnie)</p>
                    <p class="mt-0.5 text-xs text-muted">Sekcja na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>: spotkanie online (opcja zalecana, z linkiem do rezerwacji terminu) oraz harmonogram stacjonarny (kiedy i gdzie jesteśmy, np. w Krakowie). Puste pola = dana część sekcji się nie pokaże.</p>
                </div>

                <div>
                    <label for="contact_meeting_title" class="mb-1 block text-sm font-bold">Tytuł sekcji</label>
                    <input type="text" id="contact_meeting_title" name="contact_meeting_title" value="{{ old('contact_meeting_title', $settings->contact_meeting_title) }}"
                        placeholder="Spotkajmy się" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_meeting_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_remote_note" class="mb-1 block text-sm font-bold">Informacja „na co dzień działamy zdalnie” <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_remote_note" name="contact_remote_note" value="{{ old('contact_remote_note', $settings->contact_remote_note) }}"
                        placeholder="np. Na co dzień działamy zdalnie — dlatego najszybciej złapiesz nas online."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Krótka ciekawostka/informacja nad opcjami spotkania. Zostaw puste, aby ukryć.</p>
                    @error('contact_remote_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded border border-gray-200 bg-white p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Spotkanie online <span class="ml-1 rounded-full bg-brand-light px-2 py-0.5 text-[0.65rem] normal-case text-brand">opcja zalecana</span></p>
                    <div class="space-y-3">
                        <div>
                            <label for="contact_online_meeting_text" class="mb-1 block text-sm font-bold">Tekst zachęty <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="contact_online_meeting_text" name="contact_online_meeting_text" value="{{ old('contact_online_meeting_text', $settings->contact_online_meeting_text) }}"
                                placeholder="np. Najwygodniej spotkać się online — wybierz dogodny termin."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('contact_online_meeting_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="contact_online_meeting_url" class="mb-1 block text-sm font-bold">Link do rezerwacji terminu</label>
                                <input type="text" id="contact_online_meeting_url" name="contact_online_meeting_url" value="{{ old('contact_online_meeting_url', $settings->contact_online_meeting_url) }}"
                                    placeholder="np. https://calendly.com/…"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <p class="mt-1 text-xs text-muted">Bez linku przycisk się nie pokaże.</p>
                                @error('contact_online_meeting_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="contact_online_meeting_label" class="mb-1 block text-sm font-bold">Tekst przycisku</label>
                                <input type="text" id="contact_online_meeting_label" name="contact_online_meeting_label" value="{{ old('contact_online_meeting_label', $settings->contact_online_meeting_label) }}"
                                    placeholder="Wybierz dogodny termin"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                @error('contact_online_meeting_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="contact_meeting_notify_email" class="mb-1 block text-sm font-bold">Adres do zgłoszeń „Daj znać, że przyjdziesz” <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="email" id="contact_meeting_notify_email" name="contact_meeting_notify_email" value="{{ old('contact_meeting_notify_email', $settings->contact_meeting_notify_email) }}"
                        placeholder="{{ $settings->contact_email ?: 'np. kontakt@feer.org.pl' }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Tu trafiają zgłoszenia z formularza oraz kopia (DW) powiadomień o zmianie terminu. Puste = adres kontaktowy z sekcji wyżej.</p>
                    @error('contact_meeting_notify_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded border border-gray-200 bg-white p-4" x-data="{ items: {{ \Illuminate\Support\Js::from(array_values($schedule)) }}, scheduleOn: {{ old('contact_schedule_enabled', $settings->contact_schedule_enabled) ? 'true' : 'false' }} }">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Harmonogram stacjonarny (kiedy i gdzie jesteśmy)</p>

                    <label class="mb-3 flex items-start gap-2">
                        <input type="checkbox" name="contact_schedule_enabled" value="1" x-model="scheduleOn"
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span>
                            <span class="text-sm font-bold">Umożliw wybór terminu spotkania</span>
                            <span class="block text-xs text-muted">Gdy wyłączone, na stronie kontakt zamiast harmonogramu i przycisków pokażemy komunikat poniżej.</span>
                        </span>
                    </label>

                    <div class="mb-3" x-show="! scheduleOn" x-cloak>
                        <label for="contact_no_schedule_note" class="mb-1 block text-sm font-bold">Komunikat, gdy brak terminów</label>
                        <input type="text" id="contact_no_schedule_note" name="contact_no_schedule_note" value="{{ old('contact_no_schedule_note', $settings->contact_no_schedule_note) }}"
                            placeholder="Jeszcze nie ustaliliśmy żadnych terminów."
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_no_schedule_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-3" x-show="scheduleOn" x-cloak>
                        <label for="contact_schedule_title" class="mb-1 block text-sm font-bold">Tytuł harmonogramu</label>
                        <input type="text" id="contact_schedule_title" name="contact_schedule_title" value="{{ old('contact_schedule_title', $settings->contact_schedule_title) }}"
                            placeholder="Kiedy i gdzie jesteśmy w Krakowie"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_schedule_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <template x-for="(item, index) in items" :key="index">
                        <div class="mb-3 rounded border border-gray-200 p-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold uppercase tracking-wide text-muted" x-text="'Termin ' + (index + 1)"></p>
                                <button type="button" @click="items.splice(index, 1)"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700">
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Usuń
                                </button>
                            </div>

                            <input type="hidden" :name="'contact_schedule[' + index + '][type]'" x-model="item.type">

                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label :for="'contact_schedule_type_' + index" class="mb-1 block text-sm font-bold">Rodzaj</label>
                                    <select :id="'contact_schedule_type_' + index" x-model="item.type"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        <option value="date">Konkretna data</option>
                                        <option value="weekly">Co tydzień (dzień tygodnia)</option>
                                    </select>
                                </div>
                                <div x-show="item.type === 'date'">
                                    <label :for="'contact_schedule_date_' + index" class="mb-1 block text-sm font-bold">Data</label>
                                    <input type="date" :id="'contact_schedule_date_' + index"
                                        :name="'contact_schedule[' + index + '][date]'" x-model="item.date"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div x-show="item.type === 'weekly'" x-cloak>
                                    <label :for="'contact_schedule_weekday_' + index" class="mb-1 block text-sm font-bold">Dzień tygodnia</label>
                                    <select :id="'contact_schedule_weekday_' + index"
                                        :name="'contact_schedule[' + index + '][weekday]'" x-model="item.weekday"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        @foreach ($weekdayOptions as $num => $name)
                                            <option value="{{ $num }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label :for="'contact_schedule_time_' + index" class="mb-1 block text-sm font-bold">Godziny <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                    <input type="text" :id="'contact_schedule_time_' + index"
                                        :name="'contact_schedule[' + index + '][time]'" x-model="item.time"
                                        placeholder="np. 10:00–14:00"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label :for="'contact_schedule_where_' + index" class="mb-1 block text-sm font-bold">Gdzie</label>
                                    <input type="text" :id="'contact_schedule_where_' + index"
                                        :name="'contact_schedule[' + index + '][where]'" x-model="item.where"
                                        placeholder="np. Kraków, ul. Przykładowa 1"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                            <div class="mt-2">
                                <label :for="'contact_schedule_note_' + index" class="mb-1 block text-sm font-bold">Dopisek <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="text" :id="'contact_schedule_note_' + index"
                                    :name="'contact_schedule[' + index + '][note]'" x-model="item.note"
                                    placeholder="np. wejście od podwórza, zapisy mailowo"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="items.push({ type: 'date', date: '', weekday: 1, time: '', where: '', note: '' })"
                        class="inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand hover:text-white">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj termin
                    </button>
                    @error('contact_schedule') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-start gap-2 rounded-lg border border-gray-200 bg-white p-3 text-sm">
                    <input type="hidden" name="notify_schedule_change" value="0">
                    <input type="checkbox" name="notify_schedule_change" value="1" @checked(old('notify_schedule_change')) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span>
                        <span class="font-bold">Powiadom zapisanych o zmianie terminu</span>
                        <span class="block text-xs text-muted">Po zapisaniu ustawień wyśle e-mail z aktualnym harmonogramem do osób, które wypełniły „Daj znać, że przyjdziesz”, z kopią (DW) na adres powyżej. Zaznacz tylko, gdy termin faktycznie się zmienił.</span>
                    </span>
                </label>
            </div>

            {{-- Box informacyjny pod danymi kontaktowymi (np. „zmiany w kontakcie”) --}}
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <div>
                    <p class="text-sm font-bold text-ink">Box informacyjny pod danymi kontaktowymi</p>
                    <p class="mt-0.5 text-xs text-muted">Wyróżniony box z tekstem i opcjonalnym linkiem (jak przycisk CTA), pokazywany pod danymi kontaktowymi na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a> — np. gdy zmienia się adres lub godziny. Zostaw tekst pusty, aby ukryć box.</p>
                </div>

                <div>
                    <label for="contact_box_text" class="mb-1 block text-sm font-bold">Treść boxa</label>
                    <textarea id="contact_box_text" name="contact_box_text" rows="3"
                        placeholder="np. Od 1 września zmieniamy adres biura."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('contact_box_text', $settings->contact_box_text) }}</textarea>
                    @error('contact_box_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_box_link_label" class="mb-1 block text-sm font-bold">Tekst linku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="contact_box_link_label" name="contact_box_link_label" value="{{ old('contact_box_link_label', $settings->contact_box_link_label) }}"
                            placeholder="np. Zobacz szczegóły"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_box_link_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_box_link_url" class="mb-1 block text-sm font-bold">Adres linku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="contact_box_link_url" name="contact_box_link_url" value="{{ old('contact_box_link_url', $settings->contact_box_link_url) }}"
                            placeholder="np. /aktualnosci lub https://…"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_box_link_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_box_visible_from" class="mb-1 block text-sm font-bold">Pokaż od <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="datetime-local" id="contact_box_visible_from" name="contact_box_visible_from"
                            value="{{ old('contact_box_visible_from', $settings->contact_box_visible_from?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Pusto = box widoczny od razu.</p>
                        @error('contact_box_visible_from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_box_visible_until" class="mb-1 block text-sm font-bold">Ukryj po <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="datetime-local" id="contact_box_visible_until" name="contact_box_visible_until"
                            value="{{ old('contact_box_visible_until', $settings->contact_box_visible_until?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Pusto = box widoczny bezterminowo.</p>
                        @error('contact_box_visible_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'social'" x-cloak>
            <p class="mb-4 text-xs text-muted">Wypełnione linki pojawią się w górnym pasku i stopce. Puste pola zostają ukryte, zamiast prowadzić donikąd.</p>

            <div class="mb-5 flex flex-wrap gap-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="show_topbar_bip" value="1" {{ old('show_topbar_bip', $settings->show_topbar_bip) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    Pokaż link BIP w górnym pasku
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="show_topbar_social" value="1" {{ old('show_topbar_social', $settings->show_topbar_social) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    Pokaż ikony mediów społecznościowych w górnym pasku
                </label>
            </div>
            <p class="mb-4 text-xs text-muted">Dotyczy tylko górnego paska nad logo. Ikony mediów społecznościowych w stopce pozostają widoczne niezależnie od tego ustawienia (BIP nie jest wyświetlany w stopce).</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="bip_url" class="mb-1 block text-sm font-bold">BIP</label>
                    <input type="text" id="bip_url" name="bip_url" value="{{ old('bip_url', $settings->bip_url) }}" placeholder="https://bip..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Adres Biuletynu. Dostępny też pod skrótem <code>/bip</code> (strona-pośrednik z informacją poniżej).</p>
                    @error('bip_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="mt-3">
                        <label class="mb-1 block text-sm font-bold">Treść strony <code>/bip</code> <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        @include('admin.partials.editor', ['name' => 'bip_intro', 'value' => old('bip_intro', $settings->bip_intro)])
                        <p class="mt-1 text-xs text-muted">Pełny opis Biuletynu — możesz formatować (nagłówki, listy, pogrubienia, linki). Puste = domyślny opis. Skróty <code>/instagram</code> i <code>/fb</code> przekierowują do adresów z pól obok.</p>
                        @error('bip_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-3">
                        <p class="mb-1 text-sm font-bold">Logo BIP <span class="font-normal text-muted">(opcjonalnie)</span></p>
                        @if ($settings->bipLogoUrl())
                            <div class="mb-2 flex items-center gap-3">
                                <img src="{{ $settings->bipLogoUrl() }}" alt="Logo BIP" class="h-12 w-auto rounded bg-white object-contain p-1 ring-1 ring-gray-200">
                                <label class="flex items-center gap-2 text-sm text-muted">
                                    <input type="checkbox" name="remove_bip_logo" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                                    Usuń logo BIP
                                </label>
                            </div>
                        @endif
                        <input type="file" name="bip_logo" accept="image/*" class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                        <p class="mt-1 text-xs text-muted">Oficjalne logo BIP na stronie <code>/bip</code>. Puste = wbudowany znak „BIP”.</p>
                        @error('bip_logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="facebook_url" class="mb-1 block text-sm font-bold">Facebook</label>
                    <input type="text" id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $settings->facebook_url) }}" placeholder="https://facebook.com/..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('facebook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="twitter_url" class="mb-1 block text-sm font-bold">Twitter / X</label>
                    <input type="text" id="twitter_url" name="twitter_url" value="{{ old('twitter_url', $settings->twitter_url) }}" placeholder="https://x.com/..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('twitter_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="instagram_url" class="mb-1 block text-sm font-bold">Instagram</label>
                    <input type="text" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $settings->instagram_url) }}" placeholder="https://instagram.com/..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('instagram_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="linkedin_url" class="mb-1 block text-sm font-bold">LinkedIn</label>
                    <input type="text" id="linkedin_url" name="linkedin_url" value="{{ old('linkedin_url', $settings->linkedin_url) }}" placeholder="https://linkedin.com/company/..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('linkedin_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="youtube_url" class="mb-1 block text-sm font-bold">YouTube</label>
                    <input type="text" id="youtube_url" name="youtube_url" value="{{ old('youtube_url', $settings->youtube_url) }}" placeholder="https://youtube.com/@..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('youtube_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="substack_url" class="mb-1 block text-sm font-bold">Substack (blog)</label>
                    <input type="text" id="substack_url" name="substack_url" value="{{ old('substack_url', $settings->substack_url) }}" placeholder="https://fundacjafeer.substack.com"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Adres profilu Substack. Najnowsze wpisy pojawią się na stronie głównej w sekcji „O tym piszemy” (kolejność ustawisz w zakładce „Strona główna”).</p>
                    @error('substack_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div x-show="tab === 'content'" x-cloak class="space-y-6">
            <div>
                <label for="editor-projects_intro" class="mb-1 block text-sm font-bold">Tekst wprowadzający na stronie projektów <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <p class="mb-2 text-xs text-muted">Wyświetlany pod nagłówkiem na stronie <a href="{{ route('projects.index') }}" target="_blank" rel="noopener" class="text-brand underline">/projekty</a> i na stronach kategorii.</p>
                @include('admin.partials.editor', ['name' => 'projects_intro', 'value' => old('projects_intro', $settings->projects_intro)])
                @error('projects_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-6">
                <label for="editor-materials_intro" class="mb-1 block text-sm font-bold">Tekst wprowadzający na stronie materiałów <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <p class="mb-2 text-xs text-muted">Wyświetlany pod nagłówkiem na stronie <a href="{{ route('materials.index') }}" target="_blank" rel="noopener" class="text-brand underline">/materialy</a>.</p>
                @include('admin.partials.editor', ['name' => 'materials_intro', 'value' => old('materials_intro', $settings->materials_intro)])
                @error('materials_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-6">
                <p class="mb-1 text-sm font-bold">Domyślne zdjęcie newsa <span class="font-normal text-muted">(opcjonalnie)</span></p>
                @if ($settings->newsDefaultImageUrl())
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ $settings->newsDefaultImageUrl() }}" alt="Domyślne zdjęcie newsa" class="h-20 w-32 rounded object-cover">
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remove_news_default_image" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                            Usuń
                        </label>
                    </div>
                @endif
                <input type="file" name="news_default_image" accept="image/*" class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                <p class="mt-1 text-xs text-muted">Używane dla newsów bez własnego zdjęcia (lista aktualności, strona główna, sekcja na stronie projektu).</p>
                @error('news_default_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-6">
                <p class="mb-3 text-sm font-bold">Układ listy aktualności</p>
                <div class="flex gap-6">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="news_layout" value="grid"
                            {{ old('news_layout', $settings->news_layout ?? 'grid') === 'grid' ? 'checked' : '' }}
                            class="border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm"><i class="fa-solid fa-grip text-muted"></i> Siatka (domyślna)</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="news_layout" value="list"
                            {{ old('news_layout', $settings->news_layout ?? 'grid') === 'list' ? 'checked' : '' }}
                            class="border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm"><i class="fa-solid fa-list text-muted"></i> Lista</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="news_layout" value="cards"
                            {{ old('news_layout', $settings->news_layout ?? 'grid') === 'cards' ? 'checked' : '' }}
                            class="border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm"><i class="fa-solid fa-table-cells-large text-muted"></i> Karty 3-kolumnowe</span>
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <p class="mb-3 text-sm font-bold">Układ listy ofert wolontariatu</p>
                <div class="flex gap-6">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="volunteer_layout" value="grid"
                            {{ old('volunteer_layout', $settings->volunteer_layout ?? 'grid') === 'grid' ? 'checked' : '' }}
                            class="border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm"><i class="fa-solid fa-grip text-muted"></i> Siatka (domyślna)</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" name="volunteer_layout" value="list"
                            {{ old('volunteer_layout', $settings->volunteer_layout ?? 'grid') === 'list' ? 'checked' : '' }}
                            class="border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm"><i class="fa-solid fa-list text-muted"></i> Lista</span>
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <label for="editor-materials_notice" class="mb-1 block text-sm font-bold">Informacja w ramce (materiały) <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <p class="mb-2 text-xs text-muted">Wyróżniona ramka informacyjna na stronie materiałów (np. ważny komunikat, warunki korzystania).</p>
                @include('admin.partials.editor', ['name' => 'materials_notice', 'value' => old('materials_notice', $settings->materials_notice)])
                @error('materials_notice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div x-show="tab === 'modules'" x-cloak>
            <p class="mb-4 text-xs text-muted">Wyłącz moduł, aby ukryć go z panelu i strony publicznej. Adresy powiązane z wyłączonym modułem przestaną działać (błąd 404), dopóki nie zostanie ponownie włączony.</p>

            @php
                $defaultEnabledModules = array_diff(array_keys(\App\Models\SiteSetting::MODULES), $settings->disabled_modules ?? []);
                $enabledModules = old('enabled_modules', $defaultEnabledModules);
            @endphp

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (\App\Models\SiteSetting::MODULES as $key => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="enabled_modules[]" value="{{ $key }}" {{ in_array($key, $enabledModules) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div x-show="tab === 'homepage'" x-cloak>
            <p class="mb-4 text-xs text-muted">Zmień kolejność, w jakiej sekcje pojawiają się na stronie głównej. Sekcja "Kontakt" zawsze zostaje na końcu.</p>

            <ul id="section-order-list" class="space-y-2">
                @foreach ($settings->orderedHomepageSections() as $key)
                    <li data-section="{{ $key }}" class="flex items-center justify-between rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        <span class="font-medium">{{ \App\Models\SiteSetting::HOMEPAGE_SECTIONS[$key] ?? $key }}</span>
                        <span class="flex items-center gap-1">
                            <button type="button" data-move="up" class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-gray-200 hover:text-brand" aria-label="Przenieś wyżej">
                                <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                            </button>
                            <button type="button" data-move="down" class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-gray-200 hover:text-brand" aria-label="Przenieś niżej">
                                <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                            </button>
                        </span>
                    </li>
                @endforeach
            </ul>
            <input type="hidden" id="section-order-json" name="section_order_json">

            {{-- Slider hero --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <p class="mb-3 text-sm font-bold text-ink">Slider hero</p>
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-3">
                    <input type="hidden" name="hero_mission_slide" value="0">
                    <input type="checkbox" name="hero_mission_slide" value="1"
                        {{ old('hero_mission_slide', $settings->hero_mission_slide ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Misja jako slajd <span class="font-normal text-muted">(dodaje slajd z misją organizacji na początku slidera)</span></span>
                </label>
            </div>

            {{-- Kolor sekcji „Szkolenia i wydarzenia" na stronie głównej --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <label for="events_home_color_text" class="mb-1 block text-sm font-bold">Kolor sekcji „Szkolenia i wydarzenia”</label>
                <p class="mb-2 text-xs text-muted">Akcent bloku wydarzeń na stronie głównej. Zostaw pusty, aby użyć koloru marki. Zbyt jasny kolor zostanie przyciemniony dla kontrastu (WCAG).</p>
                <div class="flex items-center gap-2">
                    <input type="color" id="events_home_color_picker" value="{{ old('events_home_color', $settings->events_home_color ?: $settings->brand_color) }}"
                        oninput="document.getElementById('events_home_color_text').value = this.value"
                        class="h-10 w-14 cursor-pointer rounded border-gray-300">
                    <input type="text" id="events_home_color_text" name="events_home_color" value="{{ old('events_home_color', $settings->events_home_color) }}"
                        placeholder="#RRGGBB (puste = kolor marki)" pattern="^#[0-9a-fA-F]{6}$"
                        oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('events_home_color_picker').value = this.value"
                        class="w-56 rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                @error('events_home_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Pasek informacyjny na górze strony głównej --}}
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <div>
                    <p class="text-sm font-bold text-ink">Pasek informacyjny na stronie głównej</p>
                    <p class="mt-0.5 text-xs text-muted">Wyróżniony pasek z tekstem i opcjonalnym linkiem, pokazywany na samej górze strony głównej — np. ważny komunikat lub zaproszenie na wydarzenie. Zostaw tekst pusty, aby ukryć pasek.</p>
                </div>

                <div>
                    <label for="homepage_banner_text" class="mb-1 block text-sm font-bold">Treść paska</label>
                    <textarea id="homepage_banner_text" name="homepage_banner_text" rows="2"
                        placeholder="np. Zapraszamy na bezpłatny webinar o dostępności — 12 marca."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('homepage_banner_text', $settings->homepage_banner_text) }}</textarea>
                    @error('homepage_banner_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="homepage_banner_link_label" class="mb-1 block text-sm font-bold">Tekst linku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="homepage_banner_link_label" name="homepage_banner_link_label" value="{{ old('homepage_banner_link_label', $settings->homepage_banner_link_label) }}"
                            placeholder="np. Zapisz się"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('homepage_banner_link_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="homepage_banner_link_url" class="mb-1 block text-sm font-bold">Adres linku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="homepage_banner_link_url" name="homepage_banner_link_url" value="{{ old('homepage_banner_link_url', $settings->homepage_banner_link_url) }}"
                            placeholder="np. /aktualnosci lub https://…"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('homepage_banner_link_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="homepage_banner_visible_from" class="mb-1 block text-sm font-bold">Pokaż od <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="datetime-local" id="homepage_banner_visible_from" name="homepage_banner_visible_from"
                            value="{{ old('homepage_banner_visible_from', $settings->homepage_banner_visible_from?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Pusto = pasek widoczny od razu.</p>
                        @error('homepage_banner_visible_from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="homepage_banner_visible_until" class="mb-1 block text-sm font-bold">Ukryj po <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="datetime-local" id="homepage_banner_visible_until" name="homepage_banner_visible_until"
                            value="{{ old('homepage_banner_visible_until', $settings->homepage_banner_visible_until?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Pusto = pasek widoczny bezterminowo.</p>
                        @error('homepage_banner_visible_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'login'" x-cloak class="space-y-6" x-data="{ msEnabled: {{ old('microsoft_login_enabled', $settings->microsoft_login_enabled) ? 'true' : 'false' }} }">
            <div>
                <h2 class="text-base font-bold text-ink">Logowanie przez Microsoft 365</h2>
                <p class="mt-1 text-xs text-muted">
                    Pozwala zalogować się do panelu kontem Microsoft 365 (Laravel Socialite). Dostęp otrzymują wyłącznie
                    użytkownicy już istniejący w zakładce „Użytkownicy" (dopasowanie po adresie e-mail). Aplikację rejestruje się
                    w <span class="font-medium">Microsoft Entra ID → App registrations</span>, a jako Redirect URI podaj:
                </p>
                <code class="mt-2 block break-all rounded bg-gray-50 px-3 py-2 text-xs text-ink">{{ url('/auth/microsoft/callback') }}</code>
            </div>

            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="hidden" name="microsoft_login_enabled" value="0">
                <input type="checkbox" name="microsoft_login_enabled" value="1" x-model="msEnabled"
                    {{ old('microsoft_login_enabled', $settings->microsoft_login_enabled) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                Włącz logowanie przez Microsoft 365
            </label>

            <div x-show="msEnabled" x-cloak class="space-y-4">
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="hidden" name="microsoft_only_login" value="0">
                        <input type="checkbox" name="microsoft_only_login" value="1"
                            {{ old('microsoft_only_login', $settings->microsoft_only_login) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        Wyłącz lokalne logowanie hasłem (tylko Microsoft 365)
                    </label>
                    <p class="ml-6 mt-1 text-xs text-muted">
                        Formularz e-mail + hasło zostaje ukryty. Konta z włączoną flagą „Dostęp awaryjny" nadal mogą się zalogować hasłem przez adres awaryjny poniżej.
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="mb-2 text-sm font-bold text-ink">Adres dostępu awaryjnego</p>
                    @if ($settings->emergency_login_token)
                        <code class="block break-all rounded bg-white px-3 py-2 text-xs text-ink ring-1 ring-gray-200">{{ url('/'.$settings->emergency_login_token) }}</code>
                        <p class="mt-1.5 text-xs text-muted">Skopiuj i przechowaj w bezpiecznym miejscu. Po wygenerowaniu nowego stary przestaje działać natychmiast.</p>
                    @else
                        <p class="text-xs text-muted italic">Brak — wygeneruj adres, aby aktywować furtkę awaryjną.</p>
                    @endif
                    <button type="submit" form="emergency-token-form"
                        onclick="return confirm('Wygenerować nowy adres? Stary przestanie działać natychmiast.')"
                        class="mt-3 inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-ink shadow-sm hover:bg-gray-50">
                        <i class="fa-solid fa-rotate-right"></i>
                        {{ $settings->emergency_login_token ? 'Wygeneruj nowy adres' : 'Wygeneruj adres' }}
                    </button>
                </div>
            </div>

            <div class="space-y-5" x-show="msEnabled" x-cloak>
                <div>
                    <label for="microsoft_client_id" class="mb-1 block text-sm font-bold">Client ID</label>
                    <input type="text" id="microsoft_client_id" name="microsoft_client_id" autocomplete="off"
                        value="{{ old('microsoft_client_id', $settings->microsoft_client_id) }}"
                        class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Application (client) ID z Microsoft Entra ID.</p>
                    @error('microsoft_client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="microsoft_client_secret" class="mb-1 block text-sm font-bold">Client Secret</label>
                    <input type="password" id="microsoft_client_secret" name="microsoft_client_secret" autocomplete="new-password"
                        placeholder="{{ $settings->microsoft_client_secret ? '•••••••• (zapisany — zostaw puste, aby nie zmieniać)' : '' }}"
                        class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Wartość sekretu (nie „Secret ID"). Przechowywana w bazie w postaci zaszyfrowanej.</p>
                    @error('microsoft_client_secret') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="microsoft_tenant_id" class="mb-1 block text-sm font-bold">Tenant ID</label>
                    <input type="text" id="microsoft_tenant_id" name="microsoft_tenant_id" autocomplete="off"
                        value="{{ old('microsoft_tenant_id', $settings->microsoft_tenant_id) }}"
                        placeholder="common"
                        class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Directory (tenant) ID fundacji zawęża logowanie do jej domeny. Puste = „common" (dowolne konto Microsoft).</p>
                    @error('microsoft_tenant_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <p class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    Pozostawione puste pola dziedziczą wartości z pliku <code>.env</code>, jeśli tam je ustawiono.
                </p>
            </div>

            {{-- ===================== Strefa wewnętrzna (współpracownicy) ===================== --}}
            <div class="border-t border-gray-200 pt-6" x-data="{ memberEnabled: {{ old('member_login_enabled', $settings->member_login_enabled) ? 'true' : 'false' }} }">
                <h2 class="text-base font-bold text-ink">Logowanie do stron wewnętrznych (strefa współpracownika)</h2>
                <p class="mt-1 text-xs text-muted">
                    Osobne logowanie przez Microsoft 365 dla stron wewnętrznych — niezależne od kont panelu (osobny guard i tabela).
                    Konto współpracownika jest zakładane automatycznie przy pierwszym logowaniu. Reużywa tej samej aplikacji Azure co
                    panel — dodaj w niej także drugi Redirect URI:
                </p>
                <code class="mt-2 block break-all rounded bg-gray-50 px-3 py-2 text-xs text-ink">{{ url('/strefa/microsoft/callback') }}</code>

                <label class="mt-4 flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="member_login_enabled" value="0">
                    <input type="checkbox" name="member_login_enabled" value="1" x-model="memberEnabled"
                        {{ old('member_login_enabled', $settings->member_login_enabled) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    Włącz logowanie do strefy wewnętrznej
                </label>

                <div class="mt-4" x-show="memberEnabled" x-cloak>
                    <label for="member_allowed_domains" class="mb-1 block text-sm font-bold">Dozwolone domeny e-mail</label>
                    <input type="text" id="member_allowed_domains" name="member_allowed_domains" autocomplete="off"
                        value="{{ old('member_allowed_domains', $settings->member_allowed_domains) }}"
                        placeholder="np. feer.org.pl, wspolpraca.feer.org.pl"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Domeny po przecinku. Puste = dowolne konto z tenanta skonfigurowanego w Azure.</p>
                    @error('member_allowed_domains') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4" x-show="memberEnabled" x-cloak>
                    <label for="szo_api_url" class="mb-1 block text-sm font-bold">Adres systemu SZO</label>
                    <input type="url" id="szo_api_url" name="szo_api_url" autocomplete="off" inputmode="url"
                        value="{{ old('szo_api_url', $settings->szo_api_url) }}"
                        placeholder="np. https://szo.feer.org.pl"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">
                        Adres bazowy. Strefa współpracownika pobiera z niego komunikaty
                        (<code class="text-ink">GET {adres}/api/komunikaty/list.php</code>). Puste = strefa nie pokazuje komunikatów.
                    </p>
                    @error('szo_api_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ===================== 2FA panelu + YubiKey ===================== --}}
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-base font-bold text-ink">Uwierzytelnianie dwuetapowe (2FA) panelu</h2>
                <p class="mt-1 text-xs text-muted">
                    Dotyczy logowania hasłem. Użytkownicy konfigurują TOTP i klucze YubiKey na swoim profilu. Logowanie przez
                    Microsoft 365 pomija ten krok (MS ma własne MFA).
                </p>

                <label class="mt-4 flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="two_factor_required_admins" value="0">
                    <input type="checkbox" name="two_factor_required_admins" value="1"
                        {{ old('two_factor_required_admins', $settings->two_factor_required_admins) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    Wymagaj 2FA od administratorów (logujących się hasłem)
                </label>

                <div class="mt-5 space-y-5">
                    <p class="text-sm font-bold text-ink">Klucze YubiKey (Yubico OTP)</p>
                    <p class="-mt-3 text-xs text-muted">Dane API z <a href="https://upgrade.yubico.com/getapikey/" target="_blank" rel="noopener" class="text-brand underline">upgrade.yubico.com/getapikey</a> (link otwiera się w nowej karcie).</p>
                    <div>
                        <label for="yubico_client_id" class="mb-1 block text-sm font-bold">Yubico Client ID</label>
                        <input type="text" id="yubico_client_id" name="yubico_client_id" autocomplete="off"
                            value="{{ old('yubico_client_id', $settings->yubico_client_id) }}"
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                        @error('yubico_client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="yubico_secret_key" class="mb-1 block text-sm font-bold">Yubico Secret Key</label>
                        <input type="password" id="yubico_secret_key" name="yubico_secret_key" autocomplete="new-password"
                            placeholder="{{ $settings->yubico_secret_key ? '•••••••• (zapisany — zostaw puste, aby nie zmieniać)' : '' }}"
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Przechowywany w bazie w postaci zaszyfrowanej.</p>
                        @error('yubico_secret_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ===================== Adres URL panelu ===================== --}}
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-base font-bold text-ink">Adres panelu administracyjnego</h2>
                <p class="mt-1 text-xs text-muted">
                    Zmiana segmentu URL otwierającego panel (domyślnie <code>/admin</code>). Utrudnia automatyczne
                    skanowanie — nie zastępuje silnego hasła ani 2FA.
                </p>

                @php $currentPrefix = config('app.admin_prefix', 'admin'); @endphp

                <div class="mt-3 flex items-center gap-2 rounded bg-gray-50 px-3 py-2">
                    <span class="text-sm text-muted">Aktualny adres:</span>
                    <code class="font-mono text-sm text-ink">{{ url('/') }}/<strong>{{ $currentPrefix }}</strong></code>
                </div>

                {{-- Formularz zmiany prefixu musi być POZA głównym formularzem ustawień
                     (HTML nie pozwala zagnieżdżać <form>). Używamy atrybutu form="prefix-change-form"
                     na kontrolkach, żeby zachować układ wizualny wewnątrz zakładki. --}}
                <div class="mt-4 space-y-4"
                     x-data="{ prefix: '{{ $currentPrefix }}' }">
                    <div>
                        <label for="admin_prefix_input" class="mb-1 block text-sm font-bold">Nowy prefix URL</label>
                        <div class="flex items-center gap-1">
                            <span class="whitespace-nowrap rounded-l border border-r-0 border-gray-300 bg-gray-100 px-3 py-2 text-sm text-muted">{{ url('/') }}/</span>
                            <input type="text" id="admin_prefix_input" name="admin_prefix"
                                form="prefix-change-form"
                                x-model="prefix"
                                value="{{ $currentPrefix }}"
                                pattern="[a-z0-9][a-z0-9\-_]*[a-z0-9]" minlength="3" maxlength="60" required
                                autocomplete="off" spellcheck="false"
                                class="flex-1 rounded-none rounded-r border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand"
                                aria-describedby="admin-prefix-hint">
                        </div>
                        <p id="admin-prefix-hint" class="mt-1 text-xs text-muted">Małe litery, cyfry, myślniki i podkreślenia. Minimum 3 znaki. Przykład: <code>zarzadzanie</code>, <code>cms-feer</code>.</p>
                        @error('admin_prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800" role="alert">
                        <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                        Po zapisaniu panel otworzy się pod <strong>nowym adresem</strong>. Stary link przestanie działać — zaktualizuj zakładkę w przeglądarce.
                    </div>

                    <button type="submit"
                        form="prefix-change-form"
                        class="inline-flex items-center gap-2 rounded bg-amber-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                        Zmień adres panelu
                    </button>
                </div>
            </div>
        </div>

        <div x-show="tab === 'mail'" x-cloak class="space-y-6" x-data="{ transport: '{{ old('mail_transport', $settings->mail_transport ?: 'default') }}' }">
            <div>
                <h2 class="text-base font-bold text-ink">Wysyłka poczty</h2>
                <p class="mt-1 text-xs text-muted">
                    Konfiguracja bramki e-mail (m.in. formularz kontaktowy). Wybierz „Dziedzicz z serwera”, aby użyć ustawień
                    z pliku <code>.env</code>, albo skonfiguruj własny serwer SMTP poniżej.
                </p>
            </div>

            <div>
                <label for="mail_transport" class="mb-1 block text-sm font-bold">Tryb wysyłki</label>
                <select id="mail_transport" name="mail_transport" x-model="transport"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @foreach (\App\Models\SiteSetting::MAIL_TRANSPORTS as $value => $label)
                        <option value="{{ $value }}" {{ old('mail_transport', $settings->mail_transport ?: 'default') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('mail_transport') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-2 text-xs text-muted" x-show="transport === 'sendmail'" x-cloak>
                    Poczta wysyłana wbudowanym mechanizmem PHP (sendmail) — bez konfiguracji SMTP. Działa, jeśli serwer/hosting ma skonfigurowaną lokalną wysyłkę poczty.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="mail_from_address" class="mb-1 block text-sm font-bold">Adres nadawcy</label>
                    <input type="email" id="mail_from_address" name="mail_from_address" value="{{ old('mail_from_address', $settings->mail_from_address) }}"
                        placeholder="np. kontakt@feer.org.pl"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Puste = wartość z <code>.env</code>.</p>
                    @error('mail_from_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mail_from_name" class="mb-1 block text-sm font-bold">Nazwa nadawcy</label>
                    <input type="text" id="mail_from_name" name="mail_from_name" value="{{ old('mail_from_name', $settings->mail_from_name) }}"
                        placeholder="{{ $settings->site_name }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('mail_from_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-5 rounded-lg border border-gray-200 p-4" x-show="transport === 'smtp'" x-cloak>
                <p class="text-sm font-bold text-ink">Ustawienia serwera SMTP</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="mail_host" class="mb-1 block text-sm font-bold">Host</label>
                        <input type="text" id="mail_host" name="mail_host" value="{{ old('mail_host', $settings->mail_host) }}"
                            placeholder="np. smtp.example.com"
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                        @error('mail_host') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="mail_port" class="mb-1 block text-sm font-bold">Port</label>
                        <input type="number" id="mail_port" name="mail_port" value="{{ old('mail_port', $settings->mail_port) }}"
                            placeholder="587" min="1" max="65535"
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                        @error('mail_port') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="mail_username" class="mb-1 block text-sm font-bold">Użytkownik</label>
                        <input type="text" id="mail_username" name="mail_username" autocomplete="off" value="{{ old('mail_username', $settings->mail_username) }}"
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                        @error('mail_username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="mail_password" class="mb-1 block text-sm font-bold">Hasło</label>
                        <input type="password" id="mail_password" name="mail_password" autocomplete="new-password"
                            placeholder="{{ $settings->mail_password ? '•••••••• (zapisane — zostaw puste, aby nie zmieniać)' : '' }}"
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Przechowywane w bazie w postaci zaszyfrowanej.</p>
                        @error('mail_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="mail_encryption" class="mb-1 block text-sm font-bold">Szyfrowanie</label>
                        <select id="mail_encryption" name="mail_encryption" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @foreach (\App\Models\SiteSetting::MAIL_ENCRYPTIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('mail_encryption', (string) $settings->mail_encryption) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('mail_encryption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                Integracja z Microsoft 365 (Azure / Graph) zostanie dodana w kolejnym kroku. Na razie dla skrzynek Microsoft
                użyj SMTP (o ile tenant ma włączone uwierzytelnianie SMTP AUTH).
            </p>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
        </div>
    </form>

    {{-- Formularz zmiany prefixu URL panelu — poza głównym formularzem ustawień.
         Kontrolki (input, button) są skojarzone atrybutem form=”prefix-change-form”. --}}
    <form id=”prefix-change-form” method=”POST” action=”{{ route('admin.ustawienia.prefix') }}”
          onsubmit=”return confirm('Zmienić prefix na /' + document.getElementById('admin_prefix_input').value + '? Po zapisaniu zostaniesz przekierowany(a) pod nowy adres — zaktualizuj zakładki.')”>
        @csrf
    </form>

    {{-- Regeneracja tokenu awaryjnego: osobny formularz poza głównym (HTML nie pozwala zagnieżdżać). --}}
    <form id=”emergency-token-form” method=”POST” action=”{{ route('admin.ustawienia.emergency-token') }}”>
        @csrf
    </form>

    {{-- Test poczty: osobny formularz (nie można zagnieżdżać formularzy), widoczny w zakładce „Poczta”. --}}
    <form method="POST" action="{{ route('admin.ustawienia.mail-test') }}" x-show="tab === 'mail'" x-cloak
        class="rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        <h2 class="mb-1 text-base font-bold text-ink">Wyślij wiadomość testową</h2>
        <p class="mb-3 text-xs text-muted">Najpierw zapisz ustawienia powyżej, a następnie sprawdź wysyłkę na wybrany adres.</p>
        <div class="flex flex-wrap items-end gap-3">
            <div class="grow">
                <label for="test_email" class="mb-1 block text-sm font-bold">Adres e-mail</label>
                <input type="email" id="test_email" name="test_email" required value="{{ old('test_email', $settings->contact_email) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('test_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded border border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light">Wyślij test</button>
        </div>
    </form>
    </div>

    <script>
        (function () {
            // Mirrors App\Models\SiteSetting::contrastRatio() so the admin gets
            // live WCAG 2.2 feedback (4.5:1 minimum for normal text) before saving.
            function relativeLuminance(hex) {
                const [r, g, b] = [0, 2, 4].map((i) => {
                    const channel = parseInt(hex.slice(i, i + 2), 16) / 255;
                    return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
                });
                return 0.2126 * r + 0.7152 * g + 0.0722 * b;
            }

            function contrastWithWhite(hex) {
                const clean = hex.replace('#', '');
                if (clean.length !== 6) return null;
                const l1 = relativeLuminance(clean);
                const l2 = 1; // white
                return (l2 + 0.05) / (l1 + 0.05);
            }

            // Mirrors App\Models\SiteSetting::shade() / contrastSafeColor() so the
            // preview matches exactly what the server will save.
            function shade(hex, amount) {
                const clean = hex.replace('#', '');
                if (clean.length !== 6) return hex;
                const target = amount < 0 ? 0 : 255;
                const ratio = Math.abs(amount);
                const mix = (channel) => Math.max(0, Math.min(255, Math.round(channel + (target - channel) * ratio)));
                const channels = [0, 2, 4].map((i) => parseInt(clean.slice(i, i + 2), 16));
                return '#' + channels.map((c) => mix(c).toString(16).padStart(2, '0')).join('');
            }

            function contrastSafeColor(hex) {
                for (let step = 0; step <= 20; step++) {
                    const candidate = step === 0 ? hex : shade(hex, -0.05 * step);
                    const ratio = contrastWithWhite(candidate);
                    if (ratio !== null && ratio >= 4.5) return candidate;
                }
                return '#000000';
            }

            const badge = document.getElementById('contrast-badge');
            const textInput = document.getElementById('brand_color_text');
            const colorInput = document.getElementById('brand_color');
            const fixButton = document.getElementById('contrast-fix-button');

            function update() {
                const ratio = contrastWithWhite(colorInput.value);
                if (ratio === null) {
                    badge.textContent = '';
                    fixButton.hidden = true;
                    return;
                }
                const passes = ratio >= 4.5;
                badge.textContent = `${ratio.toFixed(2)}:1 ${passes ? '— OK (WCAG AA)' : '— za niski kontrast'}`;
                badge.className = `rounded-full px-3 py-1 text-xs font-bold ${passes ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
                fixButton.hidden = passes;
            }

            fixButton.addEventListener('click', () => {
                const safe = contrastSafeColor(colorInput.value);
                colorInput.value = safe;
                textInput.value = safe;
                update();
            });

            colorInput.addEventListener('input', () => {
                textInput.value = colorInput.value;
                update();
            });
            textInput.addEventListener('input', update);
            update();
        })();

        (function () {
            const list = document.getElementById('section-order-list');
            if (!list) return;
            const orderInput = document.getElementById('section-order-json');

            function renumber() {
                const keys = [...list.children].map(li => li.dataset.section);
                if (orderInput) orderInput.value = JSON.stringify(keys);
            }

            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-move]');
                if (!button) return;

                const li = button.closest('li');
                const sibling = button.dataset.move === 'up' ? li.previousElementSibling : li.nextElementSibling;

                if (sibling) {
                    if (button.dataset.move === 'up') {
                        list.insertBefore(li, sibling);
                    } else {
                        list.insertBefore(sibling, li);
                    }
                    renumber();
                }
            });

            renumber();
        })();

        (function () {
            // Repeater kolorów submarek (Ustawienia → Kolory).
            const wrap = document.querySelector('[data-subbrands]');
            if (!wrap) return;
            const rows = wrap.querySelector('[data-subbrands-rows]');
            const template = wrap.querySelector('[data-subbrands-template]');
            const addBtn = wrap.querySelector('[data-subbrands-add]');
            if (!rows || !template) return;
            let nextIndex = rows.querySelectorAll('[data-subbrands-row]').length;

            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
                    const el = document.createElement('div');
                    el.innerHTML = html.trim();
                    rows.appendChild(el.firstElementChild);
                });
            }
            wrap.addEventListener('click', function (e) {
                const remove = e.target.closest('[data-subbrands-remove]');
                if (remove) {
                    const row = remove.closest('[data-subbrands-row]');
                    if (row) row.remove();
                }
            });
        })();
    </script>
@endsection
