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

        <form action="{{ route('search') }}" method="GET" role="search" class="mb-8 flex max-w-xl gap-2">
            <label for="search-q" class="sr-only">Szukana fraza</label>
            <input id="search-q" type="search" name="q" value="{{ $q }}" autofocus placeholder="Czego szukasz?"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            <button type="submit" class="rounded bg-brand px-5 py-2 font-bold text-white hover:bg-brand-dark">Szukaj</button>
        </form>

        @if (! $searched)
            <p class="text-muted">Wpisz frazę (co najmniej 2 znaki), aby przeszukać strony, aktualności, projekty, materiały i blog.</p>
        @elseif ($total === 0)
            <p class="text-muted">Brak wyników dla „<strong class="text-ink">{{ $q }}</strong>". Spróbuj innej frazy.</p>
        @else
            <p class="mb-6 text-sm text-muted">Znaleziono {{ $total }} {{ trans_choice('wynik|wyniki|wyników', $total) }} dla „<strong class="text-ink">{{ $q }}</strong>".</p>

            <div class="space-y-8">
                @foreach ($groups as $label => $items)
                    <div>
                        <h2 class="mb-3 border-b border-gray-200 pb-1 text-sm font-bold uppercase tracking-wide text-muted">{{ $label }} ({{ $items->count() }})</h2>
                        <ul class="space-y-4">
                            @foreach ($items as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="font-bold text-brand hover:text-brand-dark hover:underline">{{ $item['title'] }}</a>
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
