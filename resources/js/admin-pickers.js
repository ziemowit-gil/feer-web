// Selektory dla formularzy panelu — dołączane do zwykłych pól tekstowych:
//
//   <input data-icon-picker data-icon-format="name|class">   ikona Bootstrap Icons
//   <input data-color-picker>                                  kolor (hex) z paletą marki
//
// Pole zostaje zwykłym <input> (name, value, x-model działają jak dotąd) —
// selektor tylko ustawia wartość i wysyła zdarzenia input/change. Działa też
// dla wierszy dodawanych dynamicznie (repeatery) dzięki MutationObserver.
//
// Format ikony: 'name' → "bi-house" (pola, które same dopisują prefiks „bi"),
// 'class' → "bi bi-house" (pola, które wstawiają całą klasę <i class="…">).

const ICONS_URL = document.querySelector('meta[name="admin-icons-url"]')?.content || '';
const MATERIAL_URL = document.querySelector('meta[name="admin-material-icons-url"]')?.content || '';

let iconsPromise = null;
function loadIcons() {
    if (! iconsPromise) {
        iconsPromise = fetch(ICONS_URL, { headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status))))
            .catch((e) => { iconsPromise = null; throw e; });
    }
    return iconsPromise;
}

let materialPromise = null;
function loadMaterial() {
    if (! materialPromise) {
        materialPromise = fetch(MATERIAL_URL, { headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status))))
            .catch((e) => { materialPromise = null; throw e; });
    }
    return materialPromise;
}

// Polecane ikony (Bootstrap Icons) — pokazywane na początku, zanim ktoś zacznie szukać.
const RECOMMENDED = [
    'house-door', 'info-circle', 'people', 'person', 'envelope', 'telephone', 'geo-alt', 'calendar-event',
    'newspaper', 'journal-text', 'file-earmark-text', 'folder2-open', 'book', 'mortarboard', 'briefcase', 'heart',
    'hand-thumbs-up', 'gift', 'cart', 'question-circle', 'chat-dots', 'megaphone', 'universal-access', 'eye',
    'ear', 'building', 'bank', 'globe', 'camera', 'play-circle', 'mic', 'download', 'shield-check', 'star',
    'lightbulb', 'tree', 'hand-index', 'search', 'gear', 'box-arrow-in-right',
];

// Szukanie po polsku: polskie słowo → fragmenty angielskich nazw ikon.
const PL_KEYWORDS = {
    dom: ['house', 'home'], start: ['house', 'home'], glowna: ['house', 'home'], 'o nas': ['info', 'people'], info: ['info'], informacja: ['info'],
    ludzie: ['people', 'person', 'group'], osoba: ['person'], zespol: ['people', 'group'], uzytkownik: ['person'], konto: ['person', 'account'],
    poczta: ['envelope', 'mail'], mail: ['envelope', 'mail'], koperta: ['envelope', 'mail'], kontakt: ['envelope', 'telephone', 'phone', 'contact'],
    telefon: ['telephone', 'phone'], adres: ['geo', 'pin', 'location'], mapa: ['map', 'geo'], miejsce: ['geo', 'pin', 'location'],
    kalendarz: ['calendar'], wydarzenie: ['calendar', 'event'], wydarzenia: ['calendar', 'event'], szkolenie: ['mortarboard', 'school', 'easel'], szkolenia: ['mortarboard', 'school', 'easel'],
    aktualnosci: ['newspaper', 'news'], wiadomosci: ['newspaper', 'chat', 'news'], blog: ['journal', 'pen', 'article'], artykul: ['journal', 'article', 'file'],
    dokument: ['file', 'description', 'article'], dokumenty: ['file', 'folder', 'description'], plik: ['file'], pliki: ['file', 'folder'], folder: ['folder'], pobierz: ['download'],
    ksiazka: ['book'], edukacja: ['mortarboard', 'book', 'school'], nauka: ['mortarboard', 'book', 'school'], praca: ['briefcase', 'work'], oferta: ['briefcase', 'tag'],
    serce: ['heart', 'favorite'], wsparcie: ['heart', 'hand', 'volunteer'], darowizna: ['heart', 'gift', 'cash', 'volunteer'], pomoc: ['question', 'help', 'life-preserver', 'support'],
    wolontariat: ['hand', 'heart', 'volunteer'], prezent: ['gift'], sklep: ['cart', 'bag', 'shop', 'store'], koszyk: ['cart', 'shopping'],
    pytanie: ['question', 'help'], faq: ['question', 'help'], czat: ['chat'], rozmowa: ['chat', 'forum'], ogloszenie: ['megaphone', 'campaign'],
    dostepnosc: ['universal-access', 'accessibility', 'eye', 'ear'], wzrok: ['eye', 'visibility'], sluch: ['ear', 'hearing'], niewidomi: ['eye', 'braille'], gluchy: ['ear', 'hearing', 'sign'],
    budynek: ['building', 'bank', 'business'], urzad: ['bank', 'building'], bip: ['bank', 'building', 'account_balance'], swiat: ['globe', 'public', 'language'], jezyk: ['translate', 'globe', 'language'],
    zdjecie: ['camera', 'image', 'photo'], galeria: ['images', 'image', 'photo'], film: ['play', 'film', 'video', 'camera-video'], wideo: ['play', 'film', 'video', 'camera-video'], podcast: ['mic', 'headphones', 'podcast'],
    bezpieczenstwo: ['shield', 'lock', 'security'], rodo: ['shield', 'lock', 'privacy'], gwiazda: ['star'], pomysl: ['lightbulb'], projekt: ['kanban', 'diagram', 'lightbulb'], dzialania: ['kanban', 'diagram', 'grid'],
    natura: ['tree', 'flower', 'park'], szukaj: ['search'], ustawienia: ['gear', 'settings'], logowanie: ['box-arrow-in', 'login', 'person'], partner: ['handshake', 'people'], wspolpraca: ['handshake', 'people'],
};

