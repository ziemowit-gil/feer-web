{{--
    Kreator zestawu kafelków w oknie dialogowym — wspólny dla obu edytorów treści (TinyMCE i CKEditor).
    Układ: po lewej lista kafelków i sekcji (zwijane karty), po prawej podgląd siatki na żywo.
    API (window.TilesBuilder):
        TilesBuilder.open({ id: 12 | null, onSave: (set) => …, trigger: element|null })
    Zapis przez JSON (admin.zestawy-kafelkow.*); po zapisie wywołuje onSave({id, name}).
    Dostępność: role="dialog" + aria-modal, fokus w oknie (pętla Tab), Esc zamyka i oddaje fokus, etykiety pól,
    przyciski przełączające z aria-pressed/aria-expanded, komunikaty role="alert", podgląd ukryty przed czytnikami (aria-hidden).
--}}
@once
    <div id="tb-root" class="fixed inset-0 hidden items-start justify-center overflow-y-auto bg-black/50 p-3 sm:p-6" style="z-index:2147483000">
        <div id="tb-dialog" role="dialog" aria-modal="true" aria-labelledby="tb-title" class="w-full rounded-2xl bg-white shadow-2xl" style="max-width:68rem">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <div class="min-w-0">
                    <h2 id="tb-title" class="text-lg font-bold text-ink">Zestaw kafelków</h2>
                    <p class="text-xs text-muted">Układ kafelków widzisz na bieżąco po prawej stronie.</p>
                </div>
                <button type="button" id="tb-close" class="flex h-10 w-10 flex-none items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i><span class="sr-only">Zamknij kreator</span>
                </button>
            </div>

            <div class="tb-body">
                {{-- Lewa strona: edycja --}}
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="tb-name" class="mb-1 block text-sm font-bold text-ink">Nazwa zestawu <span class="font-normal text-muted">(tylko dla Ciebie)</span></label>
                        <input type="text" id="tb-name" maxlength="120" placeholder="np. Kafelki działu Szkolenia" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <ol id="tb-rows" class="space-y-2" role="list"></ol>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="tb-add" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kafelek
                        </button>
                        <button type="button" id="tb-add-section" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-heading" aria-hidden="true"></i> Dodaj sekcję (nagłówek)
                        </button>
                    </div>
 <div id="tb-hints" class="hidden rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900" role="status" aria-live="polite"></div>
                    <p id="tb-error" role="alert" class="hidden rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></p>
                </div>

                {{-- Prawa strona: podgląd na żywo --}}
                <aside class="tb-preview border-t border-gray-200 bg-gray-50 px-6 py-5" aria-hidden="true">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Podgląd</p>
                    <div id="tb-preview" class="space-y-3"></div>
                </aside>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4">
                <p class="text-xs text-muted">Siatka wstawi się w treść jako blok; edytujesz ją ponownie z menu „Wstaw”.</p>
                <div class="flex gap-2">
                    <button type="button" id="tb-cancel" class="rounded-lg px-4 py-2 text-sm font-bold text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</button>
                    <button type="button" id="tb-save" class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz i wstaw</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Układ kreatora w zwykłym CSS (niezależny od zbudowanych klas Tailwinda). */
        .tb-body { display: block; }
        @media (min-width: 900px) {
            .tb-body { display: grid; grid-template-columns: minmax(0, 1fr) 22rem; }
            .tb-preview { border-top: 0 !important; border-left: 1px solid #e5e7eb; position: sticky; top: 0; align-self: start; max-height: calc(100vh - 8rem); overflow-y: auto; }
        }
        .tb-card { border: 1px solid #d1d5db; border-radius: .75rem; background: #fff; }
        .tb-card[data-open="1"] { border-color: #1d1d1a; box-shadow: 0 1px 0 #1d1d1a; }
        .tb-head { display: flex; align-items: center; gap: .625rem; padding: .5rem .625rem; }
        .tb-chip { display: inline-flex; width: 2.25rem; height: 2.25rem; flex: none; align-items: center; justify-content: center; border-radius: .5rem; font-size: 1.05rem; }
        .tb-input { width: 100%; border: 1px solid #d1d5db; border-radius: .5rem; padding: .4rem .6rem; font-size: .875rem; }
        .tb-input:focus { outline: 2px solid var(--color-brand); outline-offset: 0; border-color: var(--color-brand); }
        .tb-ibtn { display: inline-flex; width: 2rem; height: 2rem; flex: none; align-items: center; justify-content: center; border-radius: .5rem; color: #374151; }
        .tb-ibtn:hover:not(:disabled) { background: #f3f4f6; }
        .tb-ibtn:disabled { opacity: .35; cursor: not-allowed; }
        .tb-ibtn:focus-visible, .tb-seg button:focus-visible, .tb-sw:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 2px; }
        .tb-seg { display: inline-flex; border: 1px solid #d1d5db; border-radius: .5rem; overflow: hidden; }
        .tb-seg button { padding: .3rem .7rem; font-size: .8rem; font-weight: 700; background: #fff; color: #1d1d1a; border-left: 1px solid #d1d5db; }
        .tb-seg button:first-child { border-left: 0; }
        .tb-seg button[aria-pressed="true"] { background: #1d1d1a; color: #fff; }
        .tb-sw { width: 1.75rem; height: 1.75rem; border-radius: 9999px; border: 2px solid #fff; box-shadow: 0 0 0 1px #9ca3af; }
        .tb-sw[aria-pressed="true"] { box-shadow: 0 0 0 2px #1d1d1a; }
        .tb-label { display: block; margin-bottom: .25rem; font-size: .75rem; font-weight: 700; color: #374151; }
        .tb-section { border: 2px solid var(--color-brand); border-radius: .75rem; background: var(--color-brand-light); padding: .5rem .625rem; display: flex; align-items: center; gap: .5rem; }
        .tb-pv-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
        .tb-pv-tile { display: flex; align-items: center; gap: .5rem; border-radius: .375rem; padding: .5rem .65rem; font-size: .8rem; font-weight: 700; line-height: 1.25; min-height: 3.25rem; }
        .tb-pv-tile.is-strip { min-height: 2.25rem; padding-top: .25rem; padding-bottom: .25rem; }
        .tb-pv-h { font-size: .85rem; font-weight: 800; color: #1d1d1a; margin-top: .75rem; }
    </style>

    <script>
        (function () {
            var storeUrl = @js(route('admin.zestawy-kafelkow.store'));
            var baseUrl = @js(url('admin/zestawy-kafelkow'));
            var csrf = @js(csrf_token());
            var palette = @js(collect(\App\Support\ThemePalette::swatches())->map(fn ($n, $h) => [$h, $n])->values()->all());
            var root, dlg, rowsEl, previewEl, nameEl, errEl;
            var state = null, items = [], openIdx = 0;

            function $(id) { return document.getElementById(id); }
            function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
            function isSection(t) { return t.heading !== undefined; }
            function blankTile() { return { label: '', url: '', icon: 'bi-lightning', color: '#1e6dff', cols: 1, is_negative: false, strip: false }; }

            function iconHtml(v) {
                v = (v || '').trim() || 'bi-lightning';
                if (/^mi-[a-z0-9_]+$/.test(v)) { return '<span class="material-symbols-outlined" style="font-size:1.2em;line-height:1">' + v.slice(3) + '</span>'; }
                var cls = (/(^|\s)fa[srlb]?(-|\s)/.test(v) || v.indexOf('bi ') === 0) ? v : (v.indexOf('bi-') === 0 ? 'bi ' + v : 'bi bi-' + v);
                return '<i class="' + esc(cls) + '"></i>';
            }
            function textOn(hex) {
                var c = (hex || '#1e6dff').replace('#', ''); var r = parseInt(c.substr(0, 2), 16) / 255, g = parseInt(c.substr(2, 2), 16) / 255, b = parseInt(c.substr(4, 2), 16) / 255;
                var f = function (x) { return x <= .03928 ? x / 12.92 : Math.pow((x + .055) / 1.055, 2.4); };
                var L = .2126 * f(r) + .7152 * f(g) + .0722 * f(b);
                return L > .4 ? '#1d1d1a' : '#ffffff';
            }

            /* ── Wskazówki dostępności (WCAG): nieblokujące, aktualizowane na bieżąco ── */
            var vague = ['kliknij tutaj', 'tutaj', 'klik', 'czytaj więcej', 'więcej', 'link', 'zobacz', 'kliknij', 'dalej', 'wejdź'];
            window.__a11yHints = function (labels, urls) {
                var out = [], seen = {};
                labels.forEach(function (l, i) {
                    var t = (l || '').trim().toLowerCase(); if (! t) { return; }
                    if (vague.indexOf(t) !== -1) { out.push('Etykieta „' + l.trim() + '” nie mówi, dokąd prowadzi — nazwij cel, np. „Zapisz się na szkolenie”.'); }
                    if (seen[t] !== undefined && (urls[seen[t]] || '') !== (urls[i] || '')) { out.push('Dwie pozycje „' + l.trim() + '” prowadzą w różne miejsca — nadaj im różne etykiety.'); }
                    seen[t] = seen[t] === undefined ? i : seen[t];
                    if (t.length > 60) { out.push('Etykieta „' + l.trim().slice(0, 30) + '…” jest długa — krótsza jest czytelniejsza dla czytnika ekranu.'); }
                });
                urls.forEach(function (u) { if ((u || '').trim() === '#') { out.push('Adres „#” nie prowadzi donikąd — wpisz docelowy adres.'); } });
                return out.filter(function (x, i) { return out.indexOf(x) === i; });
            };
            window.__renderA11yHints = function (el, hints) {
                el.classList.toggle('hidden', ! hints.length);
                el.innerHTML = hints.length ? '<strong>Wskazówki dostępności:</strong><ul style="margin:.25rem 0 0 1.1rem;list-style:disc">' + hints.map(function (h) { return '<li>' + esc(h) + '</li>'; }).join('') + '</ul>' : '';
            };
            function updateHints() {
                var tiles = items.filter(function (t) { return ! isSection(t); });
                window.__renderA11yHints($('tb-hints'), window.__a11yHints(tiles.map(function (t) { return t.label; }), tiles.map(function (t) { return t.url; })));
            }

            /* ── Podgląd ── */
            function renderPreview() {
                updateHints();
                var html = '', grid = '';
                var flush = function () { if (grid) { html += '<div class="tb-pv-grid">' + grid + '</div>'; grid = ''; } };
                items.forEach(function (t) {
                    if (isSection(t)) { flush(); html += '<div class="tb-pv-h">' + esc(t.heading || 'Nagłówek sekcji') + '</div>'; return; }
                    var col = t.color || '#1e6dff';
                    var style = t.is_negative ? 'background:' + col + ';color:' + textOn(col) : 'background:#fff;color:#1d1d1a;border:2px solid ' + col;
                    grid += '<div class="tb-pv-tile' + (t.strip ? ' is-strip' : '') + '" style="grid-column: span ' + Math.min(t.cols || 1, 3) + ';' + style + '">'
                        + '<span style="flex:none;' + (t.is_negative ? '' : 'color:' + col) + '">' + iconHtml(t.icon) + '</span><span style="min-width:0;flex:1">' + esc(t.label || 'Kafelek') + '</span><span aria-hidden="true">→</span></div>';
                });
                flush();
                previewEl.innerHTML = html || '<p class="text-sm text-muted">Dodaj pierwszy kafelek.</p>';
            }

            /* ── Lista ── */
            function btn(act, icon, label, disabled) {
                return '<button type="button" class="tb-ibtn" data-act="' + act + '" aria-label="' + label + '"' + (disabled ? ' disabled' : '') + '><i class="fa-solid ' + icon + '" aria-hidden="true"></i></button>';
            }
            function actions(i, kind) {
                return btn('up', 'fa-arrow-up', 'Przesuń wyżej', i === 0) + btn('down', 'fa-arrow-down', 'Przesuń niżej', i === items.length - 1) + btn('del', 'fa-trash', kind === 'section' ? 'Usuń nagłówek sekcji' : 'Usuń kafelek', false);
            }
            function rowHtml(t, i) {
                if (isSection(t)) {
                    return '<li class="tb-section" data-i="' + i + '" data-kind="section"><span class="tb-chip" style="background:#fff;color:#1d1d1a" aria-hidden="true"><i class="fa-solid fa-heading"></i></span>'
                        + '<div style="min-width:0;flex:1"><label class="tb-label" for="tb-s-' + i + '">Sekcja — nagłówek</label><input id="tb-s-' + i + '" class="tb-input" style="font-weight:700" data-f="heading" maxlength="160" value="' + esc(t.heading) + '" placeholder="np. Dla uczestników"></div>'
                        + '<span style="display:flex">' + actions(i, 'section') + '</span></li>';
                }
                var open = openIdx === i, col = t.color || '#1e6dff';
                var chipStyle = t.is_negative ? 'background:' + col + ';color:' + textOn(col) : 'background:#fff;color:' + col + ';border:2px solid ' + col;
                var sw = palette.map(function (p) { return '<button type="button" class="tb-sw" data-color="' + p[0] + '" aria-pressed="' + (col.toLowerCase() === p[0] ? 'true' : 'false') + '" aria-label="Kolor: ' + p[1] + '" title="' + p[1] + '" style="background:' + p[0] + '"></button>'; }).join('');
                var seg = function (field, opts, cur) { return '<span class="tb-seg" role="group">' + opts.map(function (o) { return '<button type="button" data-seg="' + field + '" data-val="' + o[0] + '" aria-pressed="' + (String(cur) === String(o[0]) ? 'true' : 'false') + '">' + o[1] + '</button>'; }).join('') + '</span>'; };
                return '<li class="tb-card" data-i="' + i + '" data-kind="tile" data-open="' + (open ? 1 : 0) + '">'
                    + '<div class="tb-head">'
                    + '<span class="tb-chip" style="' + chipStyle + '" aria-hidden="true">' + iconHtml(t.icon) + '</span>'
                    + '<div style="min-width:0;flex:1;display:grid;gap:.4rem;grid-template-columns:minmax(0,2fr) minmax(0,3fr)">'
                    + '<div><label class="sr-only" for="tb-l-' + i + '">Etykieta kafelka ' + (i + 1) + '</label><input id="tb-l-' + i + '" class="tb-input" data-f="label" maxlength="120" value="' + esc(t.label) + '" placeholder="Etykieta"></div>'
                    + '<div><label class="sr-only" for="tb-u-' + i + '">Adres kafelka ' + (i + 1) + '</label><input id="tb-u-' + i + '" class="tb-input" data-f="url" value="' + esc(t.url) + '" placeholder="https://… albo /strona"></div></div>'
                    + '<button type="button" class="tb-ibtn" data-act="toggle" aria-expanded="' + (open ? 'true' : 'false') + '" aria-controls="tb-d-' + i + '" aria-label="Ustawienia kafelka ' + (i + 1) + '"><i class="fa-solid fa-sliders" aria-hidden="true"></i></button>'
                    + actions(i, 'tile') + '</div>'
                    + (open ? '<div id="tb-d-' + i + '" style="display:grid;gap:.9rem;border-top:1px solid #e5e7eb;padding:.75rem">'
                        + '<div style="display:grid;gap:.75rem;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr))">'
                        + '<div><label class="tb-label" for="tb-i-' + i + '">Ikona (np. bi-heart, mi-home)</label><input id="tb-i-' + i + '" class="tb-input" data-f="icon" data-icon-picker data-icon-format="name" value="' + esc(t.icon || '') + '"></div>'
                        + '<div><span class="tb-label">Kolor</span><span style="display:flex;gap:.5rem;align-items:center">' + sw + '</span></div>'
                        + '<div><span class="tb-label">Szerokość</span>' + seg('cols', [[1, '1'], [2, '2'], [3, '3']], t.cols || 1) + '</div></div>'
                        + '<div style="display:flex;flex-wrap:wrap;gap:1rem"><div><span class="tb-label">Wygląd</span>' + seg('is_negative', [[0, 'Obwódka'], [1, 'Wypełniony']], t.is_negative ? 1 : 0) + '</div>'
                        + '<div><span class="tb-label">Wysokość</span>' + seg('strip', [[0, 'Standardowa'], [1, 'Niski pasek']], t.strip ? 1 : 0) + '</div></div></div>' : '')
                    + '</li>';
            }
            function renderRows() {
                rowsEl.innerHTML = items.map(rowHtml).join('') || '<li class="text-sm text-muted">Brak kafelków — dodaj pierwszy.</li>';
                renderPreview();
            }
            function showError(msg) { errEl.textContent = msg; errEl.classList.toggle('hidden', ! msg); }

            function close(restore) {
                root.classList.add('hidden'); root.classList.remove('flex');
                document.body.style.overflow = '';
                if (restore !== false && state && state.trigger && state.trigger.focus) { state.trigger.focus(); }
                state = null;
            }

            function init() {
                root = $('tb-root'); dlg = $('tb-dialog'); rowsEl = $('tb-rows'); previewEl = $('tb-preview'); nameEl = $('tb-name'); errEl = $('tb-error');
                document.body.appendChild(root);
                $('tb-close').addEventListener('click', function () { close(); });
                $('tb-cancel').addEventListener('click', function () { close(); });
                $('tb-add').addEventListener('click', function () { items.push(blankTile()); openIdx = items.length - 1; renderRows(); var el = $('tb-l-' + openIdx); if (el) { el.focus(); } });
                $('tb-add-section').addEventListener('click', function () { items.push({ heading: '' }); renderRows(); var el = $('tb-s-' + (items.length - 1)); if (el) { el.focus(); } });
                $('tb-save').addEventListener('click', save);

                // Pola tekstowe: zapis do stanu bez przerysowania listy (fokus zostaje).
                rowsEl.addEventListener('input', function (e) {
                    var f = e.target.dataset && e.target.dataset.f; if (! f) { return; }
                    var li = e.target.closest('li[data-i]'); var t = items[parseInt(li.dataset.i, 10)]; if (! t) { return; }
                    t[f] = e.target.value;
                    renderPreview();
                    if (f === 'icon') { var chip = li.querySelector('.tb-chip'); if (chip) { chip.innerHTML = iconHtml(t.icon); } }
                });
                rowsEl.addEventListener('change', function (e) { var f = e.target.dataset && e.target.dataset.f; if (f === 'icon') { e.target.dispatchEvent(new Event('input', { bubbles: true })); } });

                rowsEl.addEventListener('click', function (e) {
                    var li = e.target.closest('li[data-i]'); if (! li) { return; }
                    var i = parseInt(li.dataset.i, 10), t = items[i];
                    var a = e.target.closest('[data-act]');
                    if (a) {
                        var act = a.dataset.act, target = i;
                        if (act === 'del') { items.splice(i, 1); if (openIdx >= items.length) { openIdx = items.length - 1; } target = Math.min(i, items.length - 1); }
                        if (act === 'up' && i > 0) { var x = items.splice(i, 1)[0]; items.splice(i - 1, 0, x); if (openIdx === i) { openIdx = i - 1; } target = i - 1; }
                        if (act === 'down' && i < items.length - 1) { var y = items.splice(i, 1)[0]; items.splice(i + 1, 0, y); if (openIdx === i) { openIdx = i + 1; } target = i + 1; }
                        if (act === 'toggle') { openIdx = openIdx === i ? -1 : i; }
                        renderRows();
                        var again = rowsEl.querySelector('li[data-i="' + target + '"] [data-act="' + act + '"]') || rowsEl.querySelector('li[data-i="' + target + '"] input');
                        if (again) { again.focus(); }
                        return;
                    }
                    var sw = e.target.closest('[data-color]'); if (sw && t) { t.color = sw.dataset.color; renderRows(); var s2 = rowsEl.querySelector('li[data-i="' + i + '"] [data-color="' + t.color + '"]'); if (s2) { s2.focus(); } return; }
                    var sg = e.target.closest('[data-seg]');
                    if (sg && t) {
                        var field = sg.dataset.seg, val = parseInt(sg.dataset.val, 10);
                        t[field] = (field === 'cols') ? val : (val === 1);
                        renderRows(); var s3 = rowsEl.querySelector('li[data-i="' + i + '"] [data-seg="' + field + '"][data-val="' + sg.dataset.val + '"]'); if (s3) { s3.focus(); }
                    }
                });

                root.addEventListener('click', function (e) { if (e.target === root) { close(); } });
                root.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { e.stopPropagation(); close(); }
                    if (e.key === 'Tab') {
                        var f = Array.prototype.filter.call(dlg.querySelectorAll('button, input, select'), function (n) { return n.offsetParent !== null && ! n.disabled; });
                        if (! f.length) { return; }
                        if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
                        else if (! e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
                    }
                });
            }

            function save() {
                var tiles = items.filter(function (t) { return isSection(t) ? (t.heading || '').trim() !== '' : (t.label || t.url); }).map(function (t) {
                    if (isSection(t)) { return { heading: t.heading.trim() }; }
                    return { label: (t.label || '').trim(), url: (t.url || '').trim(), icon: (t.icon || '').trim(), color: t.color || '#1e6dff', cols: t.cols || 1, is_negative: !! t.is_negative, strip: !! t.strip };
                });
                var name = nameEl.value.trim() || 'Zestaw kafelków';
                if (! tiles.some(function (t) { return ! isSection(t); })) { showError('Dodaj przynajmniej jeden kafelek (etykieta i adres).'); return; }
                for (var i = 0; i < tiles.length; i++) {
                    if (isSection(tiles[i])) { continue; }
                    if (! tiles[i].label || ! tiles[i].url) { showError('Kafelek: uzupełnij etykietę i adres („' + (tiles[i].label || tiles[i].url) + '”).'); return; }
                    if (! /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(tiles[i].url)) { showError('Adres kafelka „' + tiles[i].label + '” musi zaczynać się od https://, /, #, mailto: lub tel:.'); return; }
                }
                showError('');
                var id = state && state.id;
                fetch(id ? baseUrl + '/' + id : storeUrl, {
                    method: id ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ name: name, tiles: tiles }),
                }).then(function (r) { return r.ok ? r.json() : r.json().then(function (j) { throw new Error((j && j.message) || 'Błąd zapisu'); }); })
                  .then(function (set) { var cb = state && state.onSave; close(false); if (cb) { cb(set); } })
                  .catch(function (e) { showError('Nie udało się zapisać zestawu: ' + e.message); });
            }

            window.TilesBuilder = {
                open: function (opts) {
                    if (! root) { init(); }
                    state = { id: opts.id || null, onSave: opts.onSave, trigger: opts.trigger || document.activeElement };
                    showError(''); nameEl.value = ''; items = []; openIdx = 0;
                    root.classList.remove('hidden'); root.classList.add('flex'); document.body.style.overflow = 'hidden';
                    $('tb-title').textContent = state.id ? 'Edytuj zestaw kafelków' : 'Nowy zestaw kafelków';
                    if (state.id) {
                        rowsEl.innerHTML = '<li class="text-sm text-muted">Ładowanie…</li>'; previewEl.innerHTML = '';
                        fetch(baseUrl + '/' + state.id, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
                            nameEl.value = d.name || ''; items = (d.tiles && d.tiles.length) ? d.tiles : [blankTile()]; openIdx = -1; renderRows(); nameEl.focus();
                        }).catch(function () { showError('Nie udało się wczytać zestawu.'); });
                    } else {
                        items = [blankTile()]; renderRows(); nameEl.focus();
                    }
                },
            };
        })();
    </script>
@endonce
