@php
    $editorId = 'editor-'.$name;
    $useCkEditor = ($siteSettings->content_editor ?? 'tinymce') === 'ckeditor';
    $msClientId = filled($siteSettings->microsoftConfig()['client_id'] ?? null) ? $siteSettings->microsoftConfig()['client_id'] : null;
    // TinyMCE keeps arbitrary <div> markup as-is, so it gets the real
    // (WCAG-friendly, responsive) two-column block. CKEditor's classic/super
    // CDN build has no General HTML Support plugin — any <div> it doesn't
    // recognise gets unwrapped to plain paragraphs on insert — so it falls
    // back to a native 2-cell table, the one multi-column construct its
    // schema actually preserves.
    $columnsHtml = '<div class="content-columns"><div class="content-column"><p>Pierwsza kolumna&hellip;</p></div><div class="content-column"><p>Druga kolumna&hellip;</p></div></div><p>&nbsp;</p>';
    $columnsHtmlForCk = '<table><tbody><tr><td><p>Pierwsza kolumna&hellip;</p></td><td><p>Druga kolumna&hellip;</p></td></tr></tbody></table><p>&nbsp;</p>';
    $ctaHtml = '<p><a href="#" class="cta-button">Sprawdź więcej</a></p><p>&nbsp;</p>';
    // Boxed/framed text block insertable from the editor. TinyMCE keeps the
    // custom <div> as-is; CKEditor's classic build unwraps unknown <div>s, so
    // it falls back to a <blockquote> — the one "boxed" construct its schema
    // preserves (and which is styled as a highlighted box on the front-end).
    $boxHtml = '<div class="content-box"><p>Wpisz tutaj tekst w ramce&hellip;</p></div><p>&nbsp;</p>';
    $boxHtmlForCk = '<blockquote><p>Wpisz tutaj tekst w ramce&hellip;</p></blockquote><p>&nbsp;</p>';
    // Blok ostrzeżenie/notatka — bursztynowa ramka boczna.
    $noteHtml = '<div class="content-note"><p>Wpisz tutaj treść notatki lub ostrzeżenia&hellip;</p></div><p>&nbsp;</p>';
    $noteHtmlForCk = '<blockquote><p>⚠ Wpisz tutaj treść notatki lub ostrzeżenia&hellip;</p></blockquote><p>&nbsp;</p>';
    // Blok ważna informacja — niebieska ramka boczna, kontrast WCAG AA+.
    $importantHtml = '<div class="content-important"><p><strong>Ważne</strong></p><p>Wpisz tutaj treść ważnej informacji&hellip;</p></div><p>&nbsp;</p>';
    $importantHtmlForCk = '<blockquote><p><strong>ℹ Ważne</strong><br>Wpisz tutaj treść ważnej informacji&hellip;</p></blockquote><p>&nbsp;</p>';
    // Historia wersji: opcjonalny kontekst przekazywany z formularza.
    $revisionType = $revisionable['type'] ?? null;
    $revisionId   = $revisionable['id'] ?? null;
    $historyJsonUrl = ($revisionType && $revisionId)
        ? route('admin.historia.json', ['type' => $revisionType, 'id' => $revisionId])
        : null;

    // Snippety wstawiane jednym, wspólnym mechanizmem (data-insert-key).
    $editorSnippets = [
        'red' => '<p><a href="#" class="cta-button cta-red">Przycisk</a></p><p>&nbsp;</p>',
        'green' => '<p><a href="#" class="cta-button cta-green">Przycisk</a></p><p>&nbsp;</p>',
        'bip' => '<div class="bip-link-box"><p><img src="/img/bip-logo.svg" alt="Logo Biuletynu Informacji Publicznej (BIP)"></p><p>Więcej informacji o naszej fundacji oraz dokumenty formalne publikujemy w Biuletynie Informacji Publicznej.</p><p><a href="/bip" class="cta-button">Więcej informacji w BIP</a></p></div><p>&nbsp;</p>',
        'accentLeft' => '<div class="accent-section accent-left"><p>Treść w kolorowej sekcji&hellip;</p></div><p>&nbsp;</p>',
        'accentRight' => '<div class="accent-section accent-right"><p>Treść w kolorowej sekcji&hellip;</p></div><p>&nbsp;</p>',
        'table' => '<table><caption>Opis tabeli</caption><thead><tr><th scope="col">Kolumna 1</th><th scope="col">Kolumna 2</th><th scope="col">Kolumna 3</th></tr></thead><tbody><tr><th scope="row">Wiersz 1</th><td>Dane</td><td>Dane</td></tr><tr><th scope="row">Wiersz 2</th><td>Dane</td><td>Dane</td></tr></tbody></table><p>&nbsp;</p>',
        'note' => $noteHtml,
        'important' => $importantHtml,
    ];
    // CKEditor nie zachowuje <div class="content-*"> — podmień na <blockquote>.
    $ckEditorSnippets = array_merge($editorSnippets, [
        'note' => $noteHtmlForCk,
        'important' => $importantHtmlForCk,
    ]);

    $pages = \App\Models\Page::where('is_published', true)->orderBy('title')->get();
    // Schedule pages, offered as ready-made CTA buttons ("Sprawdź harmonogram").
    $schedulePages = \App\Models\Page::where('is_published', true)->where('type', 'schedule')->orderBy('title')->get();

    // Wydarzenia do wstawienia jako „ramka z wydarzeniem". Snippet składamy po
    // stronie serwera (poprawne etykiety terminu/typu), a edytor tylko go wstawia.
    // TinyMCE zachowuje <div class="event-box">; CKEditor rozbija nieznane <div>y,
    // więc dostaje wariant na <blockquote> (który i tak wygląda jak ramka).
    $eventsForBox = \App\Models\Event::where('is_published', true)->orderByDesc('starts_at')->get();
    $eventBoxMeta = fn ($e) => collect([
        $e->typeLabel(),
        $e->starts_at ? $e->dateRangeLabel() : null,
        $e->location ?: $e->modeLabel(),
    ])->filter()->map(fn ($p) => e($p))->implode(' · ');
    $eventBoxHtml = fn ($e) => '<div class="event-box">'
        .'<p class="event-box-meta">'.$eventBoxMeta($e).'</p>'
        .'<p class="event-box-title"><a href="/wydarzenia/'.e($e->slug).'">'.e($e->title).'</a></p>'
        .'<p><a href="/wydarzenia/'.e($e->slug).'" class="cta-button">Szczegóły wydarzenia</a></p>'
        .'</div><p>&nbsp;</p>';
    $eventBoxHtmlCk = fn ($e) => '<blockquote>'
        .'<p><strong>'.e($e->title).'</strong><br>'.$eventBoxMeta($e).'</p>'
        .'<p><a href="/wydarzenia/'.e($e->slug).'">Szczegóły wydarzenia →</a></p>'
        .'</blockquote><p>&nbsp;</p>';
    $eventBoxOptions = $eventsForBox->map(fn ($e) => [
        'slug' => $e->slug,
        'title' => $e->title,
        'html' => $eventBoxHtml($e),
        'htmlCk' => $eventBoxHtmlCk($e),
    ])->values();

    $newsForPicker = \App\Models\News::where('is_published', true)
        ->orderByDesc('published_at')
        ->get(['id', 'title', 'slug']);
    $docxImportUrl = route('admin.editor.docx.import');
@endphp

