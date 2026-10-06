{{--
    Szablon FEER — „Szybkie akcje" (moduł quick_actions): płaska sekcja z kafelkami skrótów
    (partials._tiles-grid), pod hero. Włączana i wyłączana w Ustawienia → Strona główna („Ankieta i szybkie akcje").
--}}
@if (($quickLinks ?? collect())->isNotEmpty())
    <section class="border-b border-gray-100 bg-gray-50 py-12" aria-labelledby="feer-shortcuts-heading">
        <div class="mx-auto max-w-6xl px-4">
            <h2 id="feer-shortcuts-heading" class="mb-6 text-2xl font-bold text-ink md:text-3xl">Szybkie akcje</h2>

            @include('partials._tiles-grid', ['tiles' => $quickLinks, 'label' => 'Szybkie akcje'])
        </div>
    </section>
@endif
