{{--
    Wyszukiwarka w układzie FEER: spokojny tytuł z akcentem, duże pole wypełnione (bez obwódki, linia u dołu), zakres jako pigułki,
    wyniki w grupach jako lekkie wiersze z linią i kropką typu. Kontrast: ink/muted na bieli; linki ink z podkreśleniem po najechaniu.
--}}
<section class="mx-auto max-w-4xl px-4 py-10">
    <h1 class="text-2xl font-bold leading-tight text-ink md:text-3xl">Wyszukiwarka</h1>
    <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>

    <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukaj w serwisie" class="mt-8 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row">
            <label for="search-q" class="sr-only">Szukana fraza</label>
            <input id="search-q" type="search" name="q" value="{{ $q }}" autofocus placeholder="Czego szukasz?"
                class="min-h-14 w-full rounded-t-md rounded-b-none border-0 border-b-2 border-gray-500 bg-gray-100 px-4 text-lg text-ink transition hover:bg-gray-200/70 focus:border-brand focus:bg-white focus:outline-none focus:ring-0">
            <button type="submit" class="inline-flex min-h-14 flex-none items-center justify-center gap-2 rounded-md bg-brand px-8 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Szukaj
            </button>
        </div>

        <fieldset>
            <legend class="sr-only">Zakres wyszukiwania</legend>
            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Zakres wyszukiwania">
                @foreach (['' => 'Cały serwis', 'aktualnosci' => 'Aktualności', 'materialy' => 'Materiały edukacyjne'] as $val => $label)
                    <label class="flex min-h-11 cursor-pointer select-none items-center rounded-md bg-gray-100 px-4 text-sm font-bold text-ink transition hover:bg-gray-200 has-[:checked]:bg-brand has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ink">
                        <input type="radio" name="typ" value="{{ $val }}" {{ $typ === $val ? 'checked' : '' }} class="sr-only">{{ $label }}
                    </label>
                @endforeach
                <label class="ml-auto flex min-h-11 cursor-pointer select-none items-center gap-2 text-sm text-ink">
                    <input id="search-archiwum" type="checkbox" name="archiwum" value="1" {{ $archive ? 'checked' : '' }} aria-describedby="search-archiwum-hint" class="h-4 w-4 rounded border-gray-400 text-brand focus:ring-brand">
                    Szukaj także w archiwum
                </label>
                <span id="search-archiwum-hint" class="sr-only">Zaznacz, aby wyniki zawierały materiały oznaczone jako archiwalne.</span>
            </div>
        </fieldset>
    </form>

    <div class="mt-10">
        @if (! $searched)
            <p class="text-muted">Wpisz frazę (co najmniej 2 znaki), aby przeszukać strony, aktualności, projekty, materiały i blog.</p>
        @elseif ($total === 0)
            <div class="rounded-md bg-gray-50 p-8 text-center">
                <i class="fa-solid fa-magnifying-glass mb-3 block text-3xl text-gray-400" aria-hidden="true"></i>
                <p class="text-lg font-bold text-ink">Brak wyników dla „{{ $q }}”</p>
                <p class="mt-1 text-muted">Spróbuj innej frazy albo wybierz szerszy zakres wyszukiwania.</p>
            </div>
        @else
            <p class="mb-8 text-sm text-muted" aria-live="polite" aria-atomic="true">
                Znaleziono <strong class="text-ink">{{ $total }}</strong> {{ trans_choice('wynik|wyniki|wyników', $total) }} dla „<strong class="text-ink">{{ $q }}</strong>”.
                @if ($archive)<span class="ml-1 rounded bg-gray-100 px-2 py-0.5 text-xs font-bold text-ink">z archiwum</span>@endif
            </p>

            <div class="space-y-10">
                @foreach ($groups as $label => $items)
                    <section aria-labelledby="grp-{{ \Illuminate\Support\Str::slug($label) }}">
                        <h2 id="grp-{{ \Illuminate\Support\Str::slug($label) }}" class="mb-2 flex items-baseline gap-2 text-xl font-bold text-ink">{{ $label }} <span class="text-sm font-bold text-muted">{{ $items->count() }}</span></h2>
                        <ul class="divide-y divide-gray-100" role="list">
                            @foreach ($items as $item)
                                <li class="group py-4 pl-4" style="border-left: 4px solid var(--color-brand)">
                                    <a href="{{ $item['url'] }}" class="text-lg font-bold leading-snug text-ink underline-offset-4 hover:text-brand-dark hover:underline focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $item['title'] }}</a>
                                    @if ($item['archival'])<span class="ml-2 rounded bg-gray-100 px-1.5 py-0.5 text-xs font-bold text-ink" aria-label="Materiał archiwalny">archiwalne</span>@endif
                                    @if ($item['date'] || $item['author'])
                                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-0.5 text-sm text-muted">
                                            @if ($item['date'])<span><i class="fa-regular fa-calendar mr-1" aria-hidden="true"></i><time datetime="{{ $item['date']->toDateString() }}">{{ $item['date']->isoFormat('D MMM YYYY') }}</time></span>@endif
                                            @if ($item['author'])<span><i class="fa-regular fa-user mr-1" aria-hidden="true"></i>{{ $item['author'] }}</span>@endif
                                        </p>
                                    @endif
                                    @if ($item['snippet'] !== '')<p class="mt-1 text-[15px] leading-relaxed text-muted">{{ $item['snippet'] }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</section>
