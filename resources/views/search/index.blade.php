@extends('layouts.site')

@section('title', ($q !== '' ? 'Wyniki: ' . $q : 'Wyszukiwarka') . ' — ' . $siteSettings->site_name)
@section('meta_description', 'Wyszukiwarka serwisu ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wyszukiwarka', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-6 text-3xl font-bold text-ink">Wyszukiwarka</h1>

        <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukaj w serwisie" class="mb-8">
            <div class="flex max-w-xl gap-2">
                <label for="search-q" class="sr-only">Szukana fraza</label>
                <input id="search-q" type="search" name="q" value="{{ $q }}" autofocus placeholder="Czego szukasz?"
                    aria-label="Szukana fraza"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <button type="submit" class="rounded bg-brand px-5 py-2 font-bold text-white hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">Szukaj</button>
            </div>

            <fieldset class="mt-3">
                <legend class="sr-only">Zakres wyszukiwania</legend>
                <div class="flex flex-wrap gap-x-5 gap-y-2" role="radiogroup" aria-label="Zakres wyszukiwania">
                    @foreach ([
                        '' => 'Cały serwis',
                        'aktualnosci' => 'Tylko aktualności',
                        'materialy' => 'Tylko materiały edukacyjne',
                    ] as $val => $label)
                        <label class="flex cursor-pointer items-center gap-1.5 text-sm text-ink select-none">
                            <input type="radio" name="typ" value="{{ $val }}"
                                {{ $typ === $val ? 'checked' : '' }}
                                class="h-4 w-4 border-gray-300 text-brand focus:ring-brand">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="mt-3 flex items-center gap-2">
                <input
                    id="search-archiwum"
                    type="checkbox"
                    name="archiwum"
                    value="1"
                    {{ $archive ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand"
                    aria-describedby="search-archiwum-hint"
                >
                <label for="search-archiwum" class="text-sm text-ink cursor-pointer select-none">
                    Szukaj również w archiwum
                </label>
                <span id="search-archiwum-hint" class="sr-only">Zaznacz, aby wyniki zawierały materiały oznaczone jako archiwalne.</span>
            </div>
        </form>

        @if (! $searched)
            <p class="text-muted">Wpisz frazę (co najmniej 2 znaki), aby przeszukać strony, aktualności, projekty, materiały i blog.</p>
        @elseif ($total === 0)
            <p class="text-muted">Brak wyników dla „<strong class="text-ink">{{ $q }}</strong>". Spróbuj innej frazy.</p>
        @else
            <p class="mb-6 text-sm text-muted" aria-live="polite" aria-atomic="true">
                Znaleziono {{ $total }} {{ trans_choice('wynik|wyniki|wyników', $total) }} dla „<strong class="text-ink">{{ $q }}</strong>".
                @if ($archive)
                    <span class="ml-1 inline-flex items-center rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">z archiwum</span>
                @endif
            </p>

            <div class="space-y-8">
                @foreach ($groups as $label => $items)
                    <div>
                        <h2 class="mb-3 border-b border-gray-200 pb-1 text-sm font-bold uppercase tracking-wide text-muted">{{ $label }} ({{ $items->count() }})</h2>
                        <ul class="space-y-5" role="list">
                            @foreach ($items as $item)
                                <li class="group">
                                    <a href="{{ $item['url'] }}"
                                       class="font-bold text-brand hover:text-brand-dark hover:underline focus:rounded focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-1">{{ $item['title'] }}</a>

                                    @if ($item['archival'])
                                        <span class="ml-2 inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600" aria-label="Materiał archiwalny">archiwalne</span>
                                    @endif

                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-muted">
                                        @if ($item['date'])
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5"/>
                                                </svg>
                                                <time datetime="{{ $item['date']->toDateString() }}" title="{{ $item['date']->isoFormat('D MMMM YYYY') }}">
                                                    {{ $item['date']->isoFormat('D MMM YYYY') }}
                                                </time>
                                            </span>
                                        @endif

                                        @if ($item['author'])
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                                </svg>
                                                <span>{{ $item['author'] }}</span>
                                            </span>
                                        @endif
                                    </div>

                                    @if ($item['snippet'] !== '')
                                        <p class="mt-0.5 text-sm text-muted">{{ $item['snippet'] }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
