{{--
    Szablon FEER — zachęta do newslettera pod kaflami „Nasze działania”, w tym samym stylu co kafle:
    płaskie tło brand-light, duża ikona koperty w tle (ozdobnik bez przezroczystości, ukryty przed czytnikami),
    przycisk do strony zapisu. Tylko gdy moduł Newsletter jest włączony.
    Kontrast: ink na brand-light, biały na brand-dark (≥ 4,5:1, także po najechaniu — tło ink).
--}}
@if ($siteSettings->isModuleEnabled('newsletter') && \Illuminate\Support\Facades\Route::has('newsletter.show'))
    <div class="relative isolate mt-4 flex flex-col gap-6 overflow-hidden rounded-md bg-brand-light p-6 text-ink sm:flex-row sm:items-center sm:justify-between md:p-8">
        <span class="pointer-events-none absolute -bottom-8 right-56 -z-10 hidden select-none text-[9rem] leading-none text-white md:block" aria-hidden="true"><i class="fa-regular fa-envelope"></i></span>
        <div class="max-w-2xl">
            <span class="text-xs font-bold uppercase tracking-widest text-brand-dark">Newsletter</span>
            <h3 id="feer-projects-nl-heading" class="mt-1 text-xl font-bold leading-snug">Bądź pierwszą osobą, która dowie się o nowym szkoleniu</h3>
            <p class="mt-3 text-sm leading-relaxed">Miejsca na warsztatach szybko się kończą. Dołącz do osób, które jako pierwsze dostają terminy szkoleń, nowe bezpłatne materiały i historie zmian, które razem tworzymy. Jeden mail w miesiącu — i możesz zrezygnować w każdej chwili.</p>
        </div>
        <a href="{{ route('newsletter.show') }}"
           class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 motion-reduce:transition-none">
            Zapisuję się na newsletter <span aria-hidden="true">→</span>
        </a>
    </div>
@endif
