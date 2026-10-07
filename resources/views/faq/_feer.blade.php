{{--
    FAQ w układzie FEER: spokojny tytuł z akcentem, pytania jako płaski akordeon (linie zamiast ramek, plus/minus przy pytaniu),
    odpowiedź w jasnym bloku. Natywne <details> — działa z klawiatury i bez JS. Kontrast: ink/muted na bieli.
--}}
<section class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="text-2xl font-bold leading-tight text-ink md:text-3xl">Najczęstsze pytania</h1>
    <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
    <p class="mt-5 max-w-2xl text-lg text-ink">Odpowiedzi na pytania, które słyszymy najczęściej. Nie znalazłeś swojego? <a href="{{ route('contact.show') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:text-ink">Napisz do nas</a>.</p>

    @forelse ($groups as $category => $items)
        <div class="mt-10">
            @if ($category !== '')
                <h2 class="mb-1 text-xs font-bold uppercase tracking-widest text-muted">{{ $category }}</h2>
            @endif
            <div class="divide-y divide-gray-200 border-y border-gray-200">
                @foreach ($items as $faq)
                    <details class="group">
                        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-4 text-lg font-bold leading-snug text-ink transition hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                            <span>{{ $faq->question }}</span>
                            <span class="relative h-6 w-6 flex-none" aria-hidden="true">
                                <span class="absolute left-1 right-1 top-1/2 h-0.5 -translate-y-1/2 bg-brand"></span>
                                <span class="absolute bottom-1 left-1/2 top-1 w-0.5 -translate-x-1/2 bg-brand transition-transform group-open:scale-y-0"></span>
                            </span>
                        </summary>
                        <div class="mb-4 whitespace-pre-line rounded-md bg-gray-50 p-5 text-base leading-relaxed text-ink">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    @empty
        <div class="mt-10 rounded-md bg-gray-50 p-8 text-center text-muted">
            Nie dodaliśmy jeszcze żadnych pytań. Zajrzyj wkrótce albo <a href="{{ route('contact.show') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:text-ink">napisz do nas</a>.
        </div>
    @endforelse
</section>
