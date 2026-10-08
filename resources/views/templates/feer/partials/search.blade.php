{{-- Duża wyszukiwarka na stronie głównej (szablon FEER): prowadzi do wyszukiwarki serwisu (/szukaj?q=). --}}
<section class="mx-auto max-w-6xl px-4 py-8" aria-labelledby="home-search-heading">
    <style>
        .hs-box { padding: 1.5rem; border-radius: .5rem; background: var(--color-brand-light); }
        .hs-label { display: block; margin-bottom: .6rem; font-size: 1.25rem; font-weight: 800; color: #1d1d1a; }
        .hs-row { display: flex; flex-wrap: wrap; gap: .75rem; }
        .hs-input { flex: 1 1 18rem; min-width: 0; min-height: 3.5rem; padding: .5rem 1rem; border: 2px solid #1d1d1a; border-radius: .5rem; background: #fff; font-size: 1.125rem; color: #1d1d1a; }
        .hs-input:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; }
        .hs-btn { display: inline-flex; min-height: 3.5rem; align-items: center; justify-content: center; gap: .6rem; padding: .5rem 2rem; border: 2px solid var(--color-brand-dark); border-radius: .5rem; background: var(--color-brand-dark); color: #fff; font-size: 1.125rem; font-weight: 800; cursor: pointer; }
        .hs-btn:hover { background: #1d1d1a; border-color: #1d1d1a; }
        .hs-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
        .hs-hint { margin: .6rem 0 0; font-size: .9rem; color: #374151; }
    </style>
    <form method="GET" action="{{ route('search') }}" role="search" aria-label="Szukaj w serwisie" class="hs-box">
        <label for="home-search" id="home-search-heading" class="hs-label">Czego szukasz?</label>
        <div class="hs-row">
            <input type="search" id="home-search" name="q" placeholder="np. szkolenia, materiały dla szkół, wolontariat" class="hs-input" autocomplete="off">
            <button type="submit" class="hs-btn"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Szukaj</button>
        </div>
        <p class="hs-hint">Przeszukujemy aktualności i materiały edukacyjne.</p>
    </form>
</section>