function normalizePl(s) {
    return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ł/g, 'l').trim();
}

// Fragmenty nazw do szukania: wpisane słowo oraz jego angielskie odpowiedniki.
function searchTerms(q) {
    const n = normalizePl(q);
    const terms = new Set([n]);
    for (const [pl, en] of Object.entries(PL_KEYWORDS)) {
        if (n.length >= 2 && (pl.startsWith(n) || n.startsWith(pl))) en.forEach((t) => terms.add(t));
    }
    return [...terms].filter(Boolean);
}

// Dopasowanie po słowach nazwy („ear” nie trafia w „heart”); najpierw nazwy zaczynające się od frazy.
function matchIcons(icons, q) {
    const terms = searchTerms(q);
    const score = (n) => {
        const parts = n.split(/[-_]/);
        let best = 99;
        for (const t of terms) {
            if (n === t) return 0;
            if (n.startsWith(t)) best = Math.min(best, 1);
            else if (parts.some((p) => p.startsWith(t))) best = Math.min(best, 2);
            else if (t.includes('-') && n.includes(t)) best = Math.min(best, 3);
        }
        return best;
    };
    return icons.map((n) => [n, score(n)]).filter(([, sc]) => sc < 99)
        .sort((a, b) => a[1] - b[1] || a[0].length - b[0].length).map(([n]) => n);
}

function brandColors() {
    try { return JSON.parse(document.querySelector('meta[name="admin-brand-colors"]')?.content || '[]'); }
    catch (e) { return []; }
}

const NEUTRAL_COLORS = [
    { hex: '#1a1a1a', label: 'Tekst (czerń)' },
    { hex: '#4b5563', label: 'Szary' },
    { hex: '#ffffff', label: 'Biały' },
];

function setValue(input, value) {
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function iconClassFor(value) {
    const v = (value || '').trim();
    if (! v) return '';
    if (/(^|\s)fa[srlb]?(-|\s)/.test(v) || v.includes('fa-solid') || v.includes('fa-regular') || v.includes('fa-brands')) return v;
    if (/^mi-[a-z0-9_]+$/.test(v)) return v;
    if (v.startsWith('bi ')) return v;
    if (v.startsWith('bi-')) return 'bi ' + v;
    return 'bi bi-' + v;
}

function el(tag, attrs = {}, children = []) {
    const node = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
        if (k === 'class') node.className = v;
        else if (k === 'text') node.textContent = v;
        else if (k === 'html') node.innerHTML = v;
        else if (v !== null && v !== undefined) node.setAttribute(k, v);
    }
    for (const c of children) node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    return node;
}

