{{--
    Typ „Poradnik krok po kroku". Dwie kolumny: treść (treść główna → zanim zaczniesz
    → numerowane kroki z odhaczaniem, stan w localStorage → podsumowanie) | karta
    poradnika (czas, poziom, liczba kroków, postęp), spis kroków i menu działu.
    Płasko: bez etykiet i obramowań, tylko kolory firmowe.
--}}
@php
    $td = $page->typeData();
    $level = \App\Models\Page::GUIDE_LEVELS[$td['level'] ?? ''] ?? null;
    $storageKey = 'guide-progress-' . $page->id;
    $stepCount = count($td['steps']);
    $facts = array_filter([
        'Czas' => $td['time_estimate'] ?? null,
        'Poziom' => $level,
        'Liczba kroków' => $stepCount ?: null,
    ], fn ($v) => filled($v));
    $factIcons = ['Czas' => 'fa-clock', 'Poziom' => 'fa-signal', 'Liczba kroków' => 'fa-list-check'];
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $hasAside = $facts || $stepCount || $localNav;

    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $page->title,
            'description' => $td['lead'] ?? ($page->meta_description ?: null),
            'totalTime' => null,
            'supply' => $td['requirements'] ? array_map(fn ($r) => ['@type' => 'HowToSupply', 'name' => $r['text']], $td['requirements']) : null,
            'step' => array_values(array_map(fn ($s, $i) => array_filter([
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name' => $s['title'] ?? ('Krok ' . ($i + 1)),
                'text' => $s['text'] ?? null,
                'url' => $page->publicUrl() . '#krok-' . ($i + 1),
            ]), $td['steps'], array_keys($td['steps']))),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="guide"
         x-data="{ done: (function () { try { return JSON.parse(localStorage.getItem('{{ $storageKey }}') || '[]') } catch (e) { return [] } })(),
                   toggle(i) { this.done = this.done.includes(i) ? this.done.filter(d => d !== i) : [...this.done, i]; try { localStorage.setItem('{{ $storageKey }}', JSON.stringify(this.done)) } catch (e) {} } }">
    @include('page.partials.typed._head', ['print' => false])

    {{-- Dwie kolumny: treść | karta poradnika, spis kroków, menu działu (na telefonie karta pierwsza). --}}
    <div class="grid gap-10 {{ $hasAside ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Poradnik — najważniejsze informacje">
                <div class="lg:sticky lg:top-24">
                    @if ($facts || $stepCount)
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="guide-summary-card">
                            <h2 id="guide-summary-card" class="sr-only">Informacje o poradniku</h2>
                            @if ($facts)
                                <dl class="grid gap-y-4">
                                    @foreach ($facts as $label => $value)
                                        <div class="flex gap-3">
                                            <i class="fa-solid {{ $factIcons[$label] }} mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                            <div>
                                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
                                                <dd class="font-semibold text-ink">{{ $value }}</dd>
                                            </div>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                            @if ($stepCount)
                                <div class="{{ $facts ? 'mt-6' : '' }}">
                                    <p class="font-bold text-ink" aria-live="polite">Twój postęp: <span x-text="done.length">0</span> z {{ $stepCount }} kroków</p>
                                    <div class="mt-2 h-2 overflow-hidden rounded-md bg-white" aria-hidden="true"><div class="h-full bg-brand-dark transition-all" style="width:0" :style="'width:' + Math.round(done.length / {{ max(1, $stepCount) }} * 100) + '%'"></div></div>
                                </div>
                            @endif
                        </section>
                    @endif

                    @if ($stepCount)
                        <nav class="{{ $facts || $stepCount ? 'mt-6' : '' }} rounded-md bg-gray-50 p-6" aria-labelledby="guide-toc">
                            <h2 id="guide-toc" class="mb-3 text-lg font-bold text-ink">Spis kroków</h2>
                            <ol class="space-y-2">
                                @foreach ($td['steps'] as $i => $row)
                                    <li class="flex items-start gap-2">
                                        <span class="w-6 flex-none pt-0.5 text-right font-bold text-ink" aria-hidden="true">{{ $i + 1 }}.</span>
                                        <a href="#krok-{{ $i + 1 }}" class="{{ $link }}">{{ $row['title'] ?? 'Krok ' . ($i + 1) }}</a>
                                        <i class="fa-solid fa-check mt-1 flex-none text-brand-dark" x-show="done.includes({{ $i }})" x-cloak aria-hidden="true"></i>
                                        <span class="sr-only" x-show="done.includes({{ $i }})" x-cloak>(zrobione)</span>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    @if ($localNav)
                        <div class="{{ $facts || $stepCount ? 'mt-6' : '' }}">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            @include('partials.page-content-image')

            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @if ($td['requirements'])
                <section class="mb-10 rounded-md bg-brand-light p-6" aria-labelledby="guide-req">
                    <h2 id="guide-req" class="mb-3 flex items-center gap-2 text-lg font-bold text-ink"><i class="fa-solid fa-clipboard-check text-brand-dark" aria-hidden="true"></i>Zanim zaczniesz</h2>
                    <ul class="space-y-2">
                        @foreach ($td['requirements'] as $row)
                            <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-1 text-brand-dark" aria-hidden="true"></i><span class="text-ink">{{ $row['text'] }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($stepCount)
                <ol class="space-y-6" aria-label="Kroki poradnika">
                    @foreach ($td['steps'] as $i => $row)
                        <li id="krok-{{ $i + 1 }}" class="scroll-mt-24 rounded-md p-5 transition md:p-6"
                            :class="done.includes({{ $i }}) ? 'bg-brand-light' : 'bg-gray-50'">
                            <h2 class="flex items-start gap-2 text-xl font-bold text-ink">
                                <span><span class="sr-only">Krok </span>{{ $i + 1 }}. {{ $row['title'] ?? 'Krok ' . ($i + 1) }}</span>
                                <i class="fa-solid fa-circle-check mt-1 text-brand-dark" x-show="done.includes({{ $i }})" x-cloak aria-hidden="true"></i>
                            </h2>
                            @if (filled($row['text'] ?? null))
                                <div class="prose mt-2 max-w-none text-ink">{!! nl2br(e($row['text'])) !!}</div>
                            @endif
                            @if (filled($row['tip'] ?? null))
                                <p class="mt-3 flex items-start gap-2 text-ink"><i class="fa-solid fa-lightbulb mt-1 text-brand-dark" aria-hidden="true"></i><span><span class="font-bold">Wskazówka:</span> {{ $row['tip'] }}</span></p>
                            @endif
                            <button type="button" @click="toggle({{ $i }})" aria-pressed="false" :aria-pressed="done.includes({{ $i }}).toString()"
                                    class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white transition hover:bg-ink aria-pressed:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                <span x-text="done.includes({{ $i }}) ? 'Zrobione' : 'Oznacz jako zrobione'">Oznacz jako zrobione</span>
                                <span class="sr-only">: krok {{ $i + 1 }}</span>
                            </button>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if (filled($td['summary'] ?? null))
                <section class="mt-10 rounded-md bg-brand-light p-6" aria-labelledby="guide-summary">
                    <h2 id="guide-summary" class="mb-2 text-xl font-bold text-ink">Podsumowanie</h2>
                    <p class="text-ink">{!! nl2br(e($td['summary'])) !!}</p>
                </section>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