@php $mi = 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-xs font-bold text-ink hover:bg-brand-light hover:text-brand'; @endphp
<div id="{{ $editorId }}-toolbar" class="mb-2 flex-wrap items-center gap-2 {{ $useCkEditor ? 'flex' : 'hidden' }}">
    {{-- Menu „Wstaw" — zgrupowane akcje wstawiania bloków --}}
    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
        <button type="button" @click="open = !open" :aria-expanded="open"
            class="inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Wstaw <i class="fa-solid fa-chevron-down text-[0.6rem]" aria-hidden="true"></i>
        </button>
        <div x-show="open" x-cloak class="absolute left-0 z-20 mt-1 w-60 rounded-lg border border-gray-200 bg-white p-1 shadow-lg" role="menu">
            <button type="button" id="{{ $editorId }}-media" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-images w-4 text-center" aria-hidden="true"></i> Obraz z biblioteki</button>
            <button type="button" id="{{ $editorId }}-gallery" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-border-all w-4 text-center" aria-hidden="true"></i> Galeria zdjęć</button>
            <button type="button" id="{{ $editorId }}-cta" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-square w-4 text-center text-brand" aria-hidden="true"></i> Przycisk CTA</button>
            <button type="button" data-insert-key="red" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-square w-4 text-center" style="color:#c81e1e" aria-hidden="true"></i> Przycisk czerwony</button>
            <button type="button" data-insert-key="green" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-square w-4 text-center" style="color:#15803d" aria-hidden="true"></i> Przycisk zielony</button>
            <button type="button" data-insert-key="bip" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-landmark w-4 text-center text-muted" aria-hidden="true"></i> Więcej informacji w BIP</button>
            <button type="button" id="{{ $editorId }}-box" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-vector-square w-4 text-center" aria-hidden="true"></i> Tekst z ramką</button>
            <button type="button" data-insert-key="note" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-triangle-exclamation w-4 text-center text-amber-500" aria-hidden="true"></i> Notatka / ostrzeżenie</button>
            <button type="button" data-insert-key="important" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-circle-info w-4 text-center text-blue-600" aria-hidden="true"></i> Ważne info</button>
            <button type="button" id="{{ $editorId }}-docx-btn" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-file-word w-4 text-center text-blue-600" aria-hidden="true"></i> Importuj DOCX…</button>
            @if ($useCkEditor)
                <button type="button" id="{{ $editorId }}-columns" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-table-columns w-4 text-center" aria-hidden="true"></i> Układ 2 kolumn</button>
            @endif
            <button type="button" data-insert-key="accentLeft" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-align-left w-4 text-center" aria-hidden="true"></i> Sekcja akcentu (lewo)</button>
            <button type="button" data-insert-key="accentRight" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-align-right w-4 text-center" aria-hidden="true"></i> Sekcja akcentu (prawo)</button>
            <button type="button" data-insert-key="table" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-table w-4 text-center" aria-hidden="true"></i> Tabela dostępna (WCAG)</button>
            @if ($eventBoxOptions->isNotEmpty())
                <label for="{{ $editorId }}-event-box" class="mt-2 block px-3 pb-1 text-[0.65rem] font-bold uppercase tracking-wide text-muted">Ramka z wydarzeniem</label>
                <select id="{{ $editorId }}-event-box" @change="open = false" class="w-full rounded border-gray-300 px-2 py-1.5 text-xs font-bold text-ink focus:border-brand focus:ring-brand">
                    <option value="">— wybierz wydarzenie —</option>
                    @foreach ($eventBoxOptions as $ev)
                        <option value="{{ $ev['slug'] }}">{{ $ev['title'] }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    {{-- Menu „Wstaw link" --}}
    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
        <button type="button" @click="open = !open" :aria-expanded="open"
            class="inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            <i class="fa-solid fa-link" aria-hidden="true"></i> Wstaw link <i class="fa-solid fa-chevron-down text-[0.6rem]" aria-hidden="true"></i>
        </button>
        <div x-show="open" x-cloak class="absolute left-0 z-20 mt-1 w-64 rounded-lg border border-gray-200 bg-white p-2 shadow-lg" role="menu">
            <button type="button" id="{{ $editorId }}-ext-link" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-arrow-up-right-from-square w-4 text-center" aria-hidden="true"></i> Link zewnętrzny (nowa karta)</button>
            @if ($pages->isNotEmpty())
                <button type="button"
                    @click="window['__pmOpen_{{ $editorId }}']?.(); open = false"
                    class="{{ $mi }}">
                    <i class="fa-solid fa-file-lines w-4 text-center" aria-hidden="true"></i> Wybierz stronę serwisu…
                </button>
            @endif
            @if ($newsForPicker->isNotEmpty())
                <button type="button"
                    @click="window['__nmOpen_{{ $editorId }}']?.(); open = false"
                    class="{{ $mi }}">
                    <i class="fa-solid fa-newspaper w-4 text-center" aria-hidden="true"></i> Wybierz news…
                </button>
            @endif
            @if ($eventsForBox->isNotEmpty())
                <button type="button"
                    @click="window['__elOpen_{{ $editorId }}']?.(); open = false"
                    class="{{ $mi }}">
                    <i class="fa-solid fa-calendar-day w-4 text-center" aria-hidden="true"></i> Wybierz wydarzenie…
                </button>
            @endif
            <button type="button"
                @click="window['__afOpen_{{ $editorId }}']?.(); open = false"
                class="{{ $mi }}">
                <i class="fa-solid fa-file-arrow-down w-4 text-center" aria-hidden="true"></i> Plik do pobrania…
            </button>
            @if ($schedulePages->isNotEmpty())
                <label for="{{ $editorId }}-schedule-cta" class="mt-2 block px-3 pb-1 text-[0.65rem] font-bold uppercase tracking-wide text-muted">Przycisk CTA do harmonogramu</label>
                <select id="{{ $editorId }}-schedule-cta" @change="open = false" class="w-full rounded border-gray-300 px-2 py-1.5 text-xs font-bold text-ink focus:border-brand focus:ring-brand">
                    <option value="">— wybierz harmonogram —</option>
                    @foreach ($schedulePages as $sp)
                        <option value="/{{ $sp->slug }}" data-title="{{ $sp->title }}">{{ $sp->title }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    {{-- Menu „Kotwice" --}}
    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
        <button type="button" @click="open = !open" :aria-expanded="open"
            class="inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            <i class="fa-solid fa-hashtag" aria-hidden="true"></i> Kotwice <i class="fa-solid fa-chevron-down text-[0.6rem]" aria-hidden="true"></i>
        </button>
        <div x-show="open" x-cloak class="absolute left-0 z-20 mt-1 w-52 rounded-lg border border-gray-200 bg-white p-2 shadow-lg" role="menu">
            <button type="button" @click="window['__anchorInsertOpen_{{ $editorId }}']?.(); open = false" class="{{ $mi }}"><i class="fa-solid fa-anchor w-4 text-center" aria-hidden="true"></i> Wstaw kotwicę</button>
            <button type="button" @click="window['__anchorLinkOpen_{{ $editorId }}']?.(); open = false" class="{{ $mi }}"><i class="fa-solid fa-link w-4 text-center" aria-hidden="true"></i> Link do kotwicy</button>
        </div>
    </div>

    {{-- Wyczyść formatowanie zaznaczenia --}}
    <button type="button" @click="window['__clearFormat_{{ $editorId }}']?.()"
        title="Wyczyść formatowanie zaznaczenia (usuwa pogrubienie, kursywę, nagłówki…)"
        aria-label="Wyczyść formatowanie zaznaczenia"
        class="inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-red-400 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
        <i class="fa-solid fa-eraser" aria-hidden="true"></i> Wyczyść formatowanie
    </button>

    @if ($historyJsonUrl)
    {{-- Historia wersji --}}
    <button type="button" @click="window['__historyOpen_{{ $editorId }}']?.()"
        title="Historia wersji treści"
        aria-label="Otwórz historię wersji treści"
        class="ml-auto inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historia wersji
    </button>
    @endif
</div>

<textarea name="{{ $name }}" id="{{ $editorId }}" rows="14" placeholder="Tu wpisz tekst…"
    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $value }}</textarea>

<input type="file" id="{{ $editorId }}-docx-input" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="sr-only" aria-hidden="true" tabindex="-1">
<input type="file" id="{{ $editorId }}-txt-input" accept=".txt,text/plain" class="sr-only" aria-hidden="true" tabindex="-1">

<div id="{{ $editorId }}-stats" class="mt-1 min-h-[1.25rem] text-xs text-muted" aria-live="polite" aria-atomic="true"></div>

<div id="{{ $editorId }}-page-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-page-modal-title">
    <div class="flex max-h-[70vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="{{ $editorId }}-page-modal-title" class="text-base font-bold">Wybierz stronę do linka</h2>
            <button type="button" data-page-modal-close class="text-muted hover:text-red-600" aria-label="Zamknij okno wyboru strony">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <label class="sr-only" for="{{ $editorId }}-page-modal-search">Szukaj strony po tytule lub adresie URL</label>
        <input type="search" id="{{ $editorId }}-page-modal-search" placeholder="Szukaj po tytule lub adresie URL…"
            class="mb-3 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <div class="flex-1 overflow-y-auto">
            <ul id="{{ $editorId }}-page-modal-list" class="divide-y divide-gray-100" role="listbox" aria-label="Lista stron serwisu"></ul>
            <p id="{{ $editorId }}-page-modal-empty" class="hidden py-6 text-center text-sm text-muted">Brak wyników.</p>
        </div>
    </div>
</div>

{{-- Modal: picker newsów --}}
<div id="{{ $editorId }}-news-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-news-modal-title">
    <div class="flex max-h-[70vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="{{ $editorId }}-news-modal-title" class="text-base font-bold">Wybierz news do linka</h2>
            <button type="button" data-news-modal-close class="text-muted hover:text-red-600" aria-label="Zamknij okno wyboru newsa"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <label class="sr-only" for="{{ $editorId }}-news-modal-search">Szukaj newsa po tytule</label>
        <input type="search" id="{{ $editorId }}-news-modal-search" placeholder="Szukaj po tytule…"
            class="mb-3 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <div class="flex-1 overflow-y-auto">
            <ul id="{{ $editorId }}-news-modal-list" class="divide-y divide-gray-100" role="listbox" aria-label="Lista newsów"></ul>
            <p id="{{ $editorId }}-news-modal-empty" class="hidden py-6 text-center text-sm text-muted">Brak wyników.</p>
        </div>
    </div>
</div>

{{-- Modal: picker wydarzeń jako link --}}
<div id="{{ $editorId }}-event-link-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-event-link-modal-title">
    <div class="flex max-h-[70vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="{{ $editorId }}-event-link-modal-title" class="text-base font-bold">Wybierz wydarzenie do linka</h2>
            <button type="button" data-event-link-modal-close class="text-muted hover:text-red-600" aria-label="Zamknij okno wyboru wydarzenia"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <label class="sr-only" for="{{ $editorId }}-event-link-modal-search">Szukaj wydarzenia po tytule</label>
        <input type="search" id="{{ $editorId }}-event-link-modal-search" placeholder="Szukaj po tytule…"
            class="mb-3 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <div class="flex-1 overflow-y-auto">
            <ul id="{{ $editorId }}-event-link-modal-list" class="divide-y divide-gray-100" role="listbox" aria-label="Lista wydarzeń"></ul>
            <p id="{{ $editorId }}-event-link-modal-empty" class="hidden py-6 text-center text-sm text-muted">Brak wyników.</p>
        </div>
    </div>
</div>

{{-- Modal: wstaw kotwicę --}}
<div id="{{ $editorId }}-anchor-insert-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-anchor-insert-title">
    <div class="w-full max-w-sm rounded-lg bg-white p-5 shadow-2xl">
        <div class="mb-3 flex items-center justify-between">
            <h2 id="{{ $editorId }}-anchor-insert-title" class="text-base font-bold">Wstaw kotwicę</h2>
            <button type="button" data-anchor-insert-close class="text-muted hover:text-red-600" aria-label="Zamknij"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="mb-3 text-xs text-muted">Kotwica to punkt na stronie, do którego można prowadzić linki (<code class="font-mono">#sekcja-id</code>). Zostanie wstawiona w miejscu kursora.</p>
        <label class="mb-1 block text-xs font-bold" for="{{ $editorId }}-anchor-insert-id">ID kotwicy</label>
        <input type="text" id="{{ $editorId }}-anchor-insert-id" placeholder="np. sekcja-kontakt"
            class="mb-1 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"
            pattern="[a-z0-9\-]+" title="Tylko małe litery, cyfry i myślniki">
        <p class="mb-4 text-[0.65rem] text-muted">Tylko małe litery, cyfry i myślniki. Bez spacji.</p>
        <div class="flex justify-end gap-2">
            <button type="button" data-anchor-insert-close class="rounded border border-gray-300 px-3 py-1.5 text-sm text-ink hover:bg-gray-50">Anuluj</button>
            <button type="button" id="{{ $editorId }}-anchor-insert-submit" class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark">Wstaw kotwicę</button>
        </div>
    </div>
</div>

{{-- Modal: link do kotwicy --}}
<div id="{{ $editorId }}-anchor-link-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-anchor-link-title">
    <div class="flex max-h-[80vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
            <h2 id="{{ $editorId }}-anchor-link-title" class="text-base font-bold">Link do kotwicy</h2>
            <button type="button" data-anchor-link-close class="text-muted hover:text-red-600" aria-label="Zamknij"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="mb-3 text-xs text-muted">Kliknij kotwicę z listy lub wpisz ID ręcznie. Kotwice muszą być wcześniej wstawione w treści.</p>
        <div class="mb-3 min-h-0 flex-1 overflow-y-auto">
            <p class="mb-1 text-[0.65rem] font-bold uppercase tracking-wide text-muted">Kotwice znalezione w treści</p>
            <ul id="{{ $editorId }}-anchor-link-found" class="divide-y divide-gray-100 rounded border border-gray-200" role="listbox" aria-label="Znalezione kotwice"></ul>
            <p id="{{ $editorId }}-anchor-link-none" class="py-3 text-center text-xs text-muted">Brak kotwic w treści — najpierw wstaw kotwicę.</p>
        </div>
        <div class="space-y-2 border-t border-gray-100 pt-3">
            <div>
                <label class="mb-0.5 block text-xs font-bold" for="{{ $editorId }}-anchor-link-id">ID kotwicy (bez #)</label>
                <input type="text" id="{{ $editorId }}-anchor-link-id" placeholder="sekcja-kontakt"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-0.5 block text-xs font-bold" for="{{ $editorId }}-anchor-link-text">Tekst linku</label>
                <input type="text" id="{{ $editorId }}-anchor-link-text" placeholder="np. Przejdź do sekcji kontakt"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" data-anchor-link-close class="rounded border border-gray-300 px-3 py-1.5 text-sm text-ink hover:bg-gray-50">Anuluj</button>
            <button type="button" id="{{ $editorId }}-anchor-link-submit" class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark">Wstaw link</button>
        </div>
    </div>
</div>

@if ($historyJsonUrl)
{{-- Modal: historia wersji treści --}}
<div id="{{ $editorId }}-history-modal" class="fixed inset-0 z-50 hidden items-start justify-center bg-black/50 p-4 pt-16"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-history-title">
    <div class="flex h-[70vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-3">
            <h2 id="{{ $editorId }}-history-title" class="text-base font-bold">
                <i class="fa-solid fa-clock-rotate-left mr-2 text-brand" aria-hidden="true"></i>Historia wersji treści
            </h2>
            <button type="button" id="{{ $editorId }}-history-close"
                class="text-muted hover:text-red-600" aria-label="Zamknij historię wersji">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="flex min-h-0 flex-1">
            {{-- Lista rewizji --}}
            <div class="w-64 shrink-0 overflow-y-auto border-r border-gray-100 p-2">
                <p id="{{ $editorId }}-history-loading" class="py-6 text-center text-sm text-muted">Ładowanie…</p>
                <ul id="{{ $editorId }}-history-list" class="space-y-1" role="listbox" aria-label="Lista wersji treści"></ul>
                <p id="{{ $editorId }}-history-empty" class="hidden py-6 text-center text-sm text-muted">Brak zapisanych wersji.</p>
            </div>
            {{-- Podgląd wybranej wersji --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <div id="{{ $editorId }}-history-hint" class="flex flex-1 items-center justify-center text-sm text-muted">
                    Wybierz wersję z listy, aby zobaczyć podgląd.
                </div>
                <div id="{{ $editorId }}-history-preview-wrap" class="hidden min-h-0 flex-1 overflow-y-auto p-5">
                    <div id="{{ $editorId }}-history-preview" class="prose max-w-none text-sm"></div>
                </div>
                <div id="{{ $editorId }}-history-footer" class="hidden shrink-0 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="{{ $editorId }}-history-load"
                        class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-rotate-left mr-1" aria-hidden="true"></i> Załaduj tę wersję do edytora
                    </button>
                    <span class="ml-3 text-xs text-muted">Aktualna treść zostanie zastąpiona — możesz cofnąć (Ctrl+Z).</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Modal: galeria zdjęć (multi-select) --}}
<div id="{{ $editorId }}-gallery-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-gallery-modal-title">
    <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
            <h2 id="{{ $editorId }}-gallery-modal-title" class="text-base font-bold">Wstaw galerię zdjęć</h2>
            <button type="button" data-gallery-close class="text-muted hover:text-red-600" aria-label="Zamknij okno galerii"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="mb-3 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="{{ $editorId }}-gallery-cols" class="text-xs font-bold">Kolumny:</label>
                <select id="{{ $editorId }}-gallery-cols" class="rounded border-gray-300 py-1 text-sm focus:border-brand focus:ring-brand">
                    <option value="2">2</option>
                    <option value="3" selected>3</option>
                    <option value="4">4</option>
                </select>
            </div>
            <span id="{{ $editorId }}-gallery-count" class="text-xs text-muted" aria-live="polite">Zaznaczono: 0 zdjęć</span>
            <button type="button" id="{{ $editorId }}-gallery-submit" disabled
                class="ml-auto rounded bg-brand px-4 py-1.5 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-40">
                <i class="fa-solid fa-border-all" aria-hidden="true"></i> Wstaw galerię
            </button>
        </div>
        <label class="sr-only" for="{{ $editorId }}-gallery-search">Szukaj zdjęcia po nazwie pliku</label>
        <input type="search" id="{{ $editorId }}-gallery-search" placeholder="Szukaj po nazwie pliku…"
            class="mb-3 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <div class="flex-1 overflow-y-auto">
            <p id="{{ $editorId }}-gallery-loading" class="py-6 text-center text-sm text-muted" aria-live="polite">Ładowanie zdjęć…</p>
            <div id="{{ $editorId }}-gallery-grid" class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5"></div>
            <p id="{{ $editorId }}-gallery-empty" class="hidden py-6 text-center text-sm text-muted">Brak zdjęć.</p>
        </div>
    </div>
</div>

{{-- Modal: picker plików do pobrania --}}
<div id="{{ $editorId }}-attachment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="{{ $editorId }}-attachment-modal-title">
    <div class="flex max-h-[70vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="{{ $editorId }}-attachment-modal-title" class="text-base font-bold">Wybierz plik do pobrania</h2>
            <button type="button" data-af-close class="text-muted hover:text-red-600" aria-label="Zamknij okno wyboru pliku"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <label class="sr-only" for="{{ $editorId }}-af-search">Szukaj pliku po nazwie lub stronie</label>
        <input type="search" id="{{ $editorId }}-af-search" placeholder="Szukaj po nazwie pliku lub stronie…"
            class="mb-3 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <div class="flex-1 overflow-y-auto">
            <ul id="{{ $editorId }}-af-list" class="divide-y divide-gray-100" role="listbox" aria-label="Lista plików do pobrania"></ul>
            <p id="{{ $editorId }}-af-empty" class="hidden py-6 text-center text-sm text-muted">Brak plików do pobrania.</p>
            <p id="{{ $editorId }}-af-loading" class="py-6 text-center text-sm text-muted">Ładowanie…</p>
        </div>
    </div>
</div>

<div id="{{ $editorId }}-media-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-label="Wybierz obraz">
    <div class="flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold">Wybierz obraz</h2>
            <button type="button" data-media-close class="text-muted hover:text-red-600" aria-label="Zamknij okno wyboru obrazu"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="mb-4 flex w-fit gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 text-sm font-bold">
            <button type="button" data-media-tab-btn="library" class="rounded px-3 py-1.5 bg-brand text-white">Biblioteka</button>
            <button type="button" data-media-tab-btn="unsplash" class="rounded px-3 py-1.5 text-muted hover:bg-gray-100">Unsplash</button>
            <button type="button" data-media-tab-btn="onedrive" class="rounded px-3 py-1.5 text-muted hover:bg-gray-100">OneDrive</button>
        </div>

        <div data-media-panel="library">
            <label class="sr-only" for="{{ $editorId }}-media-search">Szukaj obrazu po nazwie pliku</label>
            <input type="search" id="{{ $editorId }}-media-search" placeholder="Szukaj po nazwie pliku&hellip;"
                class="mb-4 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            <div class="overflow-y-auto">
                <div data-media-grid class="grid grid-cols-3 gap-3 sm:grid-cols-4"></div>
                <p data-media-empty class="hidden py-6 text-center text-sm text-muted">Brak obrazów.</p>
            </div>
        </div>

        <div data-media-panel=”unsplash” class=”hidden”>
            {{-- Plain div, not <form>: this partial renders inside the page's own
                 <form>, and nested <form> elements are invalid HTML — browsers
                 silently drop them, along with everything depending on 'submit'. --}}
            <div data-unsplash-form class=”mb-4 flex gap-2”>
                <label class=”sr-only” for=”{{ $editorId }}-unsplash-search”>Szukaj zdjęć na Unsplash</label>
                <input type=”search” id=”{{ $editorId }}-unsplash-search” placeholder=”Szukaj zdjęć na Unsplash&hellip;”
                    class=”w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand”>
                <button type=”button” data-unsplash-submit class=”flex-none rounded bg-brand px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-dark”>Szukaj</button>
            </div>
            <div class=”overflow-y-auto”>
                <div data-unsplash-grid class=”grid grid-cols-3 gap-3 sm:grid-cols-4”></div>
                <p data-unsplash-hint class=”py-6 text-center text-sm text-muted”>Wpisz szukaną frazę powyżej (np. „edukacja”, „dostępność”).</p>
                <p data-unsplash-loading class=”hidden py-6 text-center text-sm text-muted”>Szukam&hellip;</p>
                <p data-unsplash-error class=”hidden py-6 text-center text-sm text-red-600”></p>
            </div>
        </div>

        <div data-media-panel=”onedrive” class=”hidden”>
            @if ($msClientId)
            <div class=”mb-4 flex justify-center”>
                <button type=”button” data-onedrive-open
                    class=”inline-flex items-center gap-2 rounded bg-[#0078d4] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#106ebe] disabled:opacity-50”
                    aria-label=”Otwórz przeglądarkę plików OneDrive”>
                    <i class=”fa-brands fa-microsoft” aria-hidden=”true”></i> Wybierz z OneDrive
                </button>
            </div>
            <p class=”mb-4 text-center text-xs text-muted”>Zaloguj się kontem Microsoft i wybierz obraz. Plik zostanie pobrany do biblioteki.</p>
            <details class=”mb-2”>
                <summary class=”cursor-pointer text-xs text-muted hover:text-ink”>Lub wklej publiczny link</summary>
                <div class=”mt-2 flex gap-2”>
            @else
            <p class=”mb-3 text-xs text-muted”>Wklej publiczny link do obrazu z OneDrive (<code class=”font-mono”>https://1drv.ms/i/&hellip;</code>). Link musi być dostępny dla każdego z linkiem.</p>
            <div class=”mb-4 flex gap-2”>
            @endif
                <label class=”sr-only” for=”{{ $editorId }}-onedrive-url”>Adres URL obrazu z OneDrive</label>
                <input type=”url” id=”{{ $editorId }}-onedrive-url” placeholder=”https://1drv.ms/i/&hellip;”
                    class=”w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand”>
                <button type=”button” data-onedrive-submit
                    class=”flex-none rounded bg-brand px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-dark disabled:opacity-50”>Importuj</button>
            @if ($msClientId)
                </div>
            </details>
            @else
            </div>
            @endif
            <p data-onedrive-hint class=”py-4 text-center text-sm text-muted”>
                @if ($msClientId) Kliknij „Wybierz z OneDrive” powyżej.
                @else Wklej link i kliknij „Importuj”.
                @endif
            </p>
            <p data-onedrive-loading class=”hidden py-4 text-center text-sm text-muted” aria-live=”polite”>Pobieranie pliku z OneDrive&hellip;</p>
            <p data-onedrive-error class=”hidden py-4 text-center text-sm text-red-600” role=”alert”></p>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('{{ $editorId }}-media-modal');
        var grid = modal.querySelector('[data-media-grid]');
        var emptyMsg = modal.querySelector('[data-media-empty]');
        var searchInput = document.getElementById('{{ $editorId }}-media-search');
        var openButton = document.getElementById('{{ $editorId }}-media');
        var allImages = null;

        function escapeHtml(text) {
            return (text || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function renderImages(list) {
            grid.innerHTML = '';
            emptyMsg.classList.toggle('hidden', list.length > 0);

            list.forEach(function (image) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'overflow-hidden rounded border border-gray-200 text-left hover:border-brand';
                button.innerHTML = '<img src="' + image.url + '" alt="' + escapeHtml(image.alt) + '" class="h-24 w-full object-cover">' +
                    '<span class="block truncate p-1 text-xs text-muted">' + escapeHtml(image.file_name) + '</span>';
                button.addEventListener('click', function () {
                    modal.dispatchEvent(new CustomEvent('media-picked', {detail: image}));
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });
                grid.appendChild(button);
            });
        }

        function loadImages() {
            if (allImages) {
                renderImages(allImages);
                return;
            }

            fetch('{{ route('admin.multimedia.images') }}')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    allImages = data;
                    renderImages(allImages);
                });
        }

        searchInput.addEventListener('input', function () {
            if (!allImages) return;
            var q = searchInput.value.toLowerCase();
            renderImages(allImages.filter(function (image) {
                return image.file_name.toLowerCase().indexOf(q) !== -1 || image.alt.toLowerCase().indexOf(q) !== -1;
            }));
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        modal.querySelectorAll('[data-media-close]').forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });

        modal.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeModal();
        });

        openButton.addEventListener('click', function () {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loadImages();
            searchInput.value = '';
            searchInput.focus();
        });

        // Tabs: switch between the local library grid and Unsplash search.
        var tabButtons = modal.querySelectorAll('[data-media-tab-btn]');
        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                tabButtons.forEach(function (b) {
                    b.classList.toggle('bg-brand', b === btn);
                    b.classList.toggle('text-white', b === btn);
                    b.classList.toggle('text-muted', b !== btn);
                });
                modal.querySelectorAll('[data-media-panel]').forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.dataset.mediaPanel !== btn.dataset.mediaTabBtn);
                });
            });
        });

        // Unsplash search + import-on-pick.
        var unsplashSubmitButton = modal.querySelector('[data-unsplash-submit]');
        var unsplashSearchInput = document.getElementById('{{ $editorId }}-unsplash-search');
        var unsplashGrid = modal.querySelector('[data-unsplash-grid]');
        var unsplashHint = modal.querySelector('[data-unsplash-hint]');
        var unsplashLoading = modal.querySelector('[data-unsplash-loading]');
        var unsplashError = modal.querySelector('[data-unsplash-error]');

        function runUnsplashSearch() {
            if (!unsplashSearchInput.value.trim()) return;
            unsplashGrid.innerHTML = '';
            unsplashHint.classList.add('hidden');
            unsplashError.classList.add('hidden');
            unsplashLoading.classList.remove('hidden');

            fetch('{{ route('admin.multimedia.unsplash.search') }}?q=' + encodeURIComponent(unsplashSearchInput.value))
                .then(function (res) {
                    if (!res.ok) throw new Error('search-failed');
                    return res.json();
                })
                .then(function (results) {
                    unsplashLoading.classList.add('hidden');
                    if (!results.length) {
                        unsplashHint.textContent = 'Brak wyników dla tej frazy.';
                        unsplashHint.classList.remove('hidden');
                        return;
                    }
                    results.forEach(function (photo) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'overflow-hidden rounded border border-gray-200 text-left hover:border-brand';
                        button.innerHTML = '<img src="' + photo.thumb_url + '" alt="' + escapeHtml(photo.alt) + '" class="h-24 w-full object-cover">' +
                            '<span class="block truncate p-1 text-[11px] text-muted">Zdjęcie: ' + escapeHtml(photo.author_name) + '</span>';
                        button.disabled = false;
                        button.addEventListener('click', function () {
                            button.disabled = true;
                            button.style.opacity = '0.5';
                            fetch('{{ route('admin.multimedia.unsplash.import') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                                },
                                body: JSON.stringify({
                                    full_url: photo.full_url,
                                    download_location: photo.download_location,
                                    author_name: photo.author_name,
                                }),
                            })
                                .then(function (res) { return res.json(); })
                                .then(function (image) {
                                    modal.dispatchEvent(new CustomEvent('media-picked', {detail: image}));
                                    closeModal();
                                })
                                .catch(function () {
                                    button.disabled = false;
                                    button.style.opacity = '1';
                                });
                        });
                        unsplashGrid.appendChild(button);
                    });
                })
                .catch(function () {
                    unsplashLoading.classList.add('hidden');
                    unsplashError.textContent = 'Nie udało się połączyć z Unsplash. Sprawdź, czy klucz API jest skonfigurowany.';
                    unsplashError.classList.remove('hidden');
                });
        }

        unsplashSubmitButton.addEventListener('click', runUnsplashSearch);
        unsplashSearchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                runUnsplashSearch();
            }
        });

        // OneDrive: picker SDK (gdy microsoft_client_id) + fallback "wklej link" (zawsze).
        var onedriveHint    = modal.querySelector('[data-onedrive-hint]');
        var onedriveLoading = modal.querySelector('[data-onedrive-loading]');
        var onedriveError   = modal.querySelector('[data-onedrive-error]');
        var onedriveSubmitButton = modal.querySelector('[data-onedrive-submit]');
        var onedriveUrlInput     = document.getElementById('{{ $editorId }}-onedrive-url');

        function oneDriveSetLoading(loading) {
            onedriveHint.classList.toggle('hidden', loading);
            onedriveLoading.classList.toggle('hidden', !loading);
            onedriveError.classList.add('hidden');
            if (onedriveSubmitButton) onedriveSubmitButton.disabled = loading;
            @if ($msClientId)
            var openBtn = modal.querySelector('[data-onedrive-open]');
            if (openBtn) openBtn.disabled = loading;
            @endif
        }

        function oneDriveImportUrl(payload) {
            oneDriveSetLoading(true);
            fetch('{{ route('admin.multimedia.onedrive.import') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                },
                body: JSON.stringify(payload),
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        if (!res.ok) throw new Error(body.message || 'Błąd importu');
                        return body;
                    });
                })
                .then(function (image) {
                    oneDriveSetLoading(false);
                    if (onedriveUrlInput) onedriveUrlInput.value = '';
                    modal.dispatchEvent(new CustomEvent('media-picked', {detail: image}));
                    closeModal();
                })
                .catch(function (err) {
                    oneDriveSetLoading(false);
                    onedriveError.textContent = err.message || 'Nie udało się pobrać pliku.';
                    onedriveError.classList.remove('hidden');
                });
        }

        @if ($msClientId)
        // Picker SDK — ładowany leniwie przy pierwszym kliknięciu.
        var odSdkLoaded = false;
        function loadOneDriveSdk(cb) {
            if (window.OneDrive) { cb(); return; }
            var s = document.createElement('script');
            s.src = 'https://js.live.net/v7.2/OneDrive.js';
            s.onload = cb;
            document.head.appendChild(s);
        }
        var odOpenBtn = modal.querySelector('[data-onedrive-open]');
        odOpenBtn.addEventListener('click', function () {
            loadOneDriveSdk(function () {
                window.OneDrive.open({
                    clientId: {{ json_encode($msClientId) }},
                    action: 'download',
                    multiSelect: false,
                    advanced: { filter: '.jpg,.jpeg,.png,.gif,.webp,.bmp,.avif,.svg' },
                    success: function (files) {
                        var file = files.value[0];
                        var dlUrl = file['@microsoft.graph.downloadUrl'] || file['@content.downloadUrl'];
                        oneDriveImportUrl({ download_url: dlUrl, name: file.name });
                    },
                    cancel: function () {},
                    error: function (e) {
                        onedriveError.textContent = 'Błąd OneDrive: ' + (e.message || JSON.stringify(e));
                        onedriveError.classList.remove('hidden');
                    },
                });
            });
        });
        @endif

        // URL paste — fallback zawsze dostępny.
        if (onedriveSubmitButton) {
            function runOneDrivePaste() {
                var url = onedriveUrlInput ? onedriveUrlInput.value.trim() : '';
                if (!url) return;
                oneDriveImportUrl({ url: url });
            }
            onedriveSubmitButton.addEventListener('click', runOneDrivePaste);
            if (onedriveUrlInput) {
                onedriveUrlInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') { event.preventDefault(); runOneDrivePaste(); }
                });
            }
        }
    })();
