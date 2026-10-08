{{--
    Kreator zestawu kafelków w oknie dialogowym — wspólny dla obu edytorów treści (TinyMCE i CKEditor).
    API (window.TilesBuilder):
        TilesBuilder.open({ id: 12 | null, onSave: (set) => …, trigger: element|null })
    Zapis przez JSON (admin.zestawy-kafelkow.*); po zapisie wywołuje onSave({id, name}).
    Dostępność: role="dialog" + aria-modal, fokus w oknie (pętla Tab), Esc zamyka i oddaje fokus, etykiety pól, komunikaty role="alert".
--}}
@once
    <div id="tb-root" class="fixed inset-0 z-[10000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-8">
        <div id="tb-dialog" role="dialog" aria-modal="true" aria-labelledby="tb-title" class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <h2 id="tb-title" class="text-lg font-bold text-ink">Zestaw kafelków</h2>
                <button type="button" id="tb-close" class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i><span class="sr-only">Zamknij kreator</span>
                </button>
            </div>
            <div class="space-y-5 px-6 py-5">
                <div>
                    <label for="tb-name" class="mb-1 block text-sm font-bold text-ink">Nazwa zestawu <span class="font-normal text-muted">(tylko dla Ciebie)</span></label>
                    <input type="text" id="tb-name" maxlength="120" placeholder="np. Kafelki działu Szkolenia" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div id="tb-rows" class="space-y-3"></div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="tb-add" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kafelek
                    </button>
                    <button type="button" id="tb-add-section" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-gray-400 px-4 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-heading" aria-hidden="true"></i> Dodaj sekcję (nagłówek)
                    </button>
                </div>
                <p id="tb-error" role="alert" class="hidden rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></p>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-6 py-4">
                <p class="text-xs text-muted">Siatka wstawi się w treść jako blok; edytujesz ją ponownie z menu „Wstaw”.</p>
                <div class="flex gap-2">
                    <button type="button" id="tb-cancel" class="rounded-lg px-4 py-2 text-sm font-bold text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</button>
                    <button type="button" id="tb-save" class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz i wstaw</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var storeUrl = @js(route('admin.zestawy-kafelkow.store'));
            var baseUrl = @js(url('admin/zestawy-kafelkow'));
            var csrf = @js(csrf_token());
            var colors = [['#1e6dff', 'Niebieski'], ['#1d1d1a', 'Grafit'], ['#ea8f00', 'Pomarańcz'], ['#cbd5e7', 'Jasnoniebieski']];
            var root, dlg, rows, nameEl, errEl, state = null;

            function $(id) { return document.getElementById(id); }
            function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

            function rowHtml(t, i) {
                if (t.heading !== undefined) {
                    return '<div class="tb-row rounded-xl border-2 border-brand bg-brand-light p-4" data-i="' + i + '" data-section="1">'
                        + '<div class="flex flex-wrap items-end gap-3"><div class="min-w-0 flex-1"><label class="mb-1 block text-xs font-bold text-ink" for="tb-s-' + i + '">Sekcja — nagłówek</label>'
                        + '<input id="tb-s-' + i + '" data-f="heading" type="text" maxlength="160" value="' + esc(t.heading) + '" placeholder="np. Dla uczestników" class="w-full rounded-lg border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand"></div>'
                        + '<span class="flex gap-1">'
                        + '<button type="button" data-act="up" class="rounded p-2 text-muted hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń sekcję wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>'
                        + '<button type="button" data-act="down" class="rounded p-2 text-muted hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń sekcję niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>'
                        + '<button type="button" data-act="del" class="rounded p-2 text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń nagłówek sekcji"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>'
                        + '</span></div><p class="mt-1 text-xs text-ink">Kolejne kafelki należą do tej sekcji — aż do następnego nagłówka.</p></div>';
                }
                var colorOpts = colors.map(function (c) { return '<option value="' + c[0] + '"' + ((t.color || '#1e6dff') === c[0] ? ' selected' : '') + '>' + c[1] + '</option>'; }).join('');
                var colsOpts = [1, 2, 3].map(function (n) { return '<option value="' + n + '"' + ((t.cols || 1) === n ? ' selected' : '') + '>' + n + (n === 1 ? ' kolumna' : ' kolumny') + '</option>'; }).join('');
                return '<div class="tb-row rounded-xl border border-gray-200 bg-gray-50 p-4" data-i="' + i + '">'
                    + '<div class="grid gap-3 sm:grid-cols-[2fr_3fr]">'
                    + '<div><label class="mb-1 block text-xs font-bold text-muted" for="tb-l-' + i + '">Etykieta</label><input id="tb-l-' + i + '" data-f="label" type="text" maxlength="120" value="' + esc(t.label) + '" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>'
                    + '<div><label class="mb-1 block text-xs font-bold text-muted" for="tb-u-' + i + '">Adres</label><input id="tb-u-' + i + '" data-f="url" type="text" value="' + esc(t.url) + '" placeholder="https://… albo /strona" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>'
                    + '</div>'
                    + '<div class="mt-3 grid gap-3 sm:grid-cols-3">'
                    + '<div><label class="mb-1 block text-xs font-bold text-muted" for="tb-i-' + i + '">Ikona (np. bi-heart, mi-home)</label><input id="tb-i-' + i + '" data-f="icon" type="text" value="' + esc(t.icon || '') + '" data-icon-picker data-icon-format="name" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>'
                    + '<div><label class="mb-1 block text-xs font-bold text-muted" for="tb-c-' + i + '">Kolor</label><select id="tb-c-' + i + '" data-f="color" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">' + colorOpts + '</select></div>'
                    + '<div><label class="mb-1 block text-xs font-bold text-muted" for="tb-w-' + i + '">Szerokość</label><select id="tb-w-' + i + '" data-f="cols" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">' + colsOpts + '</select></div>'
                    + '</div>'
                    + '<div class="mt-3 flex flex-wrap items-center gap-4">'
                    + '<label class="flex items-center gap-2 text-sm"><input type="checkbox" data-f="is_negative"' + (t.is_negative ? ' checked' : '') + ' class="rounded border-gray-300 text-brand focus:ring-brand"> Wypełniony (negatyw)</label>'
                    + '<label class="flex items-center gap-2 text-sm"><input type="checkbox" data-f="strip"' + (t.strip ? ' checked' : '') + ' class="rounded border-gray-300 text-brand focus:ring-brand"> Niski pasek</label>'
                    + '<span class="ml-auto flex gap-1">'
                    + '<button type="button" data-act="up" class="rounded p-2 text-muted hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>'
                    + '<button type="button" data-act="down" class="rounded p-2 text-muted hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>'
                    + '<button type="button" data-act="del" class="rounded p-2 text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń kafelek"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>'
                    + '</span></div></div>';
            }

            function collect() {
                return Array.prototype.map.call(rows.querySelectorAll('.tb-row'), function (r) {
                    if (r.dataset.section) { return { heading: r.querySelector('[data-f="heading"]').value.trim() }; }
                    var g = function (f) { return r.querySelector('[data-f="' + f + '"]'); };
                    return { label: g('label').value.trim(), url: g('url').value.trim(), icon: g('icon').value.trim(), color: g('color').value,
                        cols: parseInt(g('cols').value, 10) || 1, is_negative: g('is_negative').checked, strip: g('strip').checked };
                });
            }

            function render(tiles) {
                rows.innerHTML = tiles.map(rowHtml).join('');
            }

            function showError(msg) { errEl.textContent = msg; errEl.classList.toggle('hidden', ! msg); }

            function close(restore) {
                root.classList.add('hidden'); root.classList.remove('flex');
                document.body.style.overflow = '';
                if (restore !== false && state && state.trigger && state.trigger.focus) { state.trigger.focus(); }
                state = null;
            }

            function init() {
                root = $('tb-root'); dlg = $('tb-dialog'); rows = $('tb-rows'); nameEl = $('tb-name'); errEl = $('tb-error');
                $('tb-close').addEventListener('click', function () { close(); });
                $('tb-cancel').addEventListener('click', function () { close(); });
                $('tb-add-section').addEventListener('click', function () {
                    var t = collect(); t.push({ heading: '' }); render(t);
                    var last = rows.querySelector('.tb-row:last-child [data-f="heading"]'); if (last) { last.focus(); }
                });
                $('tb-add').addEventListener('click', function () {
                    var t = collect(); t.push({ label: '', url: '', icon: 'bi-lightning', color: '#1e6dff', cols: 1 }); render(t);
                    var last = rows.querySelector('.tb-row:last-child [data-f="label"]'); if (last) { last.focus(); }
                });
                rows.addEventListener('click', function (e) {
                    var b = e.target.closest('[data-act]'); if (! b) { return; }
                    var row = b.closest('.tb-row'); var i = parseInt(row.dataset.i, 10); var t = collect();
                    if (b.dataset.act === 'del') { t.splice(i, 1); }
                    if (b.dataset.act === 'up' && i > 0) { var x = t.splice(i, 1)[0]; t.splice(i - 1, 0, x); }
                    if (b.dataset.act === 'down' && i < t.length - 1) { var y = t.splice(i, 1)[0]; t.splice(i + 1, 0, y); }
                    render(t);
                });
                $('tb-save').addEventListener('click', save);
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
                var tiles = collect().filter(function (t) { return t.heading !== undefined ? t.heading !== '' : (t.label || t.url); });
                var name = nameEl.value.trim() || 'Zestaw kafelków';
                if (! tiles.length) { showError('Dodaj przynajmniej jeden kafelek (etykieta i adres).'); return; }
                if (! tiles.some(function (t) { return t.heading === undefined; })) { showError('Dodaj przynajmniej jeden kafelek (etykieta i adres).'); return; }
                for (var i = 0; i < tiles.length; i++) {
                    if (tiles[i].heading !== undefined) { continue; }
                    if (! tiles[i].label || ! tiles[i].url) { showError('Kafelek ' + (i + 1) + ': uzupełnij etykietę i adres.'); return; }
                    if (! /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(tiles[i].url)) { showError('Kafelek ' + (i + 1) + ': adres musi zaczynać się od https://, /, #, mailto: lub tel:.'); return; }
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
                    showError(''); nameEl.value = '';
                    root.classList.remove('hidden'); root.classList.add('flex'); document.body.style.overflow = 'hidden';
                    $('tb-title').textContent = state.id ? 'Edytuj zestaw kafelków' : 'Nowy zestaw kafelków';
                    if (state.id) {
                        rows.innerHTML = '<p class="text-sm text-muted">Ładowanie…</p>';
                        fetch(baseUrl + '/' + state.id, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
                            nameEl.value = d.name || ''; render(d.tiles && d.tiles.length ? d.tiles : [{}]); nameEl.focus();
                        }).catch(function () { showError('Nie udało się wczytać zestawu.'); });
                    } else {
                        render([{ label: '', url: '', icon: 'bi-lightning', color: '#1e6dff', cols: 1 }]); nameEl.focus();
                    }
                },
            };
        })();
    </script>
@endonce
