{{--
    Typ „Poradnik krok po kroku": metryka (czas, poziom) → wymagania → numerowane
    kroki z możliwością odhaczania (stan w localStorage) → podsumowanie.
    Spis kroków po prawej (desktop) prowadzi kotwicami do kroków.
--}}
@php
    $td = $page->typeData();
    $level = \App\Models\Page::GUIDE_LEVELS[$td['level'] ?? ''] ?? null;
    $storageKey = 'guide-progress-' . $page->id;
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

<section class="mx-auto max-w-5xl px-4 py-12"
         x-data="{ done: (function () { try { return JSON.parse(localStorage.getItem('{{ $storageKey }}') || '[]') } catch (e) { return [] } })(),
                   toggle(i) { this.done = this.done.includes(i) ? this.done.filter(d => d !== i) : [...this.done, i]; try { localStorage.setItem('{{ $storageKey }}', JSON.stringify(this.done)) } catch (e) {} } }">
    <div class="grid gap-10 {{ $td['steps'] ? 'lg:grid-cols-[1fr_16rem]' : '' }}">
        <div class="min-w-0">
            <span class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
                <i class="fa-solid fa-list-ol" aria-hidden="true"></i> Poradnik
            </span>
            <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
            @if (filled($td['lead'] ?? null))
                <p class="mb-6 max-w-3xl text-lg leading-relaxed text-muted">{{ $td['lead'] }}</p>
            @endif

            @if (filled($td['time_estimate'] ?? null) || $level || $td['steps'])
                <dl class="mb-8 flex flex-wrap gap-x-8 gap-y-2 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-sm">
                    @if (filled($td['time_estimate'] ?? null))
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Czas</dt><dd class="font-semibold text-ink"><i class="fa-regular fa-clock mr-1 text-brand" aria-hidden="true"></i>{{ $td['time_estimate'] }}</dd></div>
                    @endif
                    @if ($level)
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Poziom</dt><dd class="font-semibold text-ink"><i class="fa-solid fa-signal mr-1 text-brand" aria-hidden="true"></i>{{ $level }}</dd></div>
                    @endif
                    @if ($td['steps'])
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Kroki</dt><dd class="font-semibold text-ink"><i class="fa-solid fa-list-check mr-1 text-brand" aria-hidden="true"></i>{{ count($td['steps']) }}</dd></div>
                    @endif
                </dl>
            @endif

            @include('partials.page-content-image')

            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @if ($td['requirements'])
                <section class="mb-10 rounded-xl border border-amber-200 bg-amber-50 p-5" aria-labelledby="guide-req">
                    <h2 id="guide-req" class="mb-2 text-base font-bold text-ink"><i class="fa-solid fa-clipboard-check mr-1.5 text-amber-600" aria-hidden="true"></i>Zanim zaczniesz</h2>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-ink">
                        @foreach ($td['requirements'] as $row)
                            <li>{{ $row['text'] }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['steps'])
                <ol class="space-y-6" aria-label="Kroki poradnika">
                    @foreach ($td['steps'] as $i => $row)
                        <li id="krok-{{ $i + 1 }}" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-5 transition md:p-6"
                            :class="done.includes({{ $i }}) ? 'border-green-300 bg-green-50/40' : ''">
                            <div class="flex items-start gap-4">
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-base font-bold" :class="done.includes({{ $i }}) ? 'bg-green-600 text-white' : 'bg-brand text-white'" aria-hidden="true">
                                    <span x-show="!done.includes({{ $i }})">{{ $i + 1 }}</span>
                                    <i class="fa-solid fa-check" x-show="done.includes({{ $i }})" x-cloak></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-xl font-bold text-ink"><span class="sr-only">Krok {{ $i + 1 }}: </span>{{ $row['title'] ?? 'Krok ' . ($i + 1) }}</h2>
                                    @if (filled($row['text'] ?? null))
                                        <div class="prose prose-sm mt-2 max-w-none text-ink">{!! nl2br(e($row['text'])) !!}</div>
                                    @endif
                                    @if (filled($row['tip'] ?? null))
                                        <p class="mt-3 flex items-start gap-2 rounded-lg bg-brand-light px-3 py-2 text-sm text-ink"><i class="fa-solid fa-lightbulb mt-0.5 text-brand" aria-hidden="true"></i><span><span class="font-bold">Wskazówka:</span> {{ $row['tip'] }}</span></p>
                                    @endif
                                    <button type="button" @click="toggle({{ $i }})" :aria-pressed="done.includes({{ $i }}).toString()"
                                            class="mt-4 inline-flex min-h-9 items-center gap-2 rounded-md border border-gray-300 px-3 text-sm font-medium text-ink transition hover:border-brand hover:text-brand aria-pressed:border-green-600 aria-pressed:bg-green-600 aria-pressed:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                        <span x-text="done.includes({{ $i }}) ? 'Zrobione' : 'Oznacz jako zrobione'">Oznacz jako zrobione</span>
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if (filled($td['summary'] ?? null))
                <section class="mt-10 rounded-2xl bg-brand-light p-6" aria-labelledby="guide-summary">
                    <h2 id="guide-summary" class="mb-2 text-xl font-bold text-ink">Podsumowanie</h2>
                    <p class="text-ink">{!! nl2br(e($td['summary'])) !!}</p>
                </section>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>

        @if ($td['steps'])
            <nav class="hidden lg:block" aria-label="Spis kroków">
                <div class="sticky top-24 rounded-xl border border-gray-200 bg-white p-4">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Postęp</p>
                    <p class="mb-3 text-sm font-semibold text-ink" aria-live="polite"><span x-text="done.length">0</span> z {{ count($td['steps']) }} kroków</p>
                    <div class="mb-4 h-1.5 overflow-hidden rounded-full bg-gray-200" aria-hidden="true"><div class="h-full bg-green-600 transition-all" :style="'width:' + Math.round(done.length / {{ max(1, count($td['steps'])) }} * 100) + '%'"></div></div>
                    <ol class="space-y-1 text-sm">
                        @foreach ($td['steps'] as $i => $row)
                            <li>
                                <a href="#krok-{{ $i + 1 }}" class="flex items-start gap-2 rounded px-2 py-1 text-ink hover:bg-gray-50 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                    <span class="w-5 flex-none text-right text-xs font-bold" :class="done.includes({{ $i }}) ? 'text-green-600' : 'text-muted'" aria-hidden="true">{{ $i + 1 }}.</span>
                                    <span :class="done.includes({{ $i }}) ? 'line-through text-muted' : ''">{{ $row['title'] ?? 'Krok ' . ($i + 1) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </nav>
        @endif
    </div>
</section>
