{{-- Numery rachunków bankowych. --}}
@php
    // Sekcje strony kontaktowej mają dwa style opakowania: „plain" — kreska nad
    // sekcją (wariant klasyczny) i „card" — karta w siatce (nowe wyglądy).
    $sectionClass = ($sectionStyle ?? 'plain') === 'card'
        ? 'h-full scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm'
        : 'mt-12 scroll-mt-24 border-t border-gray-100 pt-8';
@endphp
@if (! empty($siteSettings->contact_bank_accounts))
    <div id="rachunki" class="{{ $sectionClass }}">
        <h2 class="mb-2 text-xl font-bold text-ink">Numery rachunków bankowych</h2>
        <p class="mb-5 max-w-2xl text-sm text-muted">Przy każdym rachunku opisujemy, do czego służy i co można na niego wpłacić.</p>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($siteSettings->contact_bank_accounts as $account)
                <div class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-gray-50/60 p-5">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid fa-building-columns"></i>
                    </span>
                    <div class="min-w-0">
                        @if (! empty($account['purpose']))
                            <p class="font-bold text-ink">{{ $account['purpose'] }}</p>
                        @endif
                        <p class="{{ ! empty($account['purpose']) ? 'mt-1' : '' }} overflow-x-auto whitespace-nowrap font-mono text-sm text-ink">{{ $account['number'] }}</p>
                        <button type="button" data-copy-button data-copy-value="{{ $account['number'] }}"
                            class="mt-2.5 inline-flex items-center gap-1.5 rounded-full border border-brand px-3 py-1 text-xs font-bold text-brand transition hover:bg-brand-light">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
