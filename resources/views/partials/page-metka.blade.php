{{--
    Metryczka strony („Pokaż metkę”) pod treścią — jak w BIP: autor, osoba publikująca,
    podmiot publikujący, data publikacji i aktualizacji. Włączana w Ustawienia → Treści → Metryczka
    (cały serwis / typy stron) albo na pojedynczej stronie (edycja → Metryczka).
    Miejsce: zawsze przy stopce — w szablonie FEER w ciemnej stopce ($onDark: biały tekst na #1D1D1A ≥ 15:1),
    w pozostałych szablonach pasek tuż nad stopką (layouts/site).
--}}
@php
    $mkSettings = \App\Models\SiteSetting::current();
    // Działa dla stron i działań (działanie nie ma własnego autora metki ani osoby publikującej — bierzemy autora wpisu).
    $mkIsPage = $page instanceof \App\Models\Page;
    $mkData = $mkIsPage ? ($page->layout_data ?? []) : [];
    $mkAuthor = ($mkIsPage ? $page->metka_author : null) ?: $page->creator?->name;
    $mkPublisher = $mkIsPage ? $page->publishedBy?->name : null;
    // Podmiot: wyłączany dla całego serwisu (Ustawienia → Treści) albo na stronie.
    $mkEntity = ($mkSettings->metka_show_entity ?? true) && ($mkData['metka_entity'] ?? true) ? ($mkSettings->metka_entity ?: $mkSettings->site_name) : null;
    $mkPublished = ($mkIsPage ? ($page->first_published_at ?? $page->publish_at) : null) ?? $page->created_at;
    $mkUpdated = $page->updated_at;
    $mkDate = fn ($d) => '<time datetime="'.e($d->toIso8601String()).'">'.e($d->locale('pl')->translatedFormat('j F Y, H:i')).'</time>';
    $mkRows = array_filter([
        'Autor' => $mkAuthor ? e($mkAuthor) : null,
        'Osoba publikująca' => $mkPublisher ? e($mkPublisher) : null,
        'Podmiot publikujący' => $mkEntity ? e($mkEntity) : null,
        'Data publikacji' => $mkPublished ? $mkDate($mkPublished) : null,
        'Data aktualizacji' => $mkUpdated && (! $mkPublished || $mkUpdated->gt($mkPublished)) ? $mkDate($mkUpdated) : null,
    ]);
@endphp
@if ($mkRows)
@php $mkDark = ! empty($onDark); @endphp
<div class="page-metka {{ $mkDark ? 'page-metka--footer' : 'mx-auto w-full max-w-6xl px-4 py-3' }}">
    <details class="group {{ $mkDark ? '' : 'ml-auto' }} w-fit max-w-full">
        <summary class="{{ $mkDark ? '' : 'ml-auto' }} flex w-fit cursor-pointer list-none items-center gap-1.5 py-2 text-xs font-semibold {{ $mkDark ? 'text-white' : 'text-gray-700 hover:text-ink' }} underline underline-offset-2 hover:no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 {{ $mkDark ? 'focus-visible:outline-white' : 'focus-visible:outline-brand-dark' }} [&::-webkit-details-marker]:hidden" style="min-height: 2.75rem">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span class="group-open:hidden">Pokaż metkę</span>
            <span class="hidden group-open:inline">Ukryj metkę</span>
            <span class="sr-only">— informacje o publikacji strony</span>
        </summary>
        <dl class="grid gap-x-6 gap-y-1.5 pb-2 pt-1 text-xs {{ $mkDark ? 'text-gray-200' : 'text-gray-700' }}" style="grid-template-columns: max-content 1fr">
            @foreach ($mkRows as $label => $value)
                <dt class="font-bold {{ $mkDark ? 'text-white' : 'text-ink' }}">{{ $label }}</dt>
                <dd>{!! $value !!}</dd>
            @endforeach
        </dl>
    </details>
</div>
@endif
