<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1024, initial-scale=1">
    <title>{{ $title }} — edytor newslettera</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">
    <script src="{{ asset('vendor/mosaico/rs/mosaico-libs-and-tinymce.min.js') }}?v=0.18.11"></script>
    <script src="{{ asset('vendor/mosaico/rs/mosaico.min.js') }}?v=0.18.11"></script>
    <link rel="stylesheet" href="{{ asset('vendor/mosaico/rs/mosaico-libs-and-tinymce.min.css') }}?v=0.18.11">
    <link rel="stylesheet" href="{{ asset('vendor/mosaico/rs/mosaico-material.min.css') }}?v=0.18.11">
    <style>
        :root { --nl-brand: #1E6DFF; --nl-brand-dark: #1752BF; --nl-ink: #1D1D1A; --nl-accent: #EA8F00; }
        html, body { height: 100%; margin: 0; }
        #nl-bar { position: fixed; top: 0; left: 0; right: 0; z-index: 10000; display: flex; align-items: center; gap: 10px; height: 46px; padding: 0 12px; background: var(--nl-ink); color: #fff; font: 700 13px Montserrat, Arial, sans-serif; box-sizing: border-box; }
        #nl-bar .title { flex: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-weight: 400; }
        #nl-bar button, #nl-bar a { display: inline-flex; align-items: center; gap: 6px; min-height: 32px; padding: 6px 12px; border: 2px solid transparent; border-radius: 6px; background: #fff; color: var(--nl-ink); font: inherit; cursor: pointer; text-decoration: none; }
        #nl-bar button.primary { background: var(--nl-brand); color: #fff; }
        #nl-bar button.primary:hover { background: var(--nl-brand-dark); }
        #nl-bar button:focus-visible, #nl-bar a:focus-visible { outline: 3px solid var(--nl-accent); outline-offset: 2px; }
        #nl-bar button[disabled] { opacity: .6; cursor: progress; }
        #nl-status { font-weight: 400; color: #CFCFCB; }
        #nl-status.err { color: #FFB4A9; font-weight: 700; }
        .mo-standalone #main-wysiwyg-area, #page { top: 46px !important; }
        #nl-contrast { display: none; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; background: var(--nl-accent); color: var(--nl-ink); font-weight: 800; }
        #nl-contrast.show { display: inline-flex; }
        /* modal wstawiania aktualności */
        #nl-news { position: fixed; inset: 0; z-index: 10001; display: none; align-items: center; justify-content: center; background: rgba(29,29,26,.6); font-family: Montserrat, Arial, sans-serif; }
        #nl-news.open { display: flex; }
        #nl-news .box { width: min(760px, 94vw); max-height: 86vh; overflow: auto; border-radius: 10px; background: #fff; color: var(--nl-ink); padding: 20px; }
        #nl-news h2 { margin: 0 0 12px; font-size: 18px; }
        #nl-news .row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        #nl-news input, #nl-news select { min-height: 40px; padding: 6px 10px; border: 2px solid #8E8E8A; border-radius: 6px; font: inherit; }
        #nl-news .item { display: grid; grid-template-columns: 72px 1fr auto; gap: 12px; align-items: center; padding: 10px; border: 2px solid #E5E7EB; border-radius: 8px; margin-bottom: 8px; }
        #nl-news .item img { width: 72px; height: 54px; object-fit: cover; border-radius: 4px; }
        #nl-news .item button { min-height: 36px; padding: 6px 12px; border: 0; border-radius: 6px; background: var(--nl-brand-dark); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        #nl-news .close { float: right; border: 0; background: none; font-size: 22px; cursor: pointer; }
    </style>
</head>
<body class="mo-standalone">
<div id="nl-bar" role="toolbar" aria-label="Pasek edytora">
    <a href="{{ $backUrl }}" aria-label="Wróć bez zapisu">← Wróć</a>
    <span class="title">{{ $title }}</span>
    <span id="nl-contrast" role="status" aria-live="polite"></span>
    @if (! empty($systemMail))
    <label for="nl-subject" class="sr-only">Temat wiadomości</label>
    <input id="nl-subject" type="text" value="{{ $mailSubject }}" placeholder="Temat wiadomości" style="min-height:32px;min-width:280px;padding:4px 10px;border-radius:6px;border:2px solid #fff;font:inherit;font-weight:400">
    <button type="button" id="nl-tags" title="Tagi do wstawienia w treści i w adresach przycisków">{ } Tagi</button>
    @else
    <button type="button" id="nl-insert-news" title="Wstaw aktualność z CMS jako blok artykułu">📰 Wstaw aktualność</button>
    @endif
    <button type="button" id="nl-preview" title="Podgląd w nowym oknie">Podgląd</button>
    <span id="nl-status" role="status" aria-live="polite"></span>
    <button type="button" id="nl-save" class="primary">Zapisz</button>
    <button type="button" id="nl-save-close" class="primary">Zapisz i zamknij</button>
</div>

@if (! empty($systemMail))
<div id="nl-tagbox" role="dialog" aria-modal="true" aria-labelledby="nl-tag-h" style="position:fixed;inset:0;z-index:10001;display:none;align-items:center;justify-content:center;background:rgba(29,29,26,.6);font-family:Montserrat,Arial,sans-serif">
    <div style="width:min(560px,94vw);border-radius:10px;background:#fff;color:#1D1D1A;padding:20px">
        <button type="button" aria-label="Zamknij" data-close-tags style="float:right;border:0;background:none;font-size:22px;cursor:pointer">×</button>
        <h2 id="nl-tag-h" style="margin:0 0 6px;font-size:18px">Tagi maila systemowego</h2>
        <p style="margin:0 0 12px;font-size:14px;color:#4A4A47">Wpisz tag w tekście albo w polu „Link” przycisku. Tag <code>@{{ {{ $systemMail['required'] }} }}</code> jest wymagany — bez niego używany jest wbudowany mail.</p>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            @foreach ($systemMail['tags'] as $tag => $desc)
            <tr><td style="padding:6px 8px;border-top:1px solid #E5E7EB"><code>@{{ {{ $tag }} }}</code></td><td style="padding:6px 8px;border-top:1px solid #E5E7EB">{{ $desc }}</td><td style="padding:6px 8px;border-top:1px solid #E5E7EB;text-align:right"><button type="button" data-copy="@{{ {{ $tag }} }}" style="border:1px solid #8E8E8A;border-radius:6px;background:#fff;padding:4px 10px;font:inherit;cursor:pointer">Kopiuj</button></td></tr>
            @endforeach
        </table>
    </div>
</div>
@endif
<div id="nl-news" role="dialog" aria-modal="true" aria-labelledby="nl-news-h">
    <div class="box">
        <button type="button" class="close" aria-label="Zamknij" data-close>×</button>
        <h2 id="nl-news-h">Wstaw aktualność</h2>
        <div class="row">
            <select id="nl-src" aria-label="Źródło">@foreach (\Modules\Newsletter\Services\ContentFeeder::SOURCES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <select id="nl-cat" aria-label="Kategoria"><option value="">Wszystkie kategorie</option>@foreach ($newsCategories ?? [] as $slug => $name)<option value="{{ $slug }}">{{ $name }}</option>@endforeach</select>
            <select id="nl-sort" aria-label="Sortowanie"><option value="newest">Od najnowszych</option><option value="category">Według kategorii</option><option value="oldest">Od najstarszych</option></select>
            <input id="nl-q" type="search" placeholder="Szukaj w tytule…" aria-label="Szukaj">
        </div>
        <div id="nl-list" aria-live="polite"></div>
    </div>
</div>

<script>
$(function () {
    if (!Mosaico.isCompatible()) { alert('Ta przeglądarka nie obsługuje edytora. Użyj aktualnego Chrome, Firefox, Safari lub Edge.'); return; }

    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var saveUrl = @json($saveUrl);
    @php $tplRel = 'vendor/mosaico/templates/' . $mosaicoTemplate . '/template-' . $mosaicoTemplate . '.html'; @endphp
    var templateUrl = @json(asset($tplRel) . '?v=' . (is_file(public_path($tplRel)) ? filemtime(public_path($tplRel)) : '1'));
    var saved = @json(['metadata' => $subject->editor_metadata, 'content' => $subject->editor_content]);
    var contentUrl = @json(route('admin.newsletter.content.items', ['source' => '__SRC__']));
    var viewModel = null, dirty = false;

    var plugins = [function (vm) {
        window.viewModel = viewModel = vm;
        vm.logoPath = ''; vm.logoUrl = @json($backUrl);
        var cmd = function (name, fn) { return { name: name, enabled: ko.observable(true), execute: fn }; };
        vm.save = cmd('Save', function () { doSave(false); });
        vm.test = cmd('Test', function () { @if ($subject instanceof \Modules\Newsletter\Models\NewsletterCampaign) window.open(@json(route('admin.newsletter.kampanie.preview', $subject)), '_blank'); @else alert('Podgląd z personalizacją jest dostępny w kampanii; tutaj użyj ikony podglądu edytora.'); @endif });
        vm.download = cmd('Download', function () { var f = document.createElement('form'); f.method = 'POST'; f.action = @json(route('admin.newsletter.mosaico.download')); f.target = '_blank';
            f.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="filename" value="newsletter"><textarea name="html"></textarea>'; f.querySelector('textarea').value = vm.exportHTML(); document.body.appendChild(f); f.submit(); f.remove(); });
        // kontrola kontrastu: nasłuch zmian modelu
        if (typeof vm.content === 'function') { ko.computed(function () { try { var json = vm.exportJSON(); dirty = true; checkContrast(json); } catch (e) {} }).extend({ rateLimit: 800 }); }
    }];

    var ok = Mosaico.init({
        imgProcessorBackend: @json(route('admin.newsletter.mosaico.image')),
        emailProcessorBackend: @json(route('admin.newsletter.mosaico.download')),
        titleToken: 'FEER',
        fileuploadConfig: { url: @json(route('admin.newsletter.mosaico.upload')), headers: { 'X-CSRF-TOKEN': csrf } },
        strings: @json(json_decode(file_get_contents(public_path('vendor/mosaico/rs/lang/mosaico-pl.json')), true)),
        template: (saved.metadata && saved.content) ? undefined : templateUrl,
        data: (saved.metadata && saved.content) ? { metadata: Object.assign({}, saved.metadata, { template: templateUrl }), content: saved.content } : undefined
    }, plugins);
    if (!ok) { document.getElementById('nl-status').textContent = 'Nie udało się uruchomić edytora.'; }

    // CSRF dla uploadów jQuery File Upload
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf } });

    function setStatus(msg, err) { var s = document.getElementById('nl-status'); s.textContent = msg; s.className = err ? 'err' : ''; }

    function doSave(close) {
        if (!viewModel) return;
        var btns = document.querySelectorAll('#nl-save, #nl-save-close'); btns.forEach(function (b) { b.disabled = true; });
        setStatus('Zapisuję…');
        var payload = { metadata: viewModel.exportMetadata(), content: viewModel.exportJSON(), html: viewModel.exportHTML() };
        var subj = document.getElementById('nl-subject'); if (subj) payload.subject = subj.value;
        if (typeof payload.content === 'string') payload.content = JSON.parse(payload.content);
        if (typeof payload.metadata === 'string') payload.metadata = JSON.parse(payload.metadata);
        fetch(saveUrl, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(payload) })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (d) { dirty = false; setStatus((d.warnings && d.warnings.length) ? '⚠ ' + d.warnings.join(' ') : 'Zapisano ' + d.saved_at, !!(d.warnings && d.warnings.length)); if (close) window.location.href = @json($backUrl); })
            .catch(function (e) { setStatus('Błąd zapisu (' + e.message + '). Spróbuj ponownie.', true); })
            .finally(function () { btns.forEach(function (b) { b.disabled = false; }); });
    }
    document.getElementById('nl-save').addEventListener('click', function () { doSave(false); });
    document.getElementById('nl-save-close').addEventListener('click', function () { doSave(true); });
    document.getElementById('nl-preview').addEventListener('click', function () { if (viewModel) viewModel.test.execute(); });
    setInterval(function () { if (dirty && viewModel) doSave(false); }, 90000);
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    // ── Kontrast WCAG: tekst vs tło w stylach globalnych ────────────────────
    function lum(hex) { var c = hex.replace('#', ''); if (c.length === 3) c = c.split('').map(function (x) { return x + x; }).join(''); var r = parseInt(c.substr(0, 2), 16) / 255, g = parseInt(c.substr(2, 2), 16) / 255, b = parseInt(c.substr(4, 2), 16) / 255;
        var f = function (v) { return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); }
    function ratio(a, b) { var l1 = lum(a), l2 = lum(b); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); }
    function checkContrast(json) {
        try {
            var m = typeof json === 'string' ? JSON.parse(json) : json; var theme = m.theme || {}; var issues = [];
            var pairs = [['contentTheme', 'longTextStyle', 'color', 'backgroundColor', 'tekst'], ['contentTheme', 'titleTextStyle', 'color', 'backgroundColor', 'tytuł'], ['contentTheme', 'buttonStyle', 'color', 'buttonColor', 'przycisk'], ['frameTheme', 'linkStyle', 'color', 'backgroundColor', 'stopka']];
            pairs.forEach(function (p) { var t = theme[p[0]] || {}; var fg = (t[p[1]] || {})[p[2]]; var bg = p[3] === 'buttonColor' ? (t[p[1]] || {}).buttonColor : t[p[3]]; if (fg && bg && /^#/.test(fg) && /^#/.test(bg)) { var r = ratio(fg, bg); if (r < 4.5) issues.push(p[4] + ' ' + r.toFixed(1) + ':1'); } });
            var el = document.getElementById('nl-contrast'); if (issues.length) { el.textContent = '⚠ Kontrast poniżej 4,5:1: ' + issues.join(', '); el.classList.add('show'); } else { el.classList.remove('show'); el.textContent = ''; }
        } catch (e) {}
    }

    // ── Wstawianie aktualności jako blok artykułu ────────────────────────────
    var modal = document.getElementById('nl-news'), list = document.getElementById('nl-list');
    function loadNews() {
        var src = document.getElementById('nl-src').value, cat = document.getElementById('nl-cat').value, sort = document.getElementById('nl-sort').value, q = document.getElementById('nl-q').value;
        list.innerHTML = '<p>Wczytywanie…</p>';
        fetch(contentUrl.replace('__SRC__', src) + '?limit=12&since=all&sort=' + encodeURIComponent(sort) + '&category=' + encodeURIComponent(cat) + '&q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (d) {
                list.innerHTML = d.items.length ? '' : '<p>Brak pozycji.</p>';
                d.items.forEach(function (it) {
                    var div = document.createElement('div'); div.className = 'item';
                    div.innerHTML = (it.image ? '<img src="' + it.image + '" alt="">' : '<span></span>') + '<div><strong>' + esc(it.title) + '</strong><br><small>' + esc((it.date || '') + (it.category ? ' · ' + it.category : '')) + '</small></div><button type="button">Wstaw</button>';
                    div.querySelector('button').addEventListener('click', function () { insertArticle(it); modal.classList.remove('open'); });
                    list.appendChild(div);
                });
            }).catch(function () { list.innerHTML = '<p>Nie udało się pobrać listy.</p>'; });
    }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function insertArticle(it) {
        if (!viewModel || !viewModel.blocks) return;
        try {
            var def = ko.utils.arrayFirst(ko.utils.unwrapObservable(viewModel.blocks), function (b) { return ko.utils.unwrapObservable(b.type) === 'singleArticleBlock'; });
            if (!def) { alert('W tym szablonie brak bloku „Artykuł”.'); return; }
            viewModel.addBlock(def);
            var added = viewModel.selectedBlock();
            var get = function (o, k) { var v = o && o[k]; return ko.isObservable(v) ? v() : v; };
            var set = function (o, k, v) { if (o && ko.isObservable(o[k])) o[k](v); };
            set(added, 'text', esc(it.title));
            set(added, 'longText', '<p>' + esc(it.excerpt || '') + '</p>');
            var img = get(added, 'image'); if (img) { if (it.image) set(img, 'src', it.image); set(img, 'url', it.url); set(img, 'alt', it.image_alt || it.title); }
            var btn = get(added, 'buttonLink'); if (btn) { set(btn, 'text', 'CZYTAJ WIĘCEJ'); set(btn, 'url', it.url); }
            setStatus('Wstawiono: ' + it.title);
        } catch (e) { console.error(e); alert('Nie udało się wstawić bloku automatycznie. Dodaj blok „Artykuł” z zakładki Bloki i wklej treść.'); }
    }
    var insertBtn = document.getElementById('nl-insert-news');
    if (insertBtn) insertBtn.addEventListener('click', function () { modal.classList.add('open'); loadNews(); document.getElementById('nl-q').focus(); });
    var tagBox = document.getElementById('nl-tagbox'), tagBtn = document.getElementById('nl-tags');
    if (tagBox && tagBtn) {
        tagBtn.addEventListener('click', function () { tagBox.style.display = 'flex'; });
        tagBox.querySelector('[data-close-tags]').addEventListener('click', function () { tagBox.style.display = 'none'; });
        tagBox.addEventListener('click', function (e) { if (e.target === tagBox) tagBox.style.display = 'none'; });
        tagBox.querySelectorAll('[data-copy]').forEach(function (b) { b.addEventListener('click', function () { navigator.clipboard.writeText(b.dataset.copy).then(function () { b.textContent = 'Skopiowano'; setTimeout(function () { b.textContent = 'Kopiuj'; }, 1500); }); }); });
        var subjInput = document.getElementById('nl-subject'); if (subjInput) subjInput.addEventListener('input', function () { dirty = true; });
    }
    modal.querySelector('[data-close]').addEventListener('click', function () { modal.classList.remove('open'); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') modal.classList.remove('open'); });
    ['nl-src', 'nl-cat', 'nl-sort'].forEach(function (id) { document.getElementById(id).addEventListener('change', loadNews); });
    var t; document.getElementById('nl-q').addEventListener('input', function () { clearTimeout(t); t = setTimeout(loadNews, 300); });
});
</script>
</body>
</html>
