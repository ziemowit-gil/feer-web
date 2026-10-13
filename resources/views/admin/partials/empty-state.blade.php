{{--
    Pusty stan listy w panelu: ikona, krótka wskazówka i przycisk „Dodaj…”.
    Gdy lista jest pusta przez filtry/wyszukiwanie — komunikat o braku wyników i „Wyczyść filtry”.

    Params: $icon (fa-…), $title, $text (opcjonalnie), $route (trasa „dodaj”, opcjonalnie), $action (napis przycisku).
    Optional: $colspan — gdy pusty stan stoi w tabeli (renderuje <tr><td colspan>).
    Zgodność wstecz (moduł Pełnomocnictw): $message zamiast $title, $createRoute (gotowy adres) i $createLabel.
--}}
@php
    $title ??= $message ?? 'Brak elementów';
    $action ??= $createLabel ?? null;
    $esFiltered = collect(request()->except(['page', 'tab', 'location']))->filter(fn ($v) => filled($v))->isNotEmpty();
    $esUrl = match (true) {
        $esFiltered => null,
        ! empty($createRoute) => $createRoute,
        ! empty($route) && \Illuminate\Support\Facades\Route::has($route) => route($route, $routeParams ?? []),
        default => null,
    };
@endphp
@isset($colspan)<tr><td colspan="{{ $colspan }}" class="p-0">@endisset
<div class="flex flex-col items-center px-6 py-10 text-center">
    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-xl text-brand-dark" aria-hidden="true">
        <i class="fa-solid {{ $esFiltered ? 'fa-filter-circle-xmark' : ($icon ?? 'fa-folder-open') }}"></i>
    </span>
    @if ($esFiltered)
        <p class="mt-3 font-bold text-ink">Nic nie pasuje do wybranych filtrów</p>
        <p class="mt-1 max-w-md text-sm text-ink">Zmień wyszukiwaną frazę albo wyczyść filtry, aby zobaczyć całą listę.</p>
        <a href="{{ url()->current() }}{{ request('location') ? '?location='.urlencode(request('location')) : '' }}" class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Wyczyść filtry
        </a>
    @else
        <p class="mt-3 font-bold text-ink">{{ $title }}</p>
        @if (! empty($text))<p class="mt-1 max-w-md text-sm text-ink">{{ $text }}</p>@endif
        @if ($esUrl)
            <a href="{{ $esUrl }}" class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $action ?? 'Dodaj' }}
            </a>
        @endif
    @endif
</div>
@isset($colspan)</td></tr>@endisset
