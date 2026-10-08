{{--
    Kreator bloków treści (przyciski CTA i akordeon) w oknie dialogowym — wspólny dla obu edytorów (TinyMCE i CKEditor).
    API (window.BlockBuilder):
        BlockBuilder.open({ type: 'cta'|'accordion', id: 5 | null, onSave: (block) => …, trigger: element|null })
    Zapis przez JSON (admin.bloki-tresci.*); po zapisie wywołuje onSave({id, type, name}).
    Style (.tb-*) pochodzą z kreatora kafelków (admin.partials.tiles-builder), dołączanego razem z edytorem.
    Dostępność: role="dialog" + aria-modal, pętla Tab, Esc zamyka, etykiety pól, przyciski z aria-pressed, komunikaty role="alert".
--}}
@once
    <div id="bb-root" class="fixed inset-0 hidden items-start justify-center overflow-y-auto bg-black/50 p-3 sm:p-6" style="z-index:2147483000">
        <div id="bb-dialog" role="dialog" aria-modal="true" aria-labelledby="bb-title" class="w-full rounded-2xl bg-white shadow-2xl" style="max-width:56rem">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <h2 id="bb-title" class="text-lg font-bold text-ink">Blok treści</h2>
                <button type="button" id="bb-close" class="flex h-10 w-10 flex-none items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i><span class="sr-only">Zamknij kreator</span>
                </button>
            </div>
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="bb-name" class="mb-1 block text-sm font-bold text-ink">Nazwa bloku <span class="font-normal text-muted">(tylko dla Ciebie)</span></label>
                    <input type="text" id="bb-name" maxlength="120" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div id="bb-opts"></div>
                <ol id="bb-rows" class="space-y-2" role="list"></ol>
                <button type="button" id="bb-add" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"></button>
 <div id="bb-hints" class="hidden rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900" role="status" aria-live="polite"></div>
                <p id="bb-error" role="alert" class="hidden rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></p>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4">
                <p class="text-xs text-muted">Blok wstawi się w treść; edytujesz go ponownie z menu „Wstaw”.</p>
                <div class="flex gap-2">
                    <button type="button" id="bb-cancel" class="rounded-lg px-4 py-2 text-sm font-bold text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</button>
                    <button type="button" id="bb-save" class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz i wstaw</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var storeUrl = @js(route('admin.bloki-tresci.store'));
            var baseUrl = @js(url('admin/bloki-tresci'));
            var csrf = @js(csrf_token());
            var palette = [['#1e6dff', 'Niebieski'], ['#1d1d1a', 'Grafit'], ['#ea8f00', 'Pomarańcz'], ['#cbd5e7', 'Jasnoniebieski']];
            var root, dlg, rowsEl, optsEl, nameEl, errEl, addBtn;
            var state = null, d = null; // d: dane bloku

            function $(id) { return document.getElementById(id); }
            function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
            function listKey() { return state.type === 'cta' ? 'buttons' : 'items'; }
            var variants = [['blue', 'Niebieska', '#1e6dff'], ['gold', 'Złota', '#f2b705'], ['red', 'Czerwona', '#b91c1c'], ['green', 'Zielona', '#166534']];
            var icons = [['exclamation', 'fa-exclamation', 'Wykrzyknik'], ['info', 'fa-info', 'Informacja'], ['coins', 'fa-coins', 'Monety'], ['warning', 'fa-triangle-exclamation', 'Ostrzeżenie'], ['check', 'fa-check', 'Potwierdzenie']];
            function blank() {
                return state.type === 'cta' ? { label: '', url: '', color: '#1e6dff', filled: true, new_tab: false } : { q: '', a: '' };
            }
            function defaults() {
                if (state.type === 'callout') { return { title: '', text: '', variant: 'blue', negative: false, icon: 'exclamation' }; }
                return state.type === 'cta' ? { align: 'left', buttons: [blank()] } : { title: '', exclusive: false, first_open: false, items: [blank()] };
            }
            function seg(field, opts, cur) {
                return '<span class="tb-seg" role="group">' + opts.map(function (o) { return '<button type="button" data-seg="' + field + '" data-val="' + o[0] + '" aria-pressed="' + (String(cur) === String(o[0]) ? 'true' : 'false') + '">' + o[1] + '</button>'; }).join('') + '</span>';
            }
            function check(field, label, val) {
                return '<label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem"><input type="checkbox" data-opt="' + field + '"' + (val ? ' checked' : '') + ' class="rounded border-gray-300 text-brand focus:ring-brand"> ' + label + '</label>';
            }
            function ibtn(act, icon, label, disabled) { return '<button type="button" class="tb-ibtn" data-act="' + act + '" aria-label="' + label + '"' + (disabled ? ' disabled' : '') + '><i class="fa-solid ' + icon + '" aria-hidden="true"></i></button>'; }

            function renderOpts() {
                if (state.type === 'callout') {
                    var sw = variants.map(function (v) { return '<button type="button" class="tb-sw" data-variant="' + v[0] + '" aria-pressed="' + (d.variant === v[0] ? 'true' : 'false') + '" aria-label="Kolor ramki: ' + v[1] + '" title="' + v[1] + '" style="background:' + v[2] + '"></button>'; }).join('');
                    var ic = icons.map(function (i) { return '<button type="button" class="tb-ibtn" data-icon="' + i[0] + '" aria-pressed="' + (d.icon === i[0] ? 'true' : 'false') + '" aria-label="Ikona: ' + i[2] + '" title="' + i[2] + '" style="border:2px solid ' + (d.icon === i[0] ? '#1d1d1a' : '#d1d5db') + '"><i class="fa-solid ' + i[1] + '" aria-hidden="true"></i></button>'; }).join('');
                    optsEl.innerHTML = '<div style="display:grid;gap:.9rem">'
                        + '<div><label class="tb-label" for="bb-co-title">Tytuł (opcjonalnie)</label><input id="bb-co-title" class="tb-input" data-opt="title" maxlength="160" value="' + esc(d.title) + '"></div>'
                        + '<div><label class="tb-label" for="bb-co-text">Treść ramki</label><textarea id="bb-co-text" class="tb-input" data-opt="text" rows="4" maxlength="1500">' + esc(d.text) + '</textarea></div>'
                        + '<div style="display:flex;flex-wrap:wrap;gap:1.25rem;align-items:flex-end">'
                        + '<div><span class="tb-label">Kolor</span><span style="display:flex;gap:.5rem">' + sw + '</span></div>'
                        + '<div><span class="tb-label">Wygląd</span>' + seg('negative', [[0, 'Obwódka'], [1, 'Negatyw']], d.negative ? 1 : 0) + '</div>'
                        + '<div><span class="tb-label">Ikona</span><span style="display:flex;gap:.35rem">' + ic + '</span></div></div>'
                        + '<div id="bb-co-prev"></div></div>';
                    renderCalloutPreview();
                    return;
                }
                if (state.type === 'cta') {
                    optsEl.innerHTML = '<span class="tb-label">Wyrównanie przycisków</span>' + seg('align', [['left', 'Do lewej'], ['center', 'Wyśrodkowane'], ['right', 'Do prawej']], d.align);
                } else {
                    optsEl.innerHTML = '<div style="display:grid;gap:.75rem"><div><label class="tb-label" for="bb-title-in">Nagłówek akordeonu (opcjonalnie)</label>'
                        + '<input id="bb-title-in" class="tb-input" data-opt="title" maxlength="160" value="' + esc(d.title) + '" placeholder="np. Najczęstsze pytania"></div>'
                        + '<div style="display:flex;flex-wrap:wrap;gap:1.25rem">' + check('exclusive', 'Otwieraj jedną sekcję naraz', d.exclusive) + check('first_open', 'Pierwsza sekcja otwarta na start', d.first_open) + '</div></div>';
                }
            }
            function rowHtml(t, i) {
                var list = d[listKey()], head, body;
                if (state.type === 'cta') {
                    var col = t.color || '#1e6dff';
                    var sw = palette.map(function (p) { return '<button type="button" class="tb-sw" data-color="' + p[0] + '" aria-pressed="' + (col.toLowerCase() === p[0] ? 'true' : 'false') + '" aria-label="Kolor: ' + p[1] + '" title="' + p[1] + '" style="background:' + p[0] + '"></button>'; }).join('');
                    return '<li class="tb-card" data-i="' + i + '" style="padding:.75rem;display:grid;gap:.75rem">'
                        + '<div style="display:grid;gap:.5rem;grid-template-columns:minmax(0,2fr) minmax(0,3fr) auto;align-items:end">'
                        + '<div><label class="tb-label" for="bb-l-' + i + '">Etykieta</label><input id="bb-l-' + i + '" class="tb-input" data-f="label" maxlength="80" value="' + esc(t.label) + '" placeholder="np. Zapisz się"></div>'
                        + '<div><label class="tb-label" for="bb-u-' + i + '">Adres</label><input id="bb-u-' + i + '" class="tb-input" data-f="url" value="' + esc(t.url) + '" placeholder="https://… albo /strona"></div>'
                        + '<span style="display:flex">' + ibtn('up', 'fa-arrow-up', 'Przesuń wyżej', i === 0) + ibtn('down', 'fa-arrow-down', 'Przesuń niżej', i === list.length - 1) + ibtn('del', 'fa-trash', 'Usuń przycisk', list.length === 1) + '</span></div>'
                        + '<div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end">'
                        + '<div><span class="tb-label">Kolor</span><span style="display:flex;gap:.5rem">' + sw + '</span></div>'
                        + '<div><span class="tb-label">Wygląd</span>' + seg('filled', [[0, 'Obwódka'], [1, 'Wypełniony']], t.filled ? 1 : 0) + '</div>'
                        + '<label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem"><input type="checkbox" data-f="new_tab"' + (t.new_tab ? ' checked' : '') + ' class="rounded border-gray-300 text-brand focus:ring-brand"> Nowa karta</label></div></li>';
                }
                return '<li class="tb-card" data-i="' + i + '" style="padding:.75rem;display:grid;gap:.5rem">'
                    + '<div style="display:flex;align-items:flex-end;gap:.5rem"><div style="flex:1;min-width:0"><label class="tb-label" for="bb-q-' + i + '">Pytanie / tytuł sekcji ' + (i + 1) + '</label><input id="bb-q-' + i + '" class="tb-input" data-f="q" maxlength="300" value="' + esc(t.q) + '"></div>'
                    + '<span style="display:flex">' + ibtn('up', 'fa-arrow-up', 'Przesuń wyżej', i === 0) + ibtn('down', 'fa-arrow-down', 'Przesuń niżej', i === list.length - 1) + ibtn('del', 'fa-trash', 'Usuń sekcję', list.length === 1) + '</span></div>'
                    + '<div><label class="tb-label" for="bb-a-' + i + '">Odpowiedź / treść</label><textarea id="bb-a-' + i + '" class="tb-input" data-f="a" rows="3" maxlength="5000">' + esc(t.a) + '</textarea></div></li>';
            }
            function updateHints() {
                var el = $('bb-hints'), list = d[listKey()], hints = [];
                if (state.type === 'callout' || ! window.__a11yHints || ! window.__renderA11yHints) { return; }
                if (state.type === 'cta') {
                    hints = window.__a11yHints(list.map(function (b) { return b.label; }), list.map(function (b) { return b.url; }));
                } else {
                    list.forEach(function (s, i) { if ((s.q || '').trim().length > 140) { hints.push('Sekcja ' + (i + 1) + ': tytuł jest bardzo długi — w akordeonie tytuł powinien być krótką frazą (to on jest przyciskiem).'); } });
                    if (list.length > 20) { hints.push('Akordeon ma ponad 20 sekcji — rozważ podział na kilka grup z nagłówkami.'); }
                }
                window.__renderA11yHints(el, hints);
            }
            function renderCalloutPreview() {
                var el = $('bb-co-prev'); if (! el) { return; }
                var col = { blue: ['#1e6dff', '#1e6dff', '#fff'], gold: ['#a16207', '#f2b705', '#1d1d1a'], red: ['#b91c1c', '#b91c1c', '#fff'], green: ['#166534', '#166534', '#fff'] }[d.variant] || ['#1e6dff', '#1e6dff', '#fff'];
                var neg = !! d.negative, ico = icons.filter(function (i) { return i[0] === d.icon; })[0] || icons[0];
                el.innerHTML = '<span class="tb-label">Podgląd</span><div style="position:relative;padding:.9rem 4.5rem .9rem 1rem;border:2px solid ' + (neg ? col[1] : col[0]) + ';border-radius:.5rem;background:' + (neg ? col[1] : '#fff') + ';color:' + (neg ? col[2] : '#1d1d1a') + ';font-weight:600">'
                    + (d.title ? '<div style="font-weight:800">' + esc(d.title) + '</div>' : '') + esc(d.text || 'Treść ramki…')
                    + '<span style="position:absolute;top:50%;right:.9rem;transform:translateY(-50%);width:2.3rem;height:2.3rem;border:2px solid ' + (neg ? col[2] : col[0]) + ';border-radius:9999px;background:' + (neg ? 'transparent' : '#fff') + ';color:' + (neg ? col[2] : col[0]) + ';display:flex;align-items:center;justify-content:center"><i class="fa-solid ' + ico[1] + '" aria-hidden="true"></i></span></div>';
            }
            function renderRows() { if (state.type === 'callout') { rowsEl.innerHTML = ''; addBtn.style.display = 'none'; return; } addBtn.style.display = ''; rowsEl.innerHTML = d[listKey()].map(rowHtml).join(''); updateHints(); }
            function showError(msg) { errEl.textContent = msg; errEl.classList.toggle('hidden', ! msg); }

            function close(restore) {
                root.classList.add('hidden'); root.classList.remove('flex');
                document.body.style.overflow = '';
                if (restore !== false && state && state.trigger && state.trigger.focus) { state.trigger.focus(); }
                state = null;
            }

            function init() {
                root = $('bb-root'); dlg = $('bb-dialog'); rowsEl = $('bb-rows'); optsEl = $('bb-opts'); nameEl = $('bb-name'); errEl = $('bb-error'); addBtn = $('bb-add');
                document.body.appendChild(root);
                $('bb-close').addEventListener('click', function () { close(); });
                $('bb-cancel').addEventListener('click', function () { close(); });
                addBtn.addEventListener('click', function () { d[listKey()].push(blank()); renderRows(); var last = rowsEl.querySelector('li:last-child input'); if (last) { last.focus(); } });
                $('bb-save').addEventListener('click', save);

                optsEl.addEventListener('input', function (e) { var f = e.target.dataset.opt; if (f === 'title' || f === 'text') { d[f] = e.target.value; if (state.type === 'callout') { renderCalloutPreview(); } } });
                optsEl.addEventListener('change', function (e) { var f = e.target.dataset.opt; if (f && e.target.type === 'checkbox') { d[f] = e.target.checked; } });
                optsEl.addEventListener('click', function (e) {
                    var vb = e.target.closest('[data-variant]'); if (vb) { d.variant = vb.dataset.variant; renderOpts(); var a1 = optsEl.querySelector('[data-variant="' + d.variant + '"]'); if (a1) { a1.focus(); } return; }
                    var ib = e.target.closest('[data-icon]'); if (ib) { d.icon = ib.dataset.icon; renderOpts(); var a2 = optsEl.querySelector('[data-icon="' + d.icon + '"]'); if (a2) { a2.focus(); } return; }
                    var s = e.target.closest('[data-seg]'); if (s && state.type === 'callout') { d[s.dataset.seg] = s.dataset.val === '1'; renderOpts(); var a3 = optsEl.querySelector('[data-seg="' + s.dataset.seg + '"][data-val="' + s.dataset.val + '"]'); if (a3) { a3.focus(); } return; } if (s) { d[s.dataset.seg] = s.dataset.val; renderOpts(); var again = optsEl.querySelector('[data-seg="' + s.dataset.seg + '"][data-val="' + s.dataset.val + '"]'); if (again) { again.focus(); } } });

                rowsEl.addEventListener('input', function (e) { var f = e.target.dataset.f; if (! f || e.target.type === 'checkbox') { return; } var i = parseInt(e.target.closest('li').dataset.i, 10); d[listKey()][i][f] = e.target.value; updateHints(); });
                rowsEl.addEventListener('change', function (e) { var f = e.target.dataset.f; if (f && e.target.type === 'checkbox') { var i = parseInt(e.target.closest('li').dataset.i, 10); d[listKey()][i][f] = e.target.checked; } });
                rowsEl.addEventListener('click', function (e) {
                    var li = e.target.closest('li[data-i]'); if (! li) { return; }
                    var i = parseInt(li.dataset.i, 10), list = d[listKey()], target = i;
                    var a = e.target.closest('[data-act]');
                    if (a) {
                        var act = a.dataset.act;
                        if (act === 'del' && list.length > 1) { list.splice(i, 1); target = Math.min(i, list.length - 1); }
                        if (act === 'up' && i > 0) { var x = list.splice(i, 1)[0]; list.splice(i - 1, 0, x); target = i - 1; }
                        if (act === 'down' && i < list.length - 1) { var y = list.splice(i, 1)[0]; list.splice(i + 1, 0, y); target = i + 1; }
                        renderRows();
                        var again = rowsEl.querySelector('li[data-i="' + target + '"] [data-act="' + act + '"]:not([disabled])') || rowsEl.querySelector('li[data-i="' + target + '"] input');
                        if (again) { again.focus(); }
                        return;
                    }
                    var sw = e.target.closest('[data-color]'); if (sw) { list[i].color = sw.dataset.color; renderRows(); var s2 = rowsEl.querySelector('li[data-i="' + i + '"] [data-color="' + sw.dataset.color + '"]'); if (s2) { s2.focus(); } return; }
                    var sg = e.target.closest('[data-seg]'); if (sg) { list[i][sg.dataset.seg] = sg.dataset.val === '1'; renderRows(); var s3 = rowsEl.querySelector('li[data-i="' + i + '"] [data-seg="' + sg.dataset.seg + '"][data-val="' + sg.dataset.val + '"]'); if (s3) { s3.focus(); } }
                });

                root.addEventListener('click', function (e) { if (e.target === root) { close(); } });
                root.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { e.stopPropagation(); close(); }
                    if (e.key === 'Tab') {
                        var f = Array.prototype.filter.call(dlg.querySelectorAll('button, input, select, textarea'), function (n) { return n.offsetParent !== null && ! n.disabled; });
                        if (! f.length) { return; }
                        if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
                        else if (! e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
                    }
                });
            }

            function save() {
                var name = nameEl.value.trim() || ({ cta: 'Przyciski CTA', accordion: 'Akordeon', callout: 'Ramka informacyjna' }[state.type]);
                var list = d[listKey()] || [];
                var payload;
                if (state.type === 'callout') {
                    if (! (d.text || '').trim()) { showError('Wpisz treść ramki.'); return; }
                    payload = { title: (d.title || '').trim(), text: d.text.trim(), variant: d.variant || 'blue', negative: !! d.negative, icon: d.icon || 'exclamation' };
                } else if (state.type === 'cta') {
                    for (var i = 0; i < list.length; i++) {
                        if (! list[i].label.trim() || ! list[i].url.trim()) { showError('Przycisk ' + (i + 1) + ': uzupełnij etykietę i adres.'); return; }
                        if (! /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(list[i].url.trim())) { showError('Przycisk ' + (i + 1) + ': adres musi zaczynać się od https://, /, #, mailto: lub tel:.'); return; }
                    }
                    payload = { align: d.align || 'left', buttons: list.map(function (b) { return { label: b.label.trim(), url: b.url.trim(), color: b.color || '#1e6dff', filled: !! b.filled, new_tab: !! b.new_tab }; }) };
                } else {
                    for (var j = 0; j < list.length; j++) { if (! list[j].q.trim() || ! list[j].a.trim()) { showError('Sekcja ' + (j + 1) + ': uzupełnij tytuł i treść.'); return; } }
                    payload = { title: (d.title || '').trim(), exclusive: !! d.exclusive, first_open: !! d.first_open, items: list.map(function (s) { return { q: s.q.trim(), a: s.a.trim() }; }) };
                }
                showError('');
                var id = state.id;
                fetch(id ? baseUrl + '/' + id : storeUrl, {
                    method: id ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ type: state.type, name: name, data: payload }),
                }).then(function (r) { return r.ok ? r.json() : r.json().then(function (j) { throw new Error((j && j.message) || 'Błąd zapisu'); }); })
                  .then(function (b) { var cb = state && state.onSave; close(false); if (cb) { cb(b); } })
                  .catch(function (e) { showError('Nie udało się zapisać bloku: ' + e.message); });
            }

            window.BlockBuilder = {
                open: function (opts) {
                    if (! root) { init(); }
                    state = { type: opts.type, id: opts.id || null, onSave: opts.onSave, trigger: opts.trigger || document.activeElement };
                    showError(''); nameEl.value = '';
                    root.classList.remove('hidden'); root.classList.add('flex'); document.body.style.overflow = 'hidden';
                    var label = { cta: 'przyciski CTA', accordion: 'akordeon', callout: 'ramka informacyjna' }[state.type];
                    $('bb-title').textContent = (state.id ? 'Edytuj: ' : 'Nowy blok: ') + label;
                    addBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i> ' + (state.type === 'cta' ? 'Dodaj przycisk' : 'Dodaj sekcję');
                    $('bb-hints').classList.add('hidden');
                    d = defaults(); renderOpts(); renderRows();
                    if (state.type === 'callout') { $('bb-hints').classList.add('hidden'); }
                    if (state.id) {
                        fetch(baseUrl + '/' + state.id, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (b) {
                            nameEl.value = b.name || ''; var base = defaults(); d = Object.assign(base, b.data || {});
                            if (state.type !== 'callout' && (! d[listKey()] || ! d[listKey()].length)) { d[listKey()] = [blank()]; }
                            renderOpts(); renderRows(); nameEl.focus();
                        }).catch(function () { showError('Nie udało się wczytać bloku.'); });
                    } else { nameEl.focus(); }
                },
            };
        })();
    </script>
@endonce
