{{--
    Sekcja: Na skróty — siatka kafelków QuickAction (moduł "quick_actions"),
    reużywa gotowy partial partials._tiles-grid zamiast wymyślać nowy styl
    kafelków (ten sam mechanizm co np. sidebar hero na stronie domyślnej).
--}}
@if ($quickLinks->isNotEmpty())
<section class="bg-gray-50 py-14" aria-labelledby="ngo3-shortcuts-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 id="ngo3-shortcuts-heading" class="mb-8 text-center text-2xl font-extrabold text-gray-900 md:text-3xl">
            Na skróty
        </h2>

        @include('partials._tiles-grid', ['tiles' => $quickLinks, 'label' => 'Na skróty'])
    </div>
</section>
@endif
