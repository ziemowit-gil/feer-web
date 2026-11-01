{{--
    Oddziały organizacji (np. oddział bez osobowości prawnej) na stronie kontaktowej. Dane z Ustawienia → Kontakt → Oddziały.
    Płasko: karta na jasnoszarym tle, bez obramowań; kontrast tekstu ≥ 4,5:1. Style opakowania jak w pozostałych sekcjach.
--}}
@php
    $branches = $siteSettings->contactBranches();
    $sectionClass = match ($sectionStyle ?? 'plain') {
        'card' => 'h-full scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm',
        'bare' => 'scroll-mt-24',
        default => 'mt-12 scroll-mt-24 border-t border-gray-100 pt-8',
    };
    $brLink = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($branches)
    <section id="oddzialy" class="{{ $sectionClass }}" aria-labelledby="oddzialy-heading">
        <h2 id="oddzialy-heading" class="mb-5 text-2xl font-bold text-ink">{{ $siteSettings->contact_branches_title ?: 'Oddziały' }}</h2>
        <ul class="grid gap-4 {{ count($branches) > 1 ? 'md:grid-cols-2' : '' }}" role="list">
            @foreach ($branches as $branch)
                <li class="rounded-md bg-gray-50 p-5">
                    <h3 class="text-lg font-bold text-ink">{{ $branch['name'] }}</h3>
                    @if (filled($branch['status'] ?? null))
                        <p class="mt-1 text-sm text-ink">{{ $branch['status'] }}</p>
                    @endif
                    <dl class="mt-4 space-y-3">
                        @if (filled($branch['address'] ?? null) || filled($branch['city'] ?? null))
                            <div class="flex gap-3">
                                <i class="fa-solid fa-location-dot mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Adres</dt><dd class="font-semibold text-ink">{{ collect([$branch['address'] ?? null, $branch['city'] ?? null])->filter()->implode(', ') }}</dd></div>
                            </div>
                        @endif
                        @if (filled($branch['email'] ?? null))
                            <div class="flex gap-3">
                                <i class="fa-solid fa-envelope mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">E-mail</dt><dd><a href="mailto:{{ $branch['email'] }}" class="{{ $brLink }} break-all">{{ $branch['email'] }}</a></dd></div>
                            </div>
                        @endif
                        @if (filled($branch['phone'] ?? null))
                            <div class="flex gap-3">
                                <i class="fa-solid fa-phone mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Telefon</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $branch['phone']) }}" class="{{ $brLink }}">{{ $branch['phone'] }}</a></dd></div>
                            </div>
                        @endif
                        @if (filled($branch['hours'] ?? null))
                            <div class="flex gap-3">
                                <i class="fa-solid fa-clock mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Godziny</dt><dd class="font-semibold text-ink">{{ $branch['hours'] }}</dd></div>
                            </div>
                        @endif
                    </dl>
                    @if (filled($branch['note'] ?? null))
                        <p class="mt-4 text-sm text-ink">{{ $branch['note'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
