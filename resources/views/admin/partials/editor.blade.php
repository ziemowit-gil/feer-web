@php
    $editorId = 'editor-'.$name;
    $useCkEditor = ($siteSettings->content_editor ?? 'tinymce') === 'ckeditor';
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

    // Snippety wstawiane jednym, wspólnym mechanizmem (data-insert-key).
    $editorSnippets = [
        'red' => '<p><a href="#" class="cta-button cta-red">Przycisk</a></p><p>&nbsp;</p>',
        'green' => '<p><a href="#" class="cta-button cta-green">Przycisk</a></p><p>&nbsp;</p>',
        'accentLeft' => '<div class="accent-section accent-left"><p>Treść w kolorowej sekcji&hellip;</p></div><p>&nbsp;</p>',
        'accentRight' => '<div class="accent-section accent-right"><p>Treść w kolorowej sekcji&hellip;</p></div><p>&nbsp;</p>',
    ];

    $pages = \App\Models\Page::where('is_published', true)->orderBy('title')->get();
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
            <button type="button" id="{{ $editorId }}-cta" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-square w-4 text-center text-brand" aria-hidden="true"></i> Przycisk CTA</button>
            <button type="button" data-insert-key="red" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-square w-4 text-center" style="color:#c81e1e" aria-hidden="true"></i> Przycisk czerwony</button>
            <button type="button" data-insert-key="green" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-square w-4 text-center" style="color:#15803d" aria-hidden="true"></i> Przycisk zielony</button>
            <button type="button" id="{{ $editorId }}-box" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-vector-square w-4 text-center" aria-hidden="true"></i> Tekst z ramką</button>
            @if ($useCkEditor)
                <button type="button" id="{{ $editorId }}-columns" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-table-columns w-4 text-center" aria-hidden="true"></i> Układ 2 kolumn</button>
            @endif
            <button type="button" data-insert-key="accentLeft" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-align-left w-4 text-center" aria-hidden="true"></i> Sekcja akcentu (lewo)</button>
            <button type="button" data-insert-key="accentRight" @click="open = false" class="{{ $mi }}"><i class="fa-solid fa-align-right w-4 text-center" aria-hidden="true"></i> Sekcja akcentu (prawo)</button>
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
                <label for="{{ $editorId }}-page-link" class="mt-2 block px-3 pb-1 text-[0.65rem] font-bold uppercase tracking-wide text-muted">Link do strony</label>
                <select id="{{ $editorId }}-page-link" @change="open = false" class="w-full rounded border-gray-300 px-2 py-1.5 text-xs font-bold text-ink focus:border-brand focus:ring-brand">
                    <option value="">— wybierz stronę —</option>
                    @foreach ($pages as $page)
                        <option value="/{{ $page->slug }}" data-title="{{ $page->title }}">{{ $page->title }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>
</div>

<textarea name="{{ $name }}" id="{{ $editorId }}" rows="14"
    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $value }}</textarea>

