{{--
    Typ „Regulamin lub dokument". Dwie kolumny: treść (z kotwicami H2/H3) | karta
    dokumentu (obowiązuje od, wersja), spis treści, poprzednie wersje i menu działu
    (na telefonie kolumna boczna pierwsza — spis treści od razu pod tytułem).
    Płasko: bez etykiet i obramowań, tylko kolory firmowe.
--}}
@php
    $td = $page->typeData();
    [$contentHtml, $toc] = \App\Support\TableOfContents::inject(\App\Support\ShortcodeParser::render($page->content));
    $versions = collect($td['versions'])->filter(fn ($v) => filled($v['label'] ?? null) && filled($v['url'] ?? null));

    // Data obowiązywania po polsku, gdy da się ją odczytać; inaczej tekst z panelu.
    $validFrom = trim((string) ($td['valid_from'] ?? ''));
    if ($validFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $validFrom)) {
        try {
            $validFrom = \Illuminate\Support\Carbon::parse($validFrom)->locale('pl')->translatedFormat('j F Y');
        } catch (\Throwable) {
        }
    }
    $facts = array_filter([
        'Obowiązuje od' => $validFrom,
        'Wersja' => $td['version'] ?? null,
    ], fn ($v) => filled($v));
    $factIcons = ['Obowiązuje od' => 'fa-calendar-check', 'Wersja' => 'fa-code-branch'];
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $hasAside = $facts || $toc || $versions->isNotEmpty() || $localNav;

    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp

@if ($toc)
    @once
        <script>
            document.addEventListener('alpine:init', () => {
                // Oznacza w spisie treści sekcję, której nagłówek jest najbliżej góry okna (aria-current="location").
                Alpine.data('regulationTocSpy', () => ({
                    current: '',
                    init() {
                        const ids = [...this.$el.querySelectorAll('a[href^="#"]')].map((a) => decodeURIComponent(a.getAttribute('href').slice(1)));
                        const headings = ids.map((id) => document.getElementById(id)).filter(Boolean);
                        if (! headings.length || ! ('IntersectionObserver' in window)) return;
                        const visible = new Map();
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach((e) => visible.set(e.target.id, e.isIntersecting));
                            const first = headings.find((h) => visible.get(h.id));
                            if (first) this.current = first.id;
                        }, { rootMargin: '-96px 0px -60% 0px', threshold: 0 });
                        headings.forEach((h) => observer.observe(h));
                    },
                }));
            });
        </script>
    @endonce
@endif

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="regulation">
    @include('page.partials.typed._head')

    <div class="grid gap-10 {{ $hasAside ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Dokument — informacje i spis treści">
                <div class="space-y-6 lg:sticky lg:top-24">
                    @if ($facts)
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="rg-facts">
                            <h2 id="rg-facts" class="sr-only">Informacje o dokumencie</h2>
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
                        </section>
                    @endif

                    @if ($toc)
                        <nav class="rounded-md bg-gray-50 p-6" aria-labelledby="rg-toc" x-data="regulationTocSpy()">
                            <h2 id="rg-toc" class="mb-3 text-lg font-bold text-ink">Spis treści</h2>
                            <ol class="space-y-1">
                                @foreach ($toc as $item)
                                    <li class="{{ $item['level'] === 3 ? 'pl-4' : '' }}">
                                        <a href="#{{ $item['id'] }}"
                                           :aria-current="current === @js($item['id']) ? 'location' : null"
                                           :class="current === @js($item['id']) ? 'bg-brand-light' : ''"
                                           class="block rounded-md px-2 py-1.5 {{ $item['level'] === 3 ? 'text-sm' : '' }} {{ $link }}">{{ $item['text'] }}</a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    @if ($versions->isNotEmpty())
                        <section class="rounded-md bg-gray-50 p-6" aria-labelledby="rg-ver-h">
                            <h2 id="rg-ver-h" class="mb-3 text-lg font-bold text-ink">Poprzednie wersje</h2>
                            <ul role="list" class="space-y-2">
                                @foreach ($versions as $v)
                                    <li class="flex items-start gap-2"><i class="fa-solid fa-clock-rotate-left mt-1 text-brand-dark" aria-hidden="true"></i><a href="{{ $v['url'] }}" class="{{ $link }}">{{ $v['label'] }}</a></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($localNav)
                        <div>@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            <div class="prose max-w-none text-ink [&_h2]:scroll-mt-24 [&_h3]:scroll-mt-24">{!! $contentHtml !!}</div>

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
