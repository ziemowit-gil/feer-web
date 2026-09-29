{{--
    Numery rachunków bankowych + dowolna notatka tekstowa.

    Układ listy rachunków wybierany w Ustawienia → Kontakt
    (SiteSetting::BANK_ACCOUNTS_LAYOUTS): cards | list | table | highlight.
    Przyciski „Kopiuj numer" obsługuje contact/partials/copy-script.
--}}
@php
    // Sekcje strony kontaktowej mają dwa style opakowania: „plain" — kreska nad
    // sekcją (wariant klasyczny) i „card" — karta w siatce (nowe wyglądy).
    $sectionClass = match ($sectionStyle ?? 'plain') {
        'card' => 'h-full scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm',
        'bare' => 'scroll-mt-24',
        default => 'mt-12 scroll-mt-24 border-t border-gray-100 pt-8',
    };
    $accounts = collect($siteSettings->contact_bank_accounts ?? [])->filter(fn ($a) => filled($a['number'] ?? null))->values();
    $layout = array_key_exists((string) $siteSettings->contact_bank_accounts_layout, \App\Models\SiteSetting::BANK_ACCOUNTS_LAYOUTS)
        ? $siteSettings->contact_bank_accounts_layout
        : 'cards';
    $copyBtn = 'inline-flex min-h-9 items-center gap-1.5 rounded-full border border-brand px-3 text-xs font-bold text-brand transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
@endphp
@if ($accounts->isNotEmpty() || filled($siteSettings->contact_bank_accounts_note))
    <div id="rachunki" class="{{ $sectionClass }}">
        <h2 class="mb-2 text-xl font-bold text-ink">Numery rachunków bankowych</h2>
        <p class="mb-5 max-w-2xl text-sm text-muted">Przy każdym rachunku opisujemy, do czego służy i co można na niego wpłacić.</p>

        @if (filled($siteSettings->contact_bank_accounts_note))
            <div class="prose prose-sm mb-5 max-w-2xl text-ink">
                {!! nl2br(e($siteSettings->contact_bank_accounts_note)) !!}
            </div>
        @endif

        @if ($accounts->isNotEmpty())
            @if ($layout === 'list')
                {{-- Lista wierszy: przeznaczenie po lewej, numer i „Kopiuj" po prawej --}}
                <ul class="divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white" aria-label="Rachunki bankowe">
                    @foreach ($accounts as $account)
                        <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <i class="fa-solid fa-building-columns mt-1 flex-none text-brand" aria-hidden="true"></i>
                                <p class="font-bold text-ink">{{ $account['purpose'] ?? 'Rachunek bankowy' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                                <code class="rounded-md bg-gray-100 px-3 py-1.5 font-mono text-sm tracking-wide text-ink">{{ $account['number'] }}</code>
                                <button type="button" data-copy-button data-copy-value="{{ $account['number'] }}" class="{{ $copyBtn }}">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

            @elseif ($layout === 'table')
                {{-- Tabela dostępna: nagłówki kolumn i wierszy, podpis, przewijanie na wąskim ekranie --}}
                <div class="overflow-x-auto rounded-2xl border border-gray-200">
                    <table class="min-w-full text-sm">
                        <caption class="sr-only">Rachunki bankowe: przeznaczenie i numer</caption>
                        <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Przeznaczenie</th>
                                <th scope="col" class="px-4 py-3">Numer rachunku</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Kopiowanie</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($accounts as $account)
                                <tr>
                                    <th scope="row" class="px-4 py-3 text-left font-bold text-ink">{{ $account['purpose'] ?? 'Rachunek bankowy' }}</th>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono tracking-wide text-ink">{{ $account['number'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" data-copy-button data-copy-value="{{ $account['number'] }}" class="{{ $copyBtn }}">
                                            <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj<span class="sr-only"> numer: {{ $account['purpose'] ?? $account['number'] }}</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif ($layout === 'highlight')
                {{-- Pierwszy rachunek wyróżniony, pozostałe jako lista --}}
                @php $main = $accounts->first(); $rest = $accounts->slice(1); @endphp
                <div class="rounded-2xl border border-brand/30 bg-brand-light/50 p-6">
                    <p class="mb-1 text-xs font-bold uppercase tracking-wide text-brand">Główny rachunek</p>
                    @if (! empty($main['purpose']))
                        <p class="text-lg font-bold text-ink">{{ $main['purpose'] }}</p>
                    @endif
                    <p class="mt-3 overflow-x-auto whitespace-nowrap font-mono text-xl tracking-wider text-ink sm:text-2xl">{{ $main['number'] }}</p>
                    <button type="button" data-copy-button data-copy-value="{{ $main['number'] }}"
                        class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-full bg-brand px-5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                    </button>
                </div>
                @if ($rest->isNotEmpty())
                    <ul class="mt-4 divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white" aria-label="Pozostałe rachunki">
                        @foreach ($rest as $account)
                            <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p class="font-bold text-ink">{{ $account['purpose'] ?? 'Rachunek bankowy' }}</p>
                                <div class="flex flex-wrap items-center gap-3">
                                    <code class="rounded-md bg-gray-100 px-3 py-1.5 font-mono text-sm tracking-wide text-ink">{{ $account['number'] }}</code>
                                    <button type="button" data-copy-button data-copy-value="{{ $account['number'] }}" class="{{ $copyBtn }}">
                                        <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

            @else
                {{-- Karty w siatce (domyślny) --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($accounts as $account)
                        <div class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-gray-50/60 p-5">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                <i class="fa-solid fa-building-columns"></i>
                            </span>
                            <div class="min-w-0">
                                @if (! empty($account['purpose']))
                                    <p class="font-bold text-ink">{{ $account['purpose'] }}</p>
                                @endif
                                <p class="{{ ! empty($account['purpose']) ? 'mt-1' : '' }} overflow-x-auto whitespace-nowrap font-mono text-sm text-ink">{{ $account['number'] }}</p>
                                <button type="button" data-copy-button data-copy-value="{{ $account['number'] }}" class="mt-2.5 {{ $copyBtn }}">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endif
