{{-- Skrót do strefy członkowskiej — funkcja włączana/wyłączana z panelu. Wąski pasek, celowo inny niż karta "Poznaj naszą organizację". --}}
@if ($siteSettings->federation_show_members_banner ?? false)
    <section aria-labelledby="members-banner-heading">
        <div class="mx-auto max-w-[1400px] px-4">
            <div class="flex flex-col items-center justify-center gap-3 rounded-md px-6 py-4 text-center sm:flex-row sm:justify-between sm:text-left"
                style="background:{{ $siteSettings->brandColorN(4) }}1a">
                <p class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-sm sm:justify-start">
                    <i class="fa-solid fa-user-lock text-brand" aria-hidden="true"></i>
                    <span id="members-banner-heading" class="font-extrabold text-ink">Jesteś organizacją członkowską?</span>
                    <span class="text-muted">Materiały i edycja wizytówki czekają w Strefie członkowskiej.</span>
                </p>
                <a href="{{ route('page.show', 'strefa-czlonkowska') }}"
                    class="flex-none text-sm font-extrabold text-brand underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Przejdź do Strefy członkowskiej
                    <i class="fa-solid fa-arrow-right ml-1 text-xs" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>
@endif