// ── Wspólny popover (pozycjonowany względem okna, nie ucina go overflow rodzica) ──
function createPopover(button, label, onClose) {
    const pop = el('div', { class: 'picker-popover', role: 'dialog', 'aria-label': label, hidden: '' });
    document.body.appendChild(pop);

    const place = () => {
        const r = button.getBoundingClientRect();
        const width = Math.min(400, window.innerWidth - 16);
        const left = Math.max(8, Math.min(r.left, window.innerWidth - width - 8));
        const below = window.innerHeight - r.bottom;
        pop.style.left = left + 'px';
        pop.style.width = width + 'px';
        if (below < 320 && r.top > below) {
            pop.style.top = '';
            pop.style.bottom = (window.innerHeight - r.top + 6) + 'px';
            pop.style.maxHeight = (r.top - 16) + 'px';
        } else {
            pop.style.bottom = '';
            pop.style.top = (r.bottom + 6) + 'px';
            pop.style.maxHeight = (below - 16) + 'px';
        }
    };

    let outsideHandler, keyHandler;
    const api = {
        el: pop,
        isOpen: () => ! pop.hidden,
        open() {
            pop.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            place();
            outsideHandler = (e) => { if (! pop.contains(e.target) && e.target !== button && ! button.contains(e.target)) api.close(false); };
            keyHandler = (e) => { if (e.key === 'Escape') { e.stopPropagation(); api.close(true); } };
            setTimeout(() => document.addEventListener('mousedown', outsideHandler), 0);
            document.addEventListener('keydown', keyHandler, true);
            window.addEventListener('scroll', place, true);
            window.addEventListener('resize', place);
        },
        close(returnFocus = true) {
            if (pop.hidden) return;
            pop.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            document.removeEventListener('mousedown', outsideHandler);
            document.removeEventListener('keydown', keyHandler, true);
            window.removeEventListener('scroll', place, true);
            window.removeEventListener('resize', place);
            if (returnFocus) button.focus();
            onClose?.();
        },
        destroy() { api.close(false); pop.remove(); },
    };
    return api;
}

function wrapInput(input) {
    const wrap = el('div', { class: 'picker-field' });
    input.replaceWith(wrap);
    wrap.appendChild(input);
    input.classList.add('picker-input');
    return wrap;
}

