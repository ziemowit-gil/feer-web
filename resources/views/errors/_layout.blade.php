{{--
    Wspólny, duży układ stron błędów (404/403/500) — ogromny kod błędu jako
    tło, ikona, tytuł i opis. Parametry: $code, $icon, $title, $description,
    $showSearch (opcjonalnie, domyślnie false).
--}}
@php $showSearch ??= false; @endphp

<section class="relative mx-auto flex min-h-[70vh] max-w-2xl flex-col items-center justify-center overflow-hidden px-4 py-20 text-center">
    <span class="pointer-events-none absolute inset-x-0 top-8 select-none text-[9rem] font-black leading-none text-brand/10 sm:text-[13rem]" aria-hidden="true">
        {{ $code }}
    </span>

    <div class="relative">
        <span class="mb-6 flex h-16 w-16 items-center justify-center rounded-lg bg-brand-light text-2xl text-brand">
            <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
        </span>

        <p class="mb-2 text-sm font-bold uppercase tracking-widest text-brand">Błąd {{ $code }}</p>
        <h1 class="mb-3 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">{{ $title }}</h1>
        <p class="mx-auto mb-8 max-w-md text-muted">{{ $description }}</p>

        @if ($showSearch)
            <form action="{{ route('search') }}" method="GET" role="search" class="mx-auto mb-6 flex max-w-sm overflow-hidden rounded-md border border-gray-300 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                <label for="error-search" class="sr-only">Wyszukaj w serwisie</label>
                <input id="error-search" type="search" name="q" placeholder="Czego szukasz?" autocomplete="off"
                    class="w-full border-none bg-transparent px-4 py-2.5 text-sm focus:outline-none">
                <button type="submit" class="flex-none px-4 text-muted hover:text-brand" aria-label="Szukaj">
                    <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                </button>
            </form>
        @endif

        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}"
                class="inline-flex items-center gap-2 rounded-md bg-brand px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                Strona główna
            </a>
            <a href="{{ route('contact.show') }}"
                class="inline-flex items-center gap-2 rounded-md border-2 border-gray-200 px-6 py-3 text-sm font-bold text-ink transition hover:border-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                Kontakt
            </a>
        </div>
    </div>
</section>
