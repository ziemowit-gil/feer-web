{{--
    Informacja o plikach cookies (CookieInfoController, /informacja-o-cookies): wstęp z Ustawienia → Ogólne → Pliki
    cookies, bieżąca decyzja z przyciskiem zmiany (otwiera baner — [data-cookie-settings]) i tabele z tym, co strona
    naprawdę zapisuje (App\Support\CookieInventory). W szablonie „kreatywni” pas z tytułem jak na podstronach.
    WCAG: tabele z caption i nagłówkami kolumn, przewijane poziomo na telefonie (region z nazwą), status w aria-live.
--}}
@extends('layouts.site')

@php
    $kc = ($siteSettings->site_template ?? 'default') === 'kreatywni';
    $bannerOn = (bool) ($siteSettings->cookie_banner_enabled ?? true);
    $hasConsentGroup = collect($groups)->contains(fn ($g) => $g['consent']);
    $kinds = ['cookie' => 'Plik cookie', 'localStorage' => 'Pamięć przeglądarki'];
@endphp

@section('title', 'Informacja o plikach cookies — ' . $siteSettings->site_name)
@section('meta_description', 'Jakie pliki cookies i dane w przeglądarce zapisuje strona ' . $siteSettings->site_name . ' i jak zmienić swoją decyzję.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [['label' => 'Informacja o plikach cookies', 'url' => null]]])
@endsection

@section('content')
    @if ($kc)
        @include('templates.kreatywni.partials.base-css')
    @endif
    <div @class(['kc' => $kc])>
        @if ($kc)
            <section class="kc-page-head" aria-labelledby="cookies-title">
                <div class="kc-wrap">
                    <p class="kc-eyebrow kc-hand">{{ $siteSettings->site_name }}</p>
                    <h1 id="cookies-title">Informacja o plikach cookies</h1>
                </div>
            </section>
        @endif

        <div class="mx-auto max-w-4xl px-4 py-10 text-ink">
            @unless ($kc)
                <h1 id="cookies-title" class="text-3xl font-bold">Informacja o plikach cookies</h1>
            @endunless

            <div class="max-w-3xl space-y-3 text-base leading-relaxed {{ $kc ? '' : 'mt-4' }}">
                @foreach ($intro as $p)
                    <p>{{ $p }}</p>
                @endforeach
            </div>

            @if ($bannerOn)
                <section aria-labelledby="cookies-decision-h" class="mt-8 rounded-lg bg-gray-50 p-5" data-cookie-decision>
                    <h2 id="cookies-decision-h" class="text-lg font-bold">Twoja decyzja</h2>
                    <p class="mt-1 text-sm" aria-live="polite" data-cookie-decision-text>
                        {{ $hasConsentGroup ? 'Sprawdzamy, co wybrano w tej przeglądarce…' : 'Ta strona nie używa plików cookies, które wymagają zgody.' }}
                    </p>
                    <button type="button" data-cookie-settings
                        class="mt-3 inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        Zmień ustawienia cookies
                    </button>
                </section>
            @endif

            @foreach ($groups as $group)
                <section aria-labelledby="cookies-g-{{ $group['key'] }}" class="mt-10">
                    <h2 id="cookies-g-{{ $group['key'] }}" class="text-xl font-bold">{{ $group['title'] }}</h2>
                    <p class="mt-1 max-w-3xl text-sm">{{ $group['description'] }}</p>
                    <div class="mt-3 overflow-x-auto rounded-lg bg-white" role="region" tabindex="0" aria-label="Tabela: {{ $group['title'] }}">
                        <table class="w-full min-w-[40rem] text-left text-sm">
                            <caption class="sr-only">{{ $group['title'] }}: nazwa, rodzaj, dostawca, cel i czas przechowywania</caption>
                            <thead class="bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-bold">Nazwa</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Rodzaj</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Dostawca</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Cel</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Jak długo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($group['items'] as $item)
                                    <tr class="align-top">
                                        <th scope="row" class="px-4 py-3 font-mono text-xs font-bold">{{ $item['name'] }}</th>
                                        <td class="px-4 py-3">{{ $kinds[$item['kind']] ?? $item['kind'] }}</td>
                                        <td class="px-4 py-3">{{ $item['provider'] }}</td>
                                        <td class="px-4 py-3">{{ $item['purpose'] }}</td>
                                        <td class="px-4 py-3">{{ $item['lifetime'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach

            <section aria-labelledby="cookies-more-h" class="mt-10 max-w-3xl text-sm">
                <h2 id="cookies-more-h" class="text-xl font-bold">Jak usunąć pliki cookies</h2>
                <p class="mt-1">Pliki cookies i dane strony usuniesz w ustawieniach przeglądarki (zwykle: Prywatność → Pliki cookies i dane witryn). Po ich usunięciu strona zapyta o zgodę ponownie.</p>
                @if ($privacyUrl)
                    <p class="mt-3">Więcej o tym, jak przetwarzamy dane, przeczytasz w
                        <a href="{{ $privacyUrl }}" class="font-bold text-brand-dark underline underline-offset-4 hover:text-ink">polityce prywatności</a>.</p>
                @endif
            </section>
        </div>
    </div>

    @if ($bannerOn && $hasConsentGroup)
        <script>
            (function () {
                var box = document.querySelector('[data-cookie-decision-text]');
                if (!box) return;
                function render() {
                    var v = null;
                    try { v = localStorage.getItem('feer_cookie_consent'); } catch (e) {}
                    box.textContent = v === '1' ? 'Zgodziłeś(-aś) się na statystyki w tej przeglądarce.'
                        : v === '0' ? 'Odmówiłeś(-aś) statystyk w tej przeglądarce — nie są zapisywane.'
                        : 'Nie wybrano jeszcze, czy zgadzasz się na statystyki.';
                }
                render();
                document.addEventListener('feer:cookie-consent', render);
                document.addEventListener('click', function (e) {
                    if (e.target.closest('[data-cookie-accept],[data-cookie-reject]')) setTimeout(render, 0);
                });
                window.addEventListener('storage', render);
            })();
        </script>
    @endif
@endsection
