<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1024, initial-scale=1">
    <title>{{ $title }} — edytor newslettera</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="{{ asset('vendor/mosaico/rs/mosaico-libs-and-tinymce.min.js') }}?v=0.18.11"></script>
    <script src="{{ asset('vendor/mosaico/rs/mosaico.min.js') }}?v=0.18.11"></script>
    <link rel="stylesheet" href="{{ asset('vendor/mosaico/rs/mosaico-libs-and-tinymce.min.css') }}?v=0.18.11">
    <link rel="stylesheet" href="{{ asset('vendor/mosaico/feer/mosaico-feer.min.css') }}?v={{ filemtime(public_path('vendor/mosaico/feer/mosaico-feer.min.css')) }}">
    <style>
        :root { --nl-brand: #1E6DFF; --nl-brand-dark: #1752BF; --nl-ink: #1D1D1A; --nl-accent: #EA8F00; }
        html, body { height: 100%; margin: 0; }
        /* Pasek edytora: jasny jak panel CMS, płaski, ikony SVG (bez emoji) */
        #nl-bar { position: fixed; top: 0; left: 0; right: 0; z-index: 10000; display: flex; align-items: center; gap: 8px; height: 60px; padding: 0 16px; background: #fff; color: var(--nl-ink); font: 700 14px Montserrat, Arial, sans-serif; box-sizing: border-box; box-shadow: 0 1px 0 #CBD5E7; }
        #nl-bar .nl-group { display: flex; align-items: center; gap: 6px; }
        #nl-bar .title { flex: 1; min-width: 0; display: flex; align-items: center; gap: 14px; }
        #nl-bar .title strong { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-size: 15px; }
        #nl-bar .nl-sep { width: 1px; height: 28px; background: #CBD5E7; margin: 0 4px; }
        #nl-bar button, #nl-bar a { display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 8px 12px; border: 0; border-radius: 8px; background: transparent; color: var(--nl-ink); font: inherit; cursor: pointer; text-decoration: none; white-space: nowrap; }
        #nl-bar button:hover, #nl-bar a:hover { background: #E8F0FF; }
        #nl-bar svg { width: 18px; height: 18px; flex: none; }
        #nl-bar button.primary { background: var(--nl-brand); color: #fff; padding: 8px 18px; }
        #nl-bar button.primary:hover { background: var(--nl-ink); }
        #nl-bar button.secondary { background: #E8F0FF; }
        #nl-bar button.secondary:hover { background: #CBD5E7; }
        #nl-bar button[aria-pressed="true"] { background: #E8F0FF; color: var(--nl-ink); box-shadow: inset 0 -3px 0 var(--nl-brand); }
        #nl-bar button:focus-visible, #nl-bar a:focus-visible, #nl-bar select:focus-visible, #nl-bar input:focus-visible { outline: 3px solid var(--nl-brand); outline-offset: 2px; }
        #nl-bar button[disabled] { cursor: progress; }
        #nl-bar label.nl-tpl { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13px; color: #4A4A47; white-space: nowrap; }
        #nl-bar select { min-height: 40px; padding: 6px 30px 6px 10px; border: 2px solid #6B6B67; border-radius: 8px; background: #fff; color: var(--nl-ink); font: 700 14px Montserrat, Arial, sans-serif; cursor: pointer; }
        #nl-status { font-weight: 400; font-size: 13px; color: #4A4A47; white-space: nowrap; }
        #nl-status.err { color: #B3261E; font-weight: 700; }
        @media (max-width: 1280px) { #nl-bar .nl-hide-narrow { display: none; } }
        .mo-standalone #main-wysiwyg-area, #page { top: 60px !important; }
        #nl-contrast { display: none; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 8px; background: #FCEBEA; color: #B3261E; font-weight: 700; font-size: 13px; }
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
        #nl-news h3.group { margin: 16px 0 8px; font-size: 15px; color: var(--nl-ink); }
        #nl-news h3.group:first-child { margin-top: 0; }
        #nl-news .close { float: right; border: 0; background: none; font-size: 22px; cursor: pointer; }
        /* wymagane wymiary obrazków przy każdym obrazku w bloku (tylko w edytorze, nie trafia do maila) */
        #main-wysiwyg-area td.uploadzone { position: relative; }
        .nl-imgsize { position: absolute; top: 6px; left: 6px; z-index: 5; pointer-events: none; padding: 3px 8px; border-radius: 4px; background: var(--nl-ink); color: #fff; font: 700 12px/1.4 Montserrat, Arial, sans-serif; white-space: nowrap; }
        body.nl-hide-sizes .nl-imgsize { display: none; }
        #nl-sizes[aria-pressed="true"] { background: var(--nl-brand); color: #fff; }
    </style>
</head>
<body class="mo-standalone">
@php
    $nlTemplates = \Modules\Newsletter\Models\NewsletterTemplate::MOSAICO_TEMPLATES;
    $nlShort = ['feer-1' => 'FEER-1 · klasyczny', 'feer-2' => 'FEER-2 · kolory marki'];
    // Szablon bazowy: ?szablon= (przełącznik w pasku) → kampania: zapisany w metadanych Mosaico → kolumna mosaico_template.
    $nlSaved = preg_match('#/templates/([a-z0-9-]+)/#', (string) data_get($subject->editor_metadata, 'template'), $nlM) ? $nlM[1] : null;
    // Przełączenie szablonu: zapisany projekt ma motyw (kolory, style) poprzedniego szablonu — przy zmianie go pomijamy.
    $nlSwitching = false;
    if (isset($nlTemplates[(string) request('szablon')])) {
        $mosaicoTemplate = request('szablon');
        $nlSwitching = $nlSaved !== null && $nlSaved !== $mosaicoTemplate;
    } elseif ($subject instanceof \Modules\Newsletter\Models\NewsletterCampaign && $nlSaved && isset($nlTemplates[$nlSaved])) {
        $mosaicoTemplate = $nlSaved;
    }
@endphp
<div id="nl-bar" role="toolbar" aria-label="Pasek edytora">
    <a href="{{ $backUrl }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>Wróć</a>
    <span class="nl-sep" aria-hidden="true"></span>
    <div class="title">
        <strong>{{ $title }}</strong>
        <label class="nl-tpl" for="nl-template">Szablon
            <select id="nl-template" aria-describedby="nl-template-hint">@foreach ($nlTemplates as $key => $label)<option value="{{ $key }}" @selected($key === $mosaicoTemplate) title="{{ $label }}">{{ $nlShort[$key] ?? strtoupper($key) }}</option>@endforeach</select>
        </label>
        <span id="nl-template-hint" class="sr-only">Zmiana szablonu zapisuje bieżącą treść i przeładowuje edytor z nowym wyglądem. Treść bloków zostaje.</span>
    </div>
    <span id="nl-contrast" role="status" aria-live="polite"></span>
    <span id="nl-status" role="status" aria-live="polite"></span>
    @if (! empty($systemMail))
    <label for="nl-subject" class="sr-only">Temat wiadomości</label>
    <input id="nl-subject" type="text" value="{{ $mailSubject }}" placeholder="Temat wiadomości" style="min-height:40px;min-width:260px;padding:6px 10px;border-radius:8px;border:2px solid #6B6B67;font:inherit;font-weight:400">
    <button type="button" id="nl-tags" title="Tagi do wstawienia w treści i w adresach przycisków"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H7a2 2 0 00-2 2v5a2 2 0 01-2 2 2 2 0 012 2v5a2 2 0 002 2h1M16 3h1a2 2 0 012 2v5a2 2 0 002 2 2 2 0 00-2 2v5a2 2 0 01-2 2h-1"/></svg>Tagi</button>
    @else
    <button type="button" id="nl-insert-news" title="Wstaw aktualność, wydarzenie, materiał albo baner z CMS"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg><span class="nl-hide-narrow">Wstaw z </span>CMS</button>
    @endif
    <button type="button" id="nl-sizes" aria-pressed="true" title="Pokaż przy obrazkach wymagane wymiary w pikselach"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21L21 3"/><path d="M3 15v6h6M21 9V3h-6"/></svg>Wymiary<span class="nl-hide-narrow"> obrazków</span></button>
    <button type="button" id="nl-preview" title="Podgląd w nowym oknie"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>Podgląd</button>
    <span class="nl-sep" aria-hidden="true"></span>
    <button type="button" id="nl-save" class="secondary">Zapisz</button>
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
        <h2 id="nl-news-h">Wstaw z CMS</h2>
        <div class="row">
            <select id="nl-src" aria-label="Źródło">@foreach (\Modules\Newsletter\Services\ContentFeeder::SOURCES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach<option value="banners">Banery</option></select>
            <select id="nl-cat" aria-label="Kategoria"><option value="">Wszystkie kategorie</option>@foreach ($newsCategories ?? [] as $slug => $name)<option value="{{ $slug }}">{{ $name }}</option>@endforeach</select>
            <select id="nl-sort" aria-label="Sortowanie"><option value="category" selected>Według kategorii</option><option value="newest">Od najnowszych</option><option value="oldest">Od najstarszych</option></select>
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
    @php $brandSite = \App\Models\SiteSetting::current(); $brandData = ['logo' => $brandSite->logoUrl(), 'name' => (string) $brandSite->site_name, 'title' => (string) ($brandSite->getAttributes()['newsletter_name'] ?? null ?: 'FEERletter'), 'social' => array_filter(['fb' => $brandSite->facebook_url, 'inst' => $brandSite->instagram_url, 'you' => $brandSite->youtube_url, 'in' => $brandSite->linkedin_url, 'tw' => $brandSite->twitter_url, 'web' => rtrim((string) config('app.url'), '/')]), 'contact' => trim(implode(' · ', array_filter([(string) $brandSite->site_name, trim(implode(', ', array_filter([$brandSite->contact_address, $brandSite->contact_city])))]))), 'url' => rtrim((string) config('app.url'), '/')]; @endphp
    var brand = @json($brandData);
    var contentUrl = @json(route('admin.newsletter.content.items', ['source' => '__SRC__']));
    var viewModel = null, dirty = false;

    var plugins = [function (vm) {
        window.viewModel = viewModel = vm;
        // Nowy projekt: na górę nagłówek „logo + FEERletter”; każdy później dodany blok logo/nagłówka wypełnia się sam.
        setTimeout(function () { watchLogoBlocks(vm); if (!(saved.metadata && saved.content)) insertBrandHeader(vm); }, 0);
        vm.logoPath = ''; vm.logoUrl = @json($backUrl);
        var cmd = function (name, fn) { return { name: name, enabled: ko.observable(true), execute: fn }; };
        vm.save = cmd('Save', function () { doSave(false); });
        vm.test = cmd('Test', function () { @if ($subject instanceof \Modules\Newsletter\Models\NewsletterCampaign) window.open(@json(route('admin.newsletter.kampanie.preview', $subject)), '_blank'); @else alert('Podgląd z personalizacją jest dostępny w kampanii; tutaj użyj ikony podglądu edytora.'); @endif });
        vm.download = cmd('Download', function () { var f = document.createElement('form'); f.method = 'POST'; f.action = @json(route('admin.newsletter.mosaico.download')); f.target = '_blank';
            f.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="filename" value="newsletter"><textarea name="html"></textarea>'; f.querySelector('textarea').value = vm.exportHTML(); document.body.appendChild(f); f.submit(); f.remove(); });
        // kontrola kontrastu: nasłuch zmian modelu
        if (typeof vm.content === 'function') { ko.computed(function () { try { var json = vm.exportJSON(); dirty = true; checkContrast(json); } catch (e) {} }).extend({ rateLimit: 800 }); }
    }];

    // Paleta próbnika kolorów = kolory marki z brandbooku (+ ciemniejszy niebieski do linków, 4,5:1 na białym)
    if ($.evol && $.evol.colorpicker) { $.evol.colorpicker.prototype.options.customTheme = ['#1E6DFF', '#1752BF', '#1D1D1A', '#EA8F00', '#CBD5E7', '#E8F0FF', '#FFFFFF']; }

    // „Zmienne” w pasku edytora tekstu: wstawia tag personalizacji (np. first_name w podwójnych klamrach) w miejscu kursora.
    var nlVars = @json(! empty($systemMail) ? $systemMail['tags'] : \Modules\Newsletter\Services\Personalizer::TAGS);
    (function (wys) {
        if (!wys) return;
        var full = wys.fullOptions || {};
        full.toolbar1 = (full.toolbar1 || 'bold italic forecolor backcolor hr styleselect removeformat | link unlink | pastetext code') + ' | nlvars';
        var prevSetup = full.setup;
        full.setup = function (editor) {
            if (prevSetup) prevSetup(editor);
            editor.addButton('nlvars', {
                type: 'menubutton', text: 'Zmienne', icon: false, tooltip: 'Wstaw zmienną personalizacji (imię, link wypisu…)',
                menu: Object.keys(nlVars).map(function (key) {
                    var tag = '\x7b\x7b' + key + '\x7d\x7d'; // klamry zapisane kodami, żeby nie łapał ich Blade
                    return { text: nlVars[key] + '  ' + tag, onclick: function () { editor.insertContent(tag); } };
                })
            });
        };
        wys.fullOptions = full;
    })(window.ko && ko.bindingHandlers && ko.bindingHandlers.wysiwyg);

    // Blok „Logo” i nagłówek „logo + FEERletter”: przy dodaniu dostają logo organizacji (alt, link do strony) i nazwę newslettera.
    function fillLogo(block) {
        try {
            block = ko.utils.unwrapObservable(block);
            var type = block && ko.utils.unwrapObservable(block.type);
            if (type === 'socialBlock' || type === 'bigSocialBlock') { fillSocial(block); return; }
            if (type !== 'logoBlock' && type !== 'brandHeaderBlock') return;
            var img = ko.utils.unwrapObservable(block.image);
            if (img && brand.logo && ko.isObservable(img.src) && !img.src()) {
                img.src(brand.logo);
                if (ko.isObservable(img.alt) && !img.alt()) img.alt(brand.name);
                if (ko.isObservable(img.url) && !img.url()) img.url(brand.url);
            }
            if (type === 'brandHeaderBlock' && ko.isObservable(block.orgName)) {
                var cur = (block.orgName() || '').replace(/<[^>]*>/g, '').trim();
                if (!cur || cur === 'Nazwa organizacji') block.orgName(brand.title);
            }
        } catch (e) { console.error('Blok logo:', e); }
    }
    // Bloki społecznościowe: adresy profili z ustawień witryny (Facebook, Instagram…), ikony bez adresu ukryte.
    // Tylko gdy blok nie ma jeszcze żadnego adresu — ręcznie wpisanych nie nadpisujemy.
    var SOCIAL_KEYS = ['fb', 'tw', 'gg', 'in', 'pi', 'fl', 'vi', 'web', 'wa', 'tg', 'inst', 'you', 'tt', 'tc'];
    function fillSocial(block) {
        var any = SOCIAL_KEYS.some(function (k) { return ko.isObservable(block[k + 'Url']) && block[k + 'Url'](); });
        if (any || !brand.social) return;
        SOCIAL_KEYS.forEach(function (k) {
            var url = brand.social[k] || '';
            if (ko.isObservable(block[k + 'Url'])) block[k + 'Url'](url);
            if (ko.isObservable(block[k + 'Visible'])) block[k + 'Visible'](!!url);
        });
        if (ko.isObservable(block.longText) && brand.contact) {
            var cur = (block.longText() || '').replace(/<[^>]*>/g, '').trim();
            if (!cur || /^Address and Contacts$/i.test(cur)) block.longText('<p>' + $('<div>').text(brand.contact).html() + '</p>');
        }
    }

    function watchLogoBlocks(vm) {
        if (!vm.content) return;
        var list = vm.content().mainBlocks().blocks;
        list().forEach(fillLogo); // zapisany projekt z pustym blokiem logo/nagłówka
        list.subscribe(function (changes) {
            changes.forEach(function (c) { if (c.status === 'added') setTimeout(function () { fillLogo(c.value); }, 50); }); // po wyrenderowaniu bloku (wiązanie obrazka Mosaico)
        }, null, 'arrayChange');
    }

    function insertBrandHeader(vm) {
        try {
            var def = ko.utils.arrayFirst(ko.utils.unwrapObservable(vm.blocks), function (b) { return ko.utils.unwrapObservable(b.type) === 'brandHeaderBlock'; });
            if (!def || !vm.content) return;
            var list = vm.content().mainBlocks().blocks;
            if (ko.utils.arrayFirst(list(), function (b) { return ko.utils.unwrapObservable(ko.utils.unwrapObservable(b).type) === 'brandHeaderBlock'; })) return;
            vm.selectedBlock(null);
            vm.addBlock(def);
            var added = list.pop(); list.unshift(added);
            fillLogo(added);
            // …i na dół blok z profilami społecznościowymi FEER
            var social = ko.utils.arrayFirst(ko.utils.unwrapObservable(vm.blocks), function (b) { return ko.utils.unwrapObservable(b.type) === 'socialBlock'; });
            if (social && brand.social && Object.keys(brand.social).length) { vm.selectedBlock(null); vm.addBlock(social); }
            vm.selectedBlock(null);
        } catch (e) { console.error('Nagłówek z logo:', e); }
    }

    // Po zmianie szablonu w pasku: bloki i treść zostają, motyw (kolory, czcionki, odstępy) bierzemy z nowego szablonu.
    function savedContent() {
        if (!@json($nlSwitching ?? false) || !saved.content) return saved.content;
        var c = Object.assign({}, saved.content); delete c.theme;
        setTimeout(function () { setStatus('Szablon zmieniony na ' + @json(strtoupper($mosaicoTemplate)) + ' — zapisz, aby zachować nowy wygląd.'); }, 500);
        return c;
    }

    var ok = Mosaico.init({
        imgProcessorBackend: @json(route('admin.newsletter.mosaico.image')),
        emailProcessorBackend: @json(route('admin.newsletter.mosaico.download')),
        titleToken: 'FEER',
        fileuploadConfig: { url: @json(route('admin.newsletter.mosaico.upload')), headers: { 'X-CSRF-TOKEN': csrf } },
        strings: @json(json_decode(file_get_contents(public_path('vendor/mosaico/rs/lang/mosaico-pl.json')), true)),
        template: (saved.metadata && saved.content) ? undefined : templateUrl,
        data: (saved.metadata && saved.content) ? { metadata: Object.assign({}, saved.metadata, { template: templateUrl }), content: savedContent() } : undefined
    }, plugins);
    if (!ok) { document.getElementById('nl-status').textContent = 'Nie udało się uruchomić edytora.'; }

    // CSRF dla uploadów jQuery File Upload
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf } });

    function setStatus(msg, err) { var s = document.getElementById('nl-status'); s.textContent = msg; s.className = err ? 'err' : ''; }

    function doSave(close, then) {
        if (!viewModel) return;
        var btns = document.querySelectorAll('#nl-save, #nl-save-close'); btns.forEach(function (b) { b.disabled = true; });
        setStatus('Zapisuję…');
        var payload = { metadata: viewModel.exportMetadata(), content: viewModel.exportJSON(), html: viewModel.exportHTML() };
        var subj = document.getElementById('nl-subject'); if (subj) payload.subject = subj.value;
        payload.mosaico_template = @json($mosaicoTemplate);
        if (typeof payload.content === 'string') payload.content = JSON.parse(payload.content);
        if (typeof payload.metadata === 'string') payload.metadata = JSON.parse(payload.metadata);
        fetch(saveUrl, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(payload) })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (d) { dirty = false; setStatus((d.warnings && d.warnings.length) ? '⚠ ' + d.warnings.join(' ') : 'Zapisano ' + d.saved_at, !!(d.warnings && d.warnings.length)); if (close) window.location.href = @json($backUrl); if (then) then(); })
            .catch(function (e) { setStatus('Błąd zapisu (' + e.message + '). Spróbuj ponownie.', true); })
            .finally(function () { btns.forEach(function (b) { b.disabled = false; }); });
    }
    document.getElementById('nl-save').addEventListener('click', function () { doSave(false); });
    // Zmiana szablonu bazowego: zapis bieżącej treści i przeładowanie edytora z nowym wyglądem (bloki FEER-1/FEER-2 są zgodne).
    var tplSelect = document.getElementById('nl-template');
    tplSelect.addEventListener('change', function () {
        var next = tplSelect.value, current = @json($mosaicoTemplate);
        if (next === current) return;
        if (!confirm('Zmienić szablon na ' + next.toUpperCase() + '? Treść bloków zostanie, zmieni się wygląd (kolory, przyciski, tło). Bieżące zmiany zapiszę przed przeładowaniem.')) { tplSelect.value = current; return; }
        var go = function () { dirty = false; var u = new URL(window.location.href); u.searchParams.set('szablon', next); window.location.href = u.toString(); };
        if (viewModel) { doSave(false, go); } else { go(); }
    });
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

    // ── Wymagane wymiary obrazków: etykieta „szer. × wys. px” przy każdym obrazku ──
    // Szerokość = atrybut width wyliczony przez Mosaico (zależy od opcji bloku), wysokość = atrybut height, gdy blok ją narzuca.
    // Etykieta siedzi tylko w DOM edytora; eksport HTML Mosaico renderuje się z modelu, więc nie trafia do maila.
    function imgSizeLabel(img) {
        var w = parseInt(img.getAttribute('width'), 10); if (!w) return null;
        var h = parseInt(img.getAttribute('height'), 10);
        return h ? { text: w + ' × ' + h + ' px' } : { text: w + ' px szer. · wysokość dowolna' };
    }
    function refreshImgSizes() {
        document.querySelectorAll('#main-wysiwyg-area td.uploadzone').forEach(function (zone) {
            var img = zone.querySelector('img'), label = zone.querySelector(':scope > .nl-imgsize');
            var info = img && imgSizeLabel(img);
            if (!info) { if (label) label.remove(); return; }
            if (!label) { label = document.createElement('span'); label.className = 'nl-imgsize'; label.setAttribute('aria-hidden', 'true'); zone.appendChild(label); }
            if (label.textContent !== info.text) label.textContent = info.text;
        });
    }
    var sizeTimer = null;
    new MutationObserver(function () { clearTimeout(sizeTimer); sizeTimer = setTimeout(refreshImgSizes, 250); })
        .observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['width', 'height', 'src'] });
    var sizesBtn = document.getElementById('nl-sizes');
    try { if (localStorage.getItem('nl-hide-sizes') === '1') { document.body.classList.add('nl-hide-sizes'); sizesBtn.setAttribute('aria-pressed', 'false'); } } catch (e) {}
    sizesBtn.addEventListener('click', function () {
        var hide = document.body.classList.toggle('nl-hide-sizes');
        sizesBtn.setAttribute('aria-pressed', hide ? 'false' : 'true');
        try { localStorage.setItem('nl-hide-sizes', hide ? '1' : '0'); } catch (e) {}
    });

    // ── Wstawianie aktualności jako blok artykułu ────────────────────────────
    var modal = document.getElementById('nl-news'), list = document.getElementById('nl-list');
    function loadNews() {
        var src = document.getElementById('nl-src').value, cat = document.getElementById('nl-cat').value, sort = document.getElementById('nl-sort').value, q = document.getElementById('nl-q').value;
        var isBanner = src === 'banners';
        document.getElementById('nl-cat').hidden = isBanner || src !== 'news'; document.getElementById('nl-sort').hidden = isBanner;
        if (isBanner) sort = 'newest';
        list.innerHTML = '<p>Wczytywanie…</p>';
        fetch(contentUrl.replace('__SRC__', src) + '?limit=' + (sort === 'category' ? 30 : 12) + '&since=all&sort=' + encodeURIComponent(sort) + '&category=' + encodeURIComponent(cat) + '&q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (d) {
                list.innerHTML = d.items.length ? '' : (isBanner ? '<p>Brak aktywnych banerów graficznych. Dodasz je w panelu: Bannery.</p>' : '<p>Brak pozycji.</p>');
                var lastCat = null;
                d.items.forEach(function (it) {
                    if (sort === 'category') {
                        var c = it.category || 'Bez kategorii';
                        if (c !== lastCat) { var h = document.createElement('h3'); h.className = 'group'; h.textContent = c; list.appendChild(h); lastCat = c; }
                    }
                    var div = document.createElement('div'); div.className = 'item';
                    div.innerHTML = (it.image ? '<img src="' + it.image + '" alt="">' : '<span></span>') + '<div><strong>' + esc(it.title) + '</strong><br><small>' + esc((it.date || '') + (it.category ? ' · ' + it.category : '')) + '</small></div><button type="button">Wstaw</button>';
                    div.querySelector('button').addEventListener('click', function () { (it.banner ? insertBanner : insertArticle)(it); modal.classList.remove('open'); });
                    list.appendChild(div);
                });
            }).catch(function () { list.innerHTML = '<p>Nie udało się pobrać listy.</p>'; });
    }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    // Baner z modułu Bannery → blok „Obraz” (grafika, alt, link).
    function insertBanner(it) {
        if (!viewModel || !viewModel.blocks) return;
        try {
            var def = ko.utils.arrayFirst(ko.utils.unwrapObservable(viewModel.blocks), function (b) { return ko.utils.unwrapObservable(b.type) === 'imageBlock'; });
            if (!def) { alert('W tym szablonie brak bloku „Obraz”.'); return; }
            viewModel.addBlock(def);
            var added = viewModel.selectedBlock();
            var img = added && ko.utils.unwrapObservable(added.image);
            if (img) {
                if (ko.isObservable(img.src)) img.src(it.image);
                if (ko.isObservable(img.alt)) img.alt(it.image_alt || it.title);
                if (ko.isObservable(img.url) && it.url) img.url(it.url);
            }
            setStatus('Wstawiono baner: ' + it.title);
        } catch (e) { console.error(e); alert('Nie udało się wstawić banera. Dodaj blok „Obraz” z zakładki Bloki i wgraj grafikę.'); }
    }
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
