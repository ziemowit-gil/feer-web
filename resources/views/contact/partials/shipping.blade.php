{{-- Przesyłki / paczkomat. Wymaga $showShipping, $shipNote, $pkCode, $pkAddr, $shipPhone. --}}
@php
    // Sekcje strony kontaktowej mają dwa style opakowania: „plain" — kreska nad
    // sekcją (wariant klasyczny) i „card" — karta w siatce (nowe wyglądy).
    $sectionClass = match ($sectionStyle ?? 'plain') {
        'card' => 'h-full scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm',
        'bare' => 'scroll-mt-24',
        default => 'mt-12 scroll-mt-24 border-t border-gray-100 pt-8',
    };
@endphp
@if ($showShipping)
    @php
        $pkLoc = $siteSettings->contact_paczkomat_location;
    @endphp
    <div id="przesylki" class="{{ $sectionClass }}">
        <h2 class="mb-2 text-lg font-bold text-ink">Wyślij do nas przesyłkę</h2>
        <p class="mb-4 max-w-2xl text-sm text-muted">{{ $shipNote ?: 'Możesz nadać do nas paczkę lub list — również na paczkomat.' }}</p>

        <div class="rounded-lg border border-gray-200 p-4">
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-light text-sm text-brand" aria-hidden="true">
                    <i class="fa-solid fa-box-open"></i>
                </span>
                <h3 class="text-sm font-bold text-ink">Paczkomat InPost</h3>
            </div>

            <div class="grid gap-x-8 gap-y-3 sm:grid-cols-2">
                @if (filled($pkCode))
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted">Kod paczkomatu</p>
                        <div class="mt-0.5 flex items-baseline gap-2">
                            <p class="font-mono text-base font-bold text-ink">{{ $pkCode }}</p>
                            <button type="button" data-copy-button data-copy-value="{{ $pkCode }}"
                                class="inline-flex min-h-6 items-center gap-1 rounded px-1.5 py-1 text-xs font-bold text-brand transition hover:bg-brand-light hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj
                            </button>
                        </div>
                    </div>
                @endif

                <ul class="space-y-1 text-sm">
                    @if (filled($pkAddr))
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-location-dot mt-0.5 w-4 flex-none text-center text-muted" aria-hidden="true"></i>
                            <span class="text-ink">{{ $pkAddr }}</span>
                        </li>
                    @endif
                    @if (filled($pkLoc))
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-info mt-0.5 w-4 flex-none text-center text-muted" aria-hidden="true"></i>
                            <span class="text-muted">{{ $pkLoc }}</span>
                        </li>
                    @endif
                    @if (filled($shipPhone))
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-phone mt-0.5 w-4 flex-none text-center text-muted" aria-hidden="true"></i>
                            <a href="tel:{{ $shipPhone }}" class="font-medium text-ink hover:text-brand">{{ $shipPhone }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endif
