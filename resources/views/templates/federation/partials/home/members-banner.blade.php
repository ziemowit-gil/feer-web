{{-- Skrót do strefy członkowskiej — funkcja włączana/wyłączana z panelu. --}}
@if ($siteSettings->federation_show_members_banner ?? true)
    <section class="py-4" aria-labelledby="members-banner-heading">
        <div class="mx-auto max-w-[1400px] px-4">
            <div class="flex flex-col items-center justify-between gap-6 rounded-lg border-2 border-dashed p-6 text-center sm:flex-row sm:text-left"
                style="border-color:{{ $siteSettings->brandColorN(1) }}">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid fa-user-lock text-lg"></i>
                    </span>
                    <div>
                        <p id="members-banner-heading" class="text-base font-extrabold text-ink">Jesteś organizacją członkowską?</p>
                        <p class="text-sm text-muted">Materiały, dokumenty i edycję swojej wizytówki znajdziesz w Strefie członkowskiej.</p>
                    </div>
                </div>
                <a href="{{ route('page.show', 'strefa-czlonkowska') }}"
                    class="flex-none rounded-md px-5 py-2.5 text-sm font-extrabold text-white transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    style="background:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                    Przejdź do Strefy członkowskiej
                </a>
            </div>
        </div>
    </section>
@endif