</script>

<script>
    (function () {
        var gmModal   = document.getElementById('{{ $editorId }}-gallery-modal');
        var gmGrid    = document.getElementById('{{ $editorId }}-gallery-grid');
        var gmSearch  = document.getElementById('{{ $editorId }}-gallery-search');
        var gmEmpty   = document.getElementById('{{ $editorId }}-gallery-empty');
        var gmLoading = document.getElementById('{{ $editorId }}-gallery-loading');
        var gmCount   = document.getElementById('{{ $editorId }}-gallery-count');
        var gmSubmit  = document.getElementById('{{ $editorId }}-gallery-submit');
        var gmCols    = document.getElementById('{{ $editorId }}-gallery-cols');
        var gmAllImages = null;
        var gmSelected  = [];

        function gmEsc(t) {
            return (t || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function gmCurrentList() {
            var q = gmSearch.value.toLowerCase();
            if (!gmAllImages) return [];
            return q ? gmAllImages.filter(function (i) {
                return i.file_name.toLowerCase().indexOf(q) !== -1 || i.alt.toLowerCase().indexOf(q) !== -1;
            }) : gmAllImages;
        }

        function gmRender(list) {
            gmGrid.innerHTML = '';
            gmEmpty.classList.toggle('hidden', list.length > 0);
            list.forEach(function (image) {
                var sel = gmSelected.some(function (s) { return s.id === image.id; });
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'relative overflow-hidden rounded border-2 text-left transition-colors '
                    + (sel ? 'border-brand' : 'border-transparent hover:border-gray-300');
                btn.setAttribute('aria-pressed', sel ? 'true' : 'false');
                btn.setAttribute('aria-label', (sel ? 'Odznacz: ' : 'Zaznacz: ') + image.file_name);
                btn.innerHTML = '<img src="' + image.url + '" alt="" class="h-24 w-full object-cover">'
                    + (sel ? '<span class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-brand text-white text-[10px]" aria-hidden="true"><i class="fa-solid fa-check"></i></span>' : '');
                btn.addEventListener('click', function () {
                    var idx = gmSelected.findIndex(function (s) { return s.id === image.id; });
                    if (idx >= 0) gmSelected.splice(idx, 1); else gmSelected.push(image);
                    gmRender(gmCurrentList());
                    gmUpdateCount();
                });
                gmGrid.appendChild(btn);
            });
        }

        function gmUpdateCount() {
            var n = gmSelected.length;
            var label = n === 1 ? '1 zdjęcie' : (n < 5 && n > 1 ? n + ' zdjęcia' : n + ' zdjęć');
            gmCount.textContent = 'Zaznaczono: ' + label;
            gmSubmit.disabled = n === 0;
        }

        function loadGalleryImages() {
            if (gmAllImages !== null) { gmLoading.classList.add('hidden'); gmRender(gmAllImages); return; }
            fetch('{{ route('admin.multimedia.images') }}')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    gmAllImages = data;
                    gmLoading.classList.add('hidden');
                    gmRender(data);
                });
        }

        gmSearch.addEventListener('input', function () { if (gmAllImages) gmRender(gmCurrentList()); });

        gmSubmit.addEventListener('click', function () {
            if (!gmSelected.length) return;
            var cols = gmCols.value;
            var colClass = cols === '3' ? '' : ' content-gallery--cols-' + cols;
            var figures = gmSelected.map(function (img) {
                return '<figure><img src="' + img.url + '" alt="' + gmEsc(img.alt) + '" loading="lazy"></figure>';
            }).join('');
            var html = '<div class="content-gallery' + colClass + '" role="group" aria-label="Galeria zdjęć">'
                + figures + '</div><p>&nbsp;</p>';
            gmModal.dispatchEvent(new CustomEvent('gallery-picked', {detail: {html: html}}));
            gmClose();
        });

        function gmOpen() {
            gmSelected = [];
            gmModal.classList.remove('hidden');
            gmModal.classList.add('flex');
            gmUpdateCount();
            loadGalleryImages();
            gmSearch.value = '';
            gmSearch.focus();
        }

        function gmClose() {
            gmModal.classList.add('hidden');
            gmModal.classList.remove('flex');
        }

        gmModal.querySelectorAll('[data-gallery-close]').forEach(function (btn) { btn.addEventListener('click', gmClose); });
        gmModal.addEventListener('click', function (e) { if (e.target === gmModal) gmClose(); });
        gmModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') gmClose(); });

        window['__gmOpen_{{ $editorId }}'] = gmOpen;
    })();
