{{--
    Uwaga o kierowaniu korespondencji — podpowiada, że listy/przesyłki mogą iść
    pod inny adres niż widoczny obok. Treść i nagłówek pochodzą z ustawień
    (zakładka „Kontakt"); bez uzupełnionej treści partial nic nie renderuje.

    Zmienne (opcjonalne):
      $variant — 'full' (domyślnie, wyróżniony blok) albo 'inline' (kompaktowa
                 podpowiedź do panelu z adresem, stopki, sekcji kontaktowej).

    WCAG: informację niesie ikona, nagłówek i tekst, a nie sam kolor (1.4.1),
    role="note" wskazuje ją czytnikom ekranu, a wariant „inline" nie wstawia
    nagłówka, żeby nie psuć hierarchii nagłówków strony (1.3.1).
--}}
@php
    $variant = $variant ?? 'full';
    $noteId  = 'korespondencja-'.\Illuminate\Support\Str::random(6);
@endphp

@if ($siteSettings->hasCorrespondenceNote())
    @if ($variant === 'inline')
        <div role="note" aria-labelledby="{{ $noteId }}"
            class="rounded-lg border border-amber-300 border-l-4 border-l-amber-600 bg-amber-50 p-3 text-sm text-amber-900">
            <p id="{{ $noteId }}" class="flex items-center gap-2 font-bold">
                <i class="fa-solid fa-triangle-exclamation shrink-0" aria-hidden="true"></i>
                {{ $siteSettings->correspondenceTitle() }}
            </p>
            <p class="mt-1 leading-relaxed">{!! nl2br(e($siteSettings->contact_correspondence_note)) !!}</p>
        </div>
    @else
        <div role="note" aria-labelledby="{{ $noteId }}"
            class="mb-8 rounded-xl border border-amber-300 border-l-4 border-l-amber-600 bg-amber-50 p-5">
            <h2 id="{{ $noteId }}" class="flex items-center gap-2 text-base font-bold text-amber-900">
                <i class="fa-solid fa-triangle-exclamation shrink-0" aria-hidden="true"></i>
                {{ $siteSettings->correspondenceTitle() }}
            </h2>
            <p class="mt-2 text-sm leading-relaxed text-amber-900">
                {!! nl2br(e($siteSettings->contact_correspondence_note)) !!}
            </p>
        </div>
    @endif
@endif
