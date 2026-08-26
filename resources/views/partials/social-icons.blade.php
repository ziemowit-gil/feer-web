{{--
    Grupa ikon social media w nagłówku — zwarty „pigułkowy" blok, wspólny dla
    nagłówka „Szeroka belka" i szablonu municipality.

    Zmienne: $socialIcons — lista [url, klasa ikony, etykieta].
--}}
@php $socialIcons = $socialIcons ?? []; @endphp

@if ($socialIcons)
    <div class="flex flex-none items-center gap-0.5 rounded-full bg-gray-50 p-0.5 ring-1 ring-gray-200">
        @foreach ($socialIcons as [$socialUrl, $socialIcon, $socialLabel])
            <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
               class="flex h-9 w-9 items-center justify-center rounded-full text-base text-muted transition hover:bg-white hover:text-brand hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
               aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                <i class="{{ $socialIcon }}" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif
