{{--
    Szablon FEER — „Szybkie akcje" (moduł quick_actions): małe, płaskie kafle w jednym rzędzie
    (ikona + nazwa), z paskiem w kolorze akcji po lewej. Tekst zawsze ciemny na białym tle (kontrast AA),
    kolor akcji (#hex lub nazwa) jest tylko akcentem. Włączana w Ustawienia → Strona główna („Ankieta i szybkie akcje").
--}}
@php
    // Kolumny dopasowane do liczby akcji, żeby kafle wypełniały cały rząd (bez pustego miejsca po prawej).
    $qaCols = match (min(($quickLinks ?? collect())->count(), 4)) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };
    $feerNamed = ['blue' => '#1e6dff', 'dark' => '#1d1d1a', 'green' => '#16a34a', 'purple' => '#7e22ce', 'orange' => '#ea8f00', 'red' => '#dc2626'];
@endphp
@if (($quickLinks ?? collect())->isNotEmpty())
    <section class="border-b border-gray-100 bg-gray-50 py-8" aria-labelledby="feer-shortcuts-heading">
        <div class="mx-auto max-w-6xl px-4">
            <h2 id="feer-shortcuts-heading" class="mb-4 text-2xl font-bold text-ink md:text-3xl">Szybkie akcje</h2>

            <nav aria-label="Szybkie akcje">
                <ul class="grid gap-3 {{ $qaCols }}" role="list">
                    @foreach ($quickLinks as $qa)
                        @php
                            $accent = \App\Support\Color::isValid($qa->color) ? $qa->color : ($feerNamed[$qa->color] ?? 'var(--color-brand)');
                            $iconClass = (str_contains((string) $qa->icon, 'fa-') || str_starts_with((string) $qa->icon, 'bi ')) ? $qa->icon : 'bi '.($qa->icon ?: 'bi-lightning');
                            $external = \Illuminate\Support\Str::startsWith($qa->url, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains($qa->url, request()->getHost());
                        @endphp
                        <li>
                            <a href="{{ $qa->url }}" @if ($external) target="_blank" rel="noopener" @endif
                               class="group flex min-h-14 items-center gap-3 rounded-md border border-gray-200 bg-white px-4 py-2.5 transition hover:border-gray-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                               style="border-left: 4px solid {{ $accent }}">
                                <i class="{{ $iconClass }} w-5 flex-none text-center text-base" style="color: {{ $accent }}" aria-hidden="true"></i>
                                <span class="min-w-0 flex-1 text-sm font-bold leading-snug text-ink group-hover:text-brand">{{ $qa->label }}</span>
                                @if ($external)<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                                <span class="flex-none text-sm text-muted transition group-hover:translate-x-0.5 group-hover:text-brand" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </section>
@endif
