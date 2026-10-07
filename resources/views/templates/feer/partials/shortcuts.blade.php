{{--
    Szablon FEER — „Na skróty" (moduł quick_actions; „Szybkie akcje" to tylko nazwa administracyjna).
    Wyraźne kafle w jednym rzędzie. Wygląd zależy od ustawień:
      • akcja zwykła  → obramówka 2 px w kolorze akcji (domyślnie kolor FEER), białe tło, ciemny tekst,
      • akcja „Negatyw" (is_negative) → wypełnione tłem w kolorze akcji, tekst dobrany pod kontrast ≥ 4,5:1,
      • tło sekcji: białe, gdy włączono „Białe tło sekcji" (Ustawienia → Strona główna), w przeciwnym razie jasnoszare.
    Obsługiwane: „Negatyw”, „Pasek” (niski kafel), „Kolumny” (szerokość 2–3 kolumn), „Białe tło sekcji”.
    Kolor obramówki jest przyciemniany do kontrastu ≥ 4,5:1 na bieli (WCAG 1.4.11).
--}}
@php
    // Kolory nazwane → paleta brandbooka (niebieski #1E6DFF, grafit #1D1D1A, pomarańcz #EA8F00); zielony/fioletowy/czerwony zostają dla starszych wpisów.
    $feerNamed = ['blue' => '#1e6dff', 'dark' => '#1d1d1a', 'green' => '#166534', 'purple' => '#7e22ce', 'orange' => '#ea8f00', 'red' => '#b91c1c'];
    $panelWhite = (bool) ($siteSettings->quick_actions_panel_negative ?? false);
    // Jedna akcja → jeden duży przycisk na całą szerokość (wyższy, większy tekst i ikona).
    $single = ($quickLinks ?? collect())->count() === 1;
    $qaN = min(($quickLinks ?? collect())->count(), 4);
    $qaCols = match ($qaN) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };
    // Szerokość kafla („Kolumny” w panelu): zajmuje 2–3 kolumny siatki, ale nie więcej niż jest kolumn.
    $qaSpan = fn (int $cols) => match (true) {
        $cols >= 3 && $qaN >= 3 => 'sm:col-span-2 lg:col-span-'.min($cols, $qaN),
        $cols >= 2 && $qaN >= 2 => 'sm:col-span-2',
        default => '',
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
                            $strip = (bool) $qa->strip && ! $single; // „Pasek”: niski kafel, ikona obok tekstu
                            $pal = $filled ? \App\Support\Color::button($base) : null;
                            $iconClass = (str_contains((string) $qa->icon, 'fa-') || str_starts_with((string) $qa->icon, 'bi ')) ? $qa->icon : 'bi '.($qa->icon ?: 'bi-lightning');
                            $external = \Illuminate\Support\Str::startsWith($qa->url, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains($qa->url, request()->getHost());
                        @endphp
                        <li class="{{ $qaSpan((int) ($qa->cols ?? 1)) }}">
                            <a href="{{ $qa->url }}" @if ($external) target="_blank" rel="noopener" @endif
                               class="feer-card group flex {{ $single ? 'min-h-32 gap-6 px-8 py-6' : ($strip ? 'min-h-14 gap-3 px-4 py-2' : 'min-h-20 gap-4 px-5 py-4') }} items-center rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 {{ $filled ? 'hover:opacity-90' : ($panelWhite ? 'bg-white' : 'bg-white').' hover:bg-gray-100' }}"
                               @if ($filled)
                                   style="background-color: {{ $pal['bg'] }}; color: {{ $pal['text'] }}"
                               @else
                                   style="border: 2px solid {{ $safe }}; color: #1d1d1a"
                               @endif>
                                <i class="{{ $iconClass }} flex-none text-center {{ $single ? 'w-12 text-5xl' : ($strip ? 'w-6 text-xl' : 'w-7 text-2xl') }}" @unless ($filled) style="color: {{ $safe }}" @endunless aria-hidden="true"></i>
                                <span class="min-w-0 flex-1 font-bold leading-snug {{ $single ? 'text-2xl md:text-3xl' : ($strip ? 'text-base' : 'text-lg') }}">{{ $qa->label }}</span>
                                @if ($external)<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                                <span class="flex-none transition {{ $single ? 'text-3xl' : 'text-xl' }} group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </section>
@endif