</script>

<script>
    (function () {
        var afModal   = document.getElementById('{{ $editorId }}-attachment-modal');
        var afList    = document.getElementById('{{ $editorId }}-af-list');
        var afSearch  = document.getElementById('{{ $editorId }}-af-search');
        var afEmpty   = document.getElementById('{{ $editorId }}-af-empty');
        var afLoading = document.getElementById('{{ $editorId }}-af-loading');
        var allAttachments = null;

        function afEsc(t) {
            return (t || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function renderAttachments(list) {
            afList.innerHTML = '';
            afEmpty.classList.toggle('hidden', list.length > 0);
            list.forEach(function (a) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('role', 'option');
                btn.className = 'flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none';
                var meta = [a.extension, a.size].filter(Boolean).join(' · ');
                btn.innerHTML = '<i class="fa-solid fa-file-arrow-down w-4 shrink-0 text-muted" aria-hidden="true"></i>'
                    + '<span class="flex-1 min-w-0">'
                    + '<span class="block truncate text-sm font-medium text-ink">' + afEsc(a.label) + '</span>'
                    + (a.owner_title ? '<span class="block truncate text-xs text-muted">' + afEsc(a.owner_title) + '</span>' : '')
                    + '</span>'
                    + (meta ? '<span class="shrink-0 text-xs text-muted">' + afEsc(meta) + '</span>' : '');
                btn.addEventListener('click', function () {
                    afModal.dispatchEvent(new CustomEvent('attachment-picked', {detail: a}));
                    afClose();
                });
                var li = document.createElement('li');
                li.appendChild(btn);
                afList.appendChild(li);
            });
            if (list.length > 0) { afList.querySelector('button')?.focus(); }
        }

        function loadAttachments() {
            if (allAttachments !== null) { renderAttachments(allAttachments); return; }
            fetch('{{ route('admin.pliki.lista') }}')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    allAttachments = data;
                    afLoading.classList.add('hidden');
                    renderAttachments(data);
                });
        }

        afSearch.addEventListener('input', function () {
            if (!allAttachments) return;
            var q = afSearch.value.toLowerCase();
            renderAttachments(allAttachments.filter(function (a) {
                return a.label.toLowerCase().indexOf(q) !== -1
                    || (a.owner_title && a.owner_title.toLowerCase().indexOf(q) !== -1);
            }));
        });

        function afOpen() {
            afModal.classList.remove('hidden');
            afModal.classList.add('flex');
            loadAttachments();
            afSearch.value = '';
            afSearch.focus();
        }

        function afClose() {
            afModal.classList.add('hidden');
            afModal.classList.remove('flex');
        }

        afModal.querySelectorAll('[data-af-close]').forEach(function (btn) { btn.addEventListener('click', afClose); });
        afModal.addEventListener('click', function (e) { if (e.target === afModal) afClose(); });
        afModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') afClose(); });

        window['__afOpen_{{ $editorId }}'] = afOpen;
    })();
