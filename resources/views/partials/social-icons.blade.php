{{--
    Grupa ikon social media — bez tła i obramowania, same znaki. Wspólna dla
    nagłówka „Szeroka belka", substylu urzędowego, szablonu gminnego i panelu
    danych na stronie kontaktowej.

    Zmienne: $socialIcons — lista [url, klasa ikony, etykieta].

    WCAG: pole klikalne ma 44×44 px (2.5.8 z zapasem), kolor ikony ma ponad 7:1
    kontrastu na białym tle (1.4.11), a fokus jest widoczny obrysem (2.4.7).
--}}
@php $socialIcons = $socialIcons ?? []; @endphp

@if ($socialIcons)
    <div class="flex flex-none items-center gap-1">
        @foreach ($socialIcons as [$socialUrl, $socialIcon, $socialLabel])
            <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
               class="flex h-11 w-11 items-center justify-center rounded-full text-xl text-muted transition hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
               aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                <i class="{{ $socialIcon }}" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif
