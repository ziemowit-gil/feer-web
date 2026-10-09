{{--
    Wspólny, minimalny układ stron błędów (404/403/500) — sam ogromny kod
    błędu i krótki, ludzki opis. Parametry: $code, $description.
--}}
@php
    $feerErr = (function () { try { return (\App\Models\SiteSetting::current()->site_template ?? 'default') === 'feer'; } catch (\Throwable $e) { return false; } })();
@endphp
@if ($feerErr)
    {{-- Szablon FEER: lewostronny, spokojny układ — kod błędu jako dyskretny napis, tytuł z akcentem, wyszukiwarka i szybkie linki. --}}
    @php
        $errTitle = match ((string) $code) { '403' => 'Brak dostępu', '500' => 'Coś poszło nie tak', default => 'Nie znaleziono strony' };
        $quick = array_filter([
            ['Działania', 'fa-diagram-project', \Illuminate\Support\Facades\Route::has('projects.index') ? route('projects.index') : null],
            ['Aktualności', 'fa-newspaper', \Illuminate\Support\Facades\Route::has('news.index') ? route('news.index') : null],
            ['Materiały edukacyjne', 'fa-book-open', \Illuminate\Support\Facades\Route::has('materials.index') ? route('materials.index') : null],
            ['Kontakt', 'fa-envelope', \Illuminate\Support\Facades\Route::has('contact.show') ? route('contact.show') : null],
        ], fn ($q) => $q[2]);
    @endphp
    <section class="mx-auto max-w-4xl px-4 py-16 md:py-24">
        <p class="text-xs font-bold uppercase tracking-widest text-muted">Błąd {{ $code }}</p>
        <h1 class="mt-2 text-3xl font-bold leading-tight text-ink md:text-4xl">{{ $errTitle }}</h1>
        <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink">{{ $description }}</p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('home') }}" class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand px-6 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                <i class="fa-solid fa-house" aria-hidden="true"></i>Strona główna
            </a>
        </div>

        @if (\Illuminate\Support\Facades\Route::has('search'))
            <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukaj w serwisie" class="mt-10 flex max-w-xl flex-col gap-3 sm:flex-row">
                <label for="err-q" class="sr-only">Szukana fraza</label>
                <input id="err-q" type="search" name="q" placeholder="Poszukaj tego, czego potrzebujesz…" class="min-h-12 w-full rounded-t-md rounded-b-none border-0 border-b-2 border-gray-500 bg-gray-100 px-4 text-base text-ink focus:border-brand focus:bg-white focus:outline-none focus:ring-0">
                <button type="submit" class="inline-flex min-h-12 flex-none items-center justify-center gap-2 rounded-md bg-ink px-6 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Szukaj</button>
            </form>
        @endif

        @if ($quick)
            <nav aria-label="Przydatne strony" class="mt-12">
                <p class="mb-3 text-xs font-bold uppercase tracking-widest text-muted">Zajrzyj tutaj</p>
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" role="list">
                    @foreach ($quick as $i => [$label, $icon, $url])
                        @php $bg = \App\Support\ThemePalette::tiles()[$i % 4]; $fg = \App\Support\ThemePalette::button($bg)['text']; @endphp
                        <li>
                            <a href="{{ $url }}" class="feer-card group flex min-h-24 flex-col justify-between rounded-md p-4 transition hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2" style="background-color: {{ $bg }}; color: {{ $fg }}">
                                <i class="fa-solid {{ $icon }} text-xl" aria-hidden="true"></i>
                                <span class="mt-3 flex items-center justify-between font-bold">{{ $label }}<span aria-hidden="true">→</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </section>
@else
<section class="mx-auto flex min-h-[70vh] max-w-xl flex-col items-center justify-center px-4 text-center">
    <p class="select-none text-[7rem] font-black leading-none tracking-tight text-brand sm:text-[10rem]">
        {{ $code }}
    </p>
    <p class="mt-4 max-w-sm text-lg leading-relaxed text-muted">{{ $description }}</p>

    <a href="{{ route('home') }}"
        class="mt-8 inline-flex items-center gap-2 rounded-md bg-brand px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        Strona główna
    </a>
</section>
@endif