</script>

<script>
    (function () {
        var pmModal  = document.getElementById('{{ $editorId }}-page-modal');
        var pmSearch = document.getElementById('{{ $editorId }}-page-modal-search');
        var pmList   = document.getElementById('{{ $editorId }}-page-modal-list');
        var pmEmpty  = document.getElementById('{{ $editorId }}-page-modal-empty');
        var pmPages  = {!! json_encode($pages->map(fn ($p) => ['url' => '/'.$p->slug, 'title' => $p->title])->values()) !!};

        function pmEsc(t) {
            return (t || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function pmRender(q) {
            var lower = q.toLowerCase();
            var list = lower ? pmPages.filter(function (p) {
                return p.title.toLowerCase().indexOf(lower) !== -1 || p.url.toLowerCase().indexOf(lower) !== -1;
            }) : pmPages;
            pmList.innerHTML = '';
            pmEmpty.classList.toggle('hidden', list.length > 0);
            list.forEach(function (p) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('role', 'option');
                btn.className = 'flex w-full items-baseline gap-3 px-3 py-2.5 text-left hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none';
                btn.innerHTML = '<span class="flex-1 text-sm font-medium text-ink">' + pmEsc(p.title) + '</span>'
                    + '<span class="shrink-0 text-xs text-muted">' + pmEsc(p.url) + '</span>';
                btn.addEventListener('click', function () {
                    pmModal.dispatchEvent(new CustomEvent('page-link-picked', { detail: p }));
                    pmClose();
                });
                var li = document.createElement('li');
                li.appendChild(btn);
                pmList.appendChild(li);
            });
            if (list.length > 0) {
                pmList.querySelector('button')?.focus();
            }
        }

        function pmOpen() {
            pmModal.classList.remove('hidden');
            pmModal.classList.add('flex');
            pmSearch.value = '';
            pmRender('');
            pmSearch.focus();
        }

        function pmClose() {
            pmModal.classList.add('hidden');
            pmModal.classList.remove('flex');
        }

        pmModal.querySelector('[data-page-modal-close]').addEventListener('click', pmClose);
        pmModal.addEventListener('click', function (e) { if (e.target === pmModal) pmClose(); });
        pmModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') pmClose(); });
        pmSearch.addEventListener('input', function () { pmRender(pmSearch.value); });

        window['__pmOpen_{{ $editorId }}'] = pmOpen;
    })();
</script>

<script>
    // ── Picker newsów ────────────────────────────────────────────────────────
    (function () {
        var nmModal  = document.getElementById('{{ $editorId }}-news-modal');
        var nmSearch = document.getElementById('{{ $editorId }}-news-modal-search');
        var nmList   = document.getElementById('{{ $editorId }}-news-modal-list');
        var nmEmpty  = document.getElementById('{{ $editorId }}-news-modal-empty');
        var nmItems  = {!! json_encode($newsForPicker->map(fn ($n) => ['url' => route('news.show', $n), 'title' => $n->title])->values()) !!};

        function nmEsc(t) { return (t||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function nmRender(q) {
            var lower = q.toLowerCase();
            var list = lower ? nmItems.filter(function (n) { return n.title.toLowerCase().indexOf(lower) !== -1; }) : nmItems;
            nmList.innerHTML = '';
            nmEmpty.classList.toggle('hidden', list.length > 0);
            list.forEach(function (n) {
                var btn = document.createElement('button');
                btn.type = 'button'; btn.setAttribute('role', 'option');
                btn.className = 'flex w-full items-baseline gap-3 px-3 py-2.5 text-left hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none';
                btn.innerHTML = '<span class="flex-1 text-sm font-medium text-ink">' + nmEsc(n.title) + '</span>'
                    + '<span class="shrink-0 text-xs text-muted">' + nmEsc(n.url) + '</span>';
                btn.addEventListener('click', function () {
                    nmModal.dispatchEvent(new CustomEvent('news-link-picked', { detail: n }));
                    nmClose();
                });
                var li = document.createElement('li'); li.appendChild(btn); nmList.appendChild(li);
            });
        }

        function nmOpen() { nmModal.classList.remove('hidden'); nmModal.classList.add('flex'); nmSearch.value = ''; nmRender(''); nmSearch.focus(); }
        function nmClose() { nmModal.classList.add('hidden'); nmModal.classList.remove('flex'); }

        nmModal.querySelector('[data-news-modal-close]').addEventListener('click', nmClose);
        nmModal.addEventListener('click', function (e) { if (e.target === nmModal) nmClose(); });
        nmModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') nmClose(); });
        nmSearch.addEventListener('input', function () { nmRender(nmSearch.value); });
        window['__nmOpen_{{ $editorId }}'] = nmOpen;
    })();

    // ── Picker wydarzeń jako link ─────────────────────────────────────────────
    (function () {
        var elModal  = document.getElementById('{{ $editorId }}-event-link-modal');
        var elSearch = document.getElementById('{{ $editorId }}-event-link-modal-search');
        var elList   = document.getElementById('{{ $editorId }}-event-link-modal-list');
        var elEmpty  = document.getElementById('{{ $editorId }}-event-link-modal-empty');
        var elItems  = {!! json_encode($eventsForBox->map(fn ($e) => ['url' => '/wydarzenia/'.$e->slug, 'title' => $e->title])->values()) !!};

        function elEsc(t) { return (t||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function elRender(q) {
            var lower = q.toLowerCase();
            var list = lower ? elItems.filter(function (ev) { return ev.title.toLowerCase().indexOf(lower) !== -1; }) : elItems;
            elList.innerHTML = '';
            elEmpty.classList.toggle('hidden', list.length > 0);
            list.forEach(function (ev) {
                var btn = document.createElement('button');
                btn.type = 'button'; btn.setAttribute('role', 'option');
                btn.className = 'flex w-full items-baseline gap-3 px-3 py-2.5 text-left hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none';
                btn.innerHTML = '<span class="flex-1 text-sm font-medium text-ink">' + elEsc(ev.title) + '</span>'
                    + '<span class="shrink-0 text-xs text-muted">' + elEsc(ev.url) + '</span>';
                btn.addEventListener('click', function () {
                    elModal.dispatchEvent(new CustomEvent('event-link-picked', { detail: ev }));
                    elClose();
                });
                var li = document.createElement('li'); li.appendChild(btn); elList.appendChild(li);
            });
        }

        function elOpen() { elModal.classList.remove('hidden'); elModal.classList.add('flex'); elSearch.value = ''; elRender(''); elSearch.focus(); }
        function elClose() { elModal.classList.add('hidden'); elModal.classList.remove('flex'); }

        elModal.querySelector('[data-event-link-modal-close]').addEventListener('click', elClose);
        elModal.addEventListener('click', function (e) { if (e.target === elModal) elClose(); });
        elModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') elClose(); });
        elSearch.addEventListener('input', function () { elRender(elSearch.value); });
        window['__elOpen_{{ $editorId }}'] = elOpen;
    })();

    // ── Wstaw kotwicę ────────────────────────────────────────────────────────
    (function () {
        var aiModal  = document.getElementById('{{ $editorId }}-anchor-insert-modal');
        var aiInput  = document.getElementById('{{ $editorId }}-anchor-insert-id');
        var aiSubmit = document.getElementById('{{ $editorId }}-anchor-insert-submit');

        function aiOpen() { aiModal.classList.remove('hidden'); aiModal.classList.add('flex'); aiInput.value = ''; aiInput.focus(); }
        function aiClose() { aiModal.classList.add('hidden'); aiModal.classList.remove('flex'); }

        aiSubmit.addEventListener('click', function () {
            var id = aiInput.value.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
            if (!id) { aiInput.focus(); return; }
            aiInput.value = id;
            aiModal.dispatchEvent(new CustomEvent('anchor-insert', { detail: { id: id } }));
            aiClose();
        });
        aiInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); aiSubmit.click(); } });
        aiModal.querySelectorAll('[data-anchor-insert-close]').forEach(function (b) { b.addEventListener('click', aiClose); });
        aiModal.addEventListener('click', function (e) { if (e.target === aiModal) aiClose(); });
        aiModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') aiClose(); });
        window['__anchorInsertOpen_{{ $editorId }}'] = aiOpen;
    })();

    // ── Link do kotwicy ──────────────────────────────────────────────────────
    (function () {
        var alModal    = document.getElementById('{{ $editorId }}-anchor-link-modal');
        var alFound    = document.getElementById('{{ $editorId }}-anchor-link-found');
        var alNone     = document.getElementById('{{ $editorId }}-anchor-link-none');
        var alIdInput  = document.getElementById('{{ $editorId }}-anchor-link-id');
        var alTxtInput = document.getElementById('{{ $editorId }}-anchor-link-text');
        var alSubmit   = document.getElementById('{{ $editorId }}-anchor-link-submit');

        function scanAnchors() {
            var getContent = window['__getContent_{{ $editorId }}'];
            if (!getContent) { alNone.classList.remove('hidden'); alFound.innerHTML = ''; return; }
            var doc = new DOMParser().parseFromString(getContent(), 'text/html');
            var ids = [];
            doc.querySelectorAll('[id]').forEach(function (el) {
                if (el.id) ids.push({ id: el.id, label: (el.textContent || '').trim().slice(0, 60) || '#' + el.id });
            });
            alFound.innerHTML = '';
            alNone.classList.toggle('hidden', ids.length > 0);
            ids.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button'; btn.setAttribute('role', 'option');
                btn.className = 'flex w-full items-baseline gap-3 px-3 py-2 text-left hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none';
                btn.innerHTML = '<code class="shrink-0 font-mono text-xs text-brand">#' + item.id + '</code>'
                    + '<span class="flex-1 truncate text-xs text-muted">' + item.label + '</span>';
                btn.addEventListener('click', function () {
                    alIdInput.value = item.id;
                    alTxtInput.value = item.label !== '#' + item.id ? item.label : '';
                    alTxtInput.focus();
                });
                var li = document.createElement('li'); li.appendChild(btn); alFound.appendChild(li);
            });
        }

        function alOpen() {
            alModal.classList.remove('hidden'); alModal.classList.add('flex');
            alIdInput.value = ''; alTxtInput.value = '';
            scanAnchors();
            alIdInput.focus();
        }
        function alClose() { alModal.classList.add('hidden'); alModal.classList.remove('flex'); }

        alSubmit.addEventListener('click', function () {
            var id = alIdInput.value.trim();
            var text = alTxtInput.value.trim();
            if (!id || !text) { (id ? alTxtInput : alIdInput).focus(); return; }
            alModal.dispatchEvent(new CustomEvent('anchor-link-insert', { detail: { id: id, text: text } }));
            alClose();
        });
        alModal.querySelectorAll('[data-anchor-link-close]').forEach(function (b) { b.addEventListener('click', alClose); });
        alModal.addEventListener('click', function (e) { if (e.target === alModal) alClose(); });
        alModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') alClose(); });
        window['__anchorLinkOpen_{{ $editorId }}'] = alOpen;
    })();

    // ── Statystyki treści ─────────────────────────────────────────────────────
    (function () {
        var statsEl = document.getElementById('{{ $editorId }}-stats');
        var debounce;

        function compute(html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var words = ((doc.body.textContent || '').trim().match(/\S+/g) || []).length;
            var mins = Math.max(1, Math.round(words / 200));
            var links = doc.querySelectorAll('a[href]').length;
            var images = doc.querySelectorAll('img').length;
            var parts = ['Słowa: ' + words, 'Czas czytania: ~' + mins + ' min'];
            if (links) parts.push('Linki: ' + links);
            if (images) parts.push('Obrazy: ' + images);
            statsEl.textContent = parts.join(' · ');
        }

        window['__updateStats_{{ $editorId }}'] = function (html) {
            clearTimeout(debounce);
            debounce = setTimeout(function () { compute(html); }, 300);
        };
    })();
