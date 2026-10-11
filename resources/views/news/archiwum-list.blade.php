{{--
    Wspólny, płaski widok archiwum aktualności (news/archiwum i news/archiwum-feer):
    nagłówek, zakres archiwum jako zwykłe linki z aria-current (bez kart i „pigułek"),
    lista wierszy na szarym tle, dostępna paginacja (news/index-pagination).
    Dane: $archived, $legacy (paginatory: arch / leg), $rowView (widok wiersza).
--}}
@php
    $tab = request('tab', 'stara-strona') === 'archiwalne' ? 'archiwalne' : 'stara-strona';
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $ranges = [
        ['stara-strona', 'Ze starej strony', $legacy->total()],
        ['archiwalne', 'Archiwalne', $archived->total()],
    ];
    $list = $tab === 'archiwalne' ? $archived : $legacy;
@endphp

<div class="mx-auto max-w-6xl px-4 py-10" data-news-archive>
    <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">Archiwum aktualności</h1>
    <p class="mt-3 max-w-2xl text-lg text-ink">Starsze materiały oraz treści przeniesione z poprzedniej wersji strony.</p>

    <nav aria-label="Zakres archiwum" class="mt-6 rounded-md bg-gray-50 px-4 py-3">
        <ul class="flex flex-wrap gap-x-6 gap-y-1" role="list">
            @foreach ($ranges as [$key, $label, $count])
                <li>
                    <a href="{{ route('news.archiwum', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center {{ $tab === $key ? 'font-bold text-ink' : $link }}">
                        {{ $label }}@if ($count)&nbsp;({{ $count }})@endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="mt-8">
        @if ($list->isEmpty())
            <p class="rounded-md bg-gray-50 p-5 text-ink">
                {{ $tab === 'archiwalne' ? 'Brak archiwalnych aktualności.' : 'Brak treści ze starej strony.' }}
            </p>
        @else
            <ul class="space-y-4" role="list">
                @foreach ($list as $item)
                    @include($rowView, ['item' => $item])
                @endforeach
            </ul>
            @include('news.index-pagination', ['paginator' => $list->appends(['tab' => $tab]), 'label' => 'Strony archiwum'])
        @endif
    </div>

    <p class="mt-10"><a href="{{ site_route('news.index') }}" class="{{ $link }}">Powrót do aktualności</a></p>
</div>
