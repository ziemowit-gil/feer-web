@extends('admin.layout')

@section('title', 'O weCMS')

@section('content')
    @php
        $features = [
            ['fa-sitemap', 'Strony w drzewie', 'Struktura serwisu jak w TYPO3: drzewo stron, przeciąganie, przenoszenie, dziedziczone wyłączanie działów i automatyczne kafelki podstron.'],
            ['fa-universal-access', 'Dostępność od początku', 'Interfejs panelu i strony publicznej budowany pod WCAG: obsługa klawiaturą, kontrast, etykiety, wersja ETR i skaner dostępności.'],
            ['fa-clock-rotate-left', 'Wersje i zatwierdzanie', 'Historia zmian z porównaniem i przywracaniem, blokada edycji, kosz oraz obieg zatwierdzania treści.'],
            ['fa-puzzle-piece', 'Moduły', 'Aktualności, formularze, szkolenia i wydarzenia, wolontariat, oferty pracy, materiały edukacyjne, sklep, BIP i inne — włączane i wyłączane osobno.'],
            ['fa-network-wired', 'Wiele witryn', 'Jedna instalacja obsługuje federację i podległe jej witryny, z własnymi treściami, kolorami i domenami.'],
            ['fa-plug', 'Integracje', 'Płatności Przelewy24, wysyłka poczty przez Microsoft Graph, logowanie Microsoft 365 oraz przekazywanie zgłoszeń i darowizn do systemu SZO.'],
        ];
        $laravel = app()->version();
        $php = PHP_VERSION;
    @endphp

    <div class="max-w-4xl space-y-8">
        <header class="rounded-2xl border border-gray-200 bg-white" style="padding: 2rem">
            <p class="text-3xl" style="font-family:'Pacifico',cursive">
                <span style="color:var(--color-brand)">We</span><span style="font-weight:300">CMS</span>
            </p>
            <h1 class="mt-3 text-2xl font-bold text-ink">System zarządzania treścią dla organizacji, którym zależy na dostępności</h1>
            <p class="mt-3 max-w-3xl text-base leading-relaxed text-muted">
                weCMS to aplikacja internetowa do prowadzenia serwisu organizacji pozarządowej: stron, aktualności, formularzy,
                szkoleń, wolontariatu, zbiórek i darowizn. Powstał z myślą o redaktorach bez wiedzy technicznej oraz o odbiorcach
                z różnymi potrzebami, dlatego dostępność i prostota pracy są w nim wymaganiem, a nie dodatkiem.
            </p>
        </header>

        <section aria-labelledby="about-features">
            <h2 id="about-features" class="mb-4 text-lg font-bold text-ink">Co potrafi</h2>
            <ul role="list" class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr))">
                @foreach ($features as [$icon, $title, $text])
                    <li class="rounded-xl border border-gray-200 bg-white" style="padding: 1.25rem">
                        <span class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-light text-brand" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
                        <h3 class="font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white" style="padding: 2rem" aria-labelledby="about-author">
            <h2 id="about-author" class="text-lg font-bold text-ink">Autor</h2>
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <span class="inline-flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand text-xl font-bold text-white" aria-hidden="true">ZG</span>
                <div>
                    <p class="text-xl font-bold text-ink">Ziemowit Gil</p>
                    <p class="text-sm text-muted">Autor i twórca weCMS — działanie, rozwój i utrzymanie systemu.</p>
                </div>
            </div>
            <p class="mt-4 text-sm leading-relaxed text-muted">
                Pytania, uwagi i zgłoszenia błędów kieruj bezpośrednio do autora:
                <a href="mailto:ziemowit.gil@gmail.com" class="font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">ziemowit.gil@gmail.com</a>.
            </p>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white" style="padding: 1.5rem 2rem" aria-labelledby="about-tech">
            <h2 id="about-tech" class="mb-3 text-lg font-bold text-ink">Informacje techniczne</h2>
            <dl class="grid gap-x-8 gap-y-3 text-sm" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr))">
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Framework</dt><dd class="mt-0.5 font-semibold text-ink">Laravel {{ $laravel }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">PHP</dt><dd class="mt-0.5 font-semibold text-ink">{{ $php }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Serwis</dt><dd class="mt-0.5 font-semibold text-ink">{{ $siteSettings->site_name }}</dd></div>
            </dl>
            @if (auth()->user()->isAdmin())
                <p class="mt-4 text-sm">
                    <a href="{{ route('admin.dokumentacja') }}" target="_blank" rel="noopener" class="inline-flex min-h-9 items-center gap-1.5 font-bold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-book" aria-hidden="true"></i> Dokumentacja techniczna<span class="sr-only"> (otwiera się w nowej karcie)</span>
                    </a>
                </p>
            @endif
        </section>
    </div>
@endsection
