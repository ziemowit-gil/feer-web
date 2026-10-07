{{--
    Szablon FEER — „Na skróty" (moduł quick_actions; „Szybkie akcje" to tylko nazwa administracyjna).
    Wyraźne kafle w jednym rzędzie. Wygląd zależy od ustawień:
      • akcja zwykła  → obramówka 2 px w kolorze akcji (domyślnie kolor FEER), białe tło, ciemny tekst,
      • akcja „Negatyw" (is_negative) → wypełnione tłem w kolorze akcji, tekst dobrany pod kontrast ≥ 4,5:1,
      • tło sekcji: białe, gdy włączono „Białe tło sekcji" (Ustawienia → Strona główna), w przeciwnym razie jasnoszare.
    Kolor obramówki jest przyciemniany do kontrastu ≥ 4,5:1 na bieli (WCAG 1.4.11).
--}}
@php
    $feerNamed = ['blue' => '#1b66f5', 'dark' => '#1d1d1a', 'green' => '#166534', 'purple' => '#7e22ce', 'orange' => '#b45309', 'red' => '#b91c1c'];
    $panelWhite = (bool) ($siteSettings->quick_actions_panel_negative ?? false);
    $qaCols = match (min(($quickLinks ?? collect())->count(), 4)) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };
@endphp
@if (($quickLinks ?? collect())->isNotEmpty())
    <section class="{{ $panelWhite ? 'bg-white' : 'bg-gray-50' }} py-10" aria-labelledby="feer-shortcuts-heading">
        <div class="mx-auto max-w-6xl px-4">
            <h2 id="feer-shortcuts-heading" class="mb-6 text-2xl font-bold text-ink md:text-3xl">Na skróty</h2>

            <nav aria-label="Na skróty">
                <ul class="grid gap-4 {{ $qaCols }}" role="list">
                    @foreach ($quickLinks as $qa)
                        @php
                            $base = \App\Support\Color::isValid($qa->color) ? $qa->color : ($feerNamed[$qa->color] ?? $feerNamed['blue']);
                            $safe = $siteSettings->contrastSafeColor($base);
                            $filled = (bool) $qa->is_negative;
                            $pal = $filled ? \App\Support\Color::button($base) : null;
                            $iconClass = (str_contains((string) $qa->icon, 'fa-') || str_starts_with((string) $qa->icon, 'bi ')) ? $qa->icon : 'bi '.($qa->icon ?: 'bi-lightning');
                            $external = \Illuminate\Support\Str::startsWith($qa->url, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains($qa->url, request()->getHost());
                        @endphp
                        <li>
                            <a href="{{ $qa->url }}" @if ($external) target="_blank" rel="noopener" @endif
                               class="feer-card group flex min-h-20 items-center gap-4 rounded-md px-5 py-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 {{ $filled ? 'hover:opacity-90' : ($panelWhite ? 'bg-white' : 'bg-white').' hover:bg-gray-100' }}"
                               @if ($filled)
                                   style="background-color: {{ $pal['bg'] }}; color: {{ $pal['text'] }}"
                               @else
                                   style="border: 2px solid {{ $safe }}; color: #1d1d1a"
                               @endif>
                                <i class="{{ $iconClass }} w-7 flex-none text-center text-2xl" @unless ($filled) style="color: {{ $safe }}" @endunless aria-hidden="true"></i>
                                <span class="min-w-0 flex-1 text-lg font-bold leading-snug">{{ $qa->label }}</span>
                                @if ($external)<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                                <span class="flex-none text-xl transition group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </section>
@endif
