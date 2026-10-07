{{--
    Szablon FEER — „Na skróty" (moduł quick_actions; „Szybkie akcje" to tylko nazwa administracyjna):
    ciemny, smukły pasek z linkami w jednym rzędzie — etykieta po lewej, skróty obok (kolor akcji jako mały kwadrat).
    Biały tekst na #1D1D1A (16,9:1), linki podkreślają się po najechaniu; na telefonie zawijają się do kolumny.
    Włączana w Ustawienia → Strona główna („Ankieta i szybkie akcje" — nazwa administracyjna).
--}}
@php
    $feerNamed = ['blue' => '#1e6dff', 'dark' => '#cbd5e7', 'green' => '#4ade80', 'purple' => '#c084fc', 'orange' => '#ea8f00', 'red' => '#f87171'];
@endphp
@if (($quickLinks ?? collect())->isNotEmpty())
    <section class="bg-ink text-white" aria-labelledby="feer-shortcuts-heading">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-5 md:flex-row md:items-center md:gap-8">
            <h2 id="feer-shortcuts-heading" class="shrink-0 text-sm font-bold uppercase tracking-widest">Na skróty</h2>

            <nav aria-label="Na skróty" class="min-w-0">
                <ul class="flex flex-col gap-1 md:flex-row md:flex-wrap md:items-center md:gap-x-8 md:gap-y-2" role="list">
                    @foreach ($quickLinks as $qa)
                        @php
                            $accent = \App\Support\Color::isValid($qa->color) ? $qa->color : ($feerNamed[$qa->color] ?? '#ffffff');
                            $external = \Illuminate\Support\Str::startsWith($qa->url, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains($qa->url, request()->getHost());
                        @endphp
                        <li>
                            <a href="{{ $qa->url }}" @if ($external) target="_blank" rel="noopener" @endif
                               class="group inline-flex min-h-11 items-center gap-3 rounded-sm text-base font-bold text-white underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                                <span class="h-3 w-3 flex-none rounded-sm" style="background: {{ $accent }}" aria-hidden="true"></span>
                                <span>{{ $qa->label }}</span>
                                @if ($external)<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                                <span class="transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </section>
@endif