</script>

@if ($useCkEditor)
    <style>
        #{{ $editorId }}-ck-wrapper .ck-editor__editable {
            min-height: 640px;
        }
    </style>
    <script>
        (function () {
            function initEditor() {
                var textarea = document.getElementById('{{ $editorId }}');
                var modal = document.getElementById('{{ $editorId }}-media-modal');

                var ckCsrfToken = document.querySelector('input[name=_token]')?.value || '';
                ClassicEditor.create(textarea, {
                    simpleUpload: {
                        uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}',
                        headers: { 'X-CSRF-TOKEN': ckCsrfToken },
                    },
                }).then(function (editor) {
                    var form = textarea.closest('form');
                    editor.ui.view.element.id = '{{ $editorId }}-ck-wrapper';

                    if (form) {
                        form.addEventListener('submit', function () {
                            textarea.value = editor.getData();
                        });
                    }

                    document.getElementById('{{ $editorId }}-columns').addEventListener('click', function () {
                        var viewFragment = editor.data.processor.toView({!! json_encode($columnsHtmlForCk) !!});
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-cta').addEventListener('click', function () {
                        var viewFragment = editor.data.processor.toView({!! json_encode($ctaHtml) !!});
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-box').addEventListener('click', function () {
                        var viewFragment = editor.data.processor.toView({!! json_encode($boxHtmlForCk) !!});
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    var ckSnippets = {!! json_encode($ckEditorSnippets) !!};
                    document.getElementById('{{ $editorId }}-toolbar').querySelectorAll('[data-insert-key]').forEach(function (b) {
                        b.addEventListener('click', function () {
                            var vf = editor.data.processor.toView(ckSnippets[b.dataset.insertKey]);
                            editor.model.insertContent(editor.data.toModel(vf));
                            editor.editing.view.focus();
                        });
                    });

                    window['__setContent_{{ $editorId }}'] = function (html) { editor.setData(html || ''); editor.editing.view.focus(); };
                    window['__clearFormat_{{ $editorId }}'] = function () {
                        if (editor.commands.get('removeFormat')) {
                            editor.execute('removeFormat');
                            editor.editing.view.focus();
                        }
                    };

                    document.getElementById('{{ $editorId }}-ext-link').addEventListener('click', function () {
                        var url = window.prompt('Adres URL (link zewnętrzny):', 'https://');
                        if (!url) return;
                        var text = window.prompt('Tekst linku:', url) || url;
                        var vf = editor.data.processor.toView('<a href="' + url + '" target="_blank" rel="noopener noreferrer external">' + text + '</a>');
                        editor.model.insertContent(editor.data.toModel(vf));
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-page-modal').addEventListener('page-link-picked', function (event) {
                        var p = event.detail;
                        var html = '<p><a href="' + p.url + '">' + p.title + '</a></p>';
                        var viewFragment = editor.data.processor.toView(html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-news-modal').addEventListener('news-link-picked', function (event) {
                        var n = event.detail;
                        var html = '<p><a href="' + n.url + '">' + n.title + '</a></p>';
                        var viewFragment = editor.data.processor.toView(html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-event-link-modal').addEventListener('event-link-picked', function (event) {
                        var ev = event.detail;
                        var html = '<p><a href="' + ev.url + '">' + ev.title + '</a></p>';
                        var viewFragment = editor.data.processor.toView(html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-anchor-insert-modal').addEventListener('anchor-insert', function (event) {
                        var html = '<span id="' + event.detail.id + '" class="page-anchor" aria-hidden="true">​</span>';
                        var viewFragment = editor.data.processor.toView(html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-anchor-link-modal').addEventListener('anchor-link-insert', function (event) {
                        var d = event.detail;
                        var html = '<a href="#' + d.id + '">' + d.text + '</a>';
                        var viewFragment = editor.data.processor.toView(html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    window['__getContent_{{ $editorId }}'] = function () { return editor.getData(); };

                    editor.model.document.on('change:data', function () {
                        window['__updateStats_{{ $editorId }}']?.(editor.getData());
                    });
                    window['__updateStats_{{ $editorId }}']?.(editor.getData());

                    var scheduleCtaSelect = document.getElementById('{{ $editorId }}-schedule-cta');
                    if (scheduleCtaSelect) {
                        scheduleCtaSelect.addEventListener('change', function () {
                            if (!this.value) return;
                            var title = this.selectedOptions[0].dataset.title;
                            var html = '<p><a href="' + this.value + '" class="cta-button">' + title + '</a></p>';
                            var viewFragment = editor.data.processor.toView(html);
                            var modelFragment = editor.data.toModel(viewFragment);
                            editor.model.insertContent(modelFragment);
                            editor.editing.view.focus();
                            this.selectedIndex = 0;
                        });
                    }

                    var eventBoxSelect = document.getElementById('{{ $editorId }}-event-box');
                    if (eventBoxSelect) {
                        var eventBoxesCk = {!! json_encode($eventBoxOptions->pluck('htmlCk', 'slug')) !!};
                        eventBoxSelect.addEventListener('change', function () {
                            if (!this.value || !eventBoxesCk[this.value]) return;
                            var viewFragment = editor.data.processor.toView(eventBoxesCk[this.value]);
                            var modelFragment = editor.data.toModel(viewFragment);
                            editor.model.insertContent(modelFragment);
                            editor.editing.view.focus();
                            this.selectedIndex = 0;
                        });
                    }

                    modal.addEventListener('media-picked', function (event) {
                        var image = event.detail;
                        var html = '<img src="' + image.url + '" alt="' + image.alt.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">';
                        var viewFragment = editor.data.processor.toView(html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-attachment-modal').addEventListener('attachment-picked', function (event) {
                        var a = event.detail;
                        var meta = [a.extension, a.size].filter(Boolean).join(', ');
                        var label = a.label + (meta ? ' (' + meta + ')' : '');
                        var html = '<p><a href="' + a.url + '" download class="cta-button">'
                            + '<i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Pobierz: ' + label + '</a></p>';
                        var viewFragment = editor.data.processor.toView(html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-gallery-modal').addEventListener('gallery-picked', function (event) {
                        var viewFragment = editor.data.processor.toView(event.detail.html);
                        var modelFragment = editor.data.toModel(viewFragment);
                        editor.model.insertContent(modelFragment);
                        editor.editing.view.focus();
                    });

                    document.getElementById('{{ $editorId }}-gallery').addEventListener('click', function () {
                        window['__gmOpen_{{ $editorId }}']?.();
                    });
                });
            }

            if (window.ClassicEditor) {
                initEditor();
                return;
            }

            // Several editors can share one page (e.g. the settings screen).
            // Inject the CDN library only once and queue every instance's init
            // to run on load, instead of each editor re-injecting the bundle.
            window.__ckInitQueue = window.__ckInitQueue || [];
            window.__ckInitQueue.push(initEditor);

            if (!window.__ckLoading) {
                window.__ckLoading = true;
                const script = document.createElement('script');
                script.src = 'https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js';
                script.onload = function () {
                    (window.__ckInitQueue || []).forEach(function (fn) { fn(); });
                    window.__ckInitQueue = [];
                };
                document.head.appendChild(script);
            }
        })();
    </script>
@else
    <script>
        (function () {
            function initEditor() {
                var modal = document.getElementById('{{ $editorId }}-media-modal');

                var snippets = {!! json_encode($editorSnippets) !!};
                var ctaHtml = {!! json_encode($ctaHtml) !!};
                var boxHtml = {!! json_encode($boxHtml) !!};
                var columnsHtml = {!! json_encode($columnsHtml) !!};
                var pageLinks = {!! json_encode($pages->map(fn ($p) => ['url' => '/'.$p->slug, 'title' => $p->title])->values()) !!};
                var scheduleLinks = {!! json_encode($schedulePages->map(fn ($p) => ['url' => '/'.$p->slug, 'title' => $p->title])->values()) !!};
                var eventBoxes = {!! json_encode($eventBoxOptions->map(fn ($o) => ['title' => $o['title'], 'html' => $o['html']])->values()) !!};
                var newsLinks = {!! json_encode($newsForPicker->map(fn ($n) => ['url' => route('news.show', $n), 'title' => $n->title])->values()) !!};
                var eventLinks = {!! json_encode($eventsForBox->map(fn ($e) => ['url' => '/wydarzenia/'.$e->slug, 'title' => $e->title])->values()) !!};

                function checkA11y(html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var problems = [];
                    doc.querySelectorAll('img').forEach(function (img) {
                        if (!img.hasAttribute('alt')) problems.push('Obraz bez atrybutu alt (dodaj opis alternatywny).');
                    });
                    var levels = [];
                    doc.querySelectorAll('h1,h2,h3,h4,h5,h6').forEach(function (h) { levels.push(parseInt(h.tagName[1], 10)); });
                    for (var i = 1; i < levels.length; i++) {
                        if (levels[i] - levels[i - 1] > 1) { problems.push('Przeskok w kolejności nagłówków (H' + levels[i - 1] + ' → H' + levels[i] + ').'); break; }
                    }
                    var generic = ['kliknij tutaj', 'tutaj', 'klik', 'czytaj więcej', 'więcej', 'link', 'zobacz'];
                    doc.querySelectorAll('a').forEach(function (a) {
                        var t = (a.textContent || '').trim().toLowerCase();
                        if (t === '') problems.push('Link bez tekstu (czytnik ekranu przeczyta sam adres).');
                        else if (generic.indexOf(t) !== -1) problems.push('Nieopisowy tekst linku: „' + a.textContent.trim() + '".');
                    });
                    if (!problems.length) {
                        return '<p style="color:#15803d;font-weight:700">✓ Nie wykryto problemów z dostępnością treści.</p>';
                    }
                    var seen = {}, list = '';
                    problems.forEach(function (p) { if (!seen[p]) { seen[p] = 1; list += '<li>' + p + '</li>'; } });
                    return '<p style="font-weight:700;margin-bottom:.5rem">Wykryto potencjalne problemy z dostępnością:</p>'
                        + '<ul style="margin-left:1.25rem;list-style:disc">' + list + '</ul>';
                }

                tinymce.init({
                    selector: '#{{ $editorId }}',
                    license_key: 'gpl',
                    height: 700,
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    convert_urls: false,
                    plugins: 'advlist autolink lists link anchor image charmap preview searchreplace visualblocks code fullscreen media table help wordcount accordion emoticons autosave quickbars',
                    block_formats: 'Akapit=p; Nagłówek 2=h2; Nagłówek 3=h3; Nagłówek 4=h4',
                    toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link table media accordion | insertmenu linkmenu importmenu | clearformat | charmap emoticons | searchreplace visualblocks fullscreen preview | a11ycheck help | code{{ $historyJsonUrl ? " | historyrevisions" : "" }}',
                    toolbar_mode: 'wrap',
                    statusbar: true,
                    paste_data_images: true,
                    automatic_uploads: true,
                    images_upload_handler: function (blobInfo, progress) {
                        return new Promise(function (resolve, reject) {
                            var formData = new FormData();
                            formData.append('file', blobInfo.blob(), blobInfo.filename());
                            formData.append('_token', document.querySelector('input[name=_token]').value);
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', '{{ route('admin.multimedia.upload-ajax') }}');
                            xhr.upload.onprogress = function (e) {
                                if (e.lengthComputable) progress(e.loaded / e.total * 100);
                            };
                            xhr.onload = function () {
                                if (xhr.status >= 200 && xhr.status < 300) {
                                    resolve(JSON.parse(xhr.responseText).location);
                                } else {
                                    reject({ message: 'Błąd uploadu (' + xhr.status + ')', remove: true });
                                }
                            };
                            xhr.onerror = function () { reject({ message: 'Błąd sieci', remove: true }); };
                            xhr.send(formData);
                        });
                    },
                    elementpath: false,
                    placeholder: 'Tu wpisz tekst…',
                    // Autozapis roboczy w przeglądarce — po awarii można przywrócić wersję.
                    autosave_interval: '20s',
                    autosave_restore_when_empty: false,
                    autosave_retention: '1440m',
                    // Szybki pasek zaznaczenia; bez natrętnego paska wstawiania.
                    quickbars_insert_toolbar: false,
                    quickbars_selection_toolbar: 'bold italic link',
                    // Czyszczenie wklejeń (np. z Worda): usuwamy style inline i śmieciowe
                    // klasy/znaczniki „mso", żeby treść trzymała spójny wygląd serwisu.
                    paste_merge_formats: true,
                    paste_postprocess: function (editor, args) {
                        args.node.querySelectorAll('[style]').forEach(function (el) { el.removeAttribute('style'); });
                        args.node.querySelectorAll('[class]').forEach(function (el) {
                            if (/(^|\s)mso/i.test(el.className) || /Mso/.test(el.className)) el.removeAttribute('class');
                        });
                        args.node.querySelectorAll('o\\:p').forEach(function (el) { el.remove(); });
                    },
                    setup: function (editor) {
                        // Wyczyść formatowanie zaznaczenia: inline + blokowe (nagłówki → paragraf).
                        editor.ui.registry.addButton('clearformat', {
                            icon: 'remove-formatting',
                            tooltip: 'Wyczyść formatowanie zaznaczenia (usuwa pogrubienie, kursywę, nagłówki…)',
                            onAction: function () {
                                editor.execCommand('FormatBlock', false, 'p');
                                editor.execCommand('RemoveFormat');
                            },
                        });

                        @if ($historyJsonUrl)
                        // Historia wersji treści.
                        editor.ui.registry.addButton('historyrevisions', {
                            icon: 'restore-draft',
                            tooltip: 'Historia wersji treści',
                            onAction: function () {
                                window['__historyOpen_{{ $editorId }}']?.();
                            },
                        });
                        @endif

                        editor.ui.registry.addMenuButton('insertmenu', {
                            text: 'Wstaw', icon: 'plus', tooltip: 'Wstaw element',
                            fetch: function (cb) {
                                var items = [
                                    { type: 'menuitem', text: 'Obraz z biblioteki', icon: 'image', onAction: function () { document.getElementById('{{ $editorId }}-media').click(); } },
                                    { type: 'menuitem', text: 'Galeria zdjęć', icon: 'gallery', onAction: function () { window['__gmOpen_{{ $editorId }}']?.(); } },
                                    { type: 'menuitem', text: 'Przycisk CTA', onAction: function () { editor.insertContent(ctaHtml); } },
                                    { type: 'menuitem', text: 'Przycisk czerwony', onAction: function () { editor.insertContent(snippets.red); } },
                                    { type: 'menuitem', text: 'Przycisk zielony', onAction: function () { editor.insertContent(snippets.green); } },
                                    { type: 'menuitem', text: 'Więcej informacji w BIP', onAction: function () { editor.insertContent(snippets.bip); } },
                                    { type: 'menuitem', text: 'Tekst z ramką', onAction: function () { editor.insertContent(boxHtml); } },
                                    { type: 'menuitem', text: 'Notatka / ostrzeżenie', onAction: function () { editor.insertContent(snippets.note); } },
                                    { type: 'menuitem', text: 'Ważne info', icon: 'info', onAction: function () { editor.insertContent(snippets.important); } },
                                    { type: 'menuitem', text: 'Importuj plik DOCX…', icon: 'upload', onAction: function () { document.getElementById('{{ $editorId }}-docx-input').click(); } },
                                    { type: 'menuitem', text: 'Sekcja akcentu (lewo)', onAction: function () { editor.insertContent(snippets.accentLeft); } },
                                    { type: 'menuitem', text: 'Sekcja akcentu (prawo)', onAction: function () { editor.insertContent(snippets.accentRight); } },
                                    { type: 'menuitem', text: 'Układ 2 kolumn', onAction: function () { editor.insertContent(columnsHtml); } },
                                    { type: 'menuitem', text: 'Linia pozioma', onAction: function () { editor.insertContent('<hr>'); } },
                                    { type: 'menuitem', text: 'Tabela dostępna (WCAG)', icon: 'table', onAction: function () { editor.insertContent(snippets.table); } },
                                    { type: 'menuitem', text: 'Wstaw kotwicę', icon: 'anchor', onAction: function () { window['__anchorInsertOpen_{{ $editorId }}']?.(); } },
                                ];
                                if (eventBoxes.length) {
                                    items.push({ type: 'nestedmenuitem', text: 'Ramka z wydarzeniem', getSubmenuItems: function () {
                                        return eventBoxes.map(function (ev) {
                                            return { type: 'menuitem', text: ev.title, onAction: function () { editor.insertContent(ev.html); } };
                                        });
                                    } });
                                }
                                cb(items);
                            },
                        });

                        editor.ui.registry.addMenuButton('linkmenu', {
                            text: 'Wstaw link', icon: 'link', tooltip: 'Wstaw link',
                            fetch: function (cb) {
                                var items = [{ type: 'menuitem', text: 'Link zewnętrzny (nowa karta)', onAction: function () {
                                    var url = window.prompt('Adres URL (link zewnętrzny):', 'https://');
                                    if (!url) return;
                                    var text = window.prompt('Tekst linku:', url) || url;
                                    editor.insertContent('<a href="' + url + '" target="_blank" rel="noopener noreferrer external">' + text + '</a>');
                                } }];
                                if (pageLinks.length) {
                                    items.push({ type: 'menuitem', text: 'Link do strony — wybierz z listy…', onAction: function () {
                                        window['__pmOpen_{{ $editorId }}']?.();
                                    }});
                                }
                                if (newsLinks.length) {
                                    items.push({ type: 'menuitem', text: 'Link do newsa — wybierz z listy…', onAction: function () {
                                        window['__nmOpen_{{ $editorId }}']?.();
                                    }});
                                }
                                if (eventLinks.length) {
                                    items.push({ type: 'menuitem', text: 'Link do wydarzenia — wybierz z listy…', onAction: function () {
                                        window['__elOpen_{{ $editorId }}']?.();
                                    }});
                                }
                                items.push({ type: 'menuitem', text: 'Plik do pobrania — wybierz z listy…', icon: 'download', onAction: function () {
                                    window['__afOpen_{{ $editorId }}']?.();
                                }});
                                items.push({ type: 'menuitem', text: 'Link do kotwicy…', icon: 'anchor', onAction: function () {
                                    window['__anchorLinkOpen_{{ $editorId }}']?.();
                                }});
                                if (scheduleLinks.length) {
                                    items.push({ type: 'nestedmenuitem', text: 'Przycisk CTA do harmonogramu', getSubmenuItems: function () {
                                        return scheduleLinks.map(function (p) {
                                            return { type: 'menuitem', text: p.title, onAction: function () { editor.insertContent('<p><a href="' + p.url + '" class="cta-button">' + p.title + '</a></p>'); } };
                                        });
                                    } });
                                }
                                cb(items);
                            },
                        });

                        editor.ui.registry.addMenuButton('importmenu', {
                            text: 'Import', icon: 'upload', tooltip: 'Importuj treść z pliku',
                            fetch: function (cb) {
                                cb([
                                    { type: 'menuitem', text: 'Importuj DOCX (Word)…', icon: 'upload', onAction: function () {
                                        document.getElementById('{{ $editorId }}-docx-input').click();
                                    }},
                                    { type: 'menuitem', text: 'Importuj TXT…', icon: 'sourcecode', onAction: function () {
                                        document.getElementById('{{ $editorId }}-txt-input').click();
                                    }},
                                ]);
                            },
                        });

                        editor.ui.registry.addButton('a11ycheck', {
                            text: 'Dostępność', tooltip: 'Sprawdź dostępność treści',
                            onAction: function () {
                                editor.windowManager.open({
                                    title: 'Kontrola dostępności',
                                    body: { type: 'panel', items: [{ type: 'htmlpanel', html: checkA11y(editor.getContent()) }] },
                                    buttons: [{ type: 'cancel', text: 'Zamknij', buttonType: 'primary' }],
                                });
                            },
                        });

                        editor.on('keyup change', function () {
                            window['__updateStats_{{ $editorId }}']?.(editor.getContent());
                        });

                        editor.on('init', function () {
                            window['__updateStats_{{ $editorId }}']?.(editor.getContent());
                            modal.addEventListener('media-picked', function (event) {
                                var image = event.detail;
                                // Wymuszenie alt: przy wstawianiu obrazu pytamy o opis alternatywny.
                                var alt = window.prompt('Opis alternatywny (alt) — opisz obraz dla osób niewidomych.\nZostaw puste tylko dla obrazu czysto dekoracyjnego:', image.alt || '');
                                if (alt === null) return; // anulowano — nie wstawiaj
                                var safeAlt = alt.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                                editor.insertContent('<img src="' + image.url + '" alt="' + safeAlt + '">');
                            });
                            document.getElementById('{{ $editorId }}-page-modal').addEventListener('page-link-picked', function (event) {
                                var p = event.detail;
                                editor.insertContent('<a href="' + p.url + '">' + p.title + '</a>');
                            });
                            document.getElementById('{{ $editorId }}-news-modal').addEventListener('news-link-picked', function (event) {
                                var n = event.detail;
                                editor.insertContent('<a href="' + n.url + '">' + n.title + '</a>');
                            });
                            document.getElementById('{{ $editorId }}-event-link-modal').addEventListener('event-link-picked', function (event) {
                                var ev = event.detail;
                                editor.insertContent('<a href="' + ev.url + '">' + ev.title + '</a>');
                            });
                            document.getElementById('{{ $editorId }}-attachment-modal').addEventListener('attachment-picked', function (event) {
                                var a = event.detail;
                                var meta = [a.extension, a.size].filter(Boolean).join(', ');
                                var label = a.label + (meta ? ' (' + meta + ')' : '');
                                editor.insertContent('<p><a href="' + a.url + '" download class="cta-button"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Pobierz: ' + label + '</a></p>');
                            });
                            document.getElementById('{{ $editorId }}-gallery-modal').addEventListener('gallery-picked', function (event) {
                                editor.insertContent(event.detail.html);
                            });
                            document.getElementById('{{ $editorId }}-anchor-insert-modal').addEventListener('anchor-insert', function (event) {
                                editor.insertContent('<a id="' + event.detail.id + '" class="page-anchor" aria-hidden="true"></a>');
                            });
                            document.getElementById('{{ $editorId }}-anchor-link-modal').addEventListener('anchor-link-insert', function (event) {
                                var d = event.detail;
                                editor.insertContent('<a href="#' + d.id + '">' + d.text + '</a>');
                            });
                            window['__getContent_{{ $editorId }}'] = function () { return editor.getContent(); };
                            window['__setContent_{{ $editorId }}'] = function (html) { editor.setContent(html || ''); editor.focus(); };
                            window['__clearFormat_{{ $editorId }}'] = function () {
                                editor.execCommand('FormatBlock', false, 'p');
                                editor.execCommand('RemoveFormat');
                            };
                        });
                    },
                });
            }

            if (window.tinymce) {
                initEditor();
                return;
            }

            // Several editors can share one page (e.g. the settings screen).
            // Inject the CDN library only once and queue every instance's init
            // to run on load, instead of each editor re-injecting/re-executing
            // the whole bundle (which raced and left some fields uninitialised).
            window.__tinymceInitQueue = window.__tinymceInitQueue || [];
            window.__tinymceInitQueue.push(initEditor);

            if (!window.__tinymceLoading) {
                window.__tinymceLoading = true;
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
                script.referrerPolicy = 'origin';
                script.onload = function () {
                    (window.__tinymceInitQueue || []).forEach(function (fn) { fn(); });
                    window.__tinymceInitQueue = [];
                };
                document.head.appendChild(script);
            }
        })();
    </script>
@endif

@if ($historyJsonUrl)
<script>
    (function () {
        var hvModal    = document.getElementById('{{ $editorId }}-history-modal');
        var hvList     = document.getElementById('{{ $editorId }}-history-list');
        var hvLoading  = document.getElementById('{{ $editorId }}-history-loading');
        var hvEmpty    = document.getElementById('{{ $editorId }}-history-empty');
        var hvHint     = document.getElementById('{{ $editorId }}-history-hint');
        var hvPreviewWrap = document.getElementById('{{ $editorId }}-history-preview-wrap');
        var hvPreview  = document.getElementById('{{ $editorId }}-history-preview');
        var hvFooter   = document.getElementById('{{ $editorId }}-history-footer');
        var hvLoad     = document.getElementById('{{ $editorId }}-history-load');
        var hvClose    = document.getElementById('{{ $editorId }}-history-close');
        var revisions  = null;
        var selected   = null;

        function hvEsc(t) {
            return (t || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function hvRender(list) {
            hvLoading.classList.add('hidden');
            if (!list.length) { hvEmpty.classList.remove('hidden'); return; }
            var itemClass = 'block w-full rounded px-3 py-2 text-left text-xs hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none';
            list.forEach(function (rev, i) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('role', 'option');
                btn.className = itemClass;
                btn.innerHTML = '<span class="block font-bold text-ink">' + hvEsc(rev.label) + '</span>'
                    + '<span class="block text-muted">' + hvEsc(rev.ago) + ' · ' + hvEsc(rev.user) + '</span>'
                    + '<span class="block text-muted">' + rev.word_count + ' słów</span>';
                btn.addEventListener('click', function () {
                    hvList.querySelectorAll('[role=option]').forEach(function (b) {
                        b.classList.remove('bg-brand-light', 'ring-1', 'ring-brand');
                    });
                    btn.classList.add('bg-brand-light', 'ring-1', 'ring-brand');
                    selected = rev;
                    hvHint.classList.add('hidden');
                    hvPreviewWrap.classList.remove('hidden');
                    hvFooter.classList.remove('hidden');
                    hvPreview.innerHTML = rev.content || '<em class="text-muted">Brak treści.</em>';
                });
                var li = document.createElement('li');
                li.appendChild(btn);
                hvList.appendChild(li);
            });
        }

        function hvOpen() {
            hvModal.classList.remove('hidden');
            hvModal.classList.add('flex');
            if (revisions !== null) return;
            fetch({{ json_encode($historyJsonUrl) }})
                .then(function (r) { return r.json(); })
                .then(function (data) { revisions = data; hvRender(data); })
                .catch(function () {
                    hvLoading.textContent = 'Nie udało się pobrać historii.';
                });
        }

        function hvClose() {
            hvModal.classList.add('hidden');
            hvModal.classList.remove('flex');
        }

        hvLoad.addEventListener('click', function () {
            if (!selected) return;
            if (!confirm('Zastąpić aktualną treść edytora wybraną wersją?')) return;
            window['__setContent_{{ $editorId }}']?.(selected.content);
            hvClose();
        });

        hvClose.addEventListener('click', hvClose);
        hvModal.addEventListener('click', function (e) { if (e.target === hvModal) hvClose(); });
        hvModal.addEventListener('keydown', function (e) { if (e.key === 'Escape') hvClose(); });

        window['__historyOpen_{{ $editorId }}'] = hvOpen;
    })();
</script>
@endif

<script>
(function () {
    var textarea = document.getElementById('{{ $editorId }}');
    if (!textarea) return;
    var form = textarea.closest('form');
    if (!form) return;
    if (form.dataset.headingCheckAttached) return;
    form.dataset.headingCheckAttached = '1';

    form.addEventListener('submit', async function (e) {
        var getContent = window['__getContent_{{ $editorId }}'];
        if (!getContent) return;
        var html = getContent();
        if (!html) return;

        var doc = new DOMParser().parseFromString(html, 'text/html');
        var headings = Array.from(doc.querySelectorAll('h1,h2,h3,h4,h5,h6'));
        var problems = [];

        var levels = headings.map(function (h) { return parseInt(h.tagName[1], 10); });

        for (var i = 1; i < levels.length; i++) {
            if (levels[i] - levels[i - 1] > 1) {
                problems.push('Przeskok w nagłówkach: H' + levels[i - 1] + ' → H' + levels[i] + '.');
                break;
            }
        }

        var h1count = levels.filter(function (l) { return l === 1; }).length;
        if (h1count > 1) {
            problems.push('Wiele nagłówków H1 (' + h1count + ') — powinna być co najwyżej jedna H1.');
        }

        headings.forEach(function (h) {
            if (!h.textContent.trim()) {
                problems.push('Pusty nagłówek H' + h.tagName[1] + '.');
            }
        });

        if (!problems.length) return;

        var message = 'Wykryto problemy z nagłówkami:\n'
            + problems.map(function (p) { return '• ' + p; }).join('\n')
            + '\n\nZapisać mimo to?';

        var ok = await Alpine.store('confirm').ask(message);
        if (!ok) {
            e.preventDefault();
        }
    });
})();
</script>

<script>
(function () {
    var docxInput  = document.getElementById('{{ $editorId }}-docx-input');
    var docxBtn    = document.getElementById('{{ $editorId }}-docx-btn');
    var importUrl  = {{ json_encode($docxImportUrl) }};
    var csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function triggerPick() { if (docxInput) docxInput.click(); }
    if (docxBtn) docxBtn.addEventListener('click', triggerPick);

    if (!docxInput) return;

    docxInput.addEventListener('change', function () {
        var file = docxInput.files?.[0];
        if (!file) return;
        docxInput.value = '';
        var fd = new FormData();
        fd.append('_token', csrfToken);
        fd.append('docx', file);

        fetch(importUrl, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { alert('Błąd: ' + data.error); return; }
                var setContent = window['__setContent_{{ $editorId }}'];
                var getContent = window['__getContent_{{ $editorId }}'];
                if (!setContent) { alert('Edytor nie jest jeszcze gotowy.'); return; }
                var current = getContent?.() || '';
                setContent(current ? current + '\n' + data.html : data.html);
            })
            .catch(function () { alert('Nie udało się zaimportować pliku DOCX.'); });
    });
})();
</script>

<script>
(function () {
    var txtInput = document.getElementById('{{ $editorId }}-txt-input');
    if (!txtInput) return;

    txtInput.addEventListener('change', function () {
        var file = txtInput.files?.[0];
        if (!file) return;
        txtInput.value = '';

        var reader = new FileReader();
        reader.onload = function (e) {
            var text = e.target.result || '';
            // Każdy niepusty akapit (oddzielony pustą linią lub nową linią) → <p>
            var html = text
                .split(/\r?\n/)
                .map(function (line) { return line.trim(); })
                .reduce(function (acc, line) {
                    if (line === '') {
                        if (acc.current) { acc.paragraphs.push(acc.current); acc.current = ''; }
                    } else {
                        acc.current = acc.current ? acc.current + ' ' + line : line;
                    }
                    return acc;
                }, { paragraphs: [], current: '' });
            if (html.current) html.paragraphs.push(html.current);

            var result = html.paragraphs
                .map(function (p) {
                    // Escapowanie HTML
                    return '<p>' + p.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</p>';
                })
                .join('\n');

            var setContent = window['__setContent_{{ $editorId }}'];
            var getContent = window['__getContent_{{ $editorId }}'];
            if (!setContent) { alert('Edytor nie jest jeszcze gotowy.'); return; }
            var current = getContent?.() || '';
            setContent(current ? current + '\n' + result : result);
        };
        reader.readAsText(file, 'UTF-8');
    });
})();
</script>
