{{--
    Metryczka strony („Pokaż metkę”) pod treścią — jak w BIP: autor, osoba publikująca,
    podmiot publikujący, data publikacji i aktualizacji. Włączana w Ustawienia → Treści → Metryczka
    (cały serwis / typy stron) albo na pojedynczej stronie (edycja → Metryczka).
--}}
@php
    $mkSettings = \App\Models\SiteSetting::current();
    $mkData = $page->layout_data ?? [];
    $mkAuthor = $page->metka_author ?: $page->creator?->name;
    $mkPublisher = $page->publishedBy?->name;
    // Podmiot: wyłączany dla całego serwisu (Ustawienia → Treści) albo na stronie.
    $mkEntity = ($mkSettings->metka_show_entity ?? true) && ($mkData['metka_entity'] ?? true) ? ($mkSettings->metka_entity ?: $mkSettings->site_name) : null;
    $mkPublished = $page->first_published_at ?? $page->publish_at ?? $page->created_at;
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
<div class="page-metka mx-auto w-full max-w-5xl px-4 pb-10 sm:px-6 lg:px-8">
    <details class="group ml-auto w-fit max-w-full">
        <summary class="ml-auto flex w-fit cursor-pointer list-none items-center gap-1.5 py-2 text-xs font-semibold text-gray-700 underline underline-offset-2 hover:text-ink hover:no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-dark [&::-webkit-details-marker]:hidden">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span class="group-open:hidden">Pokaż metkę</span>
            <span class="hidden group-open:inline">Ukryj metkę</span>
            <span class="sr-only">— informacje o publikacji strony</span>
        </summary>
        <dl class="grid gap-x-6 gap-y-1.5 pb-2 pt-1 text-xs text-gray-700 sm:grid-cols-[max-content_1fr]">
            @foreach ($mkRows as $label => $value)
                <dt class="font-bold text-ink">{{ $label }}</dt>
                <dd>{!! $value !!}</dd>
            @endforeach
        </dl>
    </details>
</div>
@endif
