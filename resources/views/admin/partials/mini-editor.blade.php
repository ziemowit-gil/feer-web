{{--
    Lekki edytor WYSIWYG (TinyMCE z CDN, ten sam co edytor klasyczny) dla krótkich pól tekstowych, np. opisów warsztatu.
    Parametry: $id (id textarea), $mode = 'inline' (tylko pogrubienie i kursywa, Enter = nowa linia) albo 'rich'
    (akapity, nagłówki H2/H3, listy, pogrubienie, kursywa, link), $height (px). Textarea zostaje w formularzu —
    bez JS albo bez CDN pole działa jak zwykłe. HTML czyści serwer (App\Support\SafeHtml) przy zapisie i wyświetlaniu.
--}}
@php
    $mode = ($mode ?? 'rich') === 'inline' ? 'inline' : 'rich';
    $height = (int) ($height ?? ($mode === 'inline' ? 160 : 360));
@endphp
<script>
    (function () {
        var id = @js($id);
        var mode = @js($mode);
        function init() {
            var el = document.getElementById(id);
            if (!el || !window.tinymce || window.tinymce.get(id)) return;
            var maxLength = parseInt(el.getAttribute('data-text-max') || '0', 10);
            window.tinymce.init({
                selector: '#' + id,
                license_key: 'gpl',
                height: @js($height),
                menubar: false,
                branding: false,
                promotion: false,
                convert_urls: false,
                plugins: mode === 'inline' ? 'wordcount' : 'lists link autolink wordcount',
                toolbar: mode === 'inline'
                    ? 'undo redo | bold italic | removeformat'
                    : 'undo redo | blocks | bold italic | bullist numlist | link | removeformat',
                block_formats: 'Akapit=p; Nagłówek 2=h2; Nagłówek 3=h3',
                valid_elements: mode === 'inline'
                    ? 'p,br,strong/b,em/i'
                    : 'p,br,strong/b,em/i,h2,h3,ul,ol,li,a[href|title],blockquote',
                forced_root_block: 'p',
                link_default_target: '',
                link_target_list: false,
                paste_as_text: mode === 'inline',
                statusbar: true,
                elementpath: false,
                setup: function (editor) {
                    // Etykieta pola i opis (aria-describedby) przechodzą na obszar edycji — czytniki ekranu je odczytają.
                    editor.on('init', function () {
                        var body = editor.getBody();
                        var label = document.querySelector('label[for="' + id + '"]');
                        if (label) body.setAttribute('aria-label', label.textContent.replace('*', '').trim());
                        if (el.getAttribute('aria-describedby')) {
                            var hint = document.getElementById(el.getAttribute('aria-describedby'));
                            if (hint) body.setAttribute('aria-description', hint.textContent.trim());
                        }
                        if (label) label.addEventListener('click', function () { editor.focus(); });
                    });
                    editor.on('change input undo redo', function () {
                        editor.save();
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    if (maxLength > 0) {
                        editor.on('keydown', function (e) {
                            var text = editor.getContent({ format: 'text' }).trim();
                            if (text.length >= maxLength && e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey) e.preventDefault();
                        });
                    }
                },
            });
        }
        if (window.tinymce) { init(); return; }
        window.__tinymceInitQueue = window.__tinymceInitQueue || [];
        window.__tinymceInitQueue.push(init);
        if (!window.__tinymceLoading) {
            window.__tinymceLoading = true;
            var script = document.createElement('script');
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
