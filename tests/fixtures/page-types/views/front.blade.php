{{-- Widok publiczny testowego typu strony (PageTypeRegistryTest). --}}
@php $fx = $page->typeData(); @endphp
<section data-fixture-front>
    <p>Notatka: {{ $fx['fixture_note'] ?? '' }}</p>
    <p>Wiersze: {{ count($fx['fixture_rows']) }}</p>
</section>
