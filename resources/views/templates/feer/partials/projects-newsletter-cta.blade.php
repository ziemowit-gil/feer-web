{{--
    Szablon FEER — zachęta do newslettera pod kaflami „Nasze działania”: jeden płaski wiersz z przyciskiem
    do strony zapisu (bez formularza i bez osobnego pasa — pasów pod „Wesprzyj” ma być mało).
    Tylko gdy moduł Newsletter jest włączony. Kontrast: ink na białym, biały na brand-dark (≥ 4,5:1, także po najechaniu).
--}}
@if ($siteSettings->isModuleEnabled('newsletter') && \Illuminate\Support\Facades\Route::has('newsletter.show'))
    <div class="mt-6 flex flex-col gap-5 rounded-md bg-white p-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-4">
            <i class="fa-regular fa-envelope mt-1 text-2xl text-brand-dark" aria-hidden="true"></i>
            <div>
                <h3 id="feer-projects-nl-heading" class="text-lg font-bold text-ink">Nie przegap nowych szkoleń i materiałów</h3>
                <p class="mt-1 text-sm leading-relaxed text-ink">Raz w miesiącu wyślemy Ci nowe działania, terminy szkoleń i bezpłatne materiały. Bez spamu — wypiszesz się jednym kliknięciem.</p>
            </div>
        </div>
        <a href="{{ route('newsletter.show') }}"
           class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 motion-reduce:transition-none">
            Zapisz się na newsletter <span aria-hidden="true">→</span>
        </a>
    </div>
@endif
