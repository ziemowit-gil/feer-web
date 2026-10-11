{{--
    Typ „Słownik pojęć": hasła sortowane alfabetycznie, indeks liter (kotwice),
    filtr tekstowy (Alpine, bez przeładowania), definicje jako <dl>.
    Hasła powiązane linkują do własnych kotwic, jeśli istnieją w słowniku.
    Dwie kolumny: hasła | wyszukiwarka i indeks liter, menu działu (na telefonie
    wyszukiwarka pierwsza). Płasko: bez etykiet i obramowań, tylko kolory firmowe.
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
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $hasAside = $terms->isNotEmpty() || $localNav;

    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
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

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="glossary" data-glossary x-data="{ q: '' }">
    @include('page.partials.typed._head', ['print' => false])

    <div class="grid gap-10 {{ $hasAside ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Słownik — wyszukiwanie i indeks">
                <div class="lg:sticky lg:top-24">
                    @if ($terms->isNotEmpty())
                        <div class="rounded-md bg-brand-light p-6">
                            <label for="glossary-q" class="mb-2 block font-bold text-ink">Szukaj hasła</label>
                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink" aria-hidden="true"></i>
                                {{-- Obrys pola daje wtyczka @tailwindcss/forms (szary #6b7280, kontrast ≥ 3:1 — WCAG 1.4.11). --}}
                                <input id="glossary-q" type="search" x-model.debounce.150ms="q" placeholder="np. dostępność" autocomplete="off"
                                       class="min-h-11 w-full rounded-md bg-white pl-9 text-base text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand-dark">
                            </div>

                            <nav class="mt-5" aria-labelledby="glossary-index" x-show="q.trim() === ''">
                                <h2 id="glossary-index" class="mb-2 font-bold text-ink">Indeks liter</h2>
                                <ul class="flex flex-wrap gap-1">
                                    @foreach ($letters as $letter)
                                        <li><a href="#litera-{{ \Illuminate\Support\Str::slug($letter) }}" class="flex h-10 w-10 items-center justify-center rounded-md text-lg font-bold text-brand-dark underline underline-offset-4 hover:bg-white hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><span class="sr-only">Litera </span>{{ $letter }}</a></li>
                                    @endforeach
                                </ul>
                            </nav>
                        </div>
                    @endif
                    @if ($localNav)
                        <div class="{{ $terms->isNotEmpty() ? 'mt-6' : '' }}">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @if ($terms->isEmpty())
                <p class="text-muted">Słownik nie ma jeszcze haseł. Dodaj je w panelu (edycja strony → Słownik pojęć).</p>
            @else
                <p class="sr-only" aria-live="polite" x-text="q.trim() ? ('Wyniki filtrowania: ' + [...$el.closest('[data-glossary]').querySelectorAll('[data-term]')].filter(el => el.dataset.term.includes(q.trim().toLowerCase())).length) : ''"></p>

                @foreach ($letters as $letter)
                    <section id="litera-{{ \Illuminate\Support\Str::slug($letter) }}" class="scroll-mt-24 {{ $loop->first ? '' : 'mt-10' }}" aria-labelledby="glossary-letter-{{ $loop->index }}"
                             x-show="q.trim() === '' || [...$el.querySelectorAll('[data-term]')].some(el => el.dataset.term.includes(q.trim().toLowerCase()))">
                        <h2 id="glossary-letter-{{ $loop->index }}" class="mb-4 rounded-md bg-gray-50 px-4 py-2 text-2xl font-bold text-ink">{{ $letter }}</h2>
                        <dl class="space-y-6">
                            @foreach ($terms->where('letter', $letter) as $t)
                                <div id="{{ $t['slug'] }}" class="scroll-mt-24 px-4" data-term="{{ mb_strtolower($t['term'] . ' ' . $t['definition']) }}"
                                     x-show="q.trim() === '' || $el.dataset.term.includes(q.trim().toLowerCase())">
                                    <dt class="text-lg font-bold text-ink">{{ $t['term'] }}</dt>
                                    @if ($t['definition'] !== '')
                                        <dd class="mt-1 max-w-3xl leading-relaxed text-ink">{!! nl2br(e($t['definition'])) !!}</dd>
                                    @endif
                                    @if ($t['related'])
                                        <dd class="mt-2 text-ink">
                                            Zobacz też:
                                            @foreach ($t['related'] as $rel)
                                                @php $relSlug = \Illuminate\Support\Str::slug($rel); @endphp
                                                @if ($bySlug->has($relSlug))
                                                    <a href="#{{ $relSlug }}" class="{{ $link }}">{{ $rel }}</a>@if (! $loop->last), @endif
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

                <p class="mt-8 text-ink" x-show="q.trim() !== '' && [...$el.closest('[data-glossary]').querySelectorAll('[data-term]')].every(el => !el.dataset.term.includes(q.trim().toLowerCase()))" x-cloak>
                    Brak haseł pasujących do „<span x-text="q"></span>".
                </p>
            @endif

            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