// ── Ikony ────────────────────────────────────────────────────────
function enhanceIcon(input) {
    if (input.dataset.pickerReady) return;
    input.dataset.pickerReady = '1';
    const format = input.dataset.iconFormat === 'class' ? 'class' : 'name';

    const wrap = wrapInput(input);
    const preview = el('span', { class: 'picker-preview', 'aria-hidden': 'true' });
    const button = el('button', { type: 'button', class: 'picker-button picker-button-text', 'aria-haspopup': 'dialog', 'aria-expanded': 'false', title: 'Wybierz ikonę z listy' },
        [el('i', { class: 'bi bi-grid-3x3-gap', 'aria-hidden': 'true' }), el('span', { text: 'Wybierz' }), el('span', { class: 'sr-only', text: ' ikonę' })]);
    wrap.prepend(preview);
    wrap.appendChild(button);

    const refresh = () => {
        const cls = iconClassFor(input.value);
        preview.innerHTML = cls.startsWith('mi-')
            ? '<span class="material-symbols-outlined mi-glyph">' + cls.slice(3) + '</span>'
            : (cls ? '<i class="' + cls.replace(/"/g, '') + '"></i>' : '<i class="bi bi-image-alt picker-preview-empty"></i>');
    };
    input.addEventListener('input', refresh);
    refresh();

    let pop = null, search = null, grid = null, hint = null, icons = [], source = 'bi', tabs = {};

    const render = () => {
        const q = search.value.trim().toLowerCase().replace(/^(bi|mi)[ -]/, '');
        let found;
        if (q) {
            found = matchIcons(icons, q);
        } else if (source === 'bi') {
            // Bez frazy: najpierw polecane, potem reszta.
            const rec = RECOMMENDED.filter((n) => icons.includes(n));
            found = rec.concat(icons.filter((n) => ! rec.includes(n)));
        } else {
            found = icons;
        }
        const shown = found.slice(0, 160);
        grid.innerHTML = '';
        for (const name of shown) {
            const glyph = source === 'mi'
                ? el('span', { class: 'material-symbols-outlined mi-glyph', 'aria-hidden': 'true', text: name })
                : el('i', { class: 'bi bi-' + name, 'aria-hidden': 'true' });
            const b = el('button', { type: 'button', class: 'picker-icon', 'aria-label': name.replace(/_/g, ' '), title: name }, [glyph]);
            b.addEventListener('click', () => choose(name));
            grid.appendChild(b);
        }
        hint.textContent = found.length === 0
            ? 'Brak ikon dla „' + search.value.trim() + '".'
            : (found.length > shown.length ? 'Pokazano ' + shown.length + ' z ' + found.length + ' — wpisz więcej liter, aby zawęzić.' : found.length + ' ikon');
    };

    const choose = (name) => {
        setValue(input, source === 'mi' ? 'mi-' + name : (format === 'class' ? 'bi bi-' + name : 'bi-' + name));
        pop.close(true);
    };

    const switchSource = async (key) => {
        source = key;
        for (const [k, t] of Object.entries(tabs)) t.setAttribute('aria-pressed', k === key ? 'true' : 'false');
        hint.textContent = 'Ładowanie listy ikon…';
        try {
            icons = key === 'mi' ? await loadMaterial() : await loadIcons();
            render();
        } catch (e) {
            hint.textContent = 'Nie udało się pobrać listy ikon. Wpisz nazwę ręcznie (np. ' + (key === 'mi' ? 'mi-home' : 'bi-house') + ').';
        }
        search.focus();
    };

    const build = () => {
        pop = createPopover(button, 'Wybierz ikonę');
        search = el('input', { type: 'search', class: 'picker-search', placeholder: 'Szukaj po polsku lub angielsku, np. dom, koperta, telefon…', 'aria-label': 'Szukaj ikony', autocomplete: 'off' });
        hint = el('p', { class: 'picker-hint', 'aria-live': 'polite' });
        grid = el('div', { class: 'picker-grid', role: 'group', 'aria-label': 'Ikony' });
        const clear = el('button', { type: 'button', class: 'picker-clear', text: 'Bez ikony' });
        clear.addEventListener('click', () => { setValue(input, ''); pop.close(true); });
        const foot = el('div', { class: 'picker-foot' }, [
            clear,
            el('a', { href: 'https://icons.getbootstrap.com/', target: '_blank', rel: 'noopener', class: 'picker-link', text: 'Pełna lista Bootstrap Icons' }),
        ]);
        const tabBar = el('div', { class: 'picker-tabs', role: 'group', 'aria-label': 'Źródło ikon' });
        for (const [key, label] of [['bi', 'Bootstrap Icons'], ['mi', 'Material Symbols']]) {
            const t = el('button', { type: 'button', class: 'picker-tab', 'aria-pressed': key === source ? 'true' : 'false', text: label });
            t.addEventListener('click', () => switchSource(key));
            tabs[key] = t;
            tabBar.appendChild(t);
        }
        pop.el.append(tabBar, search, hint, grid, foot);
        search.addEventListener('input', render);
        search.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); grid.querySelector('.picker-icon')?.click(); }
            if (e.key === 'ArrowDown') { e.preventDefault(); grid.querySelector('.picker-icon')?.focus(); }
        });
        grid.addEventListener('keydown', (e) => {
            const items = [...grid.querySelectorAll('.picker-icon')];
            const i = items.indexOf(document.activeElement);
            if (i < 0) return;
            const cols = Math.max(1, Math.floor(grid.clientWidth / 40));
            const map = { ArrowRight: i + 1, ArrowLeft: i - 1, ArrowDown: i + cols, ArrowUp: i - cols, Home: 0, End: items.length - 1 };
            if (e.key in map) { e.preventDefault(); items[Math.max(0, Math.min(items.length - 1, map[e.key]))]?.focus(); }
        });
    };

    button.addEventListener('click', async () => {
        if (! pop) build();
        if (pop.isOpen()) { pop.close(true); return; }
        pop.open();
        hint.textContent = 'Ładowanie listy ikon…';
        search.focus();
        try {
            const isMi = iconClassFor(input.value).startsWith('mi-');
            source = isMi ? 'mi' : 'bi';
            for (const [k, t] of Object.entries(tabs)) t.setAttribute('aria-pressed', k === source ? 'true' : 'false');
            icons = isMi ? await loadMaterial() : await loadIcons();
            const current = iconClassFor(input.value).replace(/^bi bi-/, '').replace(/^mi-/, '');
            search.value = current && ! current.startsWith('fa') ? current : '';
            render();
        } catch (e) {
            hint.textContent = 'Nie udało się pobrać listy ikon. Wpisz nazwę ręcznie (np. bi-house).';
        }
    });
}

