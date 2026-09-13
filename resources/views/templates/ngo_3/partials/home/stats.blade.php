{{--
    Sekcja: pasek liczników (statystyki organizacji) — treść z
    SiteSetting::ngo3Stats(), edytowalna w panelu (Ustawienia → Strona główna,
    tylko dla szablonu "ngo_3").
--}}
@if (! empty($stats))
<section class="bg-brand py-14" aria-labelledby="ngo3-stats-heading">
    <h2 id="ngo3-stats-heading" class="sr-only">Liczby, którymi się kierujemy</h2>
    <div class="mx-auto max-w-[1400px] px-4">
        <div class="grid gap-8 text-center sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="flex flex-col items-center gap-2">
                    @if (! empty($stat['icon']))
                        <i class="{{ $stat['icon'] }} text-3xl text-white/80" aria-hidden="true"></i>
                    @endif
                    <span class="text-4xl font-extrabold text-white">{{ $stat['value'] ?? '' }}</span>
                    <span class="text-sm font-semibold text-white/90">{{ $stat['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
