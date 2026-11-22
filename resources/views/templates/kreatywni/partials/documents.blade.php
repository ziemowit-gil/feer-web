{{--
    Sekcja #dokumenty na stronie „O nas” szablonu „kreatywni”: dokumenty fundacji z mikro modułu „Dokumenty – Kreatywni”
    (KreatywniDocument::forDisplay — tylko widoczne, z plikiem, opublikowaną stroną albo adresem). Płaska lista po dwie
    w rzędzie na szerokim ekranie: ikona typu, nazwa jako link z typem i rozmiarem pliku w tym samym linku (WCAG 2.4.4),
    krótki opis. Bez dokumentów sekcji nie ma. Kotwica jest w mega menu (KreatywniMegaMenu).
    Zmienne: opcjonalnie $kcDocuments (już policzone dokumenty).
--}}
@php
    $kcDocuments ??= \App\Models\KreatywniDocument::forDisplay();
    $kcDocIcon = fn (array $d) => match (true) {
        $d['kind'] === 'page' => 'fa-file-lines',
        $d['kind'] === 'link' => 'fa-arrow-up-right-from-square',
        str_starts_with((string) $d['meta'], 'PDF') => 'fa-file-pdf',
        (bool) preg_match('/^(DOC|DOCX|ODT|RTF)\b/', (string) $d['meta']) => 'fa-file-word',
        (bool) preg_match('/^(XLS|XLSX|ODS)\b/', (string) $d['meta']) => 'fa-file-excel',
        default => 'fa-file-arrow-down',
    };
@endphp
@if ($kcDocuments !== [])
    @once
        <style>
            .kc-docs { display: grid; gap: .75rem 2.5rem; margin: 0; padding: 0; list-style: none; }
            @media (min-width: 768px) { .kc-docs { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .kc-doc { display: flex; gap: .9rem; align-items: flex-start; padding: .9rem 0; border-bottom: 2px solid var(--kc-lime); }
            .kc-doc-icon { flex: none; width: 1.6rem; margin-top: .15rem; font-size: 1.35rem; color: var(--kc-green); text-align: center; }
            .kc-doc a { font-size: 1.1rem; font-weight: 700; line-height: 1.4; color: var(--kc-forest); text-decoration: underline; text-underline-offset: .2em; }
            .kc-doc a:hover { text-decoration-thickness: 3px; }
            .kc-doc a:focus-visible { outline: 3px solid var(--kc-forest); outline-offset: 3px; }
            .kc-doc-meta { font-weight: 600; white-space: nowrap; }
            .kc-doc p { margin: .3rem 0 0; line-height: 1.55; }
        </style>
    @endonce
    <section id="dokumenty" class="kc-sec" aria-labelledby="kc-about-docs">
        <div class="kc-wrap">
            <h2 id="kc-about-docs" class="kc-h2 kc-head">Dokumenty</h2>
            <ul class="kc-docs">
                @foreach ($kcDocuments as $doc)
                    <li class="kc-doc">
                        <i class="fa-solid {{ $kcDocIcon($doc) }} kc-doc-icon" aria-hidden="true"></i>
                        <div>
                            <a href="{{ $doc['url'] }}"@if ($doc['kind'] === 'link' && preg_match('#^https?://#i', $doc['url'])) target="_blank" rel="noopener"@endif>{{ $doc['title'] }}@if ($doc['meta']) <span class="kc-doc-meta">({{ $doc['meta'] }})</span>@endif @if ($doc['kind'] === 'link' && preg_match('#^https?://#i', $doc['url']))<span class="kc-sr"> (otwiera się w nowej karcie)</span>@endif</a>
                            @if ($doc['description'] !== '')<p>{{ $doc['description'] }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
