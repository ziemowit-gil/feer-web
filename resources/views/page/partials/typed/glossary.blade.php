{{--
    Typ „Słownik pojęć": hasła sortowane alfabetycznie, indeks liter (kotwice),
    filtr tekstowy (Alpine, bez przeładowania), definicje jako <dl>.
    Hasła powiązane linkują do własnych kotwic, jeśli istnieją w słowniku.
--}}
@php
    $td = $page->typeData();
    $terms = collect($td['terms'])
        ->filter(fn ($t) => filled($t['term'] ?? null))
        ->map(fn ($t) => [
            'term' => trim($t['term']),
            'definition' => trim((string) ($t['definition'] ?? '')),
            'related' => array_values(array_filter(array_map('trim', explode(',', (string) ($t['related'] ?? ''))))),
            'slug' => \Illuminate\Support\Str::slug($t['term']),
            'letter' => mb_strtoupper(mb_substr(\Illuminate\Support\Str::ascii(trim($t['term'])), 0, 1)),
        ])
        ->sortBy(fn ($t) => mb_strtolower($t['term']), SORT_NATURAL | SORT_FLAG_CASE)
        ->values();
    $letters = $terms->pluck('letter')->unique()->values();
    $bySlug  = $terms->keyBy('slug');
@endphp

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'DefinedTermSet',
            'name' => $page->title,
            'description' => $td['lead'] ?? ($page->meta_description ?: null),
            'url' => $page->publicUrl(),
            'hasDefinedTerm' => $terms->map(fn ($t) => array_filter([
                '@type' => 'DefinedTerm',
                'name' => $t['term'],
                'description' => $t['definition'] ?: null,
                'url' => $page->publicUrl() . '#' . $t['slug'],
            ]))->values()->all() ?: null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

<section class="mx-auto max-w-5xl px-4 py-12" x-data="{ q: '' }">
    <span class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
        <i class="fa-solid fa-book" aria-hidden="true"></i> Słownik
    </span>
    <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
    @if (filled($td['lead'] ?? null))
        <p class="mb-6 max-w-3xl text-lg leading-relaxed text-muted">{{ $td['lead'] }}</p>
    @endif

    @if ($page->content)
        <div class="prose mb-8 max-w-none text-ink">@shortcodes($page->content)</div>
    @endif

    @if ($terms->isEmpty())
        <p class="text-muted">Słownik nie ma jeszcze haseł. Dodaj je w panelu (edycja strony → Słownik pojęć).</p>
    @else
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="relative md:w-80">
                <label for="glossary-q" class="sr-only">Szukaj hasła</label>
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" aria-hidden="true"></i>
                <input id="glossary-q" type="search" x-model.debounce.150ms="q" placeholder="Szukaj hasła…" autocomplete="off"
                       class="min-h-11 w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-brand focus:ring-2 focus:ring-brand">
            </div>
            <nav aria-label="Indeks liter" x-show="q.trim() === ''">
                <ul class="flex flex-wrap gap-1">
                    @foreach ($letters as $letter)
                        <li><a href="#litera-{{ \Illuminate\Support\Str::slug($letter) }}" class="flex h-9 w-9 items-center justify-center rounded-md border border-gray-200 text-sm font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $letter }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <p class="sr-only" aria-live="polite" x-text="q.trim() ? ('Wyniki filtrowania: ' + $el.closest('section').querySelectorAll('[data-term]:not([hidden])').length) : ''"></p>

        @foreach ($letters as $letter)
            <section id="litera-{{ \Illuminate\Support\Str::slug($letter) }}" class="scroll-mt-24" aria-labelledby="glossary-letter-{{ $loop->index }}"
                     x-show="q.trim() === '' || [...$el.querySelectorAll('[data-term]')].some(el => el.dataset.term.includes(q.trim().toLowerCase()))">
                <h2 id="glossary-letter-{{ $loop->index }}" class="mb-3 mt-8 border-b border-gray-200 pb-1 text-2xl font-bold text-brand">{{ $letter }}</h2>
                <dl class="divide-y divide-gray-100">
                    @foreach ($terms->where('letter', $letter) as $t)
                        <div id="{{ $t['slug'] }}" class="scroll-mt-24 py-4" data-term="{{ mb_strtolower($t['term'] . ' ' . $t['definition']) }}"
                             x-show="q.trim() === '' || $el.dataset.term.includes(q.trim().toLowerCase())">
                            <dt class="text-lg font-bold text-ink">{{ $t['term'] }}</dt>
                            @if ($t['definition'] !== '')
                                <dd class="mt-1 max-w-3xl leading-relaxed text-ink">{!! nl2br(e($t['definition'])) !!}</dd>
                            @endif
                            @if ($t['related'])
                                <dd class="mt-2 text-sm text-muted">
                                    Zobacz też:
                                    @foreach ($t['related'] as $rel)
                                        @php $relSlug = \Illuminate\Support\Str::slug($rel); @endphp
                                        @if ($bySlug->has($relSlug))
                                            <a href="#{{ $relSlug }}" class="font-semibold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $rel }}</a>@if (! $loop->last), @endif
                                        @else
                                            <span class="font-semibold">{{ $rel }}</span>@if (! $loop->last), @endif
                                        @endif
                                    @endforeach
                                </dd>
                            @endif
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        <p class="mt-8 text-sm text-muted" x-show="q.trim() !== '' && [...$el.closest('section').querySelectorAll('[data-term]')].every(el => !el.dataset.term.includes(q.trim().toLowerCase()))" x-cloak>
            Brak haseł pasujących do „<span x-text="q"></span>".
        </p>
    @endif

    @include('partials.attachments-list', ['attachments' => $page->attachments])
    @include('partials.page-section-nav', ['page' => $page])
</section>
