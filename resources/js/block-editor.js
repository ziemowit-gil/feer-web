// Edytor blokowy w stylu Gutenberga — strony, aktualności i działania.
//
// Treść to drzewo bloków { id, type, attrs, inner? }. Edycja odbywa się na
// stylach frontu (.prose), więc redakcja widzi stronę tak, jak zobaczy ją
// odwiedzający. Zapis wysyła bloki (bez id) do BlockEditorController, który
// je czyści (App\Support\BlockRenderer) i renderuje HTML do kolumny `content`.
//
// Układ: górny pasek (wstawianie, cofnij/ponów, widok listy, dostępność, zapis),
// lewy panel (bloki i wzorce / widok listy / uwagi dostępności), płótno
// z blokami i pływającym paskiem narzędzi bloku, prawy panel (Strona / Blok).

const mount = document.getElementById('block-editor');
if (mount) {
    start(JSON.parse(mount.dataset.config || '{}'));
}

function start(config) {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    // Rodzaj edytowanej treści (strona / aktualność / działanie) i etykiety interfejsu.
    const TARGET = config.target || { type: 'page', id: config.page.id };
    const L = {
        kind: 'Strona', title: 'Tytuł strony', back: 'Wróć do strony', view: 'Zobacz stronę', form: 'Otwórz formularz strony',
        typeLabel: 'Typ strony', published: 'opublikowana', publishedLong: 'Opublikowana', draftLong: 'Szkic (niewidoczna publicznie)',
        contentLabel: 'Treść strony', more: 'Adres, menu, publikacja, dostęp, szablon i pola typu strony są w klasycznym formularzu.',
        imported: 'Ta strona była edytowana w klasycznym edytorze', empty: 'Strona nie ma jeszcze bloków.', titleRequired: 'Tytuł strony nie może być pusty.',
        ...(config.labels || {}),
    };
    // Klucz stron bez prefiksu typu — zgodność z kopiami roboczymi sprzed obsługi aktualności i działań.
    const DRAFT_KEY = 'block-editor-draft-' + (TARGET.type === 'page' ? '' : TARGET.type + '-') + config.page.id;

    // ── Pomocnicze ─────────────────────────────────────────────────────────
    let uidCounter = 0;
    const uid = () => 'b' + Date.now().toString(36) + (uidCounter++).toString(36);

    function h(tag, props, ...children) {
        const el = document.createElement(tag);
        for (const [k, v] of Object.entries(props || {})) {
            if (v === undefined || v === null || v === false) continue;
            if (k === 'class') el.className = v;
            else if (k === 'html') el.innerHTML = v;
            else if (k === 'text') el.textContent = v;
            else if (k === 'value') el.value = v;
            else if (k === 'checked') el.checked = !!v;
            else if (k.startsWith('on') && typeof v === 'function') el.addEventListener(k.slice(2).toLowerCase(), v);
            else if (v === true) el.setAttribute(k, '');
            else el.setAttribute(k, v);
        }
        for (const c of children.flat(Infinity)) {
            if (c === null || c === undefined || c === false) continue;
            el.append(c instanceof Node ? c : document.createTextNode(String(c)));
        }
        return el;
    }
    const icon = (name) => h('i', { class: 'fa-solid fa-' + name, 'aria-hidden': 'true' });
    const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const textOf = (html) => { const d = document.createElement('div'); d.innerHTML = html || ''; return d.textContent.replace(/ /g, ' ').trim(); };
    const safeUrl = (url) => /^(https?:\/\/|mailto:|tel:|\/(?!\/)|#|\?)/i.test((url || '').trim());
    const clone = (v) => JSON.parse(JSON.stringify(v));
    const debounce = (fn, ms) => { let t = null; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); }; };
    const SHORTCODE_RE = /^\[[a-z0-9_-]+(?::[^\]\r\n]{1,200})?\]$/i;
    // Jak App\Support\ShortcodeParser::DETECT_PATTERN — czy HTML zawiera shortcode do wyrenderowania.
    const SHORTCODE_DETECT = /\[(?:(?:formularz|kafelki):[a-z0-9_-]+|kafelki-zestaw:\d+|blok:\d+|pasek:\d+|klauzule-rodo(?::[a-z]{2})?|nabory|zapytania-ofertowe|(?:podstrony|aktualnosci)(?::[^\]\r\n<]{0,200})?)\]/i;

    // Shortcode'y list linków — jak App\Support\SubpageList::shortcode() i NewsList::shortcode().
    const LIST_LAYOUTS = [['lista', 'Prosta lista linków'], ['zajawki', 'Lista z zajawką'], ['kafle', 'Kafle w 2 kolumnach']];
    function subpagesCode(a) {
        const parts = [];
        if (a.manual && a.picked?.length) parts.push('wybrane=' + a.picked.join(','));
        else if (a.source === 'page' && a.page) parts.push('strona=' + a.page);
        if (a.sort === 'alfabet') parts.push('sortowanie=alfabet');
        if (Number(a.depth) === 2) parts.push('poziomy=2');
        if (a.layout && a.layout !== 'lista') parts.push('wyglad=' + a.layout);
        return '[podstrony' + (parts.length ? ':' + parts.join(';') : '') + ']';
    }
    function newsCode(a) {
        const parts = [];
        if (a.category) parts.push('kategoria=' + a.category);
        if (Number(a.limit) && Number(a.limit) !== 5) parts.push('liczba=' + Number(a.limit));
        if (a.sort === 'alfabet') parts.push('sortowanie=alfabet');
        if (a.layout && a.layout !== 'lista') parts.push('wyglad=' + a.layout);
        return '[aktualnosci' + (parts.length ? ':' + parts.join(';') : '') + ']';
    }
    // Shortcode listy z treści (klasyczny edytor, wklejanie) → atrybuty bloku.
    function listBlockFromCode(code) {
        const m = code.match(/^\[(podstrony|aktualnosci)(?::([^\]]*))?\]$/i);
        if (!m) return null;
        const o = {};
        (m[2] || '').split(/[;|]/).forEach((p) => { const i = p.indexOf('='); if (i > 0) o[p.slice(0, i).trim().toLowerCase()] = p.slice(i + 1).trim(); });
        const layout = ['lista', 'zajawki', 'kafle'].includes(o.wyglad) ? o.wyglad : 'lista';
        if (m[1].toLowerCase() === 'aktualnosci') {
            return block('newslist', { category: Number(o.kategoria) || null, limit: Math.min(30, Number(o.liczba) || 5), sort: o.sortowanie === 'alfabet' ? 'alfabet' : 'najnowsze', layout });
        }
        const picked = (o.wybrane || '').split(',').map(Number).filter((n) => n > 0);
        const page = Number(o.strona) || null;
        return block('subpages', { source: page ? 'page' : 'current', page, manual: picked.length > 0, picked, sort: o.sortowanie === 'alfabet' ? 'alfabet' : 'drzewo', depth: Number(o.poziomy) === 2 ? 2 : 1, layout });
    }

    async function postJson(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data),
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(Object.values(json.errors || {})[0]?.[0] || json.message || 'Błąd serwera (' + res.status + ').');
        return json;
    }

    // Podgląd HTML z serwera (shortcode'y): bez skryptów, obsługi zdarzeń i adresów javascript:.
    // <template> parsuje treść bez wczytywania obrazów i bez wykonywania czegokolwiek.
    function sanitizePreview(html) {
        const tpl = document.createElement('template');
        tpl.innerHTML = html || '';
        tpl.content.querySelectorAll('script, noscript, object, embed, base, meta, link').forEach((el) => el.remove());
        tpl.content.querySelectorAll('*').forEach((el) => {
            for (const a of [...el.attributes]) {
                const name = a.name.toLowerCase();
                if (name.startsWith('on') || name === 'autofocus') el.removeAttribute(a.name);
                else if (['href', 'src', 'action', 'formaction', 'xlink:href'].includes(name) && /^\s*javascript:/i.test(a.value)) el.removeAttribute(a.name);
            }
        });
        return tpl.content;
    }

    function embedSrc(url) {
        let m = (url || '').match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
        if (m) return 'https://www.youtube-nocookie.com/embed/' + m[1];
        m = (url || '').match(/vimeo\.com\/(?:video\/)?(\d+)/);
        if (m) return 'https://player.vimeo.com/video/' + m[1] + '?dnt=1';
        return null;
    }

    // Biała lista znaczników inline — jak App\Support\BlockRenderer::inline().
    const INLINE_TAGS = new Set(['STRONG', 'B', 'EM', 'I', 'A', 'BR', 'CODE', 'S', 'SUB', 'SUP', 'MARK']);
    const DROP_TAGS = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'TEMPLATE', 'NOSCRIPT', 'META', 'LINK', 'SVG', 'MATH', 'FORM', 'INPUT', 'BUTTON', 'SELECT', 'TEXTAREA', 'TITLE', 'HEAD']);
    const BLOCKISH = new Set(['P', 'DIV', 'LI', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'TR', 'BLOCKQUOTE']);

    function cleanInline(html) {
        const doc = new DOMParser().parseFromString('<div id="r">' + (html || '') + '</div>', 'text/html');
        const root = doc.getElementById('r');
        const walk = (node) => {
            for (const c of [...node.childNodes]) {
                if (c.nodeType === 8) { c.remove(); continue; }
                if (c.nodeType !== 1) continue;
                const tag = c.tagName;
                if (DROP_TAGS.has(tag)) { c.remove(); continue; }
                walk(c);
                // Google Docs owija całą treść w <b style="font-weight:normal">.
                const fakeBold = tag === 'B' && /font-weight:\s*(normal|400)/i.test(c.getAttribute('style') || '');
                if (!INLINE_TAGS.has(tag) || fakeBold) {
                    if (BLOCKISH.has(tag) && c.previousSibling) c.before(doc.createElement('br'));
                    c.replaceWith(...c.childNodes);
                    continue;
                }
                const href = c.getAttribute('href');
                const blank = c.getAttribute('target') === '_blank';
                for (const a of [...c.attributes]) c.removeAttribute(a.name);
                if (tag === 'A') {
                    if (href && safeUrl(href)) {
                        c.setAttribute('href', href.trim());
                        if (blank) { c.setAttribute('target', '_blank'); c.setAttribute('rel', 'noopener'); }
                    } else {
                        c.replaceWith(...c.childNodes);
                    }
                }
            }
        };
        walk(root);
        return root.innerHTML.replace(/(<br\s*\/?>\s*)+$/i, '').trim();
    }

    // ── Definicje bloków ───────────────────────────────────────────────────
    const CATEGORIES = { text: 'Tekst', media: 'Multimedia', design: 'Układ i wyróżnienia', modules: 'Moduły (jak w klasycznym edytorze)', widgets: 'Zaawansowane' };
    // Elementy z menu „Wstaw” klasycznego edytora (App\Support\ClassicInserts) — w edytorze blokowym to bloki „Shortcode”
    // z wybranym modułem albo wzorce HTML, więc oba edytory pozwalają wstawić to samo.
    const CI = config.classic || {};
    const firstCode = (list, fmt) => (list && list.length ? fmt(list[0].value) : '');

    const BLOCKS = {
        paragraph: { title: 'Akapit', icon: 'paragraph', cat: 'text', desc: 'Zwykły tekst. Wpisz „/”, aby wybrać inny blok.', keywords: ['tekst', 'p'], rich: true, align: true, transforms: ['heading', 'list', 'quote', 'callout'], create: () => ({ text: '', align: 'left', lead: false }) },
        heading: { title: 'Nagłówek', icon: 'heading', cat: 'text', desc: 'Śródtytuł porządkujący treść. Tworzy spis treści strony.', keywords: ['h2', 'h3', 'tytuł', 'śródtytuł'], rich: true, align: true, transforms: ['paragraph', 'quote'], create: () => ({ text: '', level: 2, align: 'left', anchor: '' }) },
        list: { title: 'Lista', icon: 'list-ul', cat: 'text', desc: 'Lista punktowana lub numerowana.', keywords: ['punkty', 'wypunktowanie', 'numerowana', 'ul', 'ol'], rich: true, transforms: ['paragraph'], create: () => ({ ordered: false, items: [''] }) },
        quote: { title: 'Cytat', icon: 'quote-left', cat: 'text', desc: 'Wyróżniona wypowiedź z podpisem autora.', keywords: ['wypowiedź', 'blockquote'], rich: true, transforms: ['paragraph'], create: () => ({ text: '', cite: '' }) },
        table: { title: 'Tabela', icon: 'table', cat: 'text', desc: 'Dane w wierszach i kolumnach, z nagłówkami.', keywords: ['dane', 'cennik', 'zestawienie'], create: () => ({ caption: '', headerRow: true, headerColumn: false, rows: [['', '', ''], ['', '', ''], ['', '', '']] }) },
        details: { title: 'Rozwijana sekcja', icon: 'caret-down', cat: 'text', desc: 'Tytuł i treść widoczna po rozwinięciu. Kilka pod rzędem tworzy akordeon (np. FAQ).', keywords: ['akordeon', 'faq', 'pytanie', 'szczegóły'], container: true, create: () => ({ summary: '', open: false }), inner: () => [block('paragraph')] },
        image: { title: 'Obraz', icon: 'image', cat: 'media', desc: 'Zdjęcie lub grafika z opisem alternatywnym.', keywords: ['zdjęcie', 'grafika', 'foto', 'img'], create: () => ({ url: '', alt: '', decorative: false, caption: '', width: 'full', href: '' }) },
        gallery: { title: 'Galeria', icon: 'images', cat: 'media', desc: 'Kilka zdjęć w siatce.', keywords: ['zdjęcia', 'fotorelacja'], create: () => ({ columns: 3, images: [] }) },
        embed: { title: 'Film (YouTube, Vimeo)', icon: 'circle-play', cat: 'media', desc: 'Osadzony film. YouTube w trybie bez ciasteczek.', keywords: ['wideo', 'youtube', 'vimeo', 'osadzenie', 'film'], create: () => ({ url: '', title: '', caption: '' }) },
        file: { title: 'Plik do pobrania', icon: 'file-arrow-down', cat: 'media', desc: 'Odnośnik do dokumentu (PDF, DOCX…).', keywords: ['pdf', 'dokument', 'załącznik', 'pobierz'], create: () => ({ url: '', label: '', description: '' }) },
        buttons: { title: 'Przyciski', icon: 'hand-pointer', cat: 'design', desc: 'Wezwanie do działania — jeden lub kilka przycisków.', keywords: ['cta', 'przycisk', 'link', 'zapisz się'], align: true, create: () => ({ align: 'left', items: [{ label: 'Dowiedz się więcej', url: '', variant: 'brand', newTab: false }] }) },
        callout: { title: 'Ramka', icon: 'rectangle-list', cat: 'design', desc: 'Wyróżniona treść: ramka, notatka/ostrzeżenie albo ważna informacja.', keywords: ['ważne', 'uwaga', 'notatka', 'ostrzeżenie', 'box'], container: true, create: () => ({ variant: 'box' }), inner: () => [block('paragraph')] },
        columns: { title: 'Kolumny', icon: 'table-columns', cat: 'design', desc: 'Treść obok siebie (na telefonie jedna pod drugą).', keywords: ['układ', 'dwie kolumny', 'grid'], container: true, create: () => ({ ratio: 'equal' }), inner: () => [block('column'), block('column')] },
        column: { title: 'Kolumna', icon: 'square', cat: 'design', desc: 'Jedna kolumna układu.', container: true, hidden: true, create: () => ({}), inner: () => [block('paragraph')] },
        separator: { title: 'Separator', icon: 'minus', cat: 'design', desc: 'Pozioma linia oddzielająca sekcje.', keywords: ['linia', 'hr', 'podział'], create: () => ({}) },
        shortcode: { title: 'Shortcode', icon: 'code', cat: 'widgets', desc: 'Osadzenie modułu: formularz, kafelki, blok z kreatora.', keywords: ['formularz', 'kafelki', 'blok', 'moduł'], create: () => ({ code: '' }) },
        subpages: { title: 'Lista podstron', icon: 'sitemap', cat: 'widgets', desc: 'Linki do podstron tej lub wskazanej strony. Aktualizuje się sama — nowa podstrona pojawi się bez edycji.', keywords: ['podstrony', 'spis', 'menu', 'linki', 'nawigacja', 'dzieci'], create: () => ({ source: 'current', page: null, manual: false, picked: [], sort: 'drzewo', depth: 1, layout: 'lista' }) },
        newslist: { title: 'Lista aktualności', icon: 'newspaper', cat: 'widgets', desc: 'Linki do najnowszych aktualności (wszystkich lub z kategorii). Aktualizuje się sama.', keywords: ['aktualności', 'wpisy', 'newsy', 'blog', 'linki'], create: () => ({ category: null, limit: 5, sort: 'najnowsze', layout: 'lista' }) },
        form: { as: 'shortcode', title: 'Formularz', icon: 'rectangle-list', cat: 'modules', desc: 'Formularz z modułu Formularze.', keywords: ['formularz', 'zapisy', 'zgłoszenie'], hidden: !(CI.forms || []).length, create: () => ({ code: firstCode(CI.forms, (v) => '[formularz:' + v + ']') }) },
        tileset: { as: 'shortcode', title: 'Zestaw kafelków', icon: 'table-cells-large', cat: 'modules', desc: 'Kafelki z kreatora — wybierz istniejący zestaw albo utwórz nowy.', keywords: ['kafelki', 'kafle', 'zestaw'], create: () => ({ code: firstCode(CI.tileSets, (v) => '[kafelki-zestaw:' + v + ']') }) },
        contentblock: { as: 'shortcode', title: 'Blok z kreatora', icon: 'cubes', cat: 'modules', desc: 'Przyciski CTA, akordeon, ramka informacyjna albo cennik z kreatora bloków.', keywords: ['cta', 'akordeon', 'ramka', 'cennik', 'blok'], create: () => ({ code: firstCode(CI.contentBlocks, (v) => '[blok:' + v + ']') }) },
        tilesgrid: { as: 'shortcode', title: 'Siatka kafelków', icon: 'border-all', cat: 'modules', desc: 'Kafelki ze strony typu „Siatka kafelków”.', keywords: ['kafelki', 'siatka'], hidden: !(CI.tilesGrids || []).length, create: () => ({ code: firstCode(CI.tilesGrids, (v) => '[kafelki:' + v + ']') }) },
        band: { as: 'shortcode', title: 'Pasek FEER', icon: 'grip-lines', cat: 'modules', desc: 'Pasek z modułu „FEER Paski” (tytuł, tekst, przyciski).', keywords: ['pasek', 'baner', 'band'], hidden: !(CI.bands || []).length, create: () => ({ code: firstCode(CI.bands, (v) => '[pasek:' + v + ']') }) },
        gdpr: { as: 'shortcode', title: 'Klauzule RODO', icon: 'user-shield', cat: 'modules', desc: 'Lista klauzul informacyjnych RODO z SZO.', keywords: ['rodo', 'klauzule', 'gdpr'], create: () => ({ code: '[klauzule-rodo]' }) },
        recruitments: { as: 'shortcode', title: 'Lista naborów', icon: 'user-plus', cat: 'modules', desc: 'Nabory do projektów z filtrem aktualne / zakończone.', keywords: ['nabory', 'rekrutacja'], create: () => ({ code: '[nabory]' }) },
        tenders: { as: 'shortcode', title: 'Zapytania ofertowe', icon: 'file-signature', cat: 'modules', desc: 'Lista zapytań ofertowych z filtrem.', keywords: ['zapytania', 'oferty', 'przetargi'], create: () => ({ code: '[zapytania-ofertowe]' }) },
        html: { title: 'Własny HTML', icon: 'file-code', cat: 'widgets', desc: 'Kod HTML (np. treść z klasycznego edytora).', keywords: ['kod', 'klasyczny', 'classic'], create: () => ({ html: '' }) },
    };

    function block(type, attrs = {}, inner) {
        let def = BLOCKS[type];
        // Skróty modułów („Formularz”, „Zestaw kafelków”…) to bloki „Shortcode” z gotowym kodem.
        if (def.as) { attrs = { ...def.create(), ...attrs }; type = def.as; def = BLOCKS[type]; }
        const b = { id: uid(), type, attrs: { ...def.create(), ...attrs } };
        if (def.container) b.inner = inner ?? def.inner();
        return b;
    }

    // Bloki z serwera: dokładamy id i brakujące atrybuty.
    function hydrate(list) {
        return (list || []).filter((b) => BLOCKS[b?.type] && !BLOCKS[b.type].as).map((b) => {
            const def = BLOCKS[b.type];
            const out = { id: uid(), type: b.type, attrs: { ...def.create(), ...(b.attrs || {}) } };
            if (def.container) out.inner = hydrate(b.inner);
            return out;
        });
    }

    const withNewIds = (b) => ({ ...clone(b), id: uid(), inner: b.inner ? b.inner.map(withNewIds) : undefined });

    const PATTERNS = [
        { title: 'Wezwanie do działania', desc: 'Nagłówek, krótki tekst i przycisk.', build: () => [block('heading', { text: 'Dołącz do nas' }), block('paragraph', { text: 'Napisz w jednym, dwóch zdaniach, co zyska osoba, która kliknie przycisk.' }), block('buttons', { items: [{ label: 'Zgłoś się', url: '', variant: 'brand', newTab: false }] })] },
        { title: 'Tekst i zdjęcie obok siebie', desc: 'Dwie kolumny: treść i obraz.', build: () => [block('columns', {}, [block('column', {}, [block('heading', { text: 'Tytuł sekcji', level: 3 }), block('paragraph', { text: 'Treść sekcji…' })]), block('column', {}, [block('image')])])] },
        { title: 'Pytania i odpowiedzi', desc: 'Nagłówek i trzy rozwijane pytania.', build: () => [block('heading', { text: 'Najczęstsze pytania' }), ...[1, 2, 3].map((n) => block('details', { summary: 'Pytanie ' + n }, [block('paragraph', { text: 'Odpowiedź…' })]))] },
        { title: 'Ważna informacja', desc: 'Niebieska ramka z pogrubionym tytułem.', build: () => [block('callout', { variant: 'important' }, [block('paragraph', { text: '<strong>Ważne</strong>' }), block('paragraph', { text: 'Treść ważnej informacji…' })])] },
        { title: 'Dokumenty do pobrania', desc: 'Nagłówek i lista plików.', build: () => [block('heading', { text: 'Dokumenty do pobrania' }), block('file'), block('file')] },
        { title: 'Kontakt w sprawie', desc: 'Ramka z osobą do kontaktu.', build: () => [block('callout', { variant: 'box' }, [block('heading', { text: 'Kontakt w sprawie', level: 3 }), block('paragraph', { text: 'Imię Nazwisko<br>tel. <a href="tel:+48000000000">000 000 000</a><br><a href="mailto:adres@feer.org.pl">adres@feer.org.pl</a>' })])] },
    ];

    // Wstawki HTML z klasycznego edytora (BIP, sekcje akcentu) i ramki z wydarzeniem — jako bloki „Własny HTML”.
    (CI.snippets || []).forEach((sn) => PATTERNS.push({ title: sn.title, desc: sn.desc + ' (jak w klasycznym edytorze)', build: () => [block('html', { html: sn.html })] }));
    (CI.schedules || []).forEach((sc) => PATTERNS.push({ title: 'Przycisk CTA do harmonogramu: ' + sc.title, desc: 'Przycisk prowadzący do strony harmonogramu.', build: () => [block('buttons', { items: [{ label: sc.title, url: sc.url, variant: 'brand', newTab: false }] })] }));
    (CI.events || []).forEach((ev) => PATTERNS.push({ title: 'Ramka z wydarzeniem: ' + ev.title, desc: 'Termin, miejsce i przycisk do wydarzenia.', build: () => [block('html', { html: ev.html })] }));

    // ── Stan ───────────────────────────────────────────────────────────────
    const S = {
        title: config.page.title || '',
        meta: config.page.meta_description || '',
        blocks: hydrate(config.blocks),
        selectedId: null,
        panel: null,            // 'inserter' | 'list' | 'a11y' | null
        sidebarOpen: window.innerWidth >= 1100,
        sidebarTab: 'page',     // 'page' | 'block'
        insertTarget: null,     // { parentId, index } albo null (= po zaznaczonym)
        past: [],
        future: [],
        // Ustawienia z panelu bocznego formularza (publikacja, miejsce w serwisie) — wysyłane tylko po zmianie.
        settings: config.settings ? JSON.parse(JSON.stringify(config.settings.values)) : null,
        settingsDirty: false,
        dirty: false,
        saving: false,
        savedAt: null,
        error: null,
        warnings: new Map(),
        tableCell: null,
        buttonIndex: 0,
        slash: null,
        imported: !!config.imported,
    };

    // ── Drzewo ─────────────────────────────────────────────────────────────
    function find(id, list = S.blocks, parent = null) {
        for (let i = 0; i < list.length; i++) {
            const b = list[i];
            if (b.id === id) return { block: b, list, index: i, parent };
            if (b.inner) {
                const r = find(id, b.inner, b);
                if (r) return r;
            }
        }
        return null;
    }
    function walk(list, fn, depth = 0) {
        for (const b of list) { fn(b, depth); if (b.inner) walk(b.inner, fn, depth + 1); }
    }
    const isDescendant = (ancestorId, id) => { const a = find(ancestorId); let found = false; if (a?.block.inner) walk(a.block.inner, (b) => { if (b.id === id) found = true; }); return found; };

    // ── Historia (cofnij / ponów) ──────────────────────────────────────────
    const snapshot = () => JSON.stringify({ title: S.title, meta: S.meta, blocks: S.blocks });
    // Bloki do wysłania na serwer (bez lokalnych id).
    const serialize = (blocks) => JSON.parse(JSON.stringify(blocks, (key, v) => (key === 'id' ? undefined : v)));
    function pushHistory() {
        S.past.push(snapshot());
        if (S.past.length > 150) S.past.shift();
        S.future = [];
        lastTyping = 0;
        updateTopbar();
    }
    let lastTyping = 0;
    function typingHistory() {
        const now = Date.now();
        if (now - lastTyping > 1200) { S.past.push(snapshot()); S.future = []; if (S.past.length > 150) S.past.shift(); }
        lastTyping = now;
    }
    function restore(json) {
        const data = JSON.parse(json);
        S.title = data.title; S.meta = data.meta; S.blocks = data.blocks;
        if (S.selectedId && !find(S.selectedId)) S.selectedId = null;
        markDirty();
        renderAll();
    }
    function undo() { if (!S.past.length) return; S.future.push(snapshot()); restore(S.past.pop()); announce('Cofnięto zmianę'); }
    function redo() { if (!S.future.length) return; S.past.push(snapshot()); restore(S.future.pop()); announce('Przywrócono zmianę'); }

    // ── Pola typu strony (okno #be-type-fields z widoku, te same sekcje co w formularzu) ──
    const typeDlg = config.typeFields ? document.getElementById('be-type-fields') : null;
    const typeForm = typeDlg?.querySelector('form') || null;
    if (typeForm) {
        typeForm.addEventListener('input', () => markDirty());
        typeForm.addEventListener('change', () => markDirty());
        // Dodanie, usunięcie i przeniesienie wiersza repeatera też zmienia dane.
        typeForm.addEventListener('click', (e) => {
            if (e.target.closest('[data-repeater-add],[data-repeater-add-section],[data-repeater-remove],[data-repeater-move],[data-td-add],[data-td-remove],[data-schedule-add],[data-schedule-remove]')) setTimeout(markDirty);
        });
        typeDlg.addEventListener('close', () => sidebar.querySelector('[data-type-fields-open]')?.focus());
    }
    function openTypeFields() {
        typeDlg.showModal();
        typeDlg.dispatchEvent(new Event('be-open'));
        typeForm.querySelector('.be-type-fields-body input:not([type=hidden]), .be-type-fields-body select, .be-type-fields-body textarea')?.focus();
    }
    // Pola formularza jako obiekt jak w PHP (a[b][0][c]; przy powtórzonej nazwie wygrywa ostatnie pole).
    // Indeksy liczbowe zamieniamy na kolejne pozycje listy — kolejność jak na ekranie.
    function typeFieldsData() {
        window.tinymce?.triggerSave();
        const out = {};
        const slots = new WeakMap();
        for (const [name, value] of new FormData(typeForm)) {
            if (typeof value !== 'string') continue;
            const m = name.match(/^([^[\]]+)((?:\[[^\]]*\])*)$/);
            if (!m) continue;
            const keys = [m[1], ...[...m[2].matchAll(/\[([^\]]*)\]/g)].map((x) => x[1])];
            let node = out;
            keys.forEach((k, i) => {
                let key = k;
                if (Array.isArray(node)) {
                    if (!slots.has(node)) slots.set(node, new Map());
                    const map = slots.get(node);
                    if (k === '') key = node.length;
                    else { if (!map.has(k)) map.set(k, node.length); key = map.get(k); }
                }
                if (i === keys.length - 1) { node[key] = value; return; }
                if (node[key] === undefined || typeof node[key] !== 'object') node[key] = /^\d*$/.test(keys[i + 1]) ? [] : {};
                node = node[key];
            });
        }
        return out;
    }

    // ── Ustawienia aktualności: formularz klasyczny 1:1 w ramce (?osadzony=1, bez tytułu, treści,
    // opisu SEO i publikacji — te pola są w edytorze). Zapisywany po blokach, gdy coś w nim zmieniono.
    let newsDlg = null;
    let newsFrame = null;
    let newsSnap = null;     // stan pól po wczytaniu ramki; null = do zapisu (np. wrócił z błędami)
    let newsLoaded = null;   // obietnica kolejnego wczytania ramki (po wysłaniu formularza)
    const newsForm = () => newsFrame?.contentDocument?.getElementById('news-form') || null;
    function newsFormState(form) {
        newsFrame.contentWindow.tinymce?.triggerSave();
        return JSON.stringify([...new FormData(form)].map(([k, v]) => [k, typeof v === 'string' ? v : v.name + ':' + v.size]));
    }
    function openNewsSettings() {
        if (!newsDlg) {
            let onLoad = null;
            newsFrame = h('iframe', { src: config.urls.newsForm, title: 'Formularz ustawień aktualności', class: 'be-settings-frame' });
            newsFrame.addEventListener('load', () => {
                const form = newsForm();
                if (form) {
                    const errors = Number(newsFrame.contentDocument.querySelector('[data-embedded-errors]')?.dataset.embeddedErrors || 0);
                    newsSnap = errors ? null : newsFormState(form);
                    form.addEventListener('input', () => markDirty());
                    form.addEventListener('change', () => markDirty());
                    onLoad?.(errors);
                }
                onLoad = null;
            });
            newsLoaded = () => new Promise((resolve) => { onLoad = resolve; });
            newsDlg = h('dialog', { class: 'be-dialog be-dialog--frame', 'aria-labelledby': 'be-news-settings-title' },
                h('div', { class: 'be-dialog-head' },
                    h('h2', { id: 'be-news-settings-title' }, 'Ustawienia aktualności'),
                    h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': 'Zamknij ustawienia aktualności', onClick: () => newsDlg.close() }, icon('xmark'))),
                h('p', { class: 'be-muted' }, 'Wszystkie pola formularza aktualności poza tytułem, treścią, opisem SEO i publikacją (te są w edytorze). Zmiany zapiszesz przyciskiem „Zapisz” edytora — razem z treścią.'),
                newsFrame,
                h('div', { class: 'be-dialog-actions' }, h('button', { type: 'button', class: 'be-btn be-btn--primary', onClick: () => newsDlg.close() }, 'Gotowe')));
            newsDlg.addEventListener('close', () => sidebar.querySelector('[data-news-settings-open]')?.focus());
            document.body.append(newsDlg);
        }
        newsDlg.showModal();
    }
    // Wysyła formularz ramki, jeśli coś w nim zmieniono; rzuca błąd, gdy wrócił z błędami walidacji.
    async function saveNewsSettings() {
        const form = newsForm();
        if (!form || (newsSnap !== null && newsFormState(form) === newsSnap)) return;
        const loaded = newsLoaded();
        form.requestSubmit();
        const errors = await loaded;
        if (errors) {
            if (!newsDlg.open) newsDlg.showModal();
            throw new Error('Ustawienia aktualności: popraw zaznaczone pola w oknie ustawień.');
        }
    }

    function markDirty() {
        S.dirty = true;
        S.error = null;
        scheduleDraft();
        scheduleWarnings();
        updateTopbar();
    }

    // Kopia robocza w przeglądarce — chroni przed utratą zmian przy zamknięciu karty.
    let draftTimer = null;
    function scheduleDraft() {
        clearTimeout(draftTimer);
        draftTimer = setTimeout(() => {
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify({ at: Date.now(), data: snapshot() })); } catch (e) { /* brak dostępu do pamięci */ }
        }, 800);
    }
    function clearDraft() { clearTimeout(draftTimer); try { localStorage.removeItem(DRAFT_KEY); } catch (e) { /* j.w. */ } }
    function readDraft() {
        try {
            const d = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null');
            return d && d.data && d.data !== snapshot() ? d : null;
        } catch (e) { return null; }
    }

    // ── Operacje strukturalne ──────────────────────────────────────────────
    function insertBlocks(blocks, target) {
        pushHistory();
        let list = S.blocks;
        let index = list.length;
        if (target?.parentId) {
            const p = find(target.parentId);
            if (p) { list = p.block.inner; index = target.index ?? list.length; }
        } else if (target?.afterId) {
            const r = find(target.afterId);
            if (r) { list = r.list; index = r.index + 1; }
        } else if (target?.beforeId) {
            const r = find(target.beforeId);
            if (r) { list = r.list; index = r.index; }
        } else if (target && 'index' in target) {
            index = target.index;
        }
        // Kolumny przyjmują wyłącznie bloki „Kolumna”.
        const parent = list === S.blocks ? null : findParentOfList(list);
        if (parent?.type === 'columns') blocks = blocks.map((b) => (b.type === 'column' ? b : block('column', {}, [b])));
        list.splice(index, 0, ...blocks);
        markDirty();
        renderCanvas();
        return blocks;
    }
    function findParentOfList(list) {
        let parent = null;
        walk(S.blocks, (b) => { if (b.inner === list) parent = b; });
        return parent;
    }
    function removeBlock(id, { focusPrev = true } = {}) {
        const r = find(id);
        if (!r) return;
        const prevId = focusPrev ? neighbourEditableBlockId(id, -1) : null;
        pushHistory();
        r.list.splice(r.index, 1);
        // Kolumny bez kolumn znikają; kolumna nie zostaje sama.
        if (r.parent?.type === 'columns' && r.list.length === 0) {
            const pr = find(r.parent.id);
            pr?.list.splice(pr.index, 1);
        }
        S.selectedId = null;
        markDirty();
        renderAll();
        announce('Usunięto blok');
        if (prevId && find(prevId)) focusBlock(prevId, 'end');
        else {
            const next = r.list[Math.min(r.index, r.list.length - 1)];
            if (next) focusBlock(next.id, 'start');
        }
    }
    function moveBlock(id, dir) {
        const r = find(id);
        if (!r) return;
        const to = r.index + dir;
        if (to < 0 || to >= r.list.length) { announce(dir < 0 ? 'Blok jest już na początku' : 'Blok jest już na końcu'); return; }
        pushHistory();
        r.list.splice(r.index, 1);
        r.list.splice(to, 0, r.block);
        markDirty();
        renderCanvas();
        selectBlock(id, { focusWrapper: true });
        announce(`Przeniesiono blok ${dir < 0 ? 'w górę' : 'w dół'} — pozycja ${to + 1} z ${r.list.length}`);
    }
    function moveBlockTo(id, targetId, after) {
        if (id === targetId || isDescendant(id, targetId)) return;
        const src = find(id);
        const dst = find(targetId);
        if (!src || !dst) return;
        const dstParent = dst.parent;
        if ((src.block.type === 'column') !== (dstParent?.type === 'columns')) return;
        pushHistory();
        src.list.splice(src.index, 1);
        const d = find(targetId);
        d.list.splice(d.index + (after ? 1 : 0), 0, src.block);
        if (src.parent?.type === 'columns' && src.list.length === 0) {
            const pr = find(src.parent.id);
            pr?.list.splice(pr.index, 1);
        }
        markDirty();
        renderCanvas();
        selectBlock(id);
        announce('Przeniesiono blok');
    }
    function duplicateBlock(id) {
        const r = find(id);
        if (!r) return;
        const copy = withNewIds(r.block);
        insertBlocks([copy], { afterId: id });
        selectBlock(copy.id, { focusWrapper: true });
        announce('Zduplikowano blok');
    }
    function replaceBlock(id, blocks, focus = 'start') {
        const r = find(id);
        if (!r) return;
        pushHistory();
        r.list.splice(r.index, 1, ...blocks);
        markDirty();
        renderCanvas();
        if (blocks[0]) focusBlock(blocks[blocks.length - 1].id, focus);
    }
    function updateAttrs(b, patch, { rerender = true, typing = false } = {}) {
        typing ? typingHistory() : pushHistory();
        Object.assign(b.attrs, patch);
        markDirty();
        if (rerender) rerenderBlock(b.id);
    }

    // Przekształcenia między typami (pasek bloku → ikona typu).
    function transform(b, to) {
        const text = b.type === 'list' ? b.attrs.items.join('<br>') : (b.attrs.text || '');
        let out;
        if (to === 'callout') out = [block('callout', {}, [clone({ ...b, id: uid() })])];
        else if (b.type === 'list' && to === 'paragraph') out = b.attrs.items.filter((i) => textOf(i)).map((i) => block('paragraph', { text: i }));
        else if (to === 'list') out = [block('list', { items: text.split(/<br\s*\/?>/i).filter((t) => textOf(t)) })];
        else if (to === 'heading') out = [block('heading', { text, align: b.attrs.align || 'left' })];
        else out = [block(to, { text })];
        if (!out.length) out = [block('paragraph')];
        replaceBlock(b.id, out, 'end');
        announce('Zmieniono blok na: ' + BLOCKS[to].title);
    }

    // ── Konwersja HTML → bloki (treść z klasycznego edytora, wklejanie) ────
    function htmlToBlocks(html) {
        const doc = new DOMParser().parseFromString('<body>' + (html || '') + '</body>', 'text/html');
        return nodesToBlocks([...doc.body.childNodes]);
    }
    // Klasy, które mają style na stronie (arkusze wczytane w edytorze, m.in. app.css) — takich <div> nie rozpakowujemy.
    let styledClasses = null;
    function isStyledClass(name) {
        if (!styledClasses) {
            styledClasses = new Set();
            const collect = (rules) => {
                for (const r of rules || []) {
                    if (r.selectorText) for (const m of r.selectorText.matchAll(/\.([A-Za-z0-9_-]+)/g)) styledClasses.add(m[1]);
                    if (r.cssRules) collect(r.cssRules);
                }
            };
            for (const sheet of document.styleSheets) { try { collect(sheet.cssRules); } catch (e) { /* arkusz z innej domeny */ } }
        }
        return styledClasses.has(name);
    }
    function isWrapperDiv(n) {
        return !n.id && !n.getAttribute('style') && ![...n.classList].some(isStyledClass);
    }
    function nodesToBlocks(nodes) {
        const out = [];
        const SHORTCODE = /^\[[a-z0-9_-]+(?::[^\]\r\n]{1,200})?\]$/i;
        const alignOf = (el) => (el.className.match(/has-text-align-(center|right)/) || [])[1] || (['center', 'right'].includes(el.style?.textAlign) ? el.style.textAlign : 'left');
        for (const n of nodes) {
            if (n.nodeType === 3) {
                if (n.textContent.trim()) out.push(block('paragraph', { text: escapeHtml(n.textContent.trim()) }));
                continue;
            }
            if (n.nodeType !== 1) continue;
            const tag = n.tagName;
            const cls = n.classList;
            if (tag === 'P') {
                const t = n.textContent.replace(/ /g, ' ').trim();
                const imgs = n.querySelectorAll('img');
                const ctas = n.querySelectorAll('a.cta-button');
                if (!t && !imgs.length) continue;
                if (SHORTCODE.test(t) && n.children.length === 0) { out.push(listBlockFromCode(t) || block('shortcode', { code: t })); continue; }
                if (imgs.length === 1 && !t) { out.push(imageFrom(imgs[0], n.querySelector('a')?.getAttribute('href'))); continue; }
                if (ctas.length && ctas.length === n.querySelectorAll('a').length && t === [...ctas].map((a) => a.textContent.trim()).join(' ').trim()) {
                    out.push(block('buttons', { align: alignOf(n), items: [...ctas].map((a) => ({ label: a.textContent.trim(), url: a.getAttribute('href') || '', variant: a.classList.contains('cta-red') ? 'red' : (a.classList.contains('cta-green') ? 'green' : 'brand'), newTab: a.target === '_blank' })) }));
                    continue;
                }
                if (cls.contains('content-file') && n.querySelector('a')) {
                    const a = n.querySelector('a');
                    out.push(block('file', { url: a.getAttribute('href') || '', label: (a.firstChild?.textContent || a.textContent).trim(), description: n.querySelector('.content-file-desc')?.textContent.trim() || '' }));
                    continue;
                }
                out.push(block('paragraph', { text: cleanInline(n.innerHTML), align: alignOf(n), lead: cls.contains('is-lead') }));
            } else if (/^H[1-6]$/.test(tag)) {
                if (!n.textContent.trim()) continue;
                out.push(block('heading', { text: cleanInline(n.innerHTML), level: Math.max(2, Math.min(4, Number(tag[1]))), align: alignOf(n), anchor: n.id || '' }));
            } else if (tag === 'UL' || tag === 'OL') {
                const items = [...n.children].filter((li) => li.tagName === 'LI').map((li) => cleanInline(li.innerHTML)).filter((i) => textOf(i));
                if (items.length) out.push(block('list', { ordered: tag === 'OL', items }));
            } else if (tag === 'BLOCKQUOTE') {
                out.push(block('quote', { text: cleanInline(n.innerHTML), cite: cleanInline(n.querySelector('footer, cite')?.innerHTML || '') }));
            } else if (tag === 'FIGURE') {
                const img = n.querySelector('img');
                const iframe = n.querySelector('iframe');
                const bq = n.querySelector('blockquote');
                const cap = n.querySelector('figcaption');
                if (bq) out.push(block('quote', { text: cleanInline(bq.innerHTML), cite: cleanInline((cap?.innerHTML || '').replace(/^\s*[—–-]\s*/, '')) }));
                else if (iframe) out.push(block('embed', { url: iframe.getAttribute('src') || '', title: iframe.getAttribute('title') || '', caption: cleanInline(cap?.innerHTML || '') }));
                else if (img) {
                    const b = imageFrom(img, n.querySelector('a')?.getAttribute('href'));
                    b.attrs.caption = cleanInline(cap?.innerHTML || '');
                    const w = (n.className.match(/content-figure--(full|wide|medium|small)/) || [])[1];
                    if (w) b.attrs.width = w;
                    out.push(b);
                } else out.push(block('html', { html: n.outerHTML }));
            } else if (tag === 'IMG') {
                out.push(imageFrom(n));
            } else if (tag === 'HR') {
                out.push(block('separator'));
            } else if (tag === 'TABLE') {
                const trs = [...n.querySelectorAll('tr')];
                if (!trs.length) continue;
                const rows = trs.map((tr) => [...tr.children].map((c) => cleanInline(c.innerHTML)));
                const width = Math.max(...rows.map((r) => r.length));
                rows.forEach((r) => { while (r.length < width) r.push(''); });
                const headerRow = !!n.querySelector('thead') || [...trs[0].children].every((c) => c.tagName === 'TH');
                const headerColumn = trs.slice(1).length > 0 && trs.slice(1).every((tr) => tr.firstElementChild?.tagName === 'TH');
                out.push(block('table', { caption: n.querySelector('caption')?.textContent.trim() || '', headerRow, headerColumn, rows }));
            } else if (tag === 'DETAILS') {
                const summary = n.querySelector(':scope > summary');
                const body = [...n.childNodes].filter((c) => c !== summary);
                const inner = nodesToBlocks(body.flatMap((c) => (c.nodeType === 1 && c.classList.contains('content-details-body') ? [...c.childNodes] : [c])));
                out.push(block('details', { summary: summary?.textContent.trim() || '', open: n.open }, inner.length ? inner : [block('paragraph')]));
            } else if (tag === 'DIV' && cls.contains('content-columns')) {
                const cols = [...n.children].map((c) => block('column', {}, nodesToBlocks([...c.childNodes]).concat([]).filter(Boolean)));
                cols.forEach((c) => { if (!c.inner.length) c.inner.push(block('paragraph')); });
                const ratio = (n.className.match(/content-columns--(33-67|67-33)/) || [])[1] || 'equal';
                if (cols.length) out.push(block('columns', { ratio }, cols.slice(0, 4)));
            } else if (tag === 'DIV' && (cls.contains('content-box') || cls.contains('content-note') || cls.contains('content-important'))) {
                const variant = cls.contains('content-note') ? 'note' : (cls.contains('content-important') ? 'important' : 'box');
                const inner = nodesToBlocks([...n.childNodes]);
                out.push(block('callout', { variant }, inner.length ? inner : [block('paragraph')]));
            } else if (tag === 'DIV' && cls.contains('content-gallery')) {
                const images = [...n.querySelectorAll('img')].map((img) => ({ url: img.getAttribute('src') || '', alt: img.getAttribute('alt') || '', caption: img.closest('figure')?.querySelector('figcaption')?.textContent.trim() || '' }));
                const cols = Number((n.className.match(/content-gallery--cols-(\d)/) || [])[1] || 3);
                out.push(block('gallery', { images, columns: cols }));
            } else if (tag === 'DIV' && isWrapperDiv(n)) {
                // Opakowania bez wyglądu (np. wklejka z Teams/Worda: role, aria-*, tabindex, klasy bez stylów) — rozpakowujemy.
                out.push(...nodesToBlocks([...n.childNodes]));
            } else if (tag === 'IFRAME' && embedSrc(n.getAttribute('src') || '')) {
                out.push(block('embed', { url: n.getAttribute('src'), title: n.getAttribute('title') || '' }));
            } else if (['B', 'STRONG', 'EM', 'I', 'A', 'SPAN', 'CODE', 'BR'].includes(tag)) {
                const html = cleanInline(n.outerHTML);
                if (textOf(html)) out.push(block('paragraph', { text: html }));
            } else {
                out.push(block('html', { html: n.outerHTML }));
            }
        }
        // Sąsiadujące bloki HTML łączymy w jeden.
        return out.reduce((acc, b) => {
            const last = acc[acc.length - 1];
            if (last && last.type === 'html' && b.type === 'html') last.attrs.html += '\n' + b.attrs.html;
            else acc.push(b);
            return acc;
        }, []);
    }
    function imageFrom(img, href) {
        const alt = img.getAttribute('alt');
        return block('image', { url: img.getAttribute('src') || '', alt: alt || '', decorative: alt === '', href: href && safeUrl(href) ? href : '' });
    }

    // ── Kursor ─────────────────────────────────────────────────────────────
    function placeCaret(el, atStart) {
        el.focus({ preventScroll: false });
        const sel = window.getSelection();
        const r = document.createRange();
        r.selectNodeContents(el);
        r.collapse(!!atStart);
        sel.removeAllRanges();
        sel.addRange(r);
    }
    function caretEdge(el, start) {
        const sel = window.getSelection();
        if (!sel.rangeCount || !sel.isCollapsed) return false;
        const r = sel.getRangeAt(0);
        if (!el.contains(r.startContainer)) return false;
        const probe = document.createRange();
        probe.selectNodeContents(el);
        if (start) probe.setEnd(r.startContainer, r.startOffset);
        else probe.setStart(r.endContainer, r.endOffset);
        const frag = probe.cloneContents();
        return probe.toString().replace(/​/g, '').length === 0 && !frag.querySelector?.('img');
    }
    function setCaretAtTextOffset(el, offset) {
        el.focus();
        const tw = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
        let node; let left = offset;
        while ((node = tw.nextNode())) {
            if (left <= node.textContent.length) {
                const r = document.createRange();
                r.setStart(node, left);
                r.collapse(true);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(r);
                return;
            }
            left -= node.textContent.length;
        }
        placeCaret(el, false);
    }
    const isEmptyEl = (el) => el.textContent.replace(/[​ ]/g, '').trim() === '' && !el.querySelector('img');
    function splitAtCaret(el) {
        const sel = window.getSelection();
        const r = sel.getRangeAt(0);
        if (!r.collapsed) r.deleteContents();
        const tail = document.createRange();
        tail.setStart(r.endContainer, r.endOffset);
        tail.setEnd(el, el.childNodes.length);
        const frag = tail.extractContents();
        const div = document.createElement('div');
        div.appendChild(frag);
        return [el.innerHTML.replace(/(<br\s*\/?>\s*)+$/i, ''), div.innerHTML.replace(/^(<br\s*\/?>\s*)+/i, '')];
    }
    function editablesIn(scope = canvas) { return [...scope.querySelectorAll('[data-be-editable]')]; }
    function neighbourEditableBlockId(id, dir) {
        const wrap = wrapperOf(id);
        if (!wrap) return null;
        const all = editablesIn();
        const own = editablesIn(wrap);
        const idx = dir < 0 ? all.indexOf(own[0]) - 1 : all.indexOf(own[own.length - 1]) + 1;
        const el = all[idx];
        return el ? el.closest('.be-block')?.dataset.id : null;
    }
    const wrapperOf = (id) => canvas.querySelector(`.be-block[data-id="${id}"]`);
    // Płótno renderuje się synchronicznie, więc fokus ustawiamy od razu
    // (requestAnimationFrame bywa wstrzymywany w nieaktywnej karcie).
    function focusBlock(id, where = 'end') {
        const wrap = wrapperOf(id);
        if (!wrap) return;
        const eds = editablesIn(wrap);
        const el = where === 'start' ? eds[0] : eds[eds.length - 1];
        if (el) { placeCaret(el, where === 'start'); selectBlock(id); } else selectBlock(id, { focusWrapper: true });
    }

    // Kafelki strony (pole „Kafelki na stronie”, nawigacja kafelkowa podstron) — tylko podgląd w miejscu,
    // w którym pokazuje je strona; edytuje się je w formularzu strony.
    function pageTilesPreview(html, where) {
        if (!html) return null;
        return h('section', { class: 'be-page-tiles', 'aria-label': 'Kafelki strony ' + where + ' (podgląd)' },
            h('p', { class: 'be-page-tiles-note' }, icon('table-cells-large'), ' Kafelki strony ' + where + ' — podgląd. ',
                h('a', { href: config.urls.admin }, 'Zmień je w formularzu strony'), '.'),
            h('div', { inert: true, 'aria-hidden': 'true', class: 'be-page-tiles-body' }, sanitizePreview(html)));
    }

    // ── Szkielet interfejsu ────────────────────────────────────────────────
    const live = h('div', { class: 'be-sr-only', role: 'status', 'aria-live': 'polite' });
    let liveTimer = null;
    function announce(msg) { live.textContent = ''; clearTimeout(liveTimer); liveTimer = setTimeout(() => { live.textContent = msg; }, 50); }

    const topbar = h('header', { class: 'be-topbar', role: 'banner' });
    const leftPanel = h('aside', { class: 'be-panel be-panel--left', hidden: true });
    const canvas = h('div', { class: 'be-canvas-inner prose max-w-none text-ink' });
    const titleEl = h('h1', { class: 'be-title', contenteditable: 'true', role: 'textbox', 'aria-label': L.title, 'data-placeholder': 'Dodaj tytuł', spellcheck: 'true' });
    const notices = h('div', { class: 'be-notices' });
    // Działanie: płótno w układzie strony działania — kategoria, tytuł z kreską, „Opis działania”
    // i obok podgląd menu bocznego (tylko podgląd; menu ustawia się w formularzu działania).
    const projLayout = config.layout && config.layout.kind === 'project' ? config.layout : null;
    const canvasPage = h('div', { class: 'be-canvas-page' + (projLayout ? ' be-canvas-page--project' : '') },
        notices,
        projLayout && projLayout.kicker ? h('p', { class: 'be-proj-kicker' }, projLayout.kicker) : null,
        titleEl,
        projLayout ? [h('span', { class: 'be-proj-bar', 'aria-hidden': 'true' }), h('h2', { class: 'be-proj-h2' }, projLayout.heading)] : null,
        pageTilesPreview(config.pageTiles?.before, 'nad treścią'),
        canvas,
        pageTilesPreview(config.pageTiles?.after, 'pod treścią'));
    const projNav = projLayout ? h('aside', { class: 'be-proj-nav', 'aria-label': 'Podgląd menu bocznego działania' },
        h('p', { class: 'be-proj-nav-title' }, projLayout.navTitle),
        h('span', { class: 'be-proj-bar', 'aria-hidden': 'true' }),
        h('ul', { class: 'be-proj-nav-list' }, (projLayout.nav || []).map((t, i) => h('li', { class: i === 0 ? 'is-current' : null }, t))),
        h('p', { class: 'be-proj-nav-note' }, 'Podgląd. Sekcje i podstrony ustawiasz w formularzu działania.')) : null;
    const canvasWrap = h('main', { class: 'be-canvas', id: 'be-canvas', 'aria-label': L.contentLabel },
        projLayout ? h('div', { class: 'be-proj' }, canvasPage, projNav) : canvasPage);
    const sidebar = h('aside', { class: 'be-panel be-panel--right', 'aria-label': 'Ustawienia' });
    const toolbar = h('div', { class: 'be-toolbar', role: 'toolbar', 'aria-label': 'Narzędzia bloku' });
    const slashMenu = h('div', { class: 'be-slash', role: 'listbox', id: 'be-slash', 'aria-label': 'Wybierz blok', hidden: true });
    const dropLine = h('div', { class: 'be-drop-line', hidden: true, 'aria-hidden': 'true' });

    mount.append(
        h('a', { class: 'be-skip', href: '#be-canvas' }, 'Przejdź do treści'),
        topbar,
        h('div', { class: 'be-layout' }, leftPanel, canvasWrap, sidebar),
        slashMenu, dropLine, live,
    );

    titleEl.textContent = S.title;
    titleEl.addEventListener('input', () => {
        typingHistory();
        S.title = titleEl.textContent.replace(/\n/g, ' ');
        titleEl.classList.toggle('is-empty', !S.title.trim());
        const side = sidebar.querySelector('#be-page-title');
        if (side) side.value = S.title;
        markDirty();
    });
    titleEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const first = S.blocks[0];
            if (first && ['paragraph', 'heading'].includes(first.type) && !textOf(first.attrs.text)) focusBlock(first.id, 'start');
            else { const p = block('paragraph'); insertBlocks([p], { index: 0 }); focusBlock(p.id, 'start'); }
        }
    });
    titleEl.addEventListener('paste', (e) => { e.preventDefault(); document.execCommand('insertText', false, e.clipboardData.getData('text/plain').replace(/\s+/g, ' ')); });
    titleEl.addEventListener('focus', () => { S.selectedId = null; S.sidebarTab = 'page'; applySelection(); renderSidebar(); });
    titleEl.classList.toggle('is-empty', !S.title.trim());

    // ── Górny pasek ────────────────────────────────────────────────────────
    function tbButton(label, iconName, onClick, extra = {}) {
        return h('button', { type: 'button', class: 'be-btn be-btn--icon', 'data-key': label, 'aria-label': label, title: label + (extra.shortcut ? ` (${extra.shortcut})` : ''), onClick, ...extra.attrs }, icon(iconName), extra.text ? h('span', { class: 'be-btn-text' }, extra.text) : null);
    }
    const mod = /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘' : 'Ctrl';
    function updateTopbar() {
        const focusedKey = topbar.contains(document.activeElement) ? document.activeElement.dataset.key : null;
        const count = [...S.warnings.values()].reduce((n, w) => n + w.length, 0);
        const saveLabel = S.saving ? 'Zapisywanie…' : (S.dirty || !S.savedAt ? 'Zapisz' : 'Zapisano');
        const status = S.error ? h('span', { class: 'be-status be-status--error', role: 'alert' }, icon('triangle-exclamation'), ' ', S.error)
            : h('span', { class: 'be-status' }, S.dirty ? 'Niezapisane zmiany' : (S.savedAt ? 'Zapisano o ' + S.savedAt : ''));
        topbar.replaceChildren(
            h('div', { class: 'be-topbar-start' },
                h('a', { href: config.urls.view, class: 'be-btn be-btn--icon be-back', 'aria-label': L.back, title: L.back }, icon('arrow-left')),
                tbButton('Dodaj blok', 'plus', () => togglePanel('inserter'), { shortcut: '/', attrs: { 'aria-pressed': String(S.panel === 'inserter'), class: 'be-btn be-btn--icon be-btn--primary-soft' } }),
                tbButton('Cofnij', 'rotate-left', undo, { shortcut: mod + '+Z', attrs: { 'aria-disabled': String(!S.past.length) } }),
                tbButton('Ponów', 'rotate-right', redo, { shortcut: mod + '+Shift+Z', attrs: { 'aria-disabled': String(!S.future.length) } }),
                tbButton('Widok listy', 'bars-staggered', () => togglePanel('list'), { attrs: { 'aria-pressed': String(S.panel === 'list') } }),
                config.urls.templates ? tbButton('Dodaj z szablonu / Zarządzaj szablonami', 'layer-group', openTemplatesDialog) : null,
                tbButton(config.urls.docx ? 'Importuj z pliku (DOCX, TXT)' : 'Importuj z pliku TXT', 'file-import', () => importInput.click()),
                h('button', { type: 'button', 'data-key': 'a11y', class: 'be-btn be-a11y-btn' + (count ? ' has-issues' : ''), 'aria-pressed': String(S.panel === 'a11y'), onClick: () => togglePanel('a11y') },
                    icon('universal-access'), h('span', {}, count ? `Dostępność: ${count} ${plural(count, 'uwaga', 'uwagi', 'uwag')}` : 'Dostępność: OK')),
            ),
            h('div', { class: 'be-topbar-center' },
                h('span', { class: 'be-doc-title' }, S.title || 'Bez tytułu'),
                h('span', { class: 'be-doc-meta' }, config.page.type + ' · ' + (config.page.is_published ? L.published : 'szkic')),
            ),
            h('div', { class: 'be-topbar-end' },
                status,
                h('a', { href: config.urls.view, target: '_blank', rel: 'noopener', class: 'be-btn be-btn--ghost' }, L.view, h('span', { class: 'be-sr-only' }, ' (otwiera się w nowej karcie)'), ' ', icon('arrow-up-right-from-square')),
                h('button', { type: 'button', 'data-key': 'save', class: 'be-btn be-btn--primary', onClick: save, 'aria-disabled': String(S.saving), title: `Zapisz (${mod}+S)` }, saveLabel),
                tbButton('Ustawienia', 'gear', () => { S.sidebarOpen = !S.sidebarOpen; renderSidebar(); updateTopbar(); }, { attrs: { 'aria-pressed': String(S.sidebarOpen), 'aria-controls': 'be-sidebar' } }),
                tbButton('Skróty klawiszowe', 'keyboard', openShortcuts),
            ),
        );
        if (focusedKey) topbar.querySelector(`[data-key="${focusedKey}"]`)?.focus();
    }
    // Import treści z pliku (jak „Import” klasycznego edytora): DOCX przez serwer (czysty HTML) albo TXT
    // (akapity rozdzielone pustą linią). Bloki trafiają w miejsce wstawiania.
    const importInput = h('input', { type: 'file', accept: (config.urls.docx ? '.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document,' : '') + '.txt,text/plain', class: 'be-sr-only', tabindex: '-1', 'aria-hidden': 'true' });
    importInput.addEventListener('change', async () => {
        const file = importInput.files?.[0];
        importInput.value = '';
        if (!file) return;
        try {
            let blocks;
            if (/\.docx$/i.test(file.name)) {
                if (!config.urls.docx) throw new Error('Import DOCX nie jest dostępny w tej wersji.');
                announce('Importowanie pliku…');
                const fd = new FormData();
                fd.append('docx', file);
                const res = await fetch(config.urls.docx, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || json.error) throw new Error(json.error || json.message || 'Błąd serwera (' + res.status + ').');
                blocks = htmlToBlocks(json.html || '');
            } else {
                const text = await file.text();
                blocks = text.replace(/\r\n?/g, '\n').split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean)
                    .map((p) => block('paragraph', { text: escapeHtml(p).replace(/\n/g, '<br>') }));
            }
            if (!blocks.length) { announce('Plik nie zawiera treści do zaimportowania.'); return; }
            insertFromPanel(blocks);
            announce(`Zaimportowano ${blocks.length} ${plural(blocks.length, 'blok', 'bloki', 'bloków')} z pliku ${file.name}`);
        } catch (e) {
            S.error = 'Nie udało się zaimportować pliku: ' + e.message;
            updateTopbar();
        }
    });
    mount.append(importInput);

    // Szablony treści — to samo co „Dodaj z szablonu / Zarządzaj szablonami” w formularzu (admin/szablony).
    // Szablon z edytora blokowego ma bloki (i HTML do klasycznego); szablon z formularza — HTML zamieniany na bloki.
    async function templatesRequest(path = '', opts = {}) {
        const res = await fetch(config.urls.templates.base + path, { ...opts, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', ...(opts.body ? { 'Content-Type': 'application/json' } : {}) } });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(Object.values(json.errors || {})[0]?.[0] || json.message || 'Błąd serwera (' + res.status + ').');
        return json;
    }
    function openTemplatesDialog() {
        const T = config.urls.templates;
        const n = ++dialogSeq;
        let tab = 'load';
        let templates = [];
        const trigger = document.activeElement;
        const msg = h('p', { class: 'be-help', role: 'status', 'aria-live': 'polite' });
        const error = h('p', { class: 'be-error', role: 'alert' });
        const body = h('div', {});
        const dlg = h('dialog', { class: 'be-dialog', 'aria-labelledby': `be-tpl-title-${n}` });
        const done = () => { dlg.close(); dlg.remove(); if (trigger?.isConnected) trigger.focus(); };
        const tabBtn = (key, label, iconName) => h('button', { type: 'button', role: 'tab', class: 'be-tab', id: `be-tpl-tab-${key}-${n}`, 'aria-selected': String(tab === key), 'aria-controls': `be-tpl-panel-${n}`, onClick: () => { tab = key; draw(); dlg.querySelector('[role=tab][aria-selected=true]')?.focus(); } }, icon(iconName), ' ' + label);
        const tabs = h('div', { class: 'be-tabs', role: 'tablist', 'aria-label': 'Zakładki szablonów' });
        const apply = async (t, mode) => {
            error.textContent = '';
            let data;
            try { data = await templatesRequest('/' + t.id + '/dane'); } catch (e) { error.textContent = 'Nie udało się załadować szablonu: ' + e.message; return; }
            const blocks = Array.isArray(data.blocks) ? hydrate(data.blocks) : htmlToBlocks(data.content || '');
            if (!blocks.length) { error.textContent = 'Szablon nie zawiera treści.'; return; }
            done();
            if (mode === 'replace') {
                pushHistory();
                S.blocks = blocks;
                if (data.meta_description && !S.meta) S.meta = String(data.meta_description);
                markDirty(); renderCanvas(); renderSidebar();
                focusBlock(blocks[0].id, 'start');
            } else insertFromPanel(blocks);
            announce(`Załadowano szablon „${t.name}” (${blocks.length} ${plural(blocks.length, 'blok', 'bloki', 'bloków')})`);
        };
        const draw = () => {
            tabs.replaceChildren(tabBtn('load', 'Dodaj z szablonu', 'file-import'), tabBtn('manage', 'Zarządzaj szablonami', 'list-check'));
            const list = (rowFor) => templates.length
                ? h('ul', { class: 'be-tpl-list' }, ...templates.map((t) => h('li', {}, h('span', { class: 'be-tpl-name' }, t.name), ...rowFor(t))))
                : h('p', { class: 'be-muted' }, tab === 'load' ? 'Brak szablonów tego typu. Zapisz bieżącą treść jako szablon w zakładce „Zarządzaj szablonami”.' : 'Brak zapisanych szablonów.');
            if (tab === 'load') {
                body.replaceChildren(
                    h('p', { class: 'be-help' }, '„Wstaw” dodaje bloki szablonu w miejscu kursora, „Zastąp” zamienia całą treść (można to cofnąć).'),
                    list((t) => [
                        h('button', { type: 'button', class: 'be-btn be-btn--secondary', 'aria-label': 'Wstaw szablon: ' + t.name, onClick: () => apply(t, 'insert') }, 'Wstaw'),
                        h('button', { type: 'button', class: 'be-btn be-btn--primary', 'aria-label': 'Zastąp treść szablonem: ' + t.name, onClick: () => apply(t, 'replace') }, 'Zastąp')]));
            } else {
                const name = h('input', { type: 'text', class: 'be-input', id: `be-tpl-name-${n}`, maxlength: '120', placeholder: 'np. Szkolenie FEER — standardowy' });
                const save = async () => {
                    error.textContent = '';
                    if (!name.value.trim()) { error.textContent = 'Podaj nazwę szablonu.'; name.focus(); return; }
                    try {
                        const json = await templatesRequest('', { method: 'POST', body: JSON.stringify({ type: T.type, name: name.value.trim(), data: { blocks: serialize(S.blocks), meta_description: S.meta } }) });
                        msg.textContent = json.status || 'Zapisano szablon.';
                        await load();
                    } catch (e) { error.textContent = 'Nie udało się zapisać szablonu: ' + e.message; }
                };
                name.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); save(); } });
                body.replaceChildren(
                    T.store ? h('section', { class: 'be-tpl-save' },
                        h('label', { for: name.id }, 'Zapisz bieżącą treść jako szablon'),
                        h('div', { class: 'be-row' }, name, h('button', { type: 'button', class: 'be-btn be-btn--primary', onClick: save }, icon('floppy-disk'), ' Zapisz'))) : h('p', { class: 'be-muted' }, 'Zapisywanie szablonów nie jest dostępne w tej wersji.'),
                    h('h3', {}, 'Zapisane szablony'),
                    list((t) => [h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', 'aria-label': 'Usuń szablon: ' + t.name, onClick: async () => {
                        if (!window.confirm(`Usunąć szablon „${t.name}”?`)) return;
                        try { await templatesRequest('/' + t.id, { method: 'DELETE' }); msg.textContent = `Szablon „${t.name}” usunięty.`; await load(); } catch (e) { error.textContent = 'Nie udało się usunąć szablonu: ' + e.message; }
                    } }, icon('trash'), ' Usuń')]));
            }
        };
        const load = async () => {
            try { templates = await templatesRequest('?type=' + encodeURIComponent(T.type)); } catch (e) { error.textContent = 'Nie udało się wczytać szablonów: ' + e.message; templates = []; }
            draw();
        };
        dlg.append(
            h('div', { class: 'be-dialog-head' }, h('h2', { id: `be-tpl-title-${n}` }, 'Szablony treści'),
                h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': 'Zamknij okno', onClick: done }, icon('xmark'))),
            tabs, h('div', { role: 'tabpanel', id: `be-tpl-panel-${n}`, class: 'be-mt' }, body), msg, error);
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); done(); });
        document.body.append(dlg);
        dlg.showModal();
        draw();
        load();
    }

    function plural(n, one, few, many) {
        if (n === 1) return one;
        const d = n % 10; const t = n % 100;
        return d >= 2 && d <= 4 && (t < 12 || t > 14) ? few : many;
    }

    // ── Lewy panel: wstawianie, widok listy, dostępność ────────────────────
    function togglePanel(name, target = null) {
        S.panel = S.panel === name && !target ? null : name;
        S.insertTarget = target;
        renderPanel();
        updateTopbar();
        if (S.panel === 'inserter') leftPanel.querySelector('input')?.focus();
        else if (S.panel) leftPanel.querySelector('button, a')?.focus();
    }
    function closePanel() { S.panel = null; S.insertTarget = null; renderPanel(); updateTopbar(); }

    function renderPanel() {
        leftPanel.hidden = !S.panel;
        if (!S.panel) { leftPanel.replaceChildren(); return; }
        const close = h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': 'Zamknij panel', onClick: closePanel }, icon('xmark'));
        if (S.panel === 'inserter') renderInserter(close);
        if (S.panel === 'list') renderListView(close);
        if (S.panel === 'a11y') renderA11yPanel(close);
    }

    function insertionTarget() {
        if (S.insertTarget) return S.insertTarget;
        if (S.selectedId) {
            const r = find(S.selectedId);
            if (r?.block.type === 'column') return { parentId: r.block.id };
            if (r) return { afterId: r.block.id };
        }
        return null;
    }
    function insertFromPanel(blocks) {
        const target = insertionTarget();
        // Pusty akapit na pozycji wstawiania zamieniamy (jak w Gutenbergu).
        const sel = S.selectedId && !S.insertTarget ? find(S.selectedId) : null;
        if (sel && sel.block.type === 'paragraph' && !textOf(sel.block.attrs.text)) replaceBlock(sel.block.id, blocks);
        else insertBlocks(blocks, target);
        closePanel();
        const first = blocks[0];
        if (first) {
            const def = BLOCKS[first.type];
            def.rich || def.container ? focusBlock(first.id, 'start') : selectBlock(first.id, { focusWrapper: true });
        }
        announce('Wstawiono: ' + (blocks.length > 1 ? 'wzorzec' : BLOCKS[first.type].title));
    }

    let inserterTab = 'blocks';
    function renderInserter(close) {
        const search = h('input', { type: 'search', class: 'be-input', placeholder: 'Szukaj bloku', 'aria-label': 'Szukaj bloku' });
        const results = h('div', { class: 'be-inserter-results' });
        const tabs = h('div', { class: 'be-tabs', role: 'tablist' },
            ...[['blocks', 'Bloki'], ['patterns', 'Wzorce']].map(([key, label]) => h('button', {
                type: 'button', role: 'tab', class: 'be-tab', 'aria-selected': String(inserterTab === key),
                onClick: () => { inserterTab = key; renderPanel(); leftPanel.querySelector('[role=tab][aria-selected=true]')?.focus(); },
            }, label)));
        const draw = () => {
            const q = search.value.trim().toLowerCase();
            if (inserterTab === 'patterns') {
                results.replaceChildren(...PATTERNS.filter((p) => !q || (p.title + ' ' + p.desc).toLowerCase().includes(q)).map((p) =>
                    h('button', { type: 'button', class: 'be-pattern', onClick: () => insertFromPanel(p.build()) }, h('strong', {}, p.title), h('span', {}, p.desc))));
                return;
            }
            const groups = Object.entries(CATEGORIES).map(([cat, label]) => {
                const items = Object.entries(BLOCKS).filter(([type, d]) => !d.hidden && d.cat === cat && matches(type, q));
                if (!items.length) return null;
                return h('section', { class: 'be-inserter-group' }, h('h2', {}, label),
                    h('div', { class: 'be-inserter-grid' }, ...items.map(([type, d]) => h('button', {
                        type: 'button', class: 'be-inserter-item', title: d.desc,
                        onClick: () => insertFromPanel([block(type)]),
                    }, icon(d.icon), h('span', {}, d.title)))));
            }).filter(Boolean);
            results.replaceChildren(...(groups.length ? groups : [h('p', { class: 'be-muted' }, 'Brak bloków pasujących do wyszukiwania.')]));
        };
        search.addEventListener('input', draw);
        search.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); results.querySelector('button')?.click(); }
            if (e.key === 'Escape') closePanel();
        });
        draw();
        leftPanel.setAttribute('aria-label', 'Wstaw blok');
        leftPanel.replaceChildren(h('div', { class: 'be-panel-head' }, h('h2', {}, 'Wstaw'), close), h('div', { class: 'be-panel-body' }, tabs, search, results));
    }
    function matches(type, q) {
        if (!q) return true;
        const d = BLOCKS[type];
        return [d.title, type, ...(d.keywords || [])].some((s) => s.toLowerCase().includes(q));
    }

    function renderListView(close) {
        const build = (list, depth) => h('ul', { 'aria-label': depth ? null : 'Bloki: ' + L.contentLabel.toLowerCase() }, ...list.map((b) => {
            const d = BLOCKS[b.type];
            const label = blockLabel(b);
            return h('li', {},
                h('button', {
                    type: 'button', 'aria-current': b.id === S.selectedId ? 'true' : null, class: 'be-list-item' + (b.id === S.selectedId ? ' is-current' : ''), style: `padding-left:${0.5 + depth * 1}rem`,
                    onClick: () => { selectBlock(b.id, { focusWrapper: true }); wrapperOf(b.id)?.scrollIntoView({ block: 'center', behavior: 'smooth' }); },
                }, icon(d.icon), h('span', {}, d.title), label ? h('span', { class: 'be-list-sub' }, label) : null, S.warnings.has(b.id) ? h('span', { class: 'be-list-warn', 'aria-label': 'ma uwagi dostępności' }, icon('triangle-exclamation')) : null),
                b.inner?.length ? build(b.inner, depth + 1) : null);
        }));
        leftPanel.setAttribute('aria-label', 'Widok listy');
        leftPanel.replaceChildren(h('div', { class: 'be-panel-head' }, h('h2', {}, 'Widok listy'), close),
            h('div', { class: 'be-panel-body be-list' }, S.blocks.length ? build(S.blocks, 0) : h('p', { class: 'be-muted' }, L.empty)));
    }
    function blockLabel(b) {
        const a = b.attrs;
        const t = a.text ? textOf(a.text) : (a.summary || a.caption || a.label || a.code || (a.items && typeof a.items[0] === 'string' ? textOf(a.items[0]) : '') || '');
        return t.length > 40 ? t.slice(0, 40) + '…' : t;
    }

    function renderA11yPanel(close) {
        const items = [];
        walk(S.blocks, (b) => { (S.warnings.get(b.id) || []).forEach((msg) => items.push({ b, msg })); });
        leftPanel.setAttribute('aria-label', 'Uwagi dostępności');
        leftPanel.replaceChildren(h('div', { class: 'be-panel-head' }, h('h2', {}, 'Dostępność'), close),
            h('div', { class: 'be-panel-body' },
                items.length
                    ? h('ul', { class: 'be-a11y-list' }, ...items.map(({ b, msg }) => h('li', {}, h('button', {
                        type: 'button', class: 'be-a11y-item',
                        onClick: () => { S.sidebarOpen = true; S.sidebarTab = 'block'; selectBlock(b.id, { focusWrapper: true }); wrapperOf(b.id)?.scrollIntoView({ block: 'center' }); updateTopbar(); },
                    }, h('strong', {}, BLOCKS[b.type].title), h('span', {}, msg)))))
                    : h('p', { class: 'be-ok' }, icon('circle-check'), ' Nie znaleziono problemów. Pamiętaj też o napisach do filmów i zrozumiałym języku.'),
                h('p', { class: 'be-muted be-small' }, 'Sprawdzamy: teksty alternatywne obrazów, kolejność nagłówków, tytuły filmów, nagłówki i podpisy tabel, opisy przycisków i odnośników.'),
            ));
    }

    // ── Uwagi dostępności (WCAG) ───────────────────────────────────────────
    const GENERIC_LINK = /^(kliknij( tutaj| tu)?|tutaj|tu|więcej|czytaj (dalej|więcej)|zobacz|link|szczegóły|>+|»)$/i;
    function computeWarnings() {
        const map = new Map();
        let lastLevel = 1;
        walk(S.blocks, (b) => {
            const a = b.attrs;
            const w = [];
            const linkCheck = (html) => {
                const d = document.createElement('div'); d.innerHTML = html || '';
                d.querySelectorAll('a').forEach((link) => {
                    const t = link.textContent.trim();
                    if (GENERIC_LINK.test(t)) w.push(`Odnośnik „${t}” nie mówi, dokąd prowadzi. Opisz cel odnośnika.`);
                });
            };
            switch (b.type) {
                case 'heading':
                    if (!textOf(a.text)) w.push('Pusty nagłówek — uzupełnij go albo usuń.');
                    if (a.level > lastLevel + 1) w.push(`Pominięty poziom nagłówka: po H${lastLevel} jest H${a.level}. Użyj H${lastLevel + 1}.`);
                    lastLevel = a.level;
                    linkCheck(a.text);
                    break;
                case 'paragraph': {
                    linkCheck(a.text);
                    const d = document.createElement('div'); d.innerHTML = a.text || '';
                    const t = d.textContent.trim();
                    const strong = [...d.querySelectorAll('strong, b')].map((s) => s.textContent).join('').trim();
                    if (t && t.length < 80 && strong === t && !/[.!?:]$/.test(t)) w.push('Pogrubiony krótki akapit wygląda jak nagłówek. Jeśli to śródtytuł — zmień go na blok „Nagłówek”.');
                    break;
                }
                case 'list': a.items.forEach(linkCheck); break;
                case 'quote': linkCheck(a.text); break;
                case 'image':
                    if (a.url && !a.decorative && !a.alt.trim()) w.push('Obraz nie ma tekstu alternatywnego. Opisz go albo oznacz jako dekoracyjny.');
                    if (a.alt && /^(zdjęcie|obraz|grafika|img|image|foto)[\s_-]*\d*(\.\w+)?$/i.test(a.alt.trim())) w.push('Tekst alternatywny nic nie mówi o obrazie. Opisz, co na nim widać.');
                    break;
                case 'gallery': {
                    const n = a.images.filter((img) => !img.alt.trim()).length;
                    if (n) w.push(`${n} ${plural(n, 'zdjęcie', 'zdjęcia', 'zdjęć')} w galerii bez tekstu alternatywnego.`);
                    break;
                }
                case 'embed':
                    if (a.url && !a.title.trim()) w.push('Film nie ma tytułu — czytnik ekranu nie powie, co zawiera.');
                    if (a.url && !embedSrc(a.url)) w.push('Nieobsługiwany adres filmu — na stronie pojawi się zwykły odnośnik.');
                    break;
                case 'buttons':
                    a.items.forEach((btn) => {
                        if (!btn.url) w.push(`Przycisk „${btn.label}” nie ma adresu.`);
                        if (GENERIC_LINK.test(btn.label.trim())) w.push(`Tekst przycisku „${btn.label}” nie mówi, dokąd prowadzi.`);
                    });
                    break;
                case 'table':
                    if (!a.headerRow && !a.headerColumn) w.push('Tabela nie ma nagłówków — włącz wiersz lub kolumnę nagłówków.');
                    if (!a.caption.trim()) w.push('Dodaj podpis tabeli (krótki opis jej zawartości).');
                    break;
                case 'details': if (!a.summary.trim()) w.push('Rozwijana sekcja nie ma tytułu.'); break;
                case 'file':
                    if (a.url && !a.label.trim()) w.push('Podaj czytelną nazwę pliku zamiast nazwy technicznej.');
                    break;
                default: break;
            }
            if (w.length) map.set(b.id, w);
        });
        return map;
    }
    let warnTimer = null;
    function scheduleWarnings() { clearTimeout(warnTimer); warnTimer = setTimeout(refreshWarnings, 400); }
    function refreshWarnings() {
        S.warnings = computeWarnings();
        canvas.querySelectorAll('.be-block').forEach((wrap) => {
            const w = S.warnings.get(wrap.dataset.id);
            let badge = wrap.querySelector(':scope > .be-warn-badge');
            if (!w) { badge?.remove(); return; }
            if (!badge) {
                badge = h('button', { type: 'button', class: 'be-warn-badge', onClick: (e) => { e.stopPropagation(); S.sidebarOpen = true; S.sidebarTab = 'block'; selectBlock(wrap.dataset.id); updateTopbar(); sidebar.querySelector('.be-warnings')?.focus(); } }, icon('triangle-exclamation'));
                wrap.append(badge);
            }
            badge.setAttribute('aria-label', `${w.length} ${plural(w.length, 'uwaga', 'uwagi', 'uwag')} dostępności: ${w.join(' ')}`);
            badge.title = w.join('\n');
        });
        updateTopbar();
        if (S.panel === 'a11y' || S.panel === 'list') {
            const focused = leftPanel.contains(document.activeElement);
            if (!focused) renderPanel();
        }
        if (S.sidebarTab === 'block' && !sidebar.contains(document.activeElement)) renderSidebar();
    }

    // ── Płótno ─────────────────────────────────────────────────────────────
    function renderCanvas() {
        canvas.replaceChildren(...S.blocks.map(renderBlock), rootAppender());
        applySelection();
        refreshWarnings();
        renderNotices();
    }
    function renderAll() {
        titleEl.textContent = S.title;
        titleEl.classList.toggle('is-empty', !S.title.trim());
        renderCanvas();
        renderPanel();
        renderSidebar();
        updateTopbar();
    }
    function rerenderBlock(id) {
        const r = find(id);
        const old = wrapperOf(id);
        if (!r || !old) { renderCanvas(); return; }
        old.replaceWith(renderBlock(r.block));
        applySelection();
        scheduleWarnings();
    }

    function renderBlock(b) {
        const def = BLOCKS[b.type];
        const wrap = h('div', {
            class: `be-block be-block--${b.type}`, 'data-id': b.id, tabindex: '-1',
            role: 'group', 'aria-label': 'Blok: ' + def.title,
        });
        wrap.append(def.edit ? def.edit(b) : editors[b.type](b));
        wrap.addEventListener('dragover', onDragOver);
        wrap.addEventListener('drop', onDrop);
        return wrap;
    }
    function innerList(b, label = 'Dodaj blok') {
        return h('div', { class: 'be-inner' }, ...b.inner.map(renderBlock), appender(b, label));
    }
    function appender(parent, label) {
        return h('button', {
            type: 'button', class: 'be-appender' + (parent.inner.length ? '' : ' is-empty'),
            onClick: (e) => { e.stopPropagation(); togglePanel('inserter', { parentId: parent.id, index: parent.inner.length }); },
        }, icon('plus'), h('span', {}, label));
    }
    function rootAppender() {
        return h('div', { class: 'be-root-appender' },
            h('button', {
                type: 'button', class: 'be-root-appender-text',
                onClick: () => { const p = block('paragraph'); insertBlocks([p]); focusBlock(p.id, 'start'); },
            }, 'Wpisz tekst albo „/”, aby wybrać blok'),
            h('button', { type: 'button', class: 'be-btn be-btn--icon be-btn--dark', 'aria-label': 'Dodaj blok na końcu', onClick: () => togglePanel('inserter', { index: S.blocks.length }) }, icon('plus')));
    }

    function renderNotices() {
        const items = [];
        if (S.imported && S.blocks.length === 1 && S.blocks[0].type === 'html') {
            const htmlBlock = S.blocks[0];
            items.push(h('div', { class: 'be-notice', role: 'note' },
                icon('circle-info'),
                h('p', {}, L.imported + ', więc treść jest teraz w bloku „Własny HTML”. Przekształć ją w bloki, aby edytować wygodnie akapitami, nagłówkami i obrazami.'),
                h('button', { type: 'button', class: 'be-btn be-btn--primary', onClick: () => { S.imported = false; convertHtmlBlock(htmlBlock); } }, icon('wand-magic-sparkles'), ' Przekształć w bloki'),
                h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => { S.imported = false; renderNotices(); } }, 'Nie teraz')));
        }
        const draft = draftOffer;
        if (draft) {
            items.push(h('div', { class: 'be-notice be-notice--warn', role: 'note' },
                icon('clock-rotate-left'),
                h('p', {}, `Masz niezapisane zmiany z ${new Date(draft.at).toLocaleString('pl-PL')}. Przywrócić je?`),
                h('button', { type: 'button', class: 'be-btn be-btn--primary', onClick: () => { draftOffer = null; pushHistory(); S.imported = false; restore(draft.data); announce('Przywrócono niezapisane zmiany'); } }, 'Przywróć'),
                h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => { draftOffer = null; clearDraft(); renderNotices(); } }, 'Odrzuć')));
        }
        notices.replaceChildren(...items);
    }
    function convertHtmlBlock(b) {
        const blocks = htmlToBlocks(b.attrs.html);
        replaceBlock(b.id, blocks.length ? blocks : [block('paragraph')]);
        announce(`Przekształcono w ${blocks.length} ${plural(blocks.length, 'blok', 'bloki', 'bloków')}`);
    }

    // ── Pola tekstowe (rich text) ──────────────────────────────────────────
    function richField(b, key, opts = {}) {
        const el = h(opts.tag || 'div', {
            class: (opts.class || '') + ' be-rich',
            contenteditable: 'true', 'data-be-editable': '', role: 'textbox', 'aria-multiline': 'true',
            'aria-label': opts.label || BLOCKS[b.type].title, 'data-placeholder': opts.placeholder || '', spellcheck: 'true',
        });
        el.innerHTML = b.attrs[key] || '';
        const sync = () => el.classList.toggle('is-empty', isEmptyEl(el));
        sync();
        el.addEventListener('input', () => {
            typingHistory();
            b.attrs[key] = el.innerHTML;
            sync();
            markDirty();
            if (opts.slash) handleSlash(b, el);
            if (opts.markdown) handleMarkdown(b, el);
        });
        el.addEventListener('keydown', (e) => onRichKeydown(e, b, el, key, opts));
        el.addEventListener('paste', (e) => onPaste(e, b, el, key, opts));
        el.addEventListener('blur', () => { if (S.slash?.el === el) closeSlash(); });
        return el;
    }

    function onRichKeydown(e, b, el, key, opts) {
        if (S.slash && S.slash.el === el) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); moveSlash(e.key === 'ArrowDown' ? 1 : -1); return; }
            if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); chooseSlash(); return; }
            if (e.key === 'Escape') { e.preventDefault(); closeSlash(); return; }
        }
        const modKey = e.ctrlKey || e.metaKey;
        if (modKey && e.key.toLowerCase() === 'k') { e.preventDefault(); openLinkDialog(el); return; }
        if (e.key === 'Enter' && !e.shiftKey) {
            if (opts.split) {
                e.preventDefault();
                const [before, after] = splitAtCaret(el);
                b.attrs[key] = before;
                el.innerHTML = before;
                el.classList.toggle('is-empty', isEmptyEl(el));
                const p = block('paragraph', { text: after });
                insertBlocks([p], { afterId: b.id });
                focusBlock(p.id, 'start');
                return;
            }
            if (opts.lineBreak) { e.preventDefault(); document.execCommand('insertLineBreak'); return; }
        }
        if (e.key === 'Backspace' && opts.mergeable && caretEdge(el, true)) {
            const r = find(b.id);
            const prev = r.list[r.index - 1];
            if (isEmptyEl(el)) {
                if (!prev && !r.parent && S.blocks.length === 1) return;
                e.preventDefault();
                removeBlock(b.id);
                return;
            }
            if (b.type === 'heading') { e.preventDefault(); replaceBlock(b.id, [{ ...block('paragraph', { text: b.attrs.text }), id: b.id }], 'start'); return; }
            if (prev && ['paragraph', 'heading'].includes(prev.type)) {
                e.preventDefault();
                const offset = wrapperText(prev);
                pushHistory();
                prev.attrs.text = (prev.attrs.text || '').replace(/(<br\s*\/?>\s*)+$/i, '') + b.attrs[key];
                r.list.splice(r.index, 1);
                markDirty();
                renderCanvas();
                const pel = editablesIn(wrapperOf(prev.id))[0];
                if (pel) { setCaretAtTextOffset(pel, offset); selectBlock(prev.id); }
            }
            return;
        }
        if ((e.key === 'ArrowUp' || e.key === 'ArrowLeft') && caretEdge(el, true) && !e.shiftKey) {
            const all = editablesIn();
            const prevEl = all[all.indexOf(el) - 1];
            if (prevEl && (e.key === 'ArrowUp' || isEmptyEl(el) || caretEdge(el, true))) { e.preventDefault(); placeCaret(prevEl, false); }
            return;
        }
        if ((e.key === 'ArrowDown' || e.key === 'ArrowRight') && caretEdge(el, false) && !e.shiftKey) {
            const all = editablesIn();
            const nextEl = all[all.indexOf(el) + 1];
            if (nextEl) { e.preventDefault(); placeCaret(nextEl, true); }
        }
    }
    function wrapperText(b) {
        const d = document.createElement('div');
        d.innerHTML = (b.attrs.text || '').replace(/(<br\s*\/?>\s*)+$/i, '');
        return d.textContent.length;
    }

    function onPaste(e, b, el, key, opts) {
        const html = e.clipboardData.getData('text/html');
        const text = e.clipboardData.getData('text/plain');
        e.preventDefault();
        if (html) {
            const blocks = htmlToBlocks(html);
            const inlineOnly = blocks.length === 1 && blocks[0].type === 'paragraph';
            if (inlineOnly || !opts.split || !blocks.length) {
                document.execCommand('insertHTML', false, inlineOnly ? blocks[0].attrs.text : cleanInline(html));
                return;
            }
            pasteBlocks(b, el, key, blocks);
            return;
        }
        if (!text) return;
        const paras = text.split(/\r?\n\s*\r?\n/).map((t) => t.trim()).filter(Boolean);
        if (opts.split && paras.length > 1) {
            pasteBlocks(b, el, key, paras.map((p) => block('paragraph', { text: escapeHtml(p).replace(/\r?\n/g, '<br>') })));
            return;
        }
        document.execCommand('insertText', false, text);
    }
    function pasteBlocks(b, el, key, blocks) {
        const [before, after] = splitAtCaret(el);
        pushHistory();
        b.attrs[key] = before;
        const r = find(b.id);
        const tail = textOf(after) ? [block('paragraph', { text: after })] : [];
        const replaceSelf = b.type === 'paragraph' && !textOf(before);
        r.list.splice(replaceSelf ? r.index : r.index + 1, replaceSelf ? 1 : 0, ...blocks, ...tail);
        markDirty();
        renderCanvas();
        focusBlock(blocks[blocks.length - 1].id, 'end');
        announce(`Wklejono ${blocks.length} ${plural(blocks.length, 'blok', 'bloki', 'bloków')}`);
    }

    // Skróty Markdown w pustym akapicie: „## ”, „- ”, „1. ”, „> ”, „---”.
    function handleMarkdown(b, el) {
        if (el.querySelector(':not(br)')) return;
        const t = el.textContent.replace(/ /g, ' ');
        let m;
        if ((m = t.match(/^(#{2,4}) $/))) replaceBlock(b.id, [block('heading', { level: m[1].length })]);
        else if (/^[-*] $/.test(t)) replaceBlock(b.id, [block('list')]);
        else if (/^1[.)] $/.test(t)) replaceBlock(b.id, [block('list', { ordered: true })]);
        else if (/^> $/.test(t)) replaceBlock(b.id, [block('quote')]);
        else if (/^(---|\*\*\*)$/.test(t)) { const p = block('paragraph'); replaceBlock(b.id, [block('separator'), p]); }
    }

    // Menu „/” — wybór bloku z klawiatury w pustym akapicie.
    function handleSlash(b, el) {
        const t = el.textContent.replace(/ /g, ' ');
        const m = t.match(/^\/([\p{L}\d -]{0,20})$/u);
        if (!m) { closeSlash(); return; }
        const q = m[1].trim().toLowerCase();
        const items = Object.keys(BLOCKS).filter((type) => !BLOCKS[type].hidden && matches(type, q)).slice(0, 9);
        S.slash = { blockId: b.id, el, items, index: 0 };
        drawSlash();
    }
    function drawSlash() {
        const s = S.slash;
        if (!s || !s.items.length) {
            slashMenu.replaceChildren(h('div', { class: 'be-slash-empty' }, 'Brak pasujących bloków'));
        } else {
            slashMenu.replaceChildren(...s.items.map((type, i) => h('div', {
                id: 'be-slash-' + i, role: 'option', class: 'be-slash-item', 'aria-selected': String(i === s.index),
                onMousedown: (e) => { e.preventDefault(); s.index = i; chooseSlash(); },
            }, icon(BLOCKS[type].icon), h('span', {}, BLOCKS[type].title))));
        }
        const rect = s.el.getBoundingClientRect();
        slashMenu.hidden = false;
        slashMenu.style.top = Math.min(rect.bottom + 6, window.innerHeight - 320) + 'px';
        slashMenu.style.left = Math.max(8, rect.left) + 'px';
        s.el.setAttribute('aria-controls', 'be-slash');
        s.el.setAttribute('aria-expanded', 'true');
        if (s.items.length) s.el.setAttribute('aria-activedescendant', 'be-slash-' + s.index);
    }
    function moveSlash(dir) {
        const s = S.slash;
        if (!s.items.length) return;
        s.index = (s.index + dir + s.items.length) % s.items.length;
        drawSlash();
    }
    function chooseSlash() {
        const s = S.slash;
        const type = s?.items[s.index];
        closeSlash();
        if (!type) return;
        const nb = block(type);
        replaceBlock(s.blockId, [nb]);
        const d = BLOCKS[type];
        if (!d.rich && !d.container) selectBlock(nb.id, { focusWrapper: true });
        announce('Wstawiono: ' + d.title);
    }
    function closeSlash() {
        if (!S.slash) return;
        S.slash.el.removeAttribute('aria-expanded');
        S.slash.el.removeAttribute('aria-activedescendant');
        S.slash = null;
        slashMenu.hidden = true;
    }

    // ── Edytory poszczególnych bloków ──────────────────────────────────────
    const alignCls = (a) => (a.align && a.align !== 'left' ? 'has-text-align-' + a.align : '');
    const editors = {
        paragraph: (b) => richField(b, 'text', { tag: 'p', class: [alignCls(b.attrs), b.attrs.lead ? 'is-lead' : ''].join(' '), placeholder: 'Wpisz tekst albo „/”, aby wybrać blok', slash: true, markdown: true, split: true, mergeable: true, label: 'Akapit' }),
        heading: (b) => richField(b, 'text', { tag: 'h' + b.attrs.level, class: alignCls(b.attrs), placeholder: 'Nagłówek', split: true, mergeable: true, label: 'Nagłówek H' + b.attrs.level }),
        quote: (b) => h('figure', { class: 'content-quote' },
            h('blockquote', {}, richField(b, 'text', { tag: 'p', placeholder: 'Treść cytatu', lineBreak: true, mergeable: false, label: 'Treść cytatu' })),
            richField(b, 'cite', { tag: 'figcaption', placeholder: 'Autor cytatu (opcjonalnie)', lineBreak: true, label: 'Autor cytatu' })),
        list: (b) => listEditor(b),
        image: (b) => imageEditor(b),
        gallery: (b) => galleryEditor(b),
        buttons: (b) => buttonsEditor(b),
        callout: (b) => h('div', { class: { box: 'content-box', note: 'content-note', important: 'content-important' }[b.attrs.variant] }, innerList(b)),
        columns: (b) => h('div', { class: `content-columns content-columns--${b.inner.length}` + (b.attrs.ratio !== 'equal' && b.inner.length === 2 ? ' content-columns--' + b.attrs.ratio : '') }, ...b.inner.map(renderBlock)),
        column: (b) => h('div', { class: 'content-column' }, innerList(b)),
        details: (b) => detailsEditor(b),
        table: (b) => tableEditor(b),
        separator: () => h('hr', {}),
        embed: (b) => embedEditor(b),
        file: (b) => fileEditor(b),
        html: (b) => htmlEditor(b),
        shortcode: (b) => shortcodeEditor(b),
        subpages: (b) => linkListEditor(b, subpagesCode(b.attrs), subpagesSummary(b.attrs)),
        newslist: (b) => linkListEditor(b, newsCode(b.attrs), newsSummary(b.attrs)),
    };

    function listEditor(b) {
        const tag = b.attrs.ordered ? 'ol' : 'ul';
        const el = h(tag, { contenteditable: 'true', 'data-be-editable': '', role: 'textbox', 'aria-multiline': 'true', 'aria-label': b.attrs.ordered ? 'Lista numerowana' : 'Lista punktowana', class: 'be-rich', 'data-placeholder': 'Element listy' });
        el.innerHTML = (b.attrs.items.length ? b.attrs.items : ['']).map((i) => `<li>${i || '<br>'}</li>`).join('');
        const read = () => {
            if (!el.querySelector('li')) el.innerHTML = '<li><br></li>';
            b.attrs.items = [...el.children].filter((c) => c.tagName === 'LI').map((li) => li.innerHTML.replace(/(<br\s*\/?>\s*)+$/i, ''));
        };
        el.addEventListener('input', () => { typingHistory(); read(); markDirty(); });
        el.addEventListener('paste', (e) => {
            const text = e.clipboardData.getData('text/plain');
            e.preventDefault();
            const lines = text.split(/\r?\n/).map((l) => l.replace(/^\s*([-*•]|\d+[.)])\s+/, '').trim()).filter(Boolean);
            document.execCommand('insertText', false, lines[0] || '');
            if (lines.length < 2) return;
            // Kolejne wiersze schowka → kolejne elementy listy.
            const sel = window.getSelection();
            let li = sel.anchorNode && (sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement)?.closest('li');
            if (!li) return;
            pushHistory();
            for (const line of lines.slice(1)) { const n = document.createElement('li'); n.textContent = line; li.after(n); li = n; }
            placeCaret(li, false);
            read();
            markDirty();
        });
        el.addEventListener('keydown', (e) => {
            const sel = window.getSelection();
            const li = sel.anchorNode && (sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement)?.closest('li');
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openLinkDialog(el); return; }
            if (e.key === 'Enter' && !e.shiftKey && li && isEmptyEl(li)) {
                // Pusty element na końcu → wyjście z listy do nowego akapitu.
                e.preventDefault();
                li.remove();
                read();
                const p = block('paragraph');
                if (!b.attrs.items.some((i) => textOf(i))) replaceBlock(b.id, [p]);
                else insertBlocks([p], { afterId: b.id });
                focusBlock(p.id, 'start');
                return;
            }
            if (e.key === 'Backspace' && li === el.firstElementChild && caretEdge(li, true)) {
                if (el.children.length === 1 && isEmptyEl(li)) { e.preventDefault(); replaceBlock(b.id, [block('paragraph')]); return; }
                if (caretEdge(el, true)) { e.preventDefault(); transform(b, 'paragraph'); }
                return;
            }
            if (e.key === 'ArrowUp' && caretEdge(el, true)) { const all = editablesIn(); const p = all[all.indexOf(el) - 1]; if (p) { e.preventDefault(); placeCaret(p, false); } }
            if (e.key === 'ArrowDown' && caretEdge(el, false)) { const all = editablesIn(); const n = all[all.indexOf(el) + 1]; if (n) { e.preventDefault(); placeCaret(n, true); } }
        });
        return el;
    }

    function mediaPlaceholder({ iconName, title, desc, accept, multiple = false, library = true, onFiles, onLibrary, onUrl }) {
        const fileInput = h('input', { type: 'file', accept, multiple, class: 'be-sr-only', tabindex: '-1', 'aria-hidden': 'true' });
        const urlInput = h('input', { type: 'url', class: 'be-input', placeholder: 'https://…', 'aria-label': 'Adres URL' });
        const urlRow = h('form', { class: 'be-url-row', hidden: true, onSubmit: (e) => { e.preventDefault(); if (urlInput.value.trim()) onUrl(urlInput.value.trim()); } },
            urlInput, h('button', { type: 'submit', class: 'be-btn be-btn--primary' }, 'Wstaw'));
        fileInput.addEventListener('change', async () => { if (fileInput.files.length) await onFiles([...fileInput.files]); });
        const zone = h('div', { class: 'be-placeholder' },
            h('div', { class: 'be-placeholder-title' }, icon(iconName), ' ', title),
            h('p', {}, desc),
            h('div', { class: 'be-placeholder-actions' },
                h('button', { type: 'button', class: 'be-btn be-btn--primary', onClick: () => fileInput.click() }, icon('upload'), ' Prześlij'),
                library ? h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: onLibrary }, icon('photo-film'), ' Biblioteka mediów') : null,
                onUrl ? h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => { urlRow.hidden = false; urlInput.focus(); } }, icon('link'), ' Z adresu URL') : null),
            urlRow, fileInput);
        // Upuszczanie plików na pole.
        zone.addEventListener('dragover', (e) => { if (e.dataTransfer.types.includes('Files')) { e.preventDefault(); e.stopPropagation(); zone.classList.add('is-dragover'); } });
        zone.addEventListener('dragleave', () => zone.classList.remove('is-dragover'));
        zone.addEventListener('drop', async (e) => { if (!e.dataTransfer.files.length) return; e.preventDefault(); e.stopPropagation(); zone.classList.remove('is-dragover'); await onFiles([...e.dataTransfer.files]); });
        return zone;
    }

    function imageEditor(b) {
        const a = b.attrs;
        if (!a.url) {
            return mediaPlaceholder({
                iconName: 'image', title: 'Obraz', desc: 'Prześlij plik, wybierz z biblioteki albo przeciągnij go tutaj.', accept: 'image/*',
                onFiles: async (files) => { const up = await uploadFiles(files.slice(0, 1)); if (up[0]) await setImageWithAlt(b, up[0].url, { alt: a.alt, decorative: a.decorative }); },
                onLibrary: async () => { const picked = await openMediaLibrary(); if (picked[0]) await setImageWithAlt(b, picked[0].url, { alt: a.alt || picked[0].alt || '', decorative: a.decorative }); },
                onUrl: async (url) => { if (safeUrl(url)) await setImageWithAlt(b, url, { alt: a.alt, decorative: a.decorative }); else announce('Nieprawidłowy adres obrazu'); },
            });
        }
        return h('figure', { class: `content-figure content-figure--${a.width}` },
            h('img', { src: a.url, alt: a.decorative ? '' : a.alt }),
            richField(b, 'caption', { tag: 'figcaption', placeholder: 'Dodaj podpis (opcjonalnie)', lineBreak: true, label: 'Podpis obrazu' }));
    }

    function galleryEditor(b) {
        const a = b.attrs;
        const add = (images) => addGalleryImages(b, images);
        if (!a.images.length) {
            return mediaPlaceholder({
                iconName: 'images', title: 'Galeria', desc: 'Wybierz kilka zdjęć z biblioteki albo prześlij nowe.', accept: 'image/*', multiple: true,
                onFiles: async (files) => add(await uploadFiles(files)),
                onLibrary: async () => add(await openMediaLibrary({ multiple: true })),
            });
        }
        return h('div', {},
            h('div', { class: 'content-gallery' + (a.columns !== 3 ? ' content-gallery--cols-' + a.columns : '') },
                ...a.images.map((img, i) => h('figure', { class: 'be-gallery-item' },
                    h('img', { src: img.url, alt: img.alt }),
                    img.caption ? h('figcaption', {}, img.caption) : null,
                    h('button', { type: 'button', class: 'be-gallery-remove', 'aria-label': `Usuń zdjęcie ${i + 1}`, onClick: () => updateAttrs(b, { images: a.images.filter((_, j) => j !== i) }) }, icon('xmark'))))),
            h('button', { type: 'button', class: 'be-btn be-btn--secondary be-mt', onClick: async () => add(await openMediaLibrary({ multiple: true })) }, icon('plus'), ' Dodaj zdjęcia'));
    }

    function buttonsEditor(b) {
        const a = b.attrs;
        return h('p', { class: 'content-buttons ' + alignCls(a) },
            ...a.items.map((btn, i) => {
                const label = h('span', {
                    contenteditable: 'plaintext-only', 'data-be-editable': '', role: 'textbox', 'aria-label': `Tekst przycisku ${i + 1}`,
                    class: 'be-button-label', 'data-placeholder': 'Tekst przycisku',
                });
                label.textContent = btn.label;
                label.addEventListener('input', () => { typingHistory(); btn.label = label.textContent.replace(/\s+/g, ' '); markDirty(); });
                label.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
                label.addEventListener('focus', () => { S.buttonIndex = i; if (S.sidebarTab === 'block') renderSidebar(); });
                return h('span', { class: 'cta-button be-cta' + (btn.variant !== 'brand' ? ' cta-' + btn.variant : '') }, label);
            }),
            a.items.length < 6 ? h('button', { type: 'button', class: 'be-btn be-btn--icon be-btn--dark be-add-inline', 'aria-label': 'Dodaj przycisk', onClick: () => { updateAttrs(b, { items: [...a.items, { label: 'Przycisk', url: '', variant: 'brand', newTab: false }] }); S.buttonIndex = a.items.length - 1; renderSidebar(); } }, icon('plus')) : null);
    }

    function detailsEditor(b) {
        const summary = h('div', { class: 'be-summary', contenteditable: 'plaintext-only', 'data-be-editable': '', role: 'textbox', 'aria-label': 'Tytuł rozwijanej sekcji', 'data-placeholder': 'Tytuł sekcji, np. pytanie' });
        summary.textContent = b.attrs.summary;
        summary.classList.toggle('is-empty', !b.attrs.summary);
        summary.addEventListener('input', () => { typingHistory(); b.attrs.summary = summary.textContent.replace(/\s+/g, ' ').trim(); summary.classList.toggle('is-empty', !b.attrs.summary); markDirty(); });
        summary.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); const first = b.inner[0]; if (first) focusBlock(first.id, 'start'); } });
        return h('div', { class: 'content-details be-details' }, summary, h('div', { class: 'content-details-body' }, innerList(b)));
    }

    function tableEditor(b) {
        const a = b.attrs;
        const cell = (r, c) => {
            const isHead = (a.headerRow && r === 0) || (a.headerColumn && c === 0);
            const el = h(isHead ? 'th' : 'td', {
                contenteditable: 'true', 'data-be-editable': '', role: 'textbox', class: 'be-rich',
                'aria-label': `Wiersz ${r + 1}, kolumna ${c + 1}`, scope: isHead ? (a.headerRow && r === 0 ? 'col' : 'row') : null,
            });
            el.innerHTML = a.rows[r][c] || '';
            el.addEventListener('input', () => { typingHistory(); a.rows[r][c] = el.innerHTML.replace(/(<br\s*\/?>\s*)+$/i, ''); markDirty(); });
            el.addEventListener('focus', () => { S.tableCell = { id: b.id, r, c }; });
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); document.execCommand('insertLineBreak'); }
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openLinkDialog(el); }
            });
            el.addEventListener('paste', (e) => { e.preventDefault(); document.execCommand('insertText', false, e.clipboardData.getData('text/plain')); });
            return el;
        };
        const rows = a.rows.map((row, r) => h('tr', {}, ...row.map((_, c) => cell(r, c))));
        return h('div', { class: 'be-table-wrap' },
            h('table', {},
                a.caption ? h('caption', {}, a.caption) : null,
                a.headerRow ? h('thead', {}, rows[0]) : null,
                h('tbody', {}, ...(a.headerRow ? rows.slice(1) : rows))));
    }

    function embedEditor(b) {
        const a = b.attrs;
        if (!a.url) {
            const input = h('input', { type: 'url', class: 'be-input', placeholder: 'https://www.youtube.com/watch?v=…', 'aria-label': 'Adres filmu' });
            return h('form', { class: 'be-placeholder', onSubmit: (e) => { e.preventDefault(); const url = input.value.trim(); if (!embedSrc(url)) { announce('Obsługiwane są adresy YouTube i Vimeo'); input.setAttribute('aria-invalid', 'true'); return; } updateAttrs(b, { url }); } },
                h('div', { class: 'be-placeholder-title' }, icon('circle-play'), ' Film'),
                h('p', {}, 'Wklej adres filmu z YouTube lub Vimeo. Pamiętaj o napisach — wymaga ich WCAG.'),
                h('div', { class: 'be-url-row' }, input, h('button', { type: 'submit', class: 'be-btn be-btn--primary' }, 'Osadź')));
        }
        const src = embedSrc(a.url);
        return h('figure', { class: 'content-embed' },
            src ? h('div', { class: 'content-embed-frame' }, h('iframe', { src, title: a.title || 'Osadzony film', loading: 'lazy', tabindex: '-1' }), h('div', { class: 'be-embed-overlay', 'aria-hidden': 'true' }))
                : h('p', { class: 'be-muted' }, 'Nieobsługiwany adres: ', a.url),
            richField(b, 'caption', { tag: 'figcaption', placeholder: 'Dodaj podpis (opcjonalnie)', lineBreak: true, label: 'Podpis filmu' }));
    }

    function fileEditor(b) {
        const a = b.attrs;
        if (!a.url) {
            return mediaPlaceholder({
                iconName: 'file-arrow-down', title: 'Plik do pobrania', desc: 'Prześlij dokument (PDF, DOCX, XLSX…) albo wklej jego adres.', accept: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.zip',
                library: false,
                onFiles: async (files) => { const up = await uploadFiles(files.slice(0, 1)); if (up[0]) updateAttrs(b, { url: up[0].url, label: a.label || up[0].name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ') }); },
                onUrl: (url) => { if (safeUrl(url)) updateAttrs(b, { url }); },
            });
        }
        const ext = ((a.url.split('?')[0].match(/\.([a-z0-9]{2,5})$/i) || [])[1] || '').toUpperCase();
        const label = h('span', { contenteditable: 'plaintext-only', 'data-be-editable': '', role: 'textbox', 'aria-label': 'Nazwa pliku', 'data-placeholder': 'Nazwa pliku', class: 'be-file-label' });
        label.textContent = a.label;
        label.addEventListener('input', () => { typingHistory(); a.label = label.textContent.replace(/\s+/g, ' ').trim(); markDirty(); });
        label.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
        return h('p', { class: 'content-file' }, h('span', { class: 'be-file-link' }, icon('file-arrow-down'), ' ', label, ext ? h('span', { class: 'content-file-meta' }, ` (${ext})`) : null),
            a.description ? h('span', { class: 'content-file-desc' }, a.description) : null);
    }

    function htmlEditor(b) {
        let preview = false;
        const box = h('div', { class: 'be-html' });
        const draw = () => {
            const code = h('textarea', { class: 'be-code', 'aria-label': 'Kod HTML', spellcheck: 'false', rows: String(Math.min(24, Math.max(4, (b.attrs.html.match(/\n/g) || []).length + 2))) });
            code.value = b.attrs.html;
            code.addEventListener('input', () => { typingHistory(); b.attrs.html = code.value; markDirty(); });
            box.replaceChildren(
                h('div', { class: 'be-html-bar' },
                    h('div', { class: 'be-tabs', role: 'tablist', 'aria-label': 'Tryb bloku HTML' },
                        h('button', { type: 'button', role: 'tab', class: 'be-tab', 'aria-selected': String(!preview), onClick: () => { preview = false; draw(); } }, 'Kod'),
                        h('button', { type: 'button', role: 'tab', class: 'be-tab', 'aria-selected': String(preview), onClick: () => { preview = true; draw(); } }, 'Podgląd')),
                    h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: () => convertHtmlBlock(b) }, icon('wand-magic-sparkles'), ' Przekształć w bloki')),
                preview ? htmlPreview(b.attrs.html) : code);
        };
        draw();
        return box;
    }

    // Podgląd HTML-a bloku „Własny HTML”. Gdy zawiera shortcode'y, renderuje je
    // serwer (ten sam ShortcodeParser co na stronie). Skrypty i obsługa zdarzeń są
    // usuwane, a odnośniki i formularze w podglądzie nie działają.
    let htmlPreviewCache = { html: null, promise: null };
    function htmlPreview(html) {
        const box = h('div', { class: 'be-html-preview', role: 'region', 'aria-label': 'Podgląd HTML' });
        box.addEventListener('click', (e) => { if (e.target.closest('a, button')) e.preventDefault(); });
        box.addEventListener('submit', (e) => e.preventDefault(), true);
        if (!SHORTCODE_DETECT.test(html || '')) { box.append(sanitizePreview(html)); return box; }
        box.append(h('p', { class: 'be-muted' }, 'Wczytywanie podglądu modułów…'));
        if (htmlPreviewCache.html !== html) {
            htmlPreviewCache = { html, promise: postJson(config.urls.shortcode, { html }).then((j) => j.html || '') };
            htmlPreviewCache.promise.catch(() => { htmlPreviewCache = { html: null, promise: null }; });
        }
        htmlPreviewCache.promise
            .then((out) => box.replaceChildren(sanitizePreview(out)))
            .catch((e) => box.replaceChildren(h('p', { class: 'be-error', role: 'alert' }, 'Nie udało się wyrenderować shortcode’ów: ' + e.message), sanitizePreview(html)));
        return box;
    }

    // Blok „Shortcode” z podglądem na żywo (nieaktywnym — tylko do oglądania).
    const shortcodePreviewCache = new Map();
    function fetchShortcodePreview(code) {
        if (!shortcodePreviewCache.has(code)) {
            const p = postJson(config.urls.shortcode, { code, page_id: config.subpages?.contextPage || null }).then((j) => j.html || '');
            p.catch(() => shortcodePreviewCache.delete(code));
            shortcodePreviewCache.set(code, p);
        }
        return shortcodePreviewCache.get(code);
    }
    function shortcodeEditor(b) {
        const input = h('input', { type: 'text', class: 'be-input be-mono', value: b.attrs.code, placeholder: '[formularz:nazwa-formularza]', 'aria-label': 'Shortcode', 'aria-describedby': `sc-help-${b.id} sc-status-${b.id}` });
        const status = h('p', { id: 'sc-status-' + b.id, class: 'be-small be-sc-status', 'aria-live': 'polite' });
        const body = h('div', { class: 'be-sc-preview-body' });
        // inert: podgląd nie przyjmuje fokusu ani kliknięć i jest pomijany przez czytniki
        // ekranu — stan podglądu opisuje komunikat powyżej.
        const preview = h('div', { class: 'be-sc-preview', hidden: true },
            h('p', { class: 'be-sc-preview-label', 'aria-hidden': 'true' }, icon('eye'), ' Podgląd'),
            h('div', { inert: true, 'aria-hidden': 'true' }, body));
        const showStatus = (msg, isError = false) => { status.textContent = msg; status.classList.toggle('be-error', isError); };
        const load = async () => {
            const code = input.value.trim();
            if (!code) { preview.hidden = true; showStatus(''); return; }
            if (!SHORTCODE_RE.test(code)) { preview.hidden = true; showStatus('Nieprawidłowy shortcode — wpisz go w nawiasach kwadratowych, np. [formularz:nazwa].', true); return; }
            showStatus('Wczytywanie podglądu…');
            let html;
            try { html = await fetchShortcodePreview(code); } catch (e) {
                if (input.value.trim() === code) { preview.hidden = true; showStatus('Nie udało się wczytać podglądu: ' + e.message, true); }
                return;
            }
            if (input.value.trim() !== code) return;
            const frag = sanitizePreview(html);
            const text = frag.textContent.replace(/\s+/g, ' ').trim();
            if (text === code) { preview.hidden = true; showStatus('Ten shortcode nie jest rozpoznawany — sprawdź jego nazwę.', true); return; }
            if (!text && !frag.querySelector('img, iframe, form, svg, video')) { preview.hidden = true; showStatus('Shortcode nic nie wyświetla — sprawdź, czy wskazany element istnieje i jest opublikowany.', true); return; }
            body.replaceChildren(frag);
            preview.hidden = false;
            showStatus('Podgląd poniżej pokazuje, jak moduł wygląda na stronie.');
        };
        const loadSoon = debounce(load, 500);
        // Wybór modułu z listy (jak menu „Wstaw” klasycznego edytora) i kreatory kafelków / bloków.
        const groups = [
            ['Formularze', (CI.forms || []).map((o) => ['[formularz:' + o.value + ']', o.label])],
            ['Zestawy kafelków', (CI.tileSets || []).map((o) => ['[kafelki-zestaw:' + o.value + ']', o.label])],
            ['Bloki z kreatora', (CI.contentBlocks || []).map((o) => ['[blok:' + o.value + ']', o.label])],
            ['Siatki kafelków', (CI.tilesGrids || []).map((o) => ['[kafelki:' + o.value + ']', o.label])],
            ['Paski FEER', (CI.bands || []).map((o) => ['[pasek:' + o.value + ']', o.label])],
            ['Listy', [['[klauzule-rodo]', 'Klauzule RODO'], ['[klauzule-rodo:en]', 'Klauzule RODO (wersje angielskie)'], ['[nabory]', 'Lista naborów'], ['[zapytania-ofertowe]', 'Zapytania ofertowe']]],
        ].filter(([, opts]) => opts.length);
        const setCode = (code) => { input.value = code; input.dispatchEvent(new Event('input')); pick.value = groups.some(([, o]) => o.some(([v]) => v === code)) ? code : ''; renderTools(); };
        const pick = h('select', { class: 'be-input', 'aria-label': 'Wybierz moduł' },
            h('option', { value: '' }, '— wybierz moduł z listy —'),
            ...groups.map(([label, opts]) => h('optgroup', { label }, ...opts.map(([v, l]) => h('option', { value: v, selected: v === b.attrs.code ? true : null }, l)))));
        pick.addEventListener('change', () => { if (pick.value) setCode(pick.value); });
        const tools = h('div', { class: 'be-row be-mt' });
        const renderTools = () => {
            const code = b.attrs.code || '';
            const btn = (label, iconName, onClick) => h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: (e) => onClick(e.currentTarget) }, icon(iconName), ' ' + label);
            const items = [];
            if (window.TilesBuilder) {
                items.push(btn('Nowy zestaw kafelków…', 'plus', (t) => window.TilesBuilder.open({ id: null, trigger: t, onSave: (set) => {
                    if (!(CI.tileSets ||= []).some((x) => x.value === set.id)) CI.tileSets.push({ value: set.id, label: set.name });
                    setCode('[kafelki-zestaw:' + set.id + ']');
                } })));
                const m = code.match(/^\[kafelki-zestaw:(\d+)\]$/);
                if (m) items.push(btn('Edytuj zestaw', 'pen', (t) => window.TilesBuilder.open({ id: Number(m[1]), trigger: t, onSave: () => { shortcodePreviewCache.delete(code); load(); } })));
            }
            if (window.BlockBuilder) {
                (CI.newBlockTypes || []).forEach((bt) => items.push(btn({ cta: 'Nowe przyciski CTA…', accordion: 'Nowy akordeon…', callout: 'Nowa ramka informacyjna…', pricing: 'Nowy cennik…' }[bt] || bt, 'plus', (t) => window.BlockBuilder.open({ type: bt, id: null, trigger: t, onSave: (nb) => {
                    if (!(CI.contentBlocks ||= []).some((x) => x.value === nb.id)) CI.contentBlocks.push({ value: nb.id, type: nb.type, label: ((CI.blockTypes || {})[nb.type] || nb.type) + ': ' + nb.name });
                    setCode('[blok:' + nb.id + ']');
                } }))));
                const m = code.match(/^\[blok:(\d+)\]$/);
                const cb = m && (CI.contentBlocks || []).find((x) => x.value === Number(m[1]));
                if (cb) items.push(btn('Edytuj blok', 'pen', (t) => window.BlockBuilder.open({ type: cb.type, id: cb.value, trigger: t, onSave: () => { shortcodePreviewCache.delete(code); load(); } })));
            }
            tools.replaceChildren(...items);
        };
        renderTools();
        input.addEventListener('input', () => {
            typingHistory();
            b.attrs.code = input.value.trim();
            markDirty();
            input.setAttribute('aria-invalid', String(!!b.attrs.code && !SHORTCODE_RE.test(b.attrs.code)));
            loadSoon();
        });
        load();
        return h('div', { class: 'be-placeholder be-placeholder--compact' },
            h('label', { class: 'be-placeholder-title' }, icon('code'), ' Moduł (shortcode)'),
            groups.length ? pick : null,
            tools,
            input,
            h('p', { id: 'sc-help-' + b.id, class: 'be-small' }, 'Np. [formularz:slug], [kafelki:slug], [kafelki-zestaw:ID], [blok:ID], [pasek:ID].'),
            status,
            preview);
    }

    // Bloki „Lista podstron” i „Lista aktualności”: opis ustawień i podgląd z serwera
    // (ten sam renderer co na stronie). Ustawienia są w panelu bocznym („Blok”).
    const pageTitle = (id) => (config.subpages?.pages || []).find((p) => p.id === Number(id))?.title || ('strona #' + id);
    const layoutLabel = (v) => (LIST_LAYOUTS.find(([k]) => k === v) || LIST_LAYOUTS[0])[1].toLowerCase();
    function subpagesSummary(a) {
        const src = a.manual && a.picked.length ? `wybrane strony (${a.picked.length})` : (a.source === 'page' && a.page ? 'podstrony strony „' + pageTitle(a.page) + '”' : 'podstrony tej strony');
        return [src, a.sort === 'alfabet' ? 'alfabetycznie' : 'kolejność z drzewa', Number(a.depth) === 2 ? 'dzieci i wnuki' : 'tylko dzieci', layoutLabel(a.layout)].join(' · ');
    }
    function newsSummary(a) {
        const cat = a.category ? (config.newsCategories || []).find((c) => c.id === Number(a.category))?.name : null;
        return [cat ? 'kategoria „' + cat + '”' : 'wszystkie kategorie', 'do ' + (Number(a.limit) || 5) + ' wpisów', a.sort === 'alfabet' ? 'alfabetycznie' : 'od najnowszych', layoutLabel(a.layout)].join(' · ');
    }
    function linkListEditor(b, code, summary) {
        const def = BLOCKS[b.type];
        const status = h('p', { class: 'be-small be-sc-status', 'aria-live': 'polite' }, 'Wczytywanie podglądu…');
        const body = h('div', { class: 'be-sc-preview-body' });
        const preview = h('div', { class: 'be-sc-preview', hidden: true },
            h('p', { class: 'be-sc-preview-label', 'aria-hidden': 'true' }, icon('eye'), ' Podgląd'),
            h('div', { inert: true, 'aria-hidden': 'true' }, body));
        fetchShortcodePreview(code).then((html) => {
            const frag = sanitizePreview(html);
            if (!frag.textContent.trim()) {
                status.textContent = b.type === 'subpages'
                    ? 'Na razie nie ma opublikowanych podstron do pokazania — lista pojawi się sama, gdy się pojawią.'
                    : 'Na razie nie ma opublikowanych aktualności do pokazania.';
                return;
            }
            body.replaceChildren(frag);
            preview.hidden = false;
            status.textContent = 'Podgląd poniżej — lista aktualizuje się sama przy wyświetlaniu strony.';
        }).catch((e) => { status.textContent = 'Nie udało się wczytać podglądu: ' + e.message; status.classList.add('be-error'); });
        return h('div', { class: 'be-placeholder be-placeholder--compact' },
            h('p', { class: 'be-placeholder-title' }, icon(def.icon), ' ' + def.title),
            h('p', { class: 'be-small' }, summary, ' — zmień w panelu „Blok”.'),
            status,
            preview);
    }

    // Ostatnie zaznaczenie w polu tekstowym — przyciski formatowania użyte
    // z klawiatury (fokus w pasku narzędzi) przywracają je przed wykonaniem.
    let lastSel = null;
    document.addEventListener('selectionchange', () => {
        const sel = window.getSelection();
        if (!sel.rangeCount) return;
        const node = sel.anchorNode;
        const el = (node?.nodeType === 1 ? node : node?.parentElement)?.closest?.('[data-be-editable]');
        if (el && canvas.contains(el)) lastSel = { el, range: sel.getRangeAt(0).cloneRange() };
    });
    function withSelection(fn) {
        const active = document.activeElement?.closest?.('[data-be-editable]');
        if (!active && lastSel && lastSel.el.isConnected) {
            lastSel.el.focus();
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(lastSel.range);
        }
        const el = document.activeElement?.closest?.('[data-be-editable]');
        if (!el) { announce('Najpierw zaznacz tekst'); return; }
        fn(el);
    }

    // ── Zaznaczenie i pasek narzędzi bloku ─────────────────────────────────
    function selectBlock(id, { focusWrapper = false } = {}) {
        const changed = S.selectedId !== id;
        S.selectedId = id;
        if (changed) {
            applySelection();
            if (S.sidebarTab !== 'block' && id) S.sidebarTab = 'block';
            renderSidebar();
            if (S.panel === 'list') renderPanel();
        }
        if (focusWrapper) wrapperOf(id)?.focus({ preventScroll: false });
    }
    function applySelection() {
        canvas.querySelectorAll('.is-selected, .has-child-selected').forEach((el) => el.classList.remove('is-selected', 'has-child-selected'));
        toolbar.remove();
        const wrap = S.selectedId && wrapperOf(S.selectedId);
        if (!wrap) return;
        wrap.classList.add('is-selected');
        let p = wrap.parentElement?.closest('.be-block');
        while (p) { p.classList.add('has-child-selected'); p = p.parentElement?.closest('.be-block'); }
        buildToolbar(find(S.selectedId).block);
        wrap.prepend(toolbar);
    }

    function buildToolbar(b) {
        const def = BLOCKS[b.type];
        const keep = (e) => e.preventDefault(); // nie zabieraj zaznaczenia tekstu
        const btn = (label, iconName, onClick, attrs = {}) => h('button', { type: 'button', class: 'be-tool', 'aria-label': label, title: label, onMousedown: keep, onClick, ...attrs }, icon(iconName));
        const groups = [];

        const transforms = (def.transforms || []).filter((t) => t !== b.type);
        groups.push(h('div', { class: 'be-tool-group' },
            transforms.length
                ? dropdown(h('span', { class: 'be-tool-type' }, icon(def.icon), h('span', { class: 'be-sr-only' }, def.title + ' — zmień typ bloku')), transforms.map((t) => ({ label: BLOCKS[t].title, icon: BLOCKS[t].icon, onClick: () => transform(b, t) })), 'Zmień typ bloku: ' + def.title)
                : h('span', { class: 'be-tool be-tool--static', title: def.title }, icon(def.icon), h('span', { class: 'be-sr-only' }, def.title)),
            dragHandle(b),
            btn('Przenieś w górę', 'chevron-up', () => moveBlock(b.id, -1)),
            btn('Przenieś w dół', 'chevron-down', () => moveBlock(b.id, 1)),
        ));

        if (b.type === 'heading') {
            groups.push(h('div', { class: 'be-tool-group' }, ...[2, 3, 4].map((lvl) => h('button', {
                type: 'button', class: 'be-tool be-tool--text', 'aria-pressed': String(b.attrs.level === lvl), title: 'Nagłówek H' + lvl, onMousedown: keep,
                onClick: () => { updateAttrs(b, { level: lvl }); focusBlock(b.id, 'end'); },
            }, 'H' + lvl))));
        }
        if (b.type === 'list') {
            groups.push(h('div', { class: 'be-tool-group' },
                btn('Lista punktowana', 'list-ul', () => { updateAttrs(b, { ordered: false }); focusBlock(b.id, 'end'); }, { 'aria-pressed': String(!b.attrs.ordered) }),
                btn('Lista numerowana', 'list-ol', () => { updateAttrs(b, { ordered: true }); focusBlock(b.id, 'end'); }, { 'aria-pressed': String(b.attrs.ordered) })));
        }
        if (def.align) {
            groups.push(h('div', { class: 'be-tool-group' }, ...[['left', 'Do lewej'], ['center', 'Wyśrodkuj'], ['right', 'Do prawej']].map(([v, l]) =>
                btn(l, 'align-' + v, () => { updateAttrs(b, { align: v }); }, { 'aria-pressed': String((b.attrs.align || 'left') === v) }))));
        }
        if (def.rich || ['table', 'image', 'embed'].includes(b.type)) {
            groups.push(h('div', { class: 'be-tool-group' },
                btn(`Pogrubienie (${mod}+B)`, 'bold', () => withSelection(() => document.execCommand('bold'))),
                btn(`Kursywa (${mod}+I)`, 'italic', () => withSelection(() => document.execCommand('italic'))),
                btn(`Odnośnik (${mod}+K)`, 'link', () => withSelection((el) => openLinkDialog(el))),
                btn('Przekreślenie', 'strikethrough', () => withSelection(() => document.execCommand('strikeThrough'))),
                btn('Znak specjalny', 'section', () => withSelection((el) => openCharDialog(el, 'chars'))),
                btn('Emoji', 'face-smile', () => withSelection((el) => openCharDialog(el, 'emoji')))));
        }
        if (b.type === 'image' && b.attrs.url) {
            groups.push(h('div', { class: 'be-tool-group' }, btn('Zamień obraz', 'arrows-rotate', async () => { const p = await openMediaLibrary(); if (p[0]) await setImageWithAlt(b, p[0].url, { alt: p[0].alt || b.attrs.alt, decorative: b.attrs.decorative }); })));
        }
        if (b.type === 'embed' && b.attrs.url) {
            groups.push(h('div', { class: 'be-tool-group' }, btn('Zmień adres filmu', 'pen', () => updateAttrs(b, { url: '' }))));
        }

        groups.push(h('div', { class: 'be-tool-group' }, dropdown(icon('ellipsis-vertical'), [
            { label: 'Duplikuj', icon: 'copy', shortcut: mod + '+Shift+D', onClick: () => duplicateBlock(b.id) },
            { label: 'Wstaw blok przed', icon: 'arrow-up', onClick: () => { const r = find(b.id); togglePanel('inserter', r.parent ? { parentId: r.parent.id, index: r.index } : { index: r.index }); } },
            { label: 'Wstaw blok po', icon: 'arrow-down', onClick: () => { const r = find(b.id); togglePanel('inserter', r.parent ? { parentId: r.parent.id, index: r.index + 1 } : { index: r.index + 1 }); } },
            ...(b.type !== 'column' ? [{ label: 'Umieść w ramce', icon: 'rectangle-list', onClick: () => replaceBlock(b.id, [block('callout', {}, [b])]) }] : []),
            ...(b.type === 'callout' || b.type === 'details' ? [{ label: 'Rozgrupuj (zostaw samą treść)', icon: 'object-ungroup', onClick: () => replaceBlock(b.id, b.inner.length ? b.inner : [block('paragraph')]) }] : []),
            { label: 'Usuń blok', icon: 'trash', shortcut: 'Shift+Alt+Z', danger: true, onClick: () => removeBlock(b.id) },
        ], 'Więcej opcji')));

        toolbar.replaceChildren(...groups);
    }

    let openDropdown = null;
    function dropdown(trigger, items, label) {
        const menu = h('div', { class: 'be-menu', role: 'menu', hidden: true });
        const button = h('button', { type: 'button', class: 'be-tool', 'aria-haspopup': 'menu', 'aria-expanded': 'false', 'aria-label': label, title: label, onMousedown: (e) => e.preventDefault() }, trigger);
        const close = (focusBack = false) => { menu.hidden = true; button.setAttribute('aria-expanded', 'false'); openDropdown = null; if (focusBack) button.focus(); };
        menu.replaceChildren(...items.map((it) => h('button', {
            type: 'button', role: 'menuitem', class: 'be-menu-item' + (it.danger ? ' is-danger' : ''), tabindex: '-1',
            onMousedown: (e) => e.preventDefault(),
            onClick: () => { close(); it.onClick(); },
        }, icon(it.icon), h('span', {}, it.label), it.shortcut ? h('kbd', {}, it.shortcut) : null)));
        menu.addEventListener('keydown', (e) => {
            const all = [...menu.querySelectorAll('[role=menuitem]')];
            const i = all.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); all[(i + 1) % all.length].focus(); }
            if (e.key === 'ArrowUp') { e.preventDefault(); all[(i - 1 + all.length) % all.length].focus(); }
            if (e.key === 'Home') { e.preventDefault(); all[0].focus(); }
            if (e.key === 'End') { e.preventDefault(); all[all.length - 1].focus(); }
            if (e.key === 'Escape' || e.key === 'Tab') { e.preventDefault(); e.stopPropagation(); close(true); }
        });
        button.addEventListener('click', () => {
            if (!menu.hidden) { close(); return; }
            openDropdown?.();
            menu.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            openDropdown = () => close();
            menu.querySelector('[role=menuitem]')?.focus();
        });
        return h('div', { class: 'be-dropdown' }, button, menu);
    }
    document.addEventListener('mousedown', (e) => { if (openDropdown && !e.target.closest('.be-dropdown')) openDropdown(); });

    // ── Przeciąganie (alternatywa z klawiatury: strzałki w pasku i Alt+Shift+↑/↓) ──
    let dragId = null;
    function dragHandle(b) {
        const handle = h('span', { class: 'be-tool be-drag', draggable: 'true', title: 'Przeciągnij, aby przenieść', 'aria-hidden': 'true' }, icon('grip-vertical'));
        handle.addEventListener('dragstart', (e) => {
            dragId = b.id;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', b.id);
            const wrap = wrapperOf(b.id);
            if (wrap) { e.dataTransfer.setDragImage(wrap, 20, 20); requestAnimationFrame(() => wrap.classList.add('is-dragging')); }
        });
        handle.addEventListener('dragend', () => { dragId = null; dropLine.hidden = true; canvas.querySelectorAll('.is-dragging').forEach((el) => el.classList.remove('is-dragging')); });
        return handle;
    }
    let dropTarget = null;
    function onDragOver(e) {
        if (!dragId) return;
        const wrap = e.currentTarget;
        const id = wrap.dataset.id;
        if (id === dragId || isDescendant(dragId, id)) return;
        const src = find(dragId); const dst = find(id);
        if (!src || !dst || (src.block.type === 'column') !== (dst.parent?.type === 'columns')) return;
        e.preventDefault();
        e.stopPropagation();
        const rect = wrap.getBoundingClientRect();
        const horizontal = dst.block.type === 'column';
        const after = horizontal ? e.clientX > rect.left + rect.width / 2 : e.clientY > rect.top + rect.height / 2;
        dropTarget = { id, after };
        dropLine.hidden = false;
        if (horizontal) Object.assign(dropLine.style, { top: rect.top + 'px', left: (after ? rect.right : rect.left) - 2 + 'px', width: '4px', height: rect.height + 'px' });
        else Object.assign(dropLine.style, { top: (after ? rect.bottom : rect.top) - 2 + 'px', left: rect.left + 'px', width: rect.width + 'px', height: '4px' });
    }
    function onDrop(e) {
        if (!dragId || !dropTarget) return;
        e.preventDefault();
        e.stopPropagation();
        dropLine.hidden = true;
        const { id, after } = dropTarget;
        const moving = dragId;
        dragId = null; dropTarget = null;
        moveBlockTo(moving, id, after);
    }

    // Klik/fokus w bloku → zaznaczenie najgłębszego bloku.
    canvas.addEventListener('focusin', (e) => {
        if (e.target.closest('.be-toolbar')) return;
        const wrap = e.target.closest('.be-block');
        if (wrap) selectBlock(wrap.dataset.id);
    });
    canvas.addEventListener('mousedown', (e) => {
        if (e.target.closest('.be-toolbar, .be-appender, .be-root-appender')) return;
        const wrap = e.target.closest('.be-block');
        if (wrap && !e.target.closest('[data-be-editable], input, textarea, button, select')) {
            selectBlock(wrap.dataset.id);
            if (!wrap.contains(document.activeElement)) { e.preventDefault(); wrap.focus(); }
        }
    });
    // Tryb nawigacji: fokus na samym bloku (Esc w tekście).
    canvas.addEventListener('keydown', (e) => {
        const wrap = e.target.classList?.contains('be-block') ? e.target : null;
        if (e.key === 'Escape' && !wrap && !S.slash) {
            const w = e.target.closest?.('.be-block');
            if (w) { e.preventDefault(); w.focus(); announce('Tryb nawigacji. Strzałki zmieniają blok, Enter rozpoczyna edycję.'); }
            return;
        }
        if (!wrap) return;
        const id = wrap.dataset.id;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const all = [...canvas.querySelectorAll('.be-block')];
            const next = all[all.indexOf(wrap) + (e.key === 'ArrowDown' ? 1 : -1)];
            if (next) { selectBlock(next.dataset.id, { focusWrapper: true }); announce(next.getAttribute('aria-label')); }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const ed = editablesIn(wrap)[0] || wrap.querySelector('input, textarea, button:not(.be-tool)');
            if (ed) (ed.isContentEditable ? placeCaret(ed, false) : ed.focus());
        } else if (e.key === 'Delete' || e.key === 'Backspace') {
            e.preventDefault();
            removeBlock(id);
        }
    });

    // ── Prawy panel: Strona / Blok ─────────────────────────────────────────
    function field(label, control, help) {
        const id = control.id || ('f' + uid());
        control.id = id;
        if (help) control.setAttribute('aria-describedby', id + '-help');
        return h('div', { class: 'be-field' }, h('label', { for: id }, label), control, help ? h('p', { id: id + '-help', class: 'be-help' }, help) : null);
    }
    function textInput(value, onInput, attrs = {}) {
        const el = h(attrs.multiline ? 'textarea' : 'input', { class: 'be-input', type: attrs.multiline ? null : (attrs.type || 'text'), rows: attrs.multiline ? String(attrs.rows || 3) : null, placeholder: attrs.placeholder, maxlength: attrs.maxlength });
        el.value = value ?? '';
        el.addEventListener('input', () => onInput(el.value));
        return el;
    }
    function checkbox(label, checked, onChange, help) {
        const id = 'c' + uid();
        const input = h('input', { type: 'checkbox', id, checked, 'aria-describedby': help ? id + '-help' : null });
        input.addEventListener('change', () => onChange(input.checked));
        return h('div', { class: 'be-check' }, input, h('label', { for: id }, label), help ? h('p', { id: id + '-help', class: 'be-help' }, help) : null);
    }
    function select(value, options, onChange) {
        const el = h('select', { class: 'be-input' }, ...options.map(([v, l]) => h('option', { value: v, selected: String(v) === String(value) ? true : null }, l)));
        el.addEventListener('change', () => onChange(el.value));
        return el;
    }
    function panelSection(title, ...content) {
        return h('section', { class: 'be-side-section' }, title ? h('h3', {}, title) : null, ...content);
    }

    function renderSidebar() {
        sidebar.id = 'be-sidebar';
        sidebar.hidden = !S.sidebarOpen;
        mount.classList.toggle('has-sidebar', S.sidebarOpen);
        if (!S.sidebarOpen) return;
        const b = S.selectedId ? find(S.selectedId)?.block : null;
        const tab = (key, label) => h('button', { type: 'button', role: 'tab', class: 'be-tab', 'aria-selected': String(S.sidebarTab === key), onClick: () => { S.sidebarTab = key; renderSidebar(); sidebar.querySelector('[role=tab][aria-selected=true]')?.focus(); } }, label);
        sidebar.replaceChildren(
            h('div', { class: 'be-panel-head' },
                h('div', { class: 'be-tabs', role: 'tablist', 'aria-label': 'Ustawienia' }, tab('page', L.kind), tab('block', 'Blok')),
                h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': 'Zamknij ustawienia', onClick: () => { S.sidebarOpen = false; renderSidebar(); updateTopbar(); } }, icon('xmark'))),
            h('div', { class: 'be-panel-body', role: 'tabpanel' }, S.sidebarTab === 'page' ? pageSettings() : (b ? blockSettings(b) : h('p', { class: 'be-muted' }, 'Zaznacz blok, aby zobaczyć jego ustawienia.'))));
    }

    function pageSettings() {
        const title = textInput(S.title, (v) => { typingHistory(); S.title = v; titleEl.textContent = v; titleEl.classList.toggle('is-empty', !v.trim()); markDirty(); });
        title.id = 'be-page-title';
        const counter = h('span', { class: 'be-counter', 'aria-live': 'polite' });
        const metaCount = () => { counter.textContent = `${S.meta.length} / 160 znaków`; counter.classList.toggle('is-over', S.meta.length > 160); };
        const meta = textInput(S.meta, (v) => { typingHistory(); S.meta = v; metaCount(); markDirty(); }, { multiline: true, rows: 4, maxlength: '500' });
        metaCount();
        return h('div', {},
            ...publicationSettings(),
            panelSection(null,
                field(L.title, title),
                field('Opis dla wyszukiwarek', meta, 'Jedno, dwa zdania o tym, czego dotyczy treść. Najlepiej do 160 znaków.'),
                counter),
            typeForm ? panelSection('Pola typu strony',
                h('p', { class: 'be-help' }, 'Dane typu „' + config.typeFields.name + '” — pokazują się na stronie poza treścią.'),
                h('button', { type: 'button', class: 'be-btn be-btn--secondary be-w-full', 'data-type-fields-open': true, 'aria-haspopup': 'dialog', onClick: openTypeFields }, icon('list-check'), ' Edytuj pola typu')) : null,
            panelSection('Informacje',
                h('dl', { class: 'be-dl' },
                    h('dt', {}, L.typeLabel), h('dd', {}, config.page.type),
                    h('dt', {}, 'Status'), h('dd', {}, config.page.is_published ? L.publishedLong : L.draftLong),
                    h('dt', {}, 'Bloków'), h('dd', {}, String(countBlocks())))),
            config.urls.newsForm ? panelSection('Ustawienia aktualności',
                h('p', { class: 'be-help' }, L.more),
                h('button', { type: 'button', class: 'be-btn be-btn--secondary be-w-full', 'data-news-settings-open': true, 'aria-haspopup': 'dialog', onClick: openNewsSettings }, icon('sliders'), ' Ustawienia aktualności'))
            : panelSection('Więcej ustawień',
                h('p', { class: 'be-help' }, L.more),
                h('a', { href: config.urls.admin, class: 'be-btn be-btn--secondary be-w-full' }, icon('sliders'), ' ' + L.form)));
    }
    // Publikacja, wersja robocza, kosz i miejsce w serwisie — to, co formularz pokazuje bez przewijania.
    function publicationSettings() {
        const C = config.settings;
        const v = S.settings;
        if (!C || !v) return [];
        const change = (patch, rerender = false) => {
            Object.assign(v, patch); S.settingsDirty = true; markDirty();
            // Po przerysowaniu panelu fokus wraca na zmienione pole.
            if (rerender) { renderSidebar(); sidebar.querySelector(`[data-setting="${Object.keys(patch)[0]}"]`)?.focus(); }
        };
        const tag = (el, key) => { (el.matches('input, select') ? el : el.querySelector('input, select')).dataset.setting = key; return el; };
        const isPage = TARGET.type === 'page';
        const isNews = TARGET.type === 'news';
        const dateKey = isPage ? 'publish_at' : (isNews ? 'published_at' : null);
        const pubBox = checkbox(L.publishedLong, v.is_published, (on) => change({ is_published: on }, true),
            v.pending_approval ? 'Czeka na zatwierdzenie przez osobę zatwierdzającą.' : (C.canApprove ? 'Treść jest dostępna publicznie.' : 'Po zapisie trafi do zatwierdzenia.'));
        const date = dateKey && v.is_published ? field(isPage ? 'Opublikuj dopiero od (opcjonalnie)' : 'Data publikacji', (() => {
            const el = h('input', { type: 'datetime-local', class: 'be-input', value: v[dateKey] || '' });
            el.addEventListener('change', () => change({ [dateKey]: el.value || null }));
            return el;
        })(), isPage ? 'Puste = widoczna od razu po zapisie.' : 'Od tej chwili aktualność jest widoczna na liście.') : null;
        const parts = [panelSection('Publikacja',
            tag(pubBox, 'is_published'),
            date,
            'is_archived' in v ? checkbox('Treść archiwalna', v.is_archived, (on) => change({ is_archived: on }), 'Baner, że treść może być nieaktualna (zostaje w wyszukiwarce).') : null,
            h('div', { class: 'be-grid-2' },
                h('button', { type: 'button', class: 'be-btn be-btn--primary', onClick: () => save() }, icon('floppy-disk'), ' Zapisz'),
                h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: () => { v.is_published = false; save({ draft: true }); } }, icon('pen-ruler'), ' Wersja robocza')),
            h('p', { class: 'be-help' }, 'Wersja robocza nie jest widoczna publicznie — opublikujesz ją później.'),
            h('div', { class: 'be-row be-row--between' },
                C.index ? h('a', { href: C.index, class: 'be-btn be-btn--ghost' }, icon('arrow-left'), ' Anuluj') : null,
                C.destroy ? h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', onClick: () => {
                    if (!window.confirm(`Przenieść „${S.title || 'bez tytułu'}” do kosza?`)) return;
                    S.dirty = false;
                    clearDraft();
                    const f = h('form', { method: 'POST', action: C.destroy, hidden: true },
                        h('input', { type: 'hidden', name: '_token', value: CSRF }), h('input', { type: 'hidden', name: '_method', value: 'DELETE' }));
                    document.body.append(f);
                    f.submit();
                } }, icon('trash-can'), ' Do kosza') : null))];
        if (isPage) {
            const opts = (list, empty) => [['', empty], ...list.map((o) => [o.id, o.title])];
            const order = h('input', { type: 'number', class: 'be-input', min: '0', value: String(v.order ?? 0) });
            order.addEventListener('change', () => change({ order: Math.max(0, Number(order.value) || 0) }));
            parts.push(panelSection('Miejsce w serwisie',
                field('Nadrzędna strona', select(v.parent_id || '', opts(C.parents, '— brak (strona główna) —'), (x) => change({ parent_id: Number(x) || null })), 'Strony z tym samym rodzicem tworzą wspólne podmenu.'),
                field('Powiąż z działaniem', tag(select(v.project_id || '', opts(C.projects, '— brak —'), (x) => change({ project_id: Number(x) || null }, true)), 'project_id'), 'Strona zachowa adres i pojawi się też na stronie działania.'),
                v.project_id ? field('Jak pokazać na stronie działania', select(v.project_display, Object.entries(C.projectDisplays), (x) => change({ project_display: x }))) : null,
                field('Kolejność', order, 'Mniejsza liczba = wyżej w menu i na listach podstron.')));
        }
        return parts;
    }
    const countBlocks = () => { let n = 0; walk(S.blocks, () => n++); return n; };

    // Przycisk „Wybierz stronę” przy polu adresu w inspektorze — otwiera okno
    // wyboru odnośnika wewnętrznego; po zamknięciu fokus wraca na ten przycisk.
    function pickLinkButton(key, srSuffix, getCurrent, onPick) {
        return h('button', {
            type: 'button', class: 'be-btn be-btn--secondary be-pick-link', 'data-pick': key,
            onClick: async () => {
                const v = await openLinkPickerDialog(getCurrent() || '');
                if (v !== null) { onPick(v); announce('Ustawiono adres: ' + v); }
                renderSidebar();
                sidebar.querySelector(`[data-pick="${key}"]`)?.focus();
            },
        }, icon('link'), ' Wybierz stronę', h('span', { class: 'be-sr-only' }, srSuffix));
    }

    function blockSettings(b) {
        const def = BLOCKS[b.type];
        const a = b.attrs;
        const parts = [
            h('div', { class: 'be-block-card' }, icon(def.icon), h('div', {}, h('strong', {}, def.title), h('p', {}, def.desc))),
        ];
        const warnings = S.warnings.get(b.id);
        if (warnings) {
            parts.push(h('div', { class: 'be-warnings', tabindex: '-1', role: 'region', 'aria-label': 'Uwagi dostępności' },
                h('h3', {}, icon('triangle-exclamation'), ' Dostępność'),
                h('ul', {}, ...warnings.map((w) => h('li', {}, w)))));
        }
        const set = (patch, opts) => updateAttrs(b, patch, opts);
        const typing = (patch) => updateAttrs(b, patch, { typing: true });
        switch (b.type) {
            case 'paragraph':
                parts.push(panelSection('Wygląd', checkbox('Tekst wprowadzający (większy)', a.lead, (v) => set({ lead: v }), 'Dla pierwszego akapitu streszczającego stronę.')));
                break;
            case 'heading':
                parts.push(panelSection('Ustawienia',
                    field('Poziom', select(a.level, [[2, 'H2 — sekcja'], [3, 'H3 — podsekcja'], [4, 'H4 — szczegół']], (v) => set({ level: Number(v) })), 'Tytuł strony to H1. Nie pomijaj poziomów.'),
                    field('Kotwica (adres #)', textInput(a.anchor, (v) => typing({ anchor: v.toLowerCase().replace(/[^a-z0-9_-]+/g, '-') }), { placeholder: 'np. kontakt' }), 'Pozwala linkować do tego miejsca: adres-strony#kotwica.')));
                break;
            case 'image':
                if (a.url) {
                    parts.push(panelSection('Tekst alternatywny',
                        checkbox('Obraz dekoracyjny', a.decorative, (v) => set({ decorative: v }), 'Zaznacz, jeśli obraz nic nie wnosi do treści — czytnik ekranu go pominie.'),
                        a.decorative ? null : field('Opis obrazu', textInput(a.alt, (v) => updateAttrs(b, { alt: v }, { typing: true, rerender: false }), { multiline: true, rows: 3 }), 'Opisz, co widać i dlaczego to ważne. Nie zaczynaj od „Zdjęcie…”. Tekst na grafice przepisz w całości.')),
                    panelSection('Wygląd i odnośnik',
                        field('Szerokość', select(a.width, [['full', 'Pełna szerokość treści'], ['wide', 'Szeroki'], ['medium', 'Średni (2/3)'], ['small', 'Mały (1/3)']], (v) => set({ width: v }))),
                        field('Odnośnik po kliknięciu', textInput(a.href, (v) => updateAttrs(b, { href: v.trim() }, { typing: true, rerender: false }), { type: 'url', placeholder: 'https://… lub /strona' })),
                        pickLinkButton(`img-${b.id}`, ' jako odnośnik obrazu', () => find(b.id)?.block.attrs.href, (v) => updateAttrs(b, { href: v }, { rerender: false })),
                        h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', onClick: () => set({ url: '', alt: '', caption: '' }) }, icon('trash'), ' Usuń obraz')));
                }
                break;
            case 'gallery':
                parts.push(panelSection('Układ', field('Liczba kolumn', select(a.columns, [[2, '2'], [3, '3'], [4, '4']], (v) => set({ columns: Number(v) })))));
                parts.push(panelSection('Zdjęcia', ...a.images.map((img, i) => h('fieldset', { class: 'be-fieldset' },
                    h('legend', {}, 'Zdjęcie ' + (i + 1)),
                    h('img', { src: img.url, alt: '', class: 'be-thumb' }),
                    field('Tekst alternatywny', textInput(img.alt, (v) => { typingHistory(); img.alt = v; markDirty(); })),
                    field('Podpis', textInput(img.caption, (v) => { typingHistory(); img.caption = v; markDirty(); rerenderBlock(b.id); })),
                    h('div', { class: 'be-row' },
                        h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': `Przesuń zdjęcie ${i + 1} wcześniej`, disabled: i === 0 ? true : null, onClick: () => { const imgs = [...a.images]; [imgs[i - 1], imgs[i]] = [imgs[i], imgs[i - 1]]; set({ images: imgs }); renderSidebar(); } }, icon('arrow-up')),
                        h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': `Przesuń zdjęcie ${i + 1} dalej`, disabled: i === a.images.length - 1 ? true : null, onClick: () => { const imgs = [...a.images]; [imgs[i + 1], imgs[i]] = [imgs[i], imgs[i + 1]]; set({ images: imgs }); renderSidebar(); } }, icon('arrow-down')),
                        h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', onClick: () => { set({ images: a.images.filter((_, j) => j !== i) }); renderSidebar(); } }, icon('trash'), ' Usuń'))))));
                break;
            case 'buttons':
                parts.push(...a.items.map((btn, i) => panelSection('Przycisk ' + (i + 1),
                    field('Tekst', textInput(btn.label, (v) => { typingHistory(); btn.label = v; markDirty(); rerenderBlock(b.id); }), 'Powiedz, co się stanie: „Zapisz się na szkolenie”, nie „Kliknij tutaj”.'),
                    field('Adres', textInput(btn.url, (v) => { typingHistory(); btn.url = v.trim(); markDirty(); scheduleWarnings(); }, { type: 'url', placeholder: 'https://… lub /strona' })),
                    pickLinkButton(`btn-${b.id}-${i}`, ' dla przycisku ' + (i + 1), () => btn.url, (v) => { pushHistory(); btn.url = v; markDirty(); scheduleWarnings(); }),
                    field('Kolor', select(btn.variant, [['brand', 'Niebieski (główny)'], ['red', 'Czerwony'], ['green', 'Zielony']], (v) => { pushHistory(); btn.variant = v; markDirty(); rerenderBlock(b.id); })),
                    checkbox('Otwieraj w nowej karcie', btn.newTab, (v) => { pushHistory(); btn.newTab = v; markDirty(); }, 'Używaj oszczędnie. Czytnik ekranu usłyszy informację o nowej karcie.'),
                    a.items.length > 1 ? h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', onClick: () => { set({ items: a.items.filter((_, j) => j !== i) }); renderSidebar(); } }, icon('trash'), ' Usuń przycisk') : null)));
                break;
            case 'callout':
                parts.push(panelSection('Rodzaj ramki', field('Styl', select(a.variant, [['box', 'Ramka (kolor marki)'], ['note', 'Notatka / ostrzeżenie (bursztynowa)'], ['important', 'Ważna informacja (niebieska)']], (v) => set({ variant: v })))));
                break;
            case 'columns': {
                const setCount = (n) => {
                    pushHistory();
                    while (b.inner.length < n) b.inner.push(block('column'));
                    while (b.inner.length > n) {
                        const removed = b.inner.pop();
                        b.inner[b.inner.length - 1].inner.push(...removed.inner.filter((x) => x.type !== 'paragraph' || textOf(x.attrs.text)));
                    }
                    markDirty(); rerenderBlock(b.id); renderSidebar();
                };
                parts.push(panelSection('Układ',
                    field('Liczba kolumn', select(b.inner.length, [[2, '2'], [3, '3'], [4, '4']], (v) => setCount(Number(v))), 'Przy zmniejszaniu treść usuniętej kolumny trafia do poprzedniej.'),
                    b.inner.length === 2 ? field('Proporcje', select(a.ratio, [['equal', 'Równe'], ['33-67', 'Wąska + szeroka (1/3 + 2/3)'], ['67-33', 'Szeroka + wąska (2/3 + 1/3)']], (v) => set({ ratio: v }))) : null,
                    h('p', { class: 'be-help' }, 'Na telefonie kolumny ustawiają się jedna pod drugą, w tej samej kolejności.')));
                break;
            }
            case 'column':
                parts.push(panelSection(null, h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: () => { const r = find(b.id); selectBlock(r.parent.id, { focusWrapper: true }); } }, icon('arrow-up'), ' Zaznacz cały układ kolumn')));
                break;
            case 'details':
                parts.push(panelSection('Ustawienia', checkbox('Domyślnie rozwinięta', a.open, (v) => set({ open: v }, { rerender: false }))));
                break;
            case 'table': {
                const cellPos = S.tableCell?.id === b.id ? S.tableCell : null;
                const mutate = (fn) => { pushHistory(); fn(a.rows); markDirty(); rerenderBlock(b.id); renderSidebar(); };
                parts.push(panelSection('Ustawienia',
                    field('Podpis tabeli', textInput(a.caption, (v) => { updateAttrs(b, { caption: v }, { typing: true }); }), 'Krótko: co zawiera tabela. Czytniki ekranu odczytują go przed danymi.'),
                    checkbox('Pierwszy wiersz to nagłówki', a.headerRow, (v) => set({ headerRow: v })),
                    checkbox('Pierwsza kolumna to nagłówki', a.headerColumn, (v) => set({ headerColumn: v }))),
                panelSection('Wiersze i kolumny',
                    h('div', { class: 'be-grid-2' },
                        h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: () => mutate((rows) => rows.splice(cellPos ? cellPos.r + 1 : rows.length, 0, rows[0].map(() => ''))) }, icon('plus'), ' Wiersz'),
                        h('button', { type: 'button', class: 'be-btn be-btn--secondary', disabled: a.rows[0].length >= 12 ? true : null, onClick: () => mutate((rows) => rows.forEach((r) => r.splice(cellPos ? cellPos.c + 1 : r.length, 0, ''))) }, icon('plus'), ' Kolumna'),
                        h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', disabled: a.rows.length <= 1 ? true : null, onClick: () => mutate((rows) => rows.splice(cellPos ? cellPos.r : rows.length - 1, 1)) }, icon('minus'), ' Wiersz'),
                        h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', disabled: a.rows[0].length <= 1 ? true : null, onClick: () => mutate((rows) => rows.forEach((r) => r.splice(cellPos ? cellPos.c : r.length - 1, 1))) }, icon('minus'), ' Kolumna')),
                    h('p', { class: 'be-help' }, cellPos ? `Dodawanie i usuwanie względem komórki: wiersz ${cellPos.r + 1}, kolumna ${cellPos.c + 1}.` : 'Kliknij komórkę, aby dodawać i usuwać względem niej. Tab przechodzi do następnej komórki.')));
                break;
            }
            case 'embed':
                parts.push(panelSection('Film',
                    field('Adres filmu', textInput(a.url, (v) => updateAttrs(b, { url: v.trim() }, { typing: true }), { type: 'url' })),
                    field('Tytuł filmu', textInput(a.title, (v) => updateAttrs(b, { title: v }, { typing: true, rerender: false })), 'Wymagany dla dostępności — czytnik ekranu ogłosi go przy ramce filmu.'),
                    h('p', { class: 'be-help' }, 'Upewnij się, że film ma napisy, a jeśli przekazuje ważne treści tylko obrazem — dodaj ich opis pod filmem.')));
                break;
            case 'file':
                parts.push(panelSection('Plik',
                    field('Adres pliku', textInput(a.url, (v) => updateAttrs(b, { url: v.trim() }, { typing: true }), { type: 'url' })),
                    field('Nazwa wyświetlana', textInput(a.label, (v) => updateAttrs(b, { label: v }, { typing: true }))),
                    field('Opis (opcjonalnie)', textInput(a.description, (v) => updateAttrs(b, { description: v }, { typing: true })), 'Np. „Wersja dostępna, 2 strony”.')));
                break;
            case 'subpages': {
                const pages = config.subpages?.pages || [];
                const indent = (p) => '\u2003'.repeat(Math.min(p.depth, 6)) + p.title;
                const picker = () => {
                    const filter = h('input', { type: 'search', class: 'be-input', placeholder: 'Szukaj strony…', 'aria-label': 'Szukaj strony na liście wyboru' });
                    const list = h('div', { class: 'be-pick-list', role: 'group', 'aria-label': 'Strony do pokazania', style: 'max-height:16rem;overflow:auto' },
                        ...pages.map((p) => {
                            const row = checkbox(indent(p), a.picked.includes(p.id), (v) => {
                                const chosen = new Set(find(b.id).block.attrs.picked);
                                if (v) chosen.add(p.id); else chosen.delete(p.id);
                                // Kolejność jak w drzewie stron.
                                set({ picked: pages.filter((x) => chosen.has(x.id)).map((x) => x.id) });
                            });
                            row.dataset.title = p.title.toLowerCase();
                            return row;
                        }));
                    filter.addEventListener('input', () => { const q = filter.value.trim().toLowerCase(); list.querySelectorAll('[data-title]').forEach((r) => { r.hidden = !!q && !r.dataset.title.includes(q); }); });
                    return [filter, list, h('p', { class: 'be-help' }, `Wybrano: ${a.picked.length}. Kolejność na liście jak w drzewie stron (albo alfabetyczna).`)];
                };
                parts.push(panelSection('Źródło',
                    field('Pokaż podstrony', select(a.source, [['current', config.subpages?.contextPage ? 'Tej strony' : 'Bieżącej strony (tej, na której stoi lista)'], ['page', 'Wskazanej strony']], (v) => { set({ source: v }); renderSidebar(); })),
                    a.source === 'page' ? field('Strona', select(a.page || '', [['', '— wybierz stronę —'], ...pages.map((p) => [p.id, indent(p)])], (v) => set({ page: Number(v) || null }))) : null,
                    checkbox('Wybierz strony ręcznie', a.manual, (v) => { set({ manual: v }); renderSidebar(); }, 'Zamiast wszystkich podstron pokaże tylko zaznaczone strony.'),
                    ...(a.manual ? picker() : [])),
                panelSection('Ustawienia listy',
                    field('Sortowanie', select(a.sort, [['drzewo', 'Kolejność z drzewa stron'], ['alfabet', 'Alfabetycznie']], (v) => set({ sort: v }))),
                    field('Głębokość', select(a.depth, [[1, 'Tylko podstrony (dzieci)'], [2, 'Podstrony i ich podstrony (dzieci i wnuki)']], (v) => set({ depth: Number(v) }))),
                    field('Wygląd', select(a.layout, LIST_LAYOUTS, (v) => set({ layout: v }))),
                    h('p', { class: 'be-help' }, 'Strony nieopublikowane, wyłączone i ukryte na mapie strony są pomijane.')));
                break;
            }
            case 'newslist':
                parts.push(panelSection('Ustawienia listy',
                    field('Kategoria', select(a.category || '', [['', 'Wszystkie kategorie'], ...(config.newsCategories || []).map((c) => [c.id, c.name])], (v) => set({ category: Number(v) || null }))),
                    field('Liczba wpisów', select(a.limit, [3, 4, 5, 6, 8, 10, 12, 15, 20, 30].map((n) => [n, String(n)]), (v) => set({ limit: Number(v) }))),
                    field('Sortowanie', select(a.sort, [['najnowsze', 'Od najnowszych'], ['alfabet', 'Alfabetycznie (spośród najnowszych)']], (v) => set({ sort: v }))),
                    field('Wygląd', select(a.layout, LIST_LAYOUTS, (v) => set({ layout: v }))),
                    h('p', { class: 'be-help' }, 'Pokazuje tylko opublikowane aktualności; szkice, zaplanowane i zarchiwizowane są pomijane.')));
                break;
            default: break;
        }
        parts.push(panelSection(null, h('div', { class: 'be-grid-2' },
            h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: () => duplicateBlock(b.id) }, icon('copy'), ' Duplikuj'),
            h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', onClick: () => removeBlock(b.id) }, icon('trash'), ' Usuń'))));
        return h('div', {}, ...parts);
    }

    // ── Odnośniki ──────────────────────────────────────────────────────────
    // Wybór odnośnika wewnętrznego — wzorzec ARIA „combobox z listą”: pole adresu
    // podpowiada opublikowane strony, aktualności i działania (szukanie po tytule
    // lub adresie), a wpisany ręcznie adres działa jak dotąd.
    const linkCache = new Map();
    let dialogSeq = 0;
    function fetchLinks(q) {
        const key = q.toLowerCase();
        if (!linkCache.has(key)) {
            const url = config.urls.links + (config.urls.links.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q);
            linkCache.set(key, fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => (r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status))))
                .catch((e) => { linkCache.delete(key); throw e; }));
        }
        return linkCache.get(key);
    }

    function linkPicker({ value = '', id, label = 'Adres' }) {
        const listId = id + '-list';
        const helpId = id + '-help';
        const status = h('p', { class: 'be-sr-only', role: 'status', 'aria-live': 'polite' });
        const chosen = h('p', { class: 'be-help be-link-chosen', hidden: true });
        const input = h('input', {
            type: 'text', class: 'be-input', id, value, placeholder: 'https://…, /strona albo tytuł', role: 'combobox',
            'aria-autocomplete': 'list', 'aria-expanded': 'false', 'aria-controls': listId, 'aria-describedby': helpId,
            autocomplete: 'off', spellcheck: 'false',
        });
        const list = h('ul', { class: 'be-combo-list', id: listId, role: 'listbox', 'aria-label': 'Strony, osoby, aktualności, działania, wydarzenia, pliki i kotwice', hidden: true });
        let items = [];
        let active = -1;
        let seq = 0;
        let escapedAt = 0;
        const isOpen = () => !list.hidden;
        const open = () => { list.hidden = false; input.setAttribute('aria-expanded', 'true'); };
        const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; };
        const setActive = (i) => {
            active = i;
            [...list.children].forEach((li, j) => li.setAttribute('aria-selected', String(j === i)));
            if (i >= 0) {
                input.setAttribute('aria-activedescendant', `${listId}-${i}`);
                list.children[i]?.scrollIntoView({ block: 'nearest' });
            } else input.removeAttribute('aria-activedescendant');
        };
        const choose = (i) => {
            const it = items[i];
            if (!it) return;
            input.value = it.url;
            input.removeAttribute('aria-invalid');
            close();
            chosen.hidden = false;
            chosen.textContent = `Wybrano: ${it.title} (${it.kind.toLowerCase()})`;
            status.textContent = `Wybrano: ${it.title}, ${it.kind}.`;
            input.focus();
        };
        const search = async () => {
            const raw = input.value.trim();
            // Pełny adres (https://, mailto:, tel:) wpisany ręcznie — bez podpowiedzi.
            if (/^(https?:|mailto:|tel:|www\.)/i.test(raw)) { items = []; close(); return; }
            // Kotwice nagłówków tej treści (jak „Link do kotwicy…” w klasycznym edytorze).
            const anchorItems = (q) => {
                const out = [];
                walk(S.blocks, (b) => { if (b.type === 'heading' && b.attrs.anchor) out.push({ title: textOf(b.attrs.text) || b.attrs.anchor, url: '#' + b.attrs.anchor, kind: 'Kotwica' }); });
                const n = q.toLowerCase();
                return out.filter((a) => !n || a.title.toLowerCase().includes(n) || a.url.includes(n));
            };
            if (raw.startsWith('#')) { items = anchorItems(raw.slice(1)); showItems(raw); return; }
            // Ścieżka (/aktualnosci/nazwa) — szukamy po ostatnim członie adresu.
            const q = raw.startsWith('/') ? (raw.split(/[/?#]/).filter(Boolean).pop() || '') : raw;
            const my = ++seq;
            let data;
            try { data = await fetchLinks(q); } catch (e) {
                if (my === seq) { items = []; close(); status.textContent = 'Nie udało się wczytać podpowiedzi. Możesz wpisać adres ręcznie.'; }
                return;
            }
            if (my !== seq || document.activeElement !== input) return;
            items = [...anchorItems(q), ...(Array.isArray(data) ? data : [])];
            showItems(q);
        };
        const showItems = (q) => {
            list.replaceChildren(...items.map((it, i) => h('li', {
                id: `${listId}-${i}`, role: 'option', class: 'be-combo-option', 'aria-selected': 'false',
                onMousedown: (e) => { e.preventDefault(); choose(i); },
            }, h('span', { class: 'be-combo-title' }, it.title), h('span', { class: 'be-combo-meta' }, `${it.kind} · ${it.url}`))));
            if (!items.length) {
                close();
                status.textContent = q ? 'Brak pasujących stron. Możesz wpisać adres ręcznie.' : '';
                return;
            }
            open();
            setActive(-1);
            status.textContent = `${items.length} ${plural(items.length, 'podpowiedź', 'podpowiedzi', 'podpowiedzi')}. Strzałka w dół wybiera, Enter zatwierdza.`;
        };
        const searchSoon = debounce(search, 250);
        input.addEventListener('input', () => { chosen.hidden = true; input.removeAttribute('aria-invalid'); searchSoon(); });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!isOpen()) { search().then(() => { if (isOpen()) setActive(0); }); return; }
                setActive((active + 1) % items.length);
            } else if (e.key === 'ArrowUp' && isOpen()) {
                e.preventDefault();
                setActive(active <= 0 ? items.length - 1 : active - 1);
            } else if (e.key === 'Enter' && isOpen() && active >= 0) {
                e.preventDefault();
                choose(active);
            } else if (e.key === 'Escape' && isOpen()) {
                // Esc zamyka najpierw listę, dopiero kolejny — okno dialogowe.
                e.preventDefault();
                e.stopPropagation();
                escapedAt = Date.now();
                close();
            } else if (e.key === 'Tab') close();
        });
        input.addEventListener('blur', close);
        const root = h('div', { class: 'be-field be-combo' },
            h('label', { for: id }, label),
            input,
            list,
            h('p', { id: helpId, class: 'be-help' }, 'Wpisz adres (https://…, /strona, mailto:…) albo zacznij pisać tytuł, aby wybrać z listy stronę, osobę, aktualność, działanie, wydarzenie albo plik do pobrania (strzałka w dół). „#” pokazuje kotwice nagłówków tej treści.'),
            chosen,
            status);
        return { root, input, escapedRecently: () => Date.now() - escapedAt < 400 };
    }
    const normalizeHref = (v) => (/^www\./i.test(v) ? 'https://' + v : v);
    const validHref = (v) => safeUrl(v) || /^www\./i.test(v);

    function openLinkDialog(el) {
        const sel = window.getSelection();
        if (!sel.rangeCount || !el.contains(sel.anchorNode)) { el.focus(); return; }
        const range = sel.getRangeAt(0).cloneRange();
        const existing = (sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement).closest('a');
        if (range.collapsed && !existing) { announce('Zaznacz tekst, który ma być odnośnikiem'); return; }
        const picker = linkPicker({ value: existing?.getAttribute('href') || '', id: 'be-link-url' });
        const url = picker.input;
        const newTab = h('input', { type: 'checkbox', id: 'be-link-tab', checked: existing?.target === '_blank' });
        const error = h('p', { class: 'be-error', role: 'alert' });
        const dlg = h('dialog', { class: 'be-dialog be-dialog--small', 'aria-labelledby': 'be-link-title' });
        const restoreSel = () => { el.focus(); sel.removeAllRanges(); sel.addRange(range); };
        const finish = () => { dlg.close(); dlg.remove(); };
        dlg.append(h('form', { method: 'dialog', onSubmit: (e) => {
            e.preventDefault();
            const v = url.value.trim();
            if (!validHref(v)) { error.textContent = 'Podaj adres zaczynający się od https://, / , mailto: lub tel: albo wybierz stronę z listy.'; url.setAttribute('aria-invalid', 'true'); url.focus(); return; }
            const href = normalizeHref(v);
            finish();
            restoreSel();
            if (existing) { pushHistory(); existing.setAttribute('href', href); } else document.execCommand('createLink', false, href);
            const a = existing || (window.getSelection().anchorNode?.parentElement?.closest('a'));
            if (a) { if (newTab.checked) { a.target = '_blank'; a.rel = 'noopener'; } else { a.removeAttribute('target'); a.removeAttribute('rel'); } }
            el.dispatchEvent(new Event('input', { bubbles: true }));
        } },
        h('h2', { id: 'be-link-title' }, existing ? 'Edytuj odnośnik' : 'Wstaw odnośnik'),
        picker.root,
        h('div', { class: 'be-check' }, newTab, h('label', { for: 'be-link-tab' }, 'Otwórz w nowej karcie')),
        error,
        h('div', { class: 'be-dialog-actions' },
            existing ? h('button', { type: 'button', class: 'be-btn be-btn--ghost be-danger', onClick: () => { finish(); restoreSel(); const r = document.createRange(); r.selectNodeContents(existing); sel.removeAllRanges(); sel.addRange(r); document.execCommand('unlink'); el.dispatchEvent(new Event('input', { bubbles: true })); } }, icon('link-slash'), ' Usuń odnośnik') : null,
            h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => { finish(); restoreSel(); } }, 'Anuluj'),
            h('button', { type: 'submit', class: 'be-btn be-btn--primary' }, 'Zastosuj'))));
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); if (picker.escapedRecently()) return; finish(); restoreSel(); });
        document.body.append(dlg);
        dlg.showModal();
        url.focus();
        url.select();
    }

    // Znaki specjalne i emoji (jak „charmap” / „emoticons” w klasycznym edytorze). Siatka przycisków
    // z nazwami dla czytnika ekranu; wybrany znak wstawia się w miejscu kursora.
    const CHARS = [['„', 'cudzysłów otwierający'], ['”', 'cudzysłów zamykający'], ['«', 'cudzysłów ostrokątny lewy'], ['»', 'cudzysłów ostrokątny prawy'], ['–', 'półpauza'], ['—', 'pauza'], ['…', 'wielokropek'], ['\u00a0', 'twarda spacja'],
        ['·', 'kropka środkowa'], ['•', 'punktor'], ['§', 'paragraf'], ['©', 'copyright'], ['®', 'znak zastrzeżony'], ['™', 'znak towarowy'], ['°', 'stopień'], ['±', 'plus minus'],
        ['×', 'razy'], ['÷', 'dzielone'], ['≈', 'około'], ['≠', 'różne'], ['≤', 'mniejsze lub równe'], ['≥', 'większe lub równe'], ['€', 'euro'], ['£', 'funt'],
        ['¼', 'jedna czwarta'], ['½', 'jedna druga'], ['¾', 'trzy czwarte'], ['²', 'do kwadratu'], ['³', 'do sześcianu'], ['‰', 'promil'], ['№', 'numer'], ['′', 'prim'],
        ['→', 'strzałka w prawo'], ['←', 'strzałka w lewo'], ['↑', 'strzałka w górę'], ['↓', 'strzałka w dół'], ['✓', 'ptaszek'], ['✗', 'krzyżyk'], ['★', 'gwiazdka'], ['♥', 'serce']];
    const EMOJI = [['😀', 'uśmiech'], ['😊', 'zadowolenie'], ['😉', 'mrugnięcie'], ['😍', 'zachwyt'], ['🤔', 'zamyślenie'], ['😢', 'smutek'], ['😮', 'zdziwienie'], ['🙏', 'dziękuję'],
        ['👍', 'kciuk w górę'], ['👏', 'brawa'], ['🙌', 'hura'], ['💪', 'siła'], ['🤝', 'uścisk dłoni'], ['👋', 'cześć'], ['❤️', 'czerwone serce'], ['💙', 'niebieskie serce'],
        ['🎉', 'gratulacje'], ['✨', 'iskry'], ['⭐', 'gwiazda'], ['🔥', 'ogień'], ['✅', 'zrobione'], ['❌', 'błąd'], ['⚠️', 'uwaga'], ['ℹ️', 'informacja'],
        ['📢', 'ogłoszenie'], ['📅', 'kalendarz'], ['⏰', 'budzik'], ['📍', 'miejsce'], ['📞', 'telefon'], ['✉️', 'koperta'], ['📄', 'dokument'], ['🔗', 'link'],
        ['💡', 'pomysł'], ['🎓', 'edukacja'], ['📚', 'książki'], ['💻', 'komputer'], ['♿', 'dostępność'], ['🦻', 'aparat słuchowy'], ['🦯', 'biała laska'], ['🌍', 'świat']];
    function openCharDialog(el, kind) {
        const sel = window.getSelection();
        const range = sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
        const n = ++dialogSeq;
        const set = kind === 'emoji' ? EMOJI : CHARS;
        const title = kind === 'emoji' ? 'Wstaw emoji' : 'Wstaw znak specjalny';
        const dlg = h('dialog', { class: 'be-dialog be-dialog--small', 'aria-labelledby': `be-char-title-${n}` });
        const restore = () => { el.focus(); if (range) { sel.removeAllRanges(); sel.addRange(range); } };
        const done = (ch) => {
            dlg.close(); dlg.remove(); restore();
            if (ch) { document.execCommand('insertText', false, ch); el.dispatchEvent(new Event('input', { bubbles: true })); announce('Wstawiono: ' + set.find(([c]) => c === ch)[1]); }
        };
        const grid = h('div', { class: 'be-char-grid', role: 'group', 'aria-label': title },
            ...set.map(([ch, name]) => h('button', { type: 'button', class: 'be-char', 'aria-label': name, title: name, onClick: () => done(ch) }, ch === '\u00a0' ? '⍽' : ch)));
        // Strzałki przesuwają fokus po siatce.
        grid.addEventListener('keydown', (e) => {
            const btns = [...grid.querySelectorAll('button')];
            const i = btns.indexOf(document.activeElement);
            const step = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: 8, ArrowUp: -8 }[e.key];
            if (i < 0 || !step) return;
            e.preventDefault();
            btns[Math.max(0, Math.min(btns.length - 1, i + step))].focus();
        });
        dlg.append(h('h2', { id: `be-char-title-${n}` }, title), grid,
            h('div', { class: 'be-dialog-actions' }, h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => done(null) }, 'Anuluj')));
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); done(null); });
        document.body.append(dlg);
        dlg.showModal();
        grid.querySelector('button').focus();
    }

    // Okno wyboru adresu dla pól inspektora (przyciski, odnośnik obrazu).
    // Zwraca wybrany adres albo null (anulowano). Fokus wraca do wywołującego.
    function openLinkPickerDialog(current, title = 'Wybierz stronę') {
        return new Promise((resolve) => {
            const n = ++dialogSeq;
            const picker = linkPicker({ value: current || '', id: `be-pick-url-${n}` });
            const error = h('p', { class: 'be-error', role: 'alert' });
            const dlg = h('dialog', { class: 'be-dialog be-dialog--small', 'aria-labelledby': `be-pick-title-${n}` });
            const done = (v) => { dlg.close(); dlg.remove(); resolve(v); };
            dlg.append(h('form', { onSubmit: (e) => {
                e.preventDefault();
                const v = picker.input.value.trim();
                if (!validHref(v)) { error.textContent = 'Wybierz stronę z listy albo podaj adres zaczynający się od https://, / , mailto: lub tel:'; picker.input.setAttribute('aria-invalid', 'true'); picker.input.focus(); return; }
                done(normalizeHref(v));
            } },
            h('h2', { id: `be-pick-title-${n}` }, title),
            picker.root,
            error,
            h('div', { class: 'be-dialog-actions' },
                h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => done(null) }, 'Anuluj'),
                h('button', { type: 'submit', class: 'be-btn be-btn--primary' }, 'Zastosuj'))));
            dlg.addEventListener('cancel', (e) => { e.preventDefault(); if (picker.escapedRecently()) return; done(null); });
            document.body.append(dlg);
            dlg.showModal();
            picker.input.focus();
            picker.input.select();
        });
    }

    // ── Tekst alternatywny po wstawieniu obrazu ────────────────────────────
    // Okno pojawia się od razu po przesłaniu / wybraniu obrazu. „Pomiń” zostawia
    // obraz bez opisu (uwaga dostępności pozostaje na liście). Zwraca
    // { alt, decorative }, null (pominięto) albo 'skip-all' (galeria).
    function promptAltText({ url, alt = '', decorative = false, allowDecorative = true, position = null }) {
        return new Promise((resolve) => {
            const n = ++dialogSeq;
            const ids = { title: `be-alt-title-${n}`, intro: `be-alt-intro-${n}`, ta: `be-alt-${n}`, help: `be-alt-${n}-help`, deco: `be-alt-deco-${n}` };
            const ta = h('textarea', { class: 'be-input', id: ids.ta, rows: '3', 'aria-describedby': ids.help });
            ta.value = alt || '';
            const deco = allowDecorative ? h('input', { type: 'checkbox', id: ids.deco, checked: decorative, 'aria-describedby': ids.deco + '-help' }) : null;
            const error = h('p', { class: 'be-error', role: 'alert' });
            const dlg = h('dialog', { class: 'be-dialog be-dialog--small', 'aria-labelledby': ids.title, 'aria-describedby': ids.intro });
            const done = (v) => { dlg.close(); dlg.remove(); resolve(v); };
            const sync = () => { if (deco) ta.disabled = deco.checked; };
            deco?.addEventListener('change', () => { sync(); error.textContent = ''; });
            dlg.append(h('form', { onSubmit: (e) => {
                e.preventDefault();
                const isDeco = !!deco?.checked;
                const v = ta.value.trim();
                if (!isDeco && !v) {
                    error.textContent = allowDecorative ? 'Wpisz opis, zaznacz „Obraz dekoracyjny” albo wybierz „Pomiń”.' : 'Wpisz opis albo wybierz „Pomiń”.';
                    ta.setAttribute('aria-invalid', 'true');
                    ta.focus();
                    return;
                }
                done({ alt: isDeco ? '' : v, decorative: isDeco });
            } },
            h('h2', { id: ids.title }, position ? `Opisz zdjęcie ${position[0]} z ${position[1]}` : 'Opisz obraz'),
            h('p', { id: ids.intro, class: 'be-help' }, 'Tekst alternatywny odczyta czytnik ekranu osobom, które nie widzą obrazu. Pojawi się też, gdy obraz się nie wczyta.'),
            h('img', { src: url, alt: '', class: 'be-alt-thumb' }),
            h('div', { class: 'be-field' },
                h('label', { for: ids.ta }, 'Tekst alternatywny'),
                ta,
                h('p', { id: ids.help, class: 'be-help' }, 'Opisz, co widać i dlaczego to ważne w tym miejscu — zwykle wystarczy jedno zdanie. Nie zaczynaj od „Zdjęcie…”. Tekst widoczny na grafice przepisz w całości.')),
            deco ? h('div', { class: 'be-check' }, deco, h('label', { for: ids.deco }, 'Obraz dekoracyjny'),
                h('p', { id: ids.deco + '-help', class: 'be-help' }, 'Zaznacz, jeśli obraz nic nie wnosi do treści (ozdobnik, tło) — czytnik ekranu go pominie.')) : null,
            error,
            h('div', { class: 'be-dialog-actions' },
                position && position[1] > 1 ? h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => done('skip-all') }, 'Pomiń wszystkie') : null,
                h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => done(null) }, 'Pomiń'),
                h('button', { type: 'submit', class: 'be-btn be-btn--primary' }, 'Zapisz opis'))));
            dlg.addEventListener('cancel', (e) => { e.preventDefault(); done(null); });
            document.body.append(dlg);
            dlg.showModal();
            sync();
            (ta.disabled ? deco : ta).focus();
        });
    }

    // Obraz w bloku „Obraz”: ustaw adres, zapytaj o opis, wróć fokusem do bloku.
    async function setImageWithAlt(b, url, { alt = '', decorative = false } = {}) {
        let cur = find(b.id)?.block;
        if (!cur) return;
        updateAttrs(cur, { url, alt: alt || '', decorative });
        const r = await promptAltText({ url, alt, decorative });
        cur = find(b.id)?.block;
        if (!cur) return;
        if (r && typeof r === 'object') { updateAttrs(cur, { alt: r.alt, decorative: r.decorative }); announce('Zapisano opis obrazu'); } else if (!alt && !decorative) announce('Pominięto opis obrazu — uwaga dostępności pozostaje na liście.');
        selectBlock(cur.id, { focusWrapper: true });
    }

    // Zdjęcia do galerii: opis każdego po kolei (z możliwością pominięcia wszystkich).
    async function addGalleryImages(b, images) {
        if (!images.length) return;
        const out = [];
        let skipAll = false;
        for (let i = 0; i < images.length; i++) {
            const img = { url: images[i].url, alt: images[i].alt || '', caption: '' };
            if (!skipAll) {
                const r = await promptAltText({ url: img.url, alt: img.alt, allowDecorative: false, position: [i + 1, images.length] });
                if (r === 'skip-all') skipAll = true;
                else if (r) img.alt = r.alt;
            }
            out.push(img);
        }
        const cur = find(b.id)?.block;
        if (!cur) return;
        updateAttrs(cur, { images: [...cur.attrs.images, ...out] });
        announce(`Dodano ${out.length} ${plural(out.length, 'zdjęcie', 'zdjęcia', 'zdjęć')} do galerii`);
        selectBlock(cur.id, { focusWrapper: true });
    }

    // ── Multimedia ─────────────────────────────────────────────────────────
    async function uploadFiles(files) {
        const out = [];
        for (const file of files) {
            const fd = new FormData();
            fd.append('file', file);
            announce('Przesyłanie: ' + file.name);
            try {
                const res = await fetch(config.urls.upload, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(json.errors?.file?.[0] || json.message || 'Nie udało się przesłać pliku.');
                out.push({ url: json.url, name: file.name, alt: '' });
                mediaCache = null;
            } catch (e) {
                S.error = e.message;
                updateTopbar();
            }
        }
        if (out.length) announce(`Przesłano ${out.length} ${plural(out.length, 'plik', 'pliki', 'plików')}`);
        return out;
    }

    let mediaCache = null;
    function openMediaLibrary({ multiple = false } = {}) {
        return new Promise((resolve) => {
            const chosen = new Map();
            const grid = h('div', { class: 'be-media-grid', role: 'list' }, h('p', { class: 'be-muted' }, 'Wczytywanie…'));
            const search = h('input', { type: 'search', class: 'be-input', placeholder: 'Szukaj po nazwie lub opisie', 'aria-label': 'Szukaj w bibliotece' });
            const fileInput = h('input', { type: 'file', accept: 'image/*', multiple, class: 'be-sr-only', tabindex: '-1', 'aria-hidden': 'true' });
            const count = h('span', { class: 'be-muted', 'aria-live': 'polite' });
            const dlg = h('dialog', { class: 'be-dialog', 'aria-labelledby': 'be-media-title' });
            const done = (result) => { dlg.close(); dlg.remove(); resolve(result); };
            let items = [];
            const draw = () => {
                const q = search.value.trim().toLowerCase();
                const list = items.filter((m) => !q || (m.file_name + ' ' + (m.alt || '')).toLowerCase().includes(q));
                grid.replaceChildren(...(list.length ? list.map((m) => h('div', { role: 'listitem' }, h('button', {
                    type: 'button', class: 'be-media-item', 'aria-pressed': String(chosen.has(m.url)),
                    onClick: () => {
                        if (!multiple) { done([{ url: m.url, alt: m.alt || '' }]); return; }
                        chosen.has(m.url) ? chosen.delete(m.url) : chosen.set(m.url, { url: m.url, alt: m.alt || '' });
                        draw();
                    },
                }, h('img', { src: m.url, alt: '', loading: 'lazy' }), h('span', {}, m.alt || m.file_name)))) : [h('p', { class: 'be-muted' }, 'Brak obrazów.')]));
                count.textContent = multiple ? `Wybrano: ${chosen.size}` : '';
            };
            fileInput.addEventListener('change', async () => {
                const up = await uploadFiles([...fileInput.files]);
                if (!multiple && up[0]) { done(up); return; }
                up.forEach((u) => chosen.set(u.url, u));
                items = [...up.map((u) => ({ url: u.url, file_name: u.name, alt: '' })), ...items];
                draw();
            });
            search.addEventListener('input', draw);
            dlg.append(
                h('div', { class: 'be-dialog-head' },
                    h('h2', { id: 'be-media-title' }, 'Biblioteka mediów'),
                    h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': 'Zamknij', onClick: () => done([]) }, icon('xmark'))),
                h('div', { class: 'be-row' }, search, h('button', { type: 'button', class: 'be-btn be-btn--secondary', onClick: () => fileInput.click() }, icon('upload'), ' Prześlij'), fileInput),
                grid,
                multiple ? h('div', { class: 'be-dialog-actions' }, count,
                    h('button', { type: 'button', class: 'be-btn be-btn--ghost', onClick: () => done([]) }, 'Anuluj'),
                    h('button', { type: 'button', class: 'be-btn be-btn--primary', onClick: () => done([...chosen.values()]) }, 'Wstaw wybrane')) : null);
            dlg.addEventListener('cancel', (e) => { e.preventDefault(); done([]); });
            document.body.append(dlg);
            dlg.showModal();
            search.focus();
            (mediaCache ||= fetch(config.urls.images, { headers: { Accept: 'application/json' } }).then((r) => (r.ok ? r.json() : [])).catch(() => []))
                .then((data) => { items = data; draw(); });
        });
    }

    // ── Skróty klawiszowe ──────────────────────────────────────────────────
    function openShortcuts() {
        const rows = [
            [mod + '+S', 'Zapisz'], [mod + '+Z', 'Cofnij'], [mod + '+Shift+Z', 'Ponów'],
            ['/', 'W pustym akapicie: wybór bloku'], ['## , - , 1. , >', 'W pustym akapicie: nagłówek, lista, lista numerowana, cytat'],
            [mod + '+B / I / K', 'Pogrubienie, kursywa, odnośnik'], ['Shift+Enter', 'Nowa linia w tym samym bloku'],
            ['Esc', 'Tryb nawigacji (strzałki — wybór bloku, Enter — edycja, Delete — usuń)'],
            ['Alt+F10', 'Przejdź do paska narzędzi bloku'], ['Alt+Shift+↑ / ↓', 'Przenieś blok w górę / w dół'],
            [mod + '+Shift+D', 'Duplikuj blok'], ['Shift+Alt+Z', 'Usuń blok'],
        ];
        const dlg = h('dialog', { class: 'be-dialog be-dialog--small', 'aria-labelledby': 'be-keys-title' },
            h('div', { class: 'be-dialog-head' }, h('h2', { id: 'be-keys-title' }, 'Skróty klawiszowe'), h('button', { type: 'button', class: 'be-btn be-btn--icon', 'aria-label': 'Zamknij', onClick: () => { dlg.close(); dlg.remove(); } }, icon('xmark'))),
            h('dl', { class: 'be-keys' }, ...rows.flatMap(([k, d]) => [h('dt', {}, h('kbd', {}, k)), h('dd', {}, d)])));
        dlg.addEventListener('close', () => dlg.remove());
        document.body.append(dlg);
        dlg.showModal();
    }

    document.addEventListener('keydown', (e) => {
        if (document.querySelector('dialog[open]')) return;
        const modKey = e.ctrlKey || e.metaKey;
        const k = e.key.toLowerCase();
        const inField = e.target.matches?.('input, textarea, select');
        if (modKey && k === 's') { e.preventDefault(); save(); return; }
        if (modKey && k === 'z' && !inField) { e.preventDefault(); e.shiftKey ? redo() : undo(); return; }
        if (modKey && k === 'y' && !inField) { e.preventDefault(); redo(); return; }
        if (!S.selectedId) return;
        if (e.altKey && e.shiftKey && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) { e.preventDefault(); moveBlock(S.selectedId, e.key === 'ArrowUp' ? -1 : 1); return; }
        if (modKey && e.shiftKey && k === 'd') { e.preventDefault(); duplicateBlock(S.selectedId); return; }
        if (e.altKey && e.shiftKey && (k === 'z' || e.code === 'KeyZ')) { e.preventDefault(); removeBlock(S.selectedId); return; }
        if (e.altKey && e.key === 'F10') { e.preventDefault(); toolbar.querySelector('button')?.focus(); }
    });
    // Strzałki w pasku narzędzi (roving).
    toolbar.addEventListener('keydown', (e) => {
        if (!['ArrowLeft', 'ArrowRight'].includes(e.key) || e.target.closest('.be-menu')) return;
        const all = [...toolbar.querySelectorAll('.be-tool:not(.be-tool--static):not(.be-drag)')].filter((b) => !b.closest('.be-menu'));
        const i = all.indexOf(e.target);
        if (i < 0) return;
        e.preventDefault();
        all[(i + (e.key === 'ArrowRight' ? 1 : -1) + all.length) % all.length].focus();
    });
    toolbar.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !e.target.closest('.be-menu')) { e.preventDefault(); e.stopPropagation(); const w = wrapperOf(S.selectedId); const ed = w && editablesIn(w)[0]; ed ? placeCaret(ed, false) : w?.focus(); }
    });

    // ── Zapis ──────────────────────────────────────────────────────────────
    async function save({ draft = false } = {}) {
        if (S.saving) return;
        if (!S.title.trim()) { S.error = L.titleRequired; updateTopbar(); titleEl.focus(); return; }
        S.saving = true;
        S.error = null;
        updateTopbar();
        try {
            const res = await fetch(config.urls.save, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    title: S.title.trim(), meta_description: S.meta, blocks: serialize(S.blocks),
                    ...(typeForm ? { fields: typeFieldsData() } : {}),
                    ...(S.settings && (S.settingsDirty || draft) ? { settings: { ...S.settings, draft } } : {}),
                }),
            });
            const json = await res.json().catch(() => ({}));
            if (res.status === 419) throw new Error('Sesja wygasła. Skopiuj zmiany i odśwież stronę.');
            if (!res.ok) throw new Error(Object.values(json.errors || {})[0]?.[0] || json.message || 'Nie udało się zapisać zmian.');
            await saveNewsSettings();
            S.dirty = false;
            if (json.settings) {
                const wasRequested = S.settings?.is_published && !draft;
                S.settings = json.settings;
                S.settingsDirty = false;
                config.page.is_published = json.settings.is_published;
                if (wasRequested && json.settings.pending_approval) announce('Zapisano. Publikacja czeka na zatwierdzenie.');
                if (S.sidebarTab === 'page') renderSidebar();
            }
            S.savedAt = json.saved_at || new Date().toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
            clearDraft();
            announce('Zapisano zmiany');
        } catch (e) {
            S.error = e.message || 'Nie udało się zapisać zmian.';
        } finally {
            S.saving = false;
            updateTopbar();
        }
    }
    window.addEventListener('beforeunload', (e) => { if (S.dirty) { e.preventDefault(); e.returnValue = ''; } });

    // ── Start ──────────────────────────────────────────────────────────────
    if (!S.blocks.length) S.blocks.push(block('paragraph'));
    let draftOffer = readDraft();
    renderAll();
    if (!S.title.trim()) titleEl.focus();
}
