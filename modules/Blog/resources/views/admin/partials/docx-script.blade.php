{{--
    Import z Worda w edytorze wpisu: plik .docx → POST admin.editor.docx.import (HTML z poprawioną strukturą nagłówków,
    zdjęcia do biblioteki) → w trybie wizualnym dopisanie do TinyMCE/CKEditor, w Markdown → POST html-na-markdown
    i dopisanie na końcu pola. Komunikat w regionie role=status, błędy w alert.
--}}
<script>
(function () {
    var input = document.getElementById('blog-docx-input');
    var status = document.getElementById('blog-docx-status');
    if (!input) return;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    var importUrl = @json($docxUrl);
    var toMarkdownUrl = @json(route('admin.wiem-feer.html-na-markdown'));
    var target = 'markdown';
    var lastButton = null;

    document.querySelectorAll('[data-docx-import]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.hasAttribute('data-docx-locked')) {
                window.alert('Import z Worda jest dostępny w płatnej wersji weCMS. Treść możesz wkleić z Worda (Ctrl+V) do edytora wizualnego.');
                return;
            }
            target = btn.getAttribute('data-docx-import');
            lastButton = btn;
            input.click();
        });
    });

    function say(text) { if (status) status.textContent = text; }
    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        }).then(function (r) { if (!r.ok) throw new Error(); return r.json(); });
    }

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        input.value = '';
        if (!file) return;
        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('docx', file);
        if (lastButton) lastButton.disabled = true;
        say('Importuję plik „' + file.name + '”…');

        fetch(importUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (d) { if (!r.ok || d.error) throw new Error(d.error || ''); return d; }); })
            .then(function (data) {
                var html = data.html || '';
                if (target === 'html') {
                    var get = window['__getContent_editor-body'];
                    var set = window['__setContent_editor-body'];
                    if (!set) throw new Error('Edytor nie jest jeszcze gotowy.');
                    var current = get ? get() : '';
                    set(current ? current + '\n' + html : html);
                    return 'Wstawiono treść z pliku „' + file.name + '” na końcu wpisu.';
                }
                return postJson(toMarkdownUrl, { html: html }).then(function (res) {
                    var ta = document.getElementById('md-body');
                    var md = (res.markdown || '').trim();
                    ta.value = ta.value.trim() ? ta.value.replace(/\s+$/, '') + '\n\n' + md + '\n' : md + '\n';
                    ta.dispatchEvent(new Event('input', { bubbles: true }));
                    ta.focus();
                    return 'Wstawiono treść z pliku „' + file.name + '” na końcu wpisu (Markdown).';
                });
            })
            .then(function (msg) { say(msg + ' Zapisz wpis, aby ją zachować.'); })
            .catch(function (e) {
                say('');
                window.alert((e && e.message) ? e.message : 'Nie udało się zaimportować pliku .docx.');
            })
            .finally(function () { if (lastButton) { lastButton.disabled = false; lastButton.focus(); } });
    });
})();
</script>
