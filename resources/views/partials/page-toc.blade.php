{{--
    Spis treści „Na tej stronie" — z App\Support\TableOfContents::inject().

    Zmienne: $toc (lista {id, text, level}), $variant: 'desktop' (przypięty
    w kolumnie bocznej) albo 'mobile' (rozwijany <details> nad treścią).

    WCAG: <nav> z nazwą, lista uporządkowana, bieżąca sekcja oznaczona
    aria-current="location" (podświetlanie przez IntersectionObserver jest
    tylko wizualnym dodatkiem — linki działają bez JS), cele ≥ 36 px.
--}}
@php
    $variant = $variant ?? 'desktop';
    $tocId = 'toc-' . $variant;
@endphp

@once
    <script>
        document.addEventListener('alpine:init', () => {
            // Podświetla w spisie sekcję, której nagłówek jest najbliżej góry okna.
            Alpine.data('tocSpy', () => ({
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

@if ($variant === 'mobile')
    <details class="mb-6 rounded-xl border border-gray-200 bg-gray-50 md:hidden">
        <summary class="flex min-h-11 cursor-pointer items-center gap-2 px-4 text-sm font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            <i class="fa-solid fa-list-ul text-brand" aria-hidden="true"></i> Na tej stronie
        </summary>
        <nav id="{{ $tocId }}" aria-label="Spis treści" class="border-t border-gray-200 px-2 py-2">
            <ol class="space-y-0.5 text-sm">
                @foreach ($toc as $item)
                    <li>
                        <a href="#{{ $item['id'] }}" class="block rounded-md px-2 py-2 text-ink hover:bg-white hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $item['level'] === 3 ? 'ml-4 text-[13px] text-muted' : 'font-medium' }}">{{ $item['text'] }}</a>
                    </li>
                @endforeach
            </ol>
        </nav>
    </details>
@else
    <nav id="{{ $tocId }}" aria-label="Spis treści" class="hidden md:block" x-data="tocSpy()">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted"><i class="fa-solid fa-list-ul mr-1.5 text-brand" aria-hidden="true"></i>Na tej stronie</p>
        <ol class="space-y-0.5 border-l border-gray-200 text-sm">
            @foreach ($toc as $item)
                <li>
                    <a href="#{{ $item['id'] }}"
                       :aria-current="current === @js($item['id']) ? 'location' : null"
                       :class="current === @js($item['id']) ? 'border-brand font-semibold text-brand' : 'border-transparent text-muted hover:border-gray-400 hover:text-ink'"
                       class="-ml-px block border-l-2 py-1.5 pr-2 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $item['level'] === 3 ? 'pl-6 text-[13px]' : 'pl-3' }}">{{ $item['text'] }}</a>
                </li>
            @endforeach
        </ol>
    </nav>
@endif
