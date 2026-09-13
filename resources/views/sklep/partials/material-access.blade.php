{{--
    Dostęp do jednej zakupionej pozycji zamówienia. $item to SklepOrderItem
    (ma zrzut tytułu z chwili zakupu); $item->material to bieżący
    EducationalMaterial (może już nie istnieć, jeśli został usunięty w panelu).
--}}
@php $material = $item->material; @endphp

<div class="rounded-xl border border-gray-200 bg-white p-6">
    <h2 class="mb-4 text-lg font-bold text-ink">{{ $item->title }}</h2>

    @if (! $material)
        <p class="text-sm text-muted">Ten materiał nie jest już dostępny (został usunięty z biblioteki).</p>
    @elseif ($material->isVideo())
        @if ($material->youtubeId())
            <div class="aspect-video overflow-hidden rounded-lg border border-gray-200">
                <iframe class="h-full w-full" src="https://www.youtube.com/embed/{{ $material->youtubeId() }}"
                    title="{{ $material->title }}" allowfullscreen></iframe>
            </div>
        @else
            <a href="{{ $material->video_url }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark">
                <i class="fa-solid fa-play" aria-hidden="true"></i> Obejrzyj nagranie
            </a>
        @endif
    @elseif ($material->fileUrl)
        <a href="{{ $material->fileUrl }}" target="_blank" rel="noopener" download
            class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-download" aria-hidden="true"></i> Pobierz plik
        </a>
    @else
        <p class="text-sm text-muted">Brak pliku do pobrania.</p>
    @endif
</div>