// ── Kolory ───────────────────────────────────────────────────────
function enhanceColor(input) {
    if (input.dataset.pickerReady) return;
    input.dataset.pickerReady = '1';

    const wrap = wrapInput(input);
    const preview = el('span', { class: 'picker-preview picker-swatch', 'aria-hidden': 'true' });
    const button = el('button', { type: 'button', class: 'picker-button', 'aria-haspopup': 'dialog', 'aria-expanded': 'false', title: 'Wybierz kolor (kolory marki na początku)' },
        [el('i', { class: 'bi bi-palette', 'aria-hidden': 'true' }), ' ', el('span', { class: 'sr-only', text: 'Wybierz kolor' })]);
    wrap.prepend(preview);
    wrap.appendChild(button);

    const refresh = () => {
        const v = input.value.trim();
        preview.style.background = /^#[0-9a-f]{6}$/i.test(v) ? v : 'transparent';
        preview.classList.toggle('picker-swatch-empty', ! /^#[0-9a-f]{6}$/i.test(v));
    };
    input.addEventListener('input', refresh);
    refresh();

    let pop = null;
    const swatchGroup = (title, colors) => {
        const group = el('div', { class: 'picker-colors', role: 'group', 'aria-label': title });
        group.appendChild(el('p', { class: 'picker-colors-title', text: title }));
        const row = el('div', { class: 'picker-colors-row' });
        for (const c of colors) {
            const b = el('button', { type: 'button', class: 'picker-color', 'aria-label': c.label + ' ' + c.hex, title: c.label + ' — ' + c.hex });
            b.style.background = c.hex;
            if (c.hex.toLowerCase() === '#ffffff') b.classList.add('picker-color-light');
            b.addEventListener('click', () => { setValue(input, c.hex.toLowerCase()); pop.close(true); });
            row.appendChild(b);
        }
        group.appendChild(row);
        return group;
    };

    const build = () => {
        pop = createPopover(button, 'Wybierz kolor');
        const brand = brandColors();
        if (brand.length) pop.el.appendChild(swatchGroup('Kolory marki (zalecane)', brand));
        pop.el.appendChild(swatchGroup('Neutralne', NEUTRAL_COLORS));
        const custom = el('input', { type: 'color', class: 'picker-native', 'aria-label': 'Własny kolor' });
        custom.value = /^#[0-9a-f]{6}$/i.test(input.value) ? input.value : (brand[0]?.hex || '#1e6dff');
        custom.addEventListener('input', () => setValue(input, custom.value));
        const clear = el('button', { type: 'button', class: 'picker-clear', text: 'Wyczyść' });
        clear.addEventListener('click', () => { setValue(input, ''); pop.close(true); });
        pop.el.appendChild(el('div', { class: 'picker-foot' }, [
            el('label', { class: 'picker-custom' }, [custom, ' Własny kolor']),
            clear,
        ]));
        pop.el.appendChild(el('p', { class: 'picker-hint', text: 'Kolory marki zapewniają spójność i sprawdzony kontrast; własne sprawdź pod kątem WCAG (min. 4,5:1 dla tekstu).' }));
    };

    button.addEventListener('click', () => {
        if (! pop) build();
        pop.isOpen() ? pop.close(true) : (pop.open(), pop.el.querySelector('.picker-color')?.focus());
    });
}

// ── Obraz z biblioteki (pola z adresem URL obrazka) ─────────────────
//   <input data-library-url data-library-alt="nazwa_pola_alt">
// Wstawia adres wybranego obrazu z Multimediów; opis alternatywny trafia do pola alt (jeśli jest i jest puste).
const LIBRARY_URL = document.querySelector('meta[name="admin-library-images-url"]')?.content || '';
let libraryPromise = null;
function loadLibrary() {
    if (! libraryPromise) {
        libraryPromise = fetch(LIBRARY_URL, { headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status))))
            .catch((e) => { libraryPromise = null; throw e; });
    }
    return libraryPromise;
}