<div id="{{ $editorId }}-media-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-label="Wybierz obraz">
    <div class="flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold">Wybierz obraz</h2>
            <button type="button" data-media-close class="text-muted hover:text-red-600" aria-label="Zamknij okno wyboru obrazu"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="mb-4 flex w-fit gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 text-sm font-bold">
            <button type="button" data-media-tab-btn="library" class="rounded px-3 py-1.5 bg-brand text-white">Biblioteka</button>
            <button type="button" data-media-tab-btn="unsplash" class="rounded px-3 py-1.5 text-muted hover:bg-gray-100">Unsplash</button>
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

        <div data-media-panel="unsplash" class="hidden">
            {{-- Plain div, not <form>: this partial renders inside the page's own
                 <form>, and nested <form> elements are invalid HTML — browsers
                 silently drop them, along with everything depending on 'submit'. --}}
            <div data-unsplash-form class="mb-4 flex gap-2">
                <label class="sr-only" for="{{ $editorId }}-unsplash-search">Szukaj zdjęć na Unsplash</label>
                <input type="search" id="{{ $editorId }}-unsplash-search" placeholder="Szukaj zdjęć na Unsplash&hellip;"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <button type="button" data-unsplash-submit class="flex-none rounded bg-brand px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-dark">Szukaj</button>
            </div>
            <div class="overflow-y-auto">
                <div data-unsplash-grid class="grid grid-cols-3 gap-3 sm:grid-cols-4"></div>
                <p data-unsplash-hint class="py-6 text-center text-sm text-muted">Wpisz szukaną frazę powyżej (np. „edukacja”, „dostępność”).</p>
                <p data-unsplash-loading class="hidden py-6 text-center text-sm text-muted">Szukam&hellip;</p>
                <p data-unsplash-error class="hidden py-6 text-center text-sm text-red-600"></p>
            </div>
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

                ClassicEditor.create(textarea).then(function (editor) {
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

                    var ckSnippets = {!! json_encode($editorSnippets) !!};
                    document.getElementById('{{ $editorId }}-toolbar').querySelectorAll('[data-insert-key]').forEach(function (b) {
                        b.addEventListener('click', function () {
                            var vf = editor.data.processor.toView(ckSnippets[b.dataset.insertKey]);
                            editor.model.insertContent(editor.data.toModel(vf));
                            editor.editing.view.focus();
                        });
                    });

                    document.getElementById('{{ $editorId }}-ext-link').addEventListener('click', function () {
                        var url = window.prompt('Adres URL (link zewnętrzny):', 'https://');
                        if (!url) return;
                        var text = window.prompt('Tekst linku:', url) || url;
                        var vf = editor.data.processor.toView('<a href="' + url + '" target="_blank" rel="noopener noreferrer external">' + text + '</a>');
                        editor.model.insertContent(editor.data.toModel(vf));
                        editor.editing.view.focus();
                    });

                    var pageLinkSelect = document.getElementById('{{ $editorId }}-page-link');
                    if (pageLinkSelect) {
                        pageLinkSelect.addEventListener('change', function () {
                            if (!this.value) return;
                            var title = this.selectedOptions[0].dataset.title;
                            var html = '<p><a href="' + this.value + '">' + title + '</a></p>';
                            var viewFragment = editor.data.processor.toView(html);
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
                    plugins: 'advlist autolink lists link image charmap preview searchreplace visualblocks code fullscreen media table help wordcount accordion emoticons autosave quickbars',
                    toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link table media accordion | insertmenu linkmenu | removeformat charmap emoticons | searchreplace visualblocks fullscreen preview | a11ycheck help | code',
                    toolbar_mode: 'wrap',
                    statusbar: true,
                    elementpath: false,
                    // Autozapis roboczy w przeglądarce — po awarii można przywrócić wersję.
                    autosave_interval: '20s',
                    autosave_restore_when_empty: true,
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
                        editor.ui.registry.addMenuButton('insertmenu', {
                            text: 'Wstaw', icon: 'plus', tooltip: 'Wstaw element',
                            fetch: function (cb) {
                                cb([
                                    { type: 'menuitem', text: 'Obraz z biblioteki', icon: 'image', onAction: function () { document.getElementById('{{ $editorId }}-media').click(); } },
                                    { type: 'menuitem', text: 'Przycisk CTA', onAction: function () { editor.insertContent(ctaHtml); } },
                                    { type: 'menuitem', text: 'Przycisk czerwony', onAction: function () { editor.insertContent(snippets.red); } },
                                    { type: 'menuitem', text: 'Przycisk zielony', onAction: function () { editor.insertContent(snippets.green); } },
                                    { type: 'menuitem', text: 'Tekst z ramką', onAction: function () { editor.insertContent(boxHtml); } },
                                    { type: 'menuitem', text: 'Sekcja akcentu (lewo)', onAction: function () { editor.insertContent(snippets.accentLeft); } },
                                    { type: 'menuitem', text: 'Sekcja akcentu (prawo)', onAction: function () { editor.insertContent(snippets.accentRight); } },
                                    { type: 'menuitem', text: 'Układ 2 kolumn', onAction: function () { editor.insertContent(columnsHtml); } },
                                    { type: 'menuitem', text: 'Linia pozioma', onAction: function () { editor.insertContent('<hr>'); } },
                                ]);
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
                                    items.push({ type: 'nestedmenuitem', text: 'Link do strony', getSubmenuItems: function () {
                                        return pageLinks.map(function (p) {
                                            return { type: 'menuitem', text: p.title, onAction: function () { editor.insertContent('<a href="' + p.url + '">' + p.title + '</a>'); } };
                                        });
                                    } });
                                }
                                cb(items);
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

                        editor.on('init', function () {
                            modal.addEventListener('media-picked', function (event) {
                                var image = event.detail;
                                // Wymuszenie alt: przy wstawianiu obrazu pytamy o opis alternatywny.
                                var alt = window.prompt('Opis alternatywny (alt) — opisz obraz dla osób niewidomych.\nZostaw puste tylko dla obrazu czysto dekoracyjnego:', image.alt || '');
                                if (alt === null) return; // anulowano — nie wstawiaj
                                var safeAlt = alt.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                                editor.insertContent('<img src="' + image.url + '" alt="' + safeAlt + '">');
                            });
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
