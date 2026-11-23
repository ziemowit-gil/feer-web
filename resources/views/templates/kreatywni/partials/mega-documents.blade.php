{{--
    Blok „Dokumenty” w rozwijanym menu pozycji „O nas” (szablon „kreatywni”, KreatywniMegaMenu::withDocuments): karta
    z dokumentami z modułu „Dokumenty – Kreatywni” — ikona typu, nazwa jako link (plik, strona albo adres; dokument
    „w przygotowaniu” prowadzi do sekcji #dokumenty), typ i rozmiar pliku w treści linku — oraz „Wszystkie dokumenty”.
    Zmienne: $item (documents, documentsUrl, documentsMore), $kcMegaIndex (numer pozycji menu — do unikalnych id).
--}}
@php
    $kcMdIcon = fn (array $d) => match (true) {
        $d['kind'] === 'pending' => 'fa-hourglass-half',
        $d['kind'] === 'page' => 'fa-file-lines',
        $d['kind'] === 'link' => 'fa-arrow-up-right-from-square',
        str_starts_with((string) $d['meta'], 'PDF') => 'fa-file-pdf',
        (bool) preg_match('/^(DOC|DOCX|ODT|RTF)\b/', (string) $d['meta']) => 'fa-file-word',
        (bool) preg_match('/^(XLS|XLSX|ODS)\b/', (string) $d['meta']) => 'fa-file-excel',
        default => 'fa-file-arrow-down',
    };
    $kcMdId = 'kc-mega-docs-'.($kcMegaIndex ?? 0);
@endphp
@once
    <style>
        @media (min-width: 1024px) { .kc-mega-in.has-docs { grid-template-columns: 17rem minmax(0, 1fr) 20rem; } }
        .kc-mega-docs { align-self: start; padding: 1.35rem 1.5rem 1.5rem; border-radius: 1.25rem; background: var(--kc-mint); }
        .kc-mega-docs-title { display: flex; align-items: center; gap: .6rem; margin: 0 0 .75rem; font-size: 1.15rem; font-weight: 800; color: var(--kc-forest); }
        .kc-mega-docs-title i { display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: .75rem; background: var(--kc-sun); font-size: 1rem; }
        .kc-mega-docs ul { margin: 0; padding: 0; list-style: none; }
        .kc-mega-docs li + li { border-top: 1px solid var(--kc-line); }
        .kc-mega-docs li a { display: flex; gap: .7rem; align-items: flex-start; min-height: 2.75rem; padding: .55rem 0; color: var(--kc-ink); font-size: .95rem; font-weight: 700; line-height: 1.35; text-decoration: none; }
        .kc-mega-docs li a > i { flex: none; width: 1.25rem; margin-top: .1rem; color: var(--kc-green); text-align: center; }
        .kc-mega-docs li a:hover .kc-mega-docs-name, .kc-mega-docs li a:focus-visible .kc-mega-docs-name { text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .kc-mega-docs li a:hover > i { color: var(--kc-forest); }
        .kc-mega-docs-meta { display: block; margin-top: .1rem; font-size: .82rem; font-weight: 600; color: var(--kc-muted); }
        .kc-mega-docs-all { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.75rem; margin-top: .5rem; font-weight: 800; color: var(--kc-forest); text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .kc-mega-docs-all:hover { text-decoration-color: var(--kc-forest); }
    </style>
@endonce
<div class="kc-mega-docs">
    <p id="{{ $kcMdId }}" class="kc-mega-docs-title"><i class="fa-solid fa-folder-open" aria-hidden="true"></i>Dokumenty</p>
    <ul aria-labelledby="{{ $kcMdId }}">
        @foreach ($item->documents as $doc)
            <li>
                <a href="{{ $doc['href'] }}"@if ($doc['kind'] === 'link' && preg_match('#^https?://#i', (string) $doc['url'])) target="_blank" rel="noopener"@endif>
                    <i class="fa-solid {{ $kcMdIcon($doc) }}" aria-hidden="true"></i>
                    <span>
                        <span class="kc-mega-docs-name">{{ $doc['title'] }}</span>
                        @if ($doc['meta'])<span class="kc-mega-docs-meta">{{ $doc['meta'] }}</span>@endif
                        @if ($doc['kind'] === 'pending')<span class="kc-mega-docs-meta">w przygotowaniu</span>@endif
                        @if ($doc['kind'] === 'link' && preg_match('#^https?://#i', (string) $doc['url']))<span class="kc-sr"> (otwiera się w nowej karcie)</span>@endif
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
    <a href="{{ $item->documentsUrl }}" class="kc-mega-docs-all">{{ $item->documentsMore > 0 ? 'Wszystkie dokumenty (+'.$item->documentsMore.')' : 'Wszystkie dokumenty' }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
</div>
