@extends('admin.layout')

@section('title', 'Ustaw witrynę główną')

{{--
    Potwierdzenie zmiany witryny głównej (MainSite::switchTo). Zmiana jest odwracalna tym samym przyciskiem,
    więc wystarcza ta strona z opisem skutków (WCAG 3.3.4). Kontrast: ink na bieli, przycisk biały na brand-dark;
    cele ≥ 44 px; płasko, bez ramek.
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $siteUrl = ($site->domain || $site->slug) ? current_site_url($site) : null;
    @endphp

    <div class="max-w-3xl">
        <p class="mb-4 text-sm">
            <a href="{{ route('admin.witryny.index') }}" class="inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Zarządzaj subwitrynami
            </a>
        </p>

        <h1 class="text-lg font-bold text-ink">Ustaw „{{ $site->site_name }}” jako witrynę główną</h1>
        <p class="mt-1 text-sm text-ink">Dziś witryną główną jest „{{ $current->site_name }}”.</p>

        <section aria-labelledby="h-main-effects" class="mt-4 rounded-md bg-white p-6">
            <h2 id="h-main-effects" class="font-bold text-ink">Co się zmieni</h2>
            <ul class="mt-2 list-disc space-y-2 pl-6 text-sm text-ink">
                <li>Pod adresem serwera <strong>{{ preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')) }}</strong> otworzy się „{{ $site->site_name }}”.@if ($siteUrl) Dotychczasowy adres {{ preg_replace('#^https?://#', '', $siteUrl) }} też będzie działać.@endif</li>
                <li>
                    „{{ $current->site_name }}” stanie się subwitryną „{{ $site->site_name }}”.
                    @if ($currentHasAddress)
                        Zostanie pod swoim adresem skróconym albo domeną.
                    @else
                        Nie ma adresu skróconego ani domeny, więc przestanie być dostępna publicznie — nadaj jej adres w „Zmień nazwę i adres”.
                    @endif
                </li>
                <li>Treść, ustawienia i podwitryny obu witryn zostają bez zmian.</li>
                <li>Zadania w tle i polecenia bez wskazanej witryny (np. przypomnienia, mapa strony) będą działać na „{{ $site->site_name }}”.</li>
                <li>
                    Ustawienia całej instalacji — logowanie do panelu (SSO, Microsoft), poczta, płatności — są czytane z witryny głównej.
                    Sprawdź je po zmianie w Ustawieniach albo przenieś z „{{ $current->site_name }}”.
                </li>
            </ul>
            <p class="mt-3 text-sm text-ink">Zmianę cofniesz tym samym przyciskiem „Ustaw jako główną” przy „{{ $current->site_name }}”.</p>
        </section>

        <form method="POST" action="{{ route('admin.witryny.glowna.update', $site) }}" class="mt-4 flex flex-wrap items-center gap-3">
            @csrf
            <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                <i class="fa-solid fa-house" aria-hidden="true"></i> Ustaw „{{ $site->site_name }}” jako główną
            </button>
            @if (Route::has('admin.witryny.pozostale'))
                <button type="submit" name="potem" value="usun-pozostale" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light" aria-describedby="then-others-help">
                    <i class="fa-solid fa-broom" aria-hidden="true"></i> Ustaw jako główną i usuń pozostałe
                </button>
            @endif
            <a href="{{ route('admin.witryny.index') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Anuluj</a>
        </form>
        @if (Route::has('admin.witryny.pozostale'))
            <p id="then-others-help" class="mt-2 text-sm text-ink">„Ustaw jako główną i usuń pozostałe” po zmianie przejdzie do listy pozostałych witryn — usuniesz je dopiero po potwierdzeniu.</p>
        @endif
    </div>
@endsection
