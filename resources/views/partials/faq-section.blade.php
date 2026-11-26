{{--
    Sekcja FAQ na dowolną podstronę (wstawka [faq], App\Support\FaqSection): po lewej nagłówek z akcentem, opis i odnośniki,
    po prawej numerowane pytania jako płaskie karty z natywnym <details> (klawiatura i czytniki bez JS). Otwarta karta
    ma jasne tło marki, a pytanie kolor brand-dark (≥ 4,5:1 na brand-light i na jasnoszarym). Dane FAQPage dla wyszukiwarek.

    Zmienne: $items (kolekcja Faq), $title, $intro, $level (2–4), $uid.
--}}
@once
<style>
    .faq-sec { margin: 2.5rem 0; }
    .faq-sec-grid { display: grid; gap: 1.75rem; }
    @media (min-width: 1024px) { .faq-sec-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap: 3rem; align-items: start; } .faq-sec-head { position: sticky; top: 1.5rem; } }
    .faq-sec-title { margin: 0; font-size: clamp(1.5rem, 2.4vw, 2rem); font-weight: 800; line-height: 1.15; color: #1d1d1a; }
    .faq-sec-bar { display: block; width: 3.5rem; height: .25rem; margin-top: .875rem; background: var(--color-brand, #1e6dff); }
    .faq-sec-intro { margin: 1rem 0 0; font-size: 1.0625rem; line-height: 1.6; color: #1d1d1a; }
    .faq-sec-links { margin: 1.25rem 0 0; padding: 0; list-style: none; display: grid; gap: .5rem; font-size: .9375rem; }
    .faq-sec-links a { font-weight: 700; color: var(--color-brand-dark, #0b4bbf); text-decoration: underline; text-underline-offset: 4px; }
    .faq-sec-links a:hover { color: #1d1d1a; }
    .faq-sec-list { margin: 0; padding: 0; list-style: none; display: grid; gap: .625rem; counter-reset: faq-sec; }
    .faq-sec-item { border-radius: .625rem; background: #f3f4f6; transition: background-color .15s ease; }
    .faq-sec-item[open] { background: var(--color-brand-light, #e8f0ff); }
    .faq-sec-q { display: flex; align-items: center; gap: 1rem; min-height: 3.5rem; padding: 1rem 1.25rem; cursor: pointer; list-style: none; border-radius: .625rem;
        font-size: 1.0625rem; font-weight: 700; line-height: 1.35; color: #1d1d1a; }
    .faq-sec-q::-webkit-details-marker { display: none; }
    .faq-sec-q:hover, .faq-sec-item[open] > .faq-sec-q { color: var(--color-brand-dark, #0b4bbf); }
    .faq-sec-q:focus-visible { outline: 3px solid var(--color-brand-dark, #0b4bbf); outline-offset: 2px; }
    .faq-sec-num { flex: none; min-width: 1.75rem; font-size: .875rem; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--color-brand-dark, #0b4bbf); }
    .faq-sec-text { flex: 1; min-width: 0; }
    .faq-sec-icon { position: relative; flex: none; width: 2rem; height: 2rem; border-radius: 9999px; background: #fff; }
    .faq-sec-icon::before, .faq-sec-icon::after { content: ""; position: absolute; left: 50%; top: 50%; width: .875rem; height: 2px; margin: -1px 0 0 -.4375rem; background: var(--color-brand-dark, #0b4bbf); transition: transform .2s ease; }
    .faq-sec-icon::after { transform: rotate(90deg); }
    .faq-sec-item[open] .faq-sec-icon::after { transform: rotate(0deg); }
    .faq-sec-a { padding: 0 1.25rem 1.25rem calc(1.25rem + 2.75rem); font-size: 1rem; line-height: 1.65; color: #1d1d1a; white-space: pre-line; }
    @media (max-width: 639px) { .faq-sec-a { padding-left: 1.25rem; } }
    @media (prefers-reduced-motion: reduce) { .faq-sec-item, .faq-sec-icon::before, .faq-sec-icon::after { transition: none; } }
    @media (forced-colors: active) { .faq-sec-item { border: 1px solid CanvasText; } .faq-sec-icon::before, .faq-sec-icon::after { background: CanvasText; } }
    html.contrast-bw body .faq-sec-item, html.contrast-bw body .faq-sec-icon { background: #000 !important; outline: 1px solid #ff0; }
    html.contrast-bw body .faq-sec-icon::before, html.contrast-bw body .faq-sec-icon::after { background: #ff0 !important; }
</style>
@endonce

<section class="faq-sec not-prose" aria-labelledby="{{ $uid }}-title">
    <div class="faq-sec-grid">
        <div class="faq-sec-head">
            <h{{ $level }} id="{{ $uid }}-title" class="faq-sec-title">{{ $title }}</h{{ $level }}>
            <span class="faq-sec-bar" aria-hidden="true"></span>
            @if ($intro !== '')
                <p class="faq-sec-intro">{{ $intro }}</p>
            @endif
            <ul class="faq-sec-links" role="list">
                @if (Route::has('faq.index'))
                    <li><a href="{{ route('faq.index') }}">Wszystkie pytania<span aria-hidden="true"> →</span></a></li>
                @endif
                @if (Route::has('contact.show'))
                    <li><a href="{{ route('contact.show') }}">Nie ma Twojego pytania? Napisz do nas</a></li>
                @endif
            </ul>
        </div>

        <ol class="faq-sec-list" role="list">
            @foreach ($items as $i => $faq)
                <li>
                    <details class="faq-sec-item">
                        <summary class="faq-sec-q">
                            <span class="faq-sec-num" aria-hidden="true">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="faq-sec-text">{{ $faq->question }}</span>
                            <span class="faq-sec-icon" aria-hidden="true"></span>
                        </summary>
                        <div class="faq-sec-a">{{ trim((string) $faq->answer) }}</div>
                    </details>
                </li>
            @endforeach
        </ol>
    </div>

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $items->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => (string) $faq->question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim((string) $faq->answer)],
        ])->values()->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
</section>