function enhanceLibraryUrl(input) {
    if (input.dataset.pickerReady || ! LIBRARY_URL) return;
    input.dataset.pickerReady = '1';
    const wrap = wrapInput(input);
    const button = el('button', { type: 'button', class: 'picker-button picker-button-text', 'aria-haspopup': 'dialog', 'aria-expanded': 'false', title: 'Wybierz obraz z biblioteki plików' },
        [el('i', { class: 'bi bi-images', 'aria-hidden': 'true' }), el('span', { text: 'Z biblioteki' })]);
    wrap.appendChild(button);

    let pop = null, search = null, grid = null, hint = null, items = [];
    const altField = () => {
        const name = input.dataset.libraryAlt;
        return name ? (input.form || document).querySelector('[name="' + CSS.escape(name) + '"]') : null;
    };
    const render = () => {
        const q = normalizePl(search.value);
        const found = q ? items.filter((i) => normalizePl(i.file_name + ' ' + (i.alt || '')).includes(q)) : items;
        grid.innerHTML = '';
        for (const item of found.slice(0, 120)) {
            const b = el('button', { type: 'button', class: 'picker-thumb', 'aria-label': item.alt || item.file_name, title: item.file_name },
                [el('img', { src: item.url, alt: '', loading: 'lazy' })]);
            b.addEventListener('click', () => {
                setValue(input, item.url);
                const alt = altField();
                if (alt && ! alt.value.trim() && item.alt) setValue(alt, item.alt);
                pop.close(true);
            });
            grid.appendChild(b);
        }
        hint.textContent = found.length ? found.length + ' obrazów' : 'Brak obrazów' + (q ? ' dla „' + search.value.trim() + '”.' : ' w bibliotece.');
    };
    const build = () => {
        pop = createPopover(button, 'Wybierz obraz z biblioteki');
        search = el('input', { type: 'search', class: 'picker-search', placeholder: 'Szukaj po nazwie pliku lub opisie…', 'aria-label': 'Szukaj obrazu', autocomplete: 'off' });
        hint = el('p', { class: 'picker-hint', 'aria-live': 'polite' });
        grid = el('div', { class: 'picker-grid picker-grid-thumbs', role: 'group', 'aria-label': 'Obrazy' });
        pop.el.append(search, hint, grid);
        search.addEventListener('input', render);
    };
    button.addEventListener('click', async () => {
        if (! pop) build();
        if (pop.isOpen()) { pop.close(true); return; }
        pop.open();
        search.focus();
        hint.textContent = 'Ładowanie biblioteki…';
        try { items = await loadLibrary(); render(); }
        catch (e) { hint.textContent = 'Nie udało się wczytać biblioteki. Wgraj plik albo wklej adres.'; }
    });
}

// ── Start + obserwacja nowych pól ────────────────────────────────
function scan(root) {
    if (! root.querySelectorAll) return;
    root.querySelectorAll('input[data-icon-picker]').forEach(enhanceIcon);
    root.querySelectorAll('input[data-color-picker]').forEach(enhanceColor);
    root.querySelectorAll('input[data-library-url]').forEach(enhanceLibraryUrl);
}

scan(document);
new MutationObserver((mutations) => {
    for (const m of mutations) {
        for (const n of m.addedNodes) {
            if (n.nodeType !== 1) continue;
            if (n.matches('input[data-icon-picker]')) enhanceIcon(n);
            else if (n.matches('input[data-color-picker]')) enhanceColor(n);
            else if (n.matches('input[data-library-url]')) enhanceLibraryUrl(n);
            else scan(n);
        }
    }
}).observe(document.body, { childList: true, subtree: true });
