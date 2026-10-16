{{--
    Okno „Lista podstron” / „Lista aktualności” dla edytorów WYSIWYG (TinyMCE i CKEditor).
    API (window.LinkListBuilder):
        LinkListBuilder.open({ kind: 'subpages'|'news', code: '[podstrony:…]'|null, trigger: element|null, onInsert: (code, summary) => … })
        LinkListBuilder.summary(code) — czytelny opis shortcode'u (zaślepka w edytorze).
    Wstawia shortcode ([podstrony…] / [aktualnosci…]) — listę składa App\Support\ShortcodeParser przy renderowaniu,
    więc nowe podstrony i aktualności pojawiają się same. Opcje jak w App\Support\SubpageList i App\Support\NewsList.
    Dostępność: role="dialog" + aria-modal, pętla Tab, Esc zamyka, fokus wraca na przycisk, etykiety pól, fieldsety z legendą.
--}}
@once
    @php
        $llPages = \App\Support\SubpageList::pickerOptions();
        $llCategories = \App\Support\NewsList::categoryOptions();
        $llField = 'w-full rounded-lg border-gray-500 text-sm text-ink focus:border-brand-dark focus:ring-brand-dark';
        $llLabel = 'mb-1 block text-sm font-bold text-ink';
    @endphp
    <div id="ll-root" class="fixed inset-0 hidden items-start justify-center overflow-y-auto bg-black/50 p-3 sm:p-6" style="z-index:2147483000">
        <div id="ll-dialog" role="dialog" aria-modal="true" aria-labelledby="ll-title" aria-describedby="ll-intro" class="w-full rounded-2xl bg-white shadow-2xl" style="max-width:40rem">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <h2 id="ll-title" class="text-lg font-bold text-ink">Lista podstron</h2>
                <button type="button" id="ll-close" class="flex h-10 w-10 flex-none items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i><span class="sr-only">Zamknij okno</span>
                </button>
            </div>
            <div class="space-y-4 px-6 py-5">
                <p id="ll-intro" class="text-sm text-muted">Lista aktualizuje się sama przy wyświetlaniu strony. Pomijane są treści nieopublikowane i ukryte.</p>

                {{-- Lista podstron --}}
                <div id="ll-subpages" class="space-y-4">
                    <fieldset>
                        <legend class="{{ $llLabel }}">Pokaż podstrony</legend>
                        <label class="flex items-center gap-2 text-sm text-ink"><input type="radio" name="ll-source" value="current" class="border-gray-500 text-brand-dark focus:ring-brand-dark"> bieżącej strony (tej, na której stoi lista)</label>
                        <label class="mt-1 flex items-center gap-2 text-sm text-ink"><input type="radio" name="ll-source" value="page" class="border-gray-500 text-brand-dark focus:ring-brand-dark"> wskazanej strony</label>
                    </fieldset>
                    <div id="ll-page-wrap">
                        <label for="ll-page" class="{{ $llLabel }}">Strona</label>
                        <select id="ll-page" class="{{ $llField }}">
                            <option value="">— wybierz stronę —</option>
                            @foreach ($llPages as $p)
                                <option value="{{ $p['id'] }}">{{ str_repeat("\u{2003}", min($p['depth'], 6)) }}{{ $p['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold text-ink"><input type="checkbox" id="ll-manual" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Wybierz strony ręcznie</label>
                        <p class="mt-0.5 pl-6 text-xs text-muted">Zamiast wszystkich podstron pokaże tylko zaznaczone strony.</p>
                    </div>
                    <fieldset id="ll-pick-wrap">
                        <legend class="{{ $llLabel }}">Strony do pokazania <span id="ll-pick-count" class="font-normal text-muted"></span></legend>
                        <label for="ll-pick-filter" class="sr-only">Szukaj strony na liście</label>
                        <input type="search" id="ll-pick-filter" placeholder="Szukaj strony…" class="{{ $llField }}">
                        <div id="ll-pick-list" class="mt-2 max-h-64 space-y-1 overflow-y-auto rounded-lg bg-gray-50 p-2">
                            @foreach ($llPages as $p)
                                <label class="flex items-start gap-2 text-sm text-ink" data-title="{{ mb_strtolower($p['title']) }}" style="padding-left: {{ min($p['depth'], 6) * 1.1 }}rem">
                                    <input type="checkbox" value="{{ $p['id'] }}" class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> {{ $p['title'] }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ll-sort" class="{{ $llLabel }}">Sortowanie</label>
                            <select id="ll-sort" class="{{ $llField }}">
                                @foreach (\App\Support\SubpageList::SORTS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="ll-depth" class="{{ $llLabel }}">Głębokość</label>
                            <select id="ll-depth" class="{{ $llField }}">
                                @foreach (\App\Support\SubpageList::DEPTHS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Lista aktualności --}}
                <div id="ll-news" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ll-category" class="{{ $llLabel }}">Kategoria</label>
                        <select id="ll-category" class="{{ $llField }}">
                            <option value="">Wszystkie kategorie</option>
                            @foreach ($llCategories as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="ll-limit" class="{{ $llLabel }}">Liczba wpisów</label>
                        <select id="ll-limit" class="{{ $llField }}">
                            @foreach ([3, 4, 5, 6, 8, 10, 12, 15, 20, 30] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="ll-nsort" class="{{ $llLabel }}">Sortowanie</label>
                        <select id="ll-nsort" class="{{ $llField }}">
                            <option value="najnowsze">Od najnowszych</option>
                            <option value="alfabet">Alfabetycznie (spośród najnowszych)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="ll-layout" class="{{ $llLabel }}">Wygląd</label>
                    <select id="ll-layout" class="{{ $llField }}">
                        @foreach (\App\Support\SubpageList::LAYOUTS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                </div>

                <p class="text-sm text-ink">W treści pojawi się znacznik: <code id="ll-code" class="rounded bg-brand-light px-1.5 py-0.5 text-brand-dark"></code></p>
                <p id="ll-error" role="alert" class="hidden rounded-md bg-red-50 px-3 py-2 text-sm text-red-800"></p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" id="ll-cancel" class="rounded-lg px-4 py-2 text-sm font-bold text-muted hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</button>
                <button type="button" id="ll-save" class="rounded-lg bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Wstaw listę</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var pages = @js(collect($llPages)->mapWithKeys(fn ($p) => [$p['id'] => $p['title']]));
            var categories = @js(collect($llCategories)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']]));
            var layouts = @js(\App\Support\SubpageList::LAYOUTS);
            var state = null;
            function $(id) { return document.getElementById(id); }

            function parse(code) {
                var m = String(code || '').match(/^\[(podstrony|aktualnosci)(?::([^\]]*))?\]$/i);
                if (!m) return null;
                var o = {};
                (m[2] || '').split(/[;|]/).forEach(function (p) { var i = p.indexOf('='); if (i > 0) o[p.slice(0, i).trim().toLowerCase()] = p.slice(i + 1).trim(); });
                o.kind = m[1].toLowerCase() === 'aktualnosci' ? 'news' : 'subpages';
                return o;
            }
            function build() {
                var parts = [], layout = $('ll-layout').value;
                if (state.kind === 'news') {
                    if ($('ll-category').value) parts.push('kategoria=' + $('ll-category').value);
                    if ($('ll-limit').value !== '5') parts.push('liczba=' + $('ll-limit').value);
                    if ($('ll-nsort').value === 'alfabet') parts.push('sortowanie=alfabet');
                } else {
                    var picked = [].slice.call($('ll-pick-list').querySelectorAll('input:checked')).map(function (i) { return i.value; });
                    if ($('ll-manual').checked && picked.length) parts.push('wybrane=' + picked.join(','));
                    else if (source() === 'page' && $('ll-page').value) parts.push('strona=' + $('ll-page').value);
                    if ($('ll-sort').value === 'alfabet') parts.push('sortowanie=alfabet');
                    if ($('ll-depth').value === '2') parts.push('poziomy=2');
                }
                if (layout !== 'lista') parts.push('wyglad=' + layout);
                return '[' + (state.kind === 'news' ? 'aktualnosci' : 'podstrony') + (parts.length ? ':' + parts.join(';') : '') + ']';
            }
            function source() { var r = document.querySelector('input[name="ll-source"]:checked'); return r ? r.value : 'current'; }

            // Czytelny opis shortcode'u — zaślepka w treści edytora (bez nawiasów, bo wchodzi w atrybut akapitu).
            function summary(code) {
                var o = parse(code);
                if (!o) return '';
                var layout = (layouts[o.wyglad] || layouts.lista).toLowerCase();
                if (o.kind === 'news') {
                    return 'Lista aktualności: ' + [o.kategoria ? 'kategoria „' + (categories[o.kategoria] || '#' + o.kategoria) + '”' : 'wszystkie kategorie', 'do ' + (o.liczba || 5) + ' wpisów', o.sortowanie === 'alfabet' ? 'alfabetycznie' : 'od najnowszych', layout].join(' · ');
                }
                var picked = (o.wybrane || '').split(',').filter(Boolean);
                var src = picked.length ? 'wybrane strony (' + picked.length + ')' : (o.strona ? 'podstrony strony „' + (pages[o.strona] || '#' + o.strona) + '”' : 'podstrony bieżącej strony');
                return 'Lista podstron: ' + [src, o.sortowanie === 'alfabet' ? 'alfabetycznie' : 'kolejność z drzewa', o.poziomy === '2' ? 'dzieci i wnuki' : 'tylko dzieci', layout].join(' · ');
            }

            function sync() {
                var news = state.kind === 'news';
                $('ll-title').textContent = news ? 'Lista aktualności' : 'Lista podstron';
                $('ll-news').classList.toggle('hidden', !news);
                $('ll-subpages').classList.toggle('hidden', news);
                $('ll-page-wrap').classList.toggle('hidden', source() !== 'page');
                $('ll-pick-wrap').classList.toggle('hidden', !$('ll-manual').checked);
                $('ll-pick-count').textContent = '(zaznaczono: ' + $('ll-pick-list').querySelectorAll('input:checked').length + ')';
                $('ll-code').textContent = build();
                $('ll-error').classList.add('hidden');
            }

            function fill(o) {
                o = o || {};
                var news = state.kind === 'news';
                $('ll-layout').value = layouts[o.wyglad] ? o.wyglad : 'lista';
                if (news) {
                    $('ll-category').value = o.kategoria && categories[o.kategoria] ? o.kategoria : '';
                    $('ll-limit').value = [].some.call($('ll-limit').options, function (op) { return op.value === String(o.liczba); }) ? String(o.liczba) : '5';
                    $('ll-nsort').value = o.sortowanie === 'alfabet' ? 'alfabet' : 'najnowsze';
                    return;
                }
                var picked = (o.wybrane || '').split(',').filter(Boolean);
                document.querySelector('input[name="ll-source"][value="' + (o.strona ? 'page' : 'current') + '"]').checked = true;
                $('ll-page').value = o.strona && pages[o.strona] ? o.strona : '';
                $('ll-manual').checked = picked.length > 0;
                $('ll-pick-filter').value = '';
                [].forEach.call($('ll-pick-list').querySelectorAll('label'), function (l) { l.classList.remove('hidden'); });
                [].forEach.call($('ll-pick-list').querySelectorAll('input'), function (i) { i.checked = picked.indexOf(i.value) !== -1; });
                $('ll-sort').value = o.sortowanie === 'alfabet' ? 'alfabet' : 'drzewo';
                $('ll-depth').value = o.poziomy === '2' ? '2' : '1';
            }

            function focusables() {
                return [].filter.call($('ll-dialog').querySelectorAll('button, input, select, [tabindex]:not([tabindex="-1"])'), function (el) { return !el.disabled && el.offsetParent !== null; });
            }
            function onKey(e) {
                if (e.key === 'Escape') { e.preventDefault(); close(); return; }
                if (e.key !== 'Tab') return;
                var f = focusables(); if (!f.length) return;
                if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
                else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
            }
            function open(opts) {
                var parsed = parse(opts.code);
                state = { kind: parsed ? parsed.kind : (opts.kind === 'news' ? 'news' : 'subpages'), trigger: opts.trigger || document.activeElement, onInsert: opts.onInsert };
                fill(parsed);
                sync();
                $('ll-save').textContent = parsed ? 'Zapisz zmiany' : 'Wstaw listę';
                var root = $('ll-root');
                root.classList.remove('hidden'); root.classList.add('flex');
                document.addEventListener('keydown', onKey, true);
                (state.kind === 'news' ? $('ll-category') : document.querySelector('input[name="ll-source"]:checked')).focus();
            }
            function close() {
                var root = $('ll-root');
                root.classList.add('hidden'); root.classList.remove('flex');
                document.removeEventListener('keydown', onKey, true);
                var t = state && state.trigger; state = null;
                if (t && t.focus && (!(t instanceof Node) || document.contains(t))) t.focus();
            }
            function save() {
                if (state.kind === 'subpages' && source() === 'page' && !$('ll-manual').checked && !$('ll-page').value) {
                    $('ll-error').textContent = 'Wybierz stronę, której podstrony pokazać.'; $('ll-error').classList.remove('hidden'); $('ll-page').focus(); return;
                }
                if (state.kind === 'subpages' && $('ll-manual').checked && !$('ll-pick-list').querySelector('input:checked')) {
                    $('ll-error').textContent = 'Zaznacz co najmniej jedną stronę albo wyłącz wybór ręczny.'; $('ll-error').classList.remove('hidden'); $('ll-pick-filter').focus(); return;
                }
                var code = build(), cb = state.onInsert;
                close();
                if (cb) cb(code, summary(code));
            }

            document.addEventListener('DOMContentLoaded', function () {
                var dlg = $('ll-dialog');
                dlg.addEventListener('change', function () { if (state) sync(); });
                $('ll-pick-filter').addEventListener('input', function () {
                    var q = this.value.trim().toLowerCase();
                    [].forEach.call($('ll-pick-list').querySelectorAll('label'), function (l) { l.classList.toggle('hidden', !!q && l.dataset.title.indexOf(q) === -1); });
                });
                $('ll-close').addEventListener('click', close);
                $('ll-cancel').addEventListener('click', close);
                $('ll-save').addEventListener('click', save);
                $('ll-root').addEventListener('mousedown', function (e) { if (e.target === this) close(); });
            });

            // Akapit z shortcodem listy w treści edytora: <p class="content-links-shortcode" data-summary="…">[podstrony…]</p>.
            function html(code) {
                var s = summary(code).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                return '<p class="content-links-shortcode" data-summary="' + s + '">' + code + '</p>';
            }

            window.LinkListBuilder = { open: open, summary: summary, parse: parse, html: html };
        })();
    </script>
@endonce
