{{--
    „Nawigacja kafelkowa": podstrony działu jako duże kafelki (styl serwisów typu CKE). Kolor kafelka — kolejno z kolorów marki
    (1–4); kolor tekstu dobiera Color::button, więc kontrast zawsze ≥ 4,5:1. Zmienna: $tiles (kolekcja Page).
    Dostępność: lista linków, cały kafelek klikalny, widoczny fokus, tytuł jako tekst linku.
--}}
@php
    $tilePalette = ['#1a56a4', '#166534', '#7e22ce', '#c2410c'];
@endphp
@if ($tiles->isNotEmpty())
    <nav aria-label="Podstrony: {{ $page->title }}" class="mt-10">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($tiles as $i => $tile)
                @php
                    $base = $siteSettings->brandColorN(($i % 4) + 1);
                    if (! \App\Support\Color::isValid($base)) {
                        $base = $tilePalette[$i % 4];
                    }
                    $pal = \App\Support\Color::button($base);
                    $desc = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($tile->meta_description ?? ''))), 90);
                @endphp
                <li>
                    <a href="{{ $tile->publicUrl() }}"
                       class="feer-card group flex min-h-36 h-full flex-col justify-between rounded-md p-6 transition hover:-translate-y-0.5 hover:opacity-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2"
                       style="background-color: {{ $pal['bg'] }}; color: {{ $pal['text'] }}">
                        <span class="text-xl font-bold leading-snug">{{ $tile->title }}</span>
                        <span class="mt-4 flex items-end justify-between gap-3">
                            <span class="text-sm leading-snug">{{ $desc }}</span>
                            <span class="flex-none text-2xl transition group-hover:translate-x-1" aria-hidden="true">→</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
