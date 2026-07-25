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
    $pages = \App\Models\Page::where('is_published', true)->orderBy('title')->get();
@endphp

<div class="mb-2 flex flex-wrap items-center gap-2">
    @if ($useCkEditor)
        <button type="button" id="{{ $editorId }}-columns" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand"
            title="W CKEditor wstawia tabelę 1×2 — silnik nie zachowuje niestandardowych znaczników div bez dodatkowego pluginu.">
            <i class="fa-solid fa-table-columns" aria-hidden="true"></i> Wstaw układ 2 kolumn
        </button>
    @endif
    <button type="button" id="{{ $editorId }}-media" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand">
        <i class="fa-solid fa-images" aria-hidden="true"></i> Wstaw obraz z biblioteki
    </button>
    <button type="button" id="{{ $editorId }}-cta" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand">
        <i class="fa-solid fa-square" aria-hidden="true"></i> Wstaw przycisk CTA
    </button>
    <button type="button" id="{{ $editorId }}-box" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand">
        <i class="fa-solid fa-vector-square" aria-hidden="true"></i> Wstaw tekst z ramką
    </button>
    @if ($pages->isNotEmpty())
        <select id="{{ $editorId }}-page-link" class="rounded border border-gray-300 px-2 py-1.5 text-xs font-bold text-ink focus:border-brand focus:ring-brand">
            <option value="">Wstaw link do strony&hellip;</option>
            @foreach ($pages as $page)
                <option value="/{{ $page->slug }}" data-title="{{ $page->title }}">{{ $page->title }}</option>
            @endforeach
        </select>
    @endif
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

                tinymce.init({
                    selector: '#{{ $editorId }}',
                    license_key: 'gpl',
                    height: 700,
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    convert_urls: false,
                    plugins: 'link lists table code',
                    toolbar: 'blocks | bold italic underline | forecolor backcolor | bullist numlist | link table columns | code',
                    setup: function (editor) {
                        editor.ui.registry.addButton('columns', {
                            text: 'Kolumny',
                            icon: 'table',
                            tooltip: 'Wstaw układ 2 kolumn',
                            onAction: function () {
                                editor.insertContent({!! json_encode($columnsHtml) !!});
                            },
                        });

                        editor.on('init', function () {
                            modal.addEventListener('media-picked', function (event) {
                                var image = event.detail;
                                var html = '<img src="' + image.url + '" alt="' + image.alt.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">';
                                editor.insertContent(html);
                            });

                            document.getElementById('{{ $editorId }}-cta').addEventListener('click', function () {
                                editor.insertContent({!! json_encode($ctaHtml) !!});
                            });

                            document.getElementById('{{ $editorId }}-box').addEventListener('click', function () {
                                editor.insertContent({!! json_encode($boxHtml) !!});
                            });

                            var pageLinkSelect = document.getElementById('{{ $editorId }}-page-link');
                            if (pageLinkSelect) {
                                pageLinkSelect.addEventListener('change', function () {
                                    if (!this.value) return;
                                    var title = this.selectedOptions[0].dataset.title;
                                    editor.insertContent('<p><a href="' + this.value + '">' + title + '</a></p>');
                                    this.selectedIndex = 0;
                                });
                            }
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
