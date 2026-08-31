<section class="bg-white" aria-labelledby="federation-hero-heading">
    <div class="mx-auto grid max-w-[1400px] gap-12 px-4 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-24">
        <div>
            <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">O nas</p>
            <h1 id="federation-hero-heading" class="mb-6 text-4xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl">
                Razem dla lepszego jutra
            </h1>
            <div class="max-w-2xl space-y-4 text-base leading-relaxed text-muted">
                <p>
                    {{ $siteSettings->site_name }} jest jedną z pierwszych w Małopolsce federacji organizacji pozarządowych.
                    Od 1998 roku działamy nieprzerwanie, aby szybciej i skuteczniej rozwiązywać problemy w działalności
                    organizacji pozarządowych oraz lokalnej społeczności. Pracujemy na rzecz rozwoju społeczeństwa
                    obywatelskiego, reprezentujemy interesy organizacji i grup nieformalnych, budujemy ich rzetelny
                    wizerunek oraz promujemy partnerską współpracę z administracją publiczną.
                </p>
                <p>
                    Najważniejszy jest dla nas człowiek, dlatego nieustannie niesiemy pomoc osobom w trudnej sytuacji
                    życiowej, rodzinom, jak również organizacjom, które niosą wsparcie potrzebującym.
                </p>
            </div>
        </div>

        {{-- Ilustracja zastępcza: mozaika kafelków w kolorach marki (zamiast jednej dużej karty) — edytowalna w panelu (Ustawienia → Strona główna). --}}
        <div class="mx-auto grid w-full max-w-sm grid-cols-2 gap-3" role="img" aria-label="Organizacje pozarządowe działające razem dla Krakowa">
            @foreach ($siteSettings->federationHeroTiles() as $tile)
                @php $tileColor = $siteSettings->brandColorN((int) ($tile['color'] ?? 1)); @endphp
                <div class="flex items-center gap-3 rounded-lg p-6 text-white {{ ($tile['wide'] ?? false) ? 'col-span-2' : 'flex-col items-start' }}" style="background:{{ $tileColor }}">
                    @if (filled($tile['value'] ?? null))
                        <span class="text-4xl font-extrabold leading-none">{{ $tile['value'] }}</span>
                    @elseif (filled($tile['icon'] ?? null))
                        <i class="{{ $tile['icon'] }} text-2xl" aria-hidden="true"></i>
                    @endif
                    <span class="text-sm font-semibold leading-snug {{ filled($tile['value'] ?? null) ? 'text-white/90' : '' }}">{{ $tile['title'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
