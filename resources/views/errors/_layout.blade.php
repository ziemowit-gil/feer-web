{{--
    Wspólny, minimalny układ stron błędów (404/403/500) — sam ogromny kod
    błędu i krótki, ludzki opis. Parametry: $code, $description.
--}}
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
