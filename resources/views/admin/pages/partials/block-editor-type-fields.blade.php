{{--
    Edytor blokowy: okno „Pola typu strony” — te same sekcje pól co w formularzu
    strony (App\Support\PageTypeFields::render), zapisywane razem z blokami.
    Okno otwiera przycisk w zakładce strony panelu bocznego (resources/js/block-editor.js),
    który przy zapisie zbiera pola formularza #be-type-fields-form jako `fields`.
    Repeatery: type_data (data-td-repeater — skrypt w type-data-fields), ogólny
    data-repeater (FAQ, marka) i terminy harmonogramu — jak w formularzu strony.
--}}
<dialog id="be-type-fields" class="be-dialog" aria-labelledby="be-type-fields-title">
    <form id="be-type-fields-form" method="dialog" novalidate>
        <div class="be-dialog-head">
            <h2 id="be-type-fields-title">Pola typu strony: {{ $typeName }}</h2>
            <button type="submit" class="be-btn be-btn--icon" aria-label="Zamknij okno pól typu strony"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <p class="be-muted">Te pola wypełniają część strony charakterystyczną dla jej typu. Zmiany zapiszesz przyciskiem „Zapisz” edytora — razem z treścią.</p>
        <div class="be-type-fields-body">
            {!! $typeFieldsHtml !!}
        </div>
        <div class="be-dialog-actions">
            <button type="submit" class="be-btn be-btn--primary">Gotowe</button>
        </div>
    </form>
</dialog>

<script>
    (function () {
        const root = document.getElementById('be-type-fields-form');
        if (!root) return;

        // Ogólny repeater (FAQ, sekcje marki): dodawanie z <template>, usuwanie, przenoszenie z przeliczeniem indeksów.
        root.querySelectorAll('[data-repeater]').forEach(function (rep) {
            const rows = rep.querySelector('[data-repeater-rows]');
            const template = rep.querySelector('[data-repeater-template]');
            const addBtn = rep.querySelector('[data-repeater-add]');
            if (!rows || !template) return;
            let next = rows.querySelectorAll('[data-repeater-row]').length;
            const reindex = function () {
                rows.querySelectorAll('[data-repeater-row]').forEach(function (row, n) {
                    row.querySelectorAll('[name]').forEach(function (el) { el.name = el.name.replace(/^([^\[]+)\[[^\]]*\]/, '$1[' + n + ']'); });
                });
            };
            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    const wrap = document.createElement('div');
                    wrap.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(next++)).trim();
                    const row = wrap.firstElementChild;
                    rows.appendChild(row);
                    row.querySelector('input, textarea')?.focus();
                });
            }
            rep.addEventListener('click', function (e) {
                const remove = e.target.closest('[data-repeater-remove]');
                if (remove) { remove.closest('[data-repeater-row]')?.remove(); addBtn?.focus(); return; }
                const move = e.target.closest('[data-repeater-move]');
                const row = move?.closest('[data-repeater-row]');
                if (!row) return;
                if (move.dataset.repeaterMove === 'up' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
                else if (move.dataset.repeaterMove === 'down' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
                reindex();
                move.focus();
            });
        });

        // Terminy harmonogramu.
        const schedule = root.querySelector('[data-schedule-fields]');
        if (schedule) {
            const rows = schedule.querySelector('[data-schedule-rows]');
            const template = schedule.querySelector('[data-schedule-template]');
            const addBtn = schedule.querySelector('[data-schedule-add]');
            let next = rows.querySelectorAll('[data-schedule-row]').length;
            const addRow = function (focus) {
                const wrap = document.createElement('div');
                wrap.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(next++)).trim();
                const row = wrap.firstElementChild;
                rows.appendChild(row);
                if (focus) row.querySelector('input')?.focus();
            };
            addBtn?.addEventListener('click', function () { addRow(true); });
            schedule.addEventListener('click', function (e) {
                const remove = e.target.closest('[data-schedule-remove]');
                if (remove) { remove.closest('[data-schedule-row]')?.remove(); addBtn?.focus(); }
            });
            if (!rows.querySelector('[data-schedule-row]')) addRow(false);
            const toggle = schedule.querySelector('[data-schedule-pending-toggle]');
            const box = schedule.querySelector('[data-schedule-pending-box]');
            const sync = function () { box.classList.toggle('bg-amber-50', toggle.checked); box.classList.toggle('bg-gray-50', !toggle.checked); };
            if (toggle && box) { toggle.addEventListener('change', sync); sync(); }
        }

        // FAQ: lekki WYSIWYG (TinyMCE) w odpowiedziach, jak w formularzu — ładowany przy pierwszym otwarciu okna.
        const faq = root.querySelector('[data-faq-fields]');
        if (faq) {
            const changed = function (ed) { ed.save(); ed.targetElm.dispatchEvent(new Event('input', { bubbles: true })); };
            const initOne = function (ta) {
                if (ta.dataset.mceReady) return;
                ta.dataset.mceReady = '1';
                window.tinymce.init({
                    target: ta, license_key: 'gpl', ui_container: '#be-type-fields', // okna TinyMCE w oknie modalnym (reszta strony jest wtedy nieaktywna)
                    menubar: false, statusbar: false, branding: false, convert_urls: false, height: 200,
                    plugins: 'link lists autolink', toolbar: 'bold italic | bullist numlist | link unlink | removeformat',
                    setup: function (ed) { ed.on('change keyup', function () { changed(ed); }); },
                });
            };
            const initAll = function () { faq.querySelectorAll('textarea[data-faq-answer]').forEach(initOne); };
            let loading = false;
            document.getElementById('be-type-fields').addEventListener('be-open', function () {
                if (window.tinymce) return initAll();
                if (loading) return;
                loading = true;
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
                s.referrerPolicy = 'origin';
                s.onload = function () {
                    initAll();
                    new MutationObserver(initAll).observe(faq.querySelector('[data-repeater-rows]'), { childList: true });
                };
                document.head.appendChild(s);
            });

            const pageLink = faq.querySelector('[data-faq-page-link]');
            pageLink?.addEventListener('change', function () {
                const url = this.value;
                const title = this.selectedOptions[0] ? this.selectedOptions[0].dataset.title : url;
                this.selectedIndex = 0;
                const ed = window.tinymce?.activeEditor;
                if (!url || !ed) return;
                if (ed.targetElm?.matches('textarea[data-faq-answer]')) { ed.insertContent('<a href="' + url + '">' + title + '</a>'); changed(ed); }
                else alert('Najpierw kliknij w treść odpowiedzi, w której chcesz wstawić link.');
            });
        }
    })();
</script>
