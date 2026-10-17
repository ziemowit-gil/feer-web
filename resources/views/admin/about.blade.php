@extends('admin.layout')

@section('title', 'O weCMS')

{{--
    O weCMS: wersja główna (config/cms.php), nowości tej wersji z linkami do ekranów, możliwości w 8 obszarach,
    autor i informacje techniczne. Płasko (karty bg-white — motyw panelu dodaje im ramkę), kolory marki,
    ikony dekoracyjne aria-hidden, linki z widocznym podkreśleniem przy najechaniu i fokusie.
--}}
@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $version = config('cms.version');
        // Nowe w tej wersji: [ikona, tytuł, opis, trasa (tylko dla administratora) albo null].
        $whatsNew = [
            ['fa-heart-pulse', 'Kondycja serwisu', 'Jedna strona z harmonogramem, kolejką, ostatnim wdrożeniem, kopiami zapasowymi, bazą i błędami z ostatniej doby — bez logowania na serwer.', 'admin.health.index'],
            ['fa-database', 'Przejście na MySQL', 'Konwerter bazy z SQLite do MySQL z raportem zgodności każdej tabeli i bezpiecznym przełączeniem (z powrotem do SQLite jednym przyciskiem).', 'admin.baza-mysql.index'],
            ['fa-clock', 'Harmonogram (cron)', 'Podgląd i edycja crontaba z diagnozą wpisu harmonogramu, naprawą jednym kliknięciem i kopiami poprzednich wersji.', 'admin.cron.index'],
            ['fa-sitemap', 'Kosz witryn', 'Usunięta witryna sieci trafia na 30 dni do kosza i można ją przywrócić razem z całą treścią.', 'admin.witryny.index'],
            ['fa-rectangle-ad', 'Banery', 'Baner sekcji i baner graficzny w edytorze blokowym, zestawy banerów ze stref, statystyki kliknięć i strefa na stronie głównej.', 'admin.banery.index'],
            ['fa-book-open', 'Materiały edukacyjne', 'Nowy wygląd strony materiałów, wyszukiwarka z filtrem typu i blok „Materiały edukacyjne” do wstawiania w treści.', null],
            ['fa-palette', 'Schematy kolorów panelu', 'Odświeżony wygląd panelu i pięć schematów kolorów do wyboru — każdy kontrastowy, ustawiany dla konta.', 'profile.edit'],
            ['fa-shield-halved', 'Potwierdzanie przez Microsoft 365', 'Wrażliwe operacje można potwierdzić kontem Microsoft 365 zamiast hasła.', null],
        ];
        // Co potrafi: [ikona, obszar, opis].
        $features = [
            ['fa-pen-to-square', 'Treść i edytory', 'Edytor blokowy z podglądem jak na stronie i klasyczny edytor — z tymi samymi modułami, wzorcami i banerami.'],
            ['fa-universal-access', 'Dostępność od początku', 'Panel i strona budowane pod WCAG 2.2: klawiatura, kontrast, etykiety, wersja ETR, napisy i skaner dostępności.'],
            ['fa-clock-rotate-left', 'Wersje i zatwierdzanie', 'Historia zmian z porównaniem i przywracaniem, blokada edycji, kosz oraz obieg zatwierdzania treści.'],
            ['fa-puzzle-piece', 'Moduły', 'Aktualności, formularze, szkolenia, wolontariat, oferty pracy, materiały, sklep, newsletter, BIP i inne — włączane osobno.'],
            ['fa-network-wired', 'Wiele witryn', 'Jedna instalacja dla federacji i podległych witryn, z własną treścią, kolorami, domenami i koszem witryn.'],
            ['fa-plug', 'Integracje', 'Płatności Przelewy24, poczta przez Microsoft Graph, logowanie Microsoft 365 i przekazywanie zgłoszeń i darowizn do SZO.'],
            ['fa-screwdriver-wrench', 'Utrzymanie serwisu', 'Kondycja serwisu, kopie zapasowe z harmonogramem, harmonogram zadań, wdrożenie jednym poleceniem i baza MySQL.'],
            ['fa-lock', 'Bezpieczeństwo', 'Logowanie dwuetapowe, klucze dostępu, Microsoft 365, potwierdzanie wrażliwych operacji i dziennik zdarzeń.'],
        ];
        $dbDriver = match (\Illuminate\Support\Facades\DB::connection()->getDriverName()) { 'mysql' => 'MySQL', 'mariadb' => 'MariaDB', 'sqlite' => 'SQLite', default => \Illuminate\Support\Facades\DB::connection()->getDriverName() };
        $link = 'font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp

    <div class="max-w-5xl space-y-8">
        {{-- Nagłówek --}}
        <header class="rounded-md bg-white p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <p class="text-3xl" style="font-family:'Pacifico',cursive" aria-hidden="true">
                    <span style="color:var(--color-brand-dark)">We</span><span style="font-weight:300">CMS</span>
                </p>
                <p class="rounded-md bg-brand-light px-3 py-1 text-sm font-bold text-brand-dark">Wersja {{ $version }}</p>
            </div>
            <h1 class="mt-4 text-2xl font-bold leading-snug text-ink">System zarządzania treścią dla organizacji, którym zależy na dostępności</h1>
            <p class="mt-3 max-w-3xl text-base leading-relaxed text-ink">
                weCMS to aplikacja do prowadzenia serwisu organizacji pozarządowej: stron, aktualności, formularzy, szkoleń,
                wolontariatu, zbiórek i darowizn. Powstał z myślą o redaktorach bez wiedzy technicznej i o odbiorcach
                z różnymi potrzebami — dostępność i prostota pracy są w nim wymaganiem, a nie dodatkiem.
            </p>
        </header>

        {{-- Nowe w tej wersji --}}
        <section aria-labelledby="about-new">
            <h2 id="about-new" class="mb-1 text-lg font-bold text-ink">Nowe w wersji {{ $version }}</h2>
            <p class="mb-4 text-sm text-muted">Najważniejsze zmiany ostatniego wydania.</p>
            <ul role="list" class="grid gap-4 sm:grid-cols-2">
                @foreach ($whatsNew as [$icon, $title, $text, $route])
                    @php $href = $route && \Illuminate\Support\Facades\Route::has($route) && ($isAdmin || $route === 'profile.edit') ? route($route).($route === 'profile.edit' ? '#wyglad-panelu' : '') : null; @endphp
                    <li class="flex gap-4 rounded-md bg-white p-5">
                        <span class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-brand-light text-brand-dark" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
                        <div class="min-w-0">
                            <h3 class="font-bold text-ink">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-ink">{{ $text }}</p>
                            @if ($href)
                                <a href="{{ $href }}" class="mt-2 inline-flex min-h-11 items-center gap-1.5 text-sm {{ $link }}">Przejdź<span class="sr-only"> — {{ $title }}</span> <span aria-hidden="true">→</span></a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Co potrafi --}}
        <section aria-labelledby="about-features">
            <h2 id="about-features" class="mb-4 text-lg font-bold text-ink">Co potrafi</h2>
            <ul role="list" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($features as [$icon, $title, $text])
                    <li class="rounded-md bg-white p-5">
                        <span class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-light text-brand-dark" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
                        <h3 class="font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- Autor --}}
            <section class="rounded-md bg-white p-6 sm:p-8" aria-labelledby="about-author">
                <h2 id="about-author" class="text-lg font-bold text-ink">Autor</h2>
                <div class="mt-4 flex items-center gap-4">
                    <span class="inline-flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand-dark text-xl font-bold text-white" aria-hidden="true">ZG</span>
                    <div>
                        <p class="text-xl font-bold text-ink">Ziemowit Gil</p>
                        <p class="text-sm text-ink">Autor weCMS — projekt, rozwój i utrzymanie systemu.</p>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-ink">
                    Pytania, uwagi i zgłoszenia błędów:
                    <a href="mailto:ziemowit.gil@gmail.com" class="{{ $link }}">ziemowit.gil@gmail.com</a>.
                </p>
            </section>

            {{-- Informacje techniczne --}}
            <section class="rounded-md bg-white p-6 sm:p-8" aria-labelledby="about-tech">
                <h2 id="about-tech" class="text-lg font-bold text-ink">Informacje techniczne</h2>
                <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-muted">Wersja weCMS</dt><dd class="mt-0.5 font-bold text-ink" data-cms-version>{{ $version }}</dd></div>
                    <div><dt class="text-muted">Framework</dt><dd class="mt-0.5 font-bold text-ink">Laravel {{ app()->version() }}</dd></div>
                    <div><dt class="text-muted">PHP</dt><dd class="mt-0.5 font-bold text-ink">{{ PHP_VERSION }}</dd></div>
                    <div><dt class="text-muted">Baza danych</dt><dd class="mt-0.5 font-bold text-ink">{{ $dbDriver }}</dd></div>
                    <div class="col-span-2"><dt class="text-muted">Serwis</dt><dd class="mt-0.5 font-bold text-ink">{{ $siteSettings->site_name }}</dd></div>
                </dl>
                @if ($isAdmin)
                    <div class="mt-4 flex flex-wrap gap-x-5">
                        @if (\Illuminate\Support\Facades\Route::has('admin.health.index'))
                            <a href="{{ route('admin.health.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm {{ $link }}"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i> Kondycja serwisu</a>
                        @endif
                        <a href="{{ route('admin.dokumentacja') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1.5 text-sm {{ $link }}"><i class="fa-solid fa-book" aria-hidden="true"></i> Dokumentacja techniczna<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
