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

        {{-- Ilustracja zastępcza: mozaika kafelków w kolorach marki (zamiast jednej dużej karty) --}}
        <div class="mx-auto grid w-full max-w-sm grid-cols-2 gap-3" role="img" aria-label="Organizacje pozarządowe działające razem dla Krakowa">
            <div class="col-span-2 flex items-center gap-4 rounded-lg p-6 text-white" style="background:{{ $siteSettings->brandColorN(1) }}">
                <span class="text-4xl font-extrabold leading-none">1998</span>
                <span class="text-sm font-semibold leading-snug text-white/90">rok powstania federacji</span>
            </div>
            <div class="flex flex-col items-start gap-3 rounded-lg p-6 text-white" style="background:{{ $siteSettings->brandColorN(2) }}">
                <i class="fa-solid fa-people-group text-2xl" aria-hidden="true"></i>
                <span class="text-sm font-semibold leading-snug">Organizacje członkowskie</span>
            </div>
            <div class="flex flex-col items-start gap-3 rounded-lg p-6 text-white" style="background:{{ $siteSettings->brandColorN(4) }}">
                <i class="fa-solid fa-handshake-angle text-2xl" aria-hidden="true"></i>
                <span class="text-sm font-semibold leading-snug">Partnerska współpraca</span>
            </div>
            <div class="col-span-2 flex flex-col items-start gap-3 rounded-lg p-6 text-ink ring-1 ring-gray-200" style="background:{{ $siteSettings->brandColorN(3) }}22">
                <i class="fa-solid fa-city text-2xl" style="color:{{ $siteSettings->brandColorN(3) }}" aria-hidden="true"></i>
                <span class="text-sm font-semibold leading-snug">Razem dla Krakowa i jego mieszkańców</span>
            </div>
        </div>
    </div>
</section>
