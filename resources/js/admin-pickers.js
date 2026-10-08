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
    const button = el('button', { type: 'button', class: 'picker-button', 'aria-haspopup': 'dialog', 'aria-expanded': 'false', title: 'Wybierz ikonę z biblioteki Bootstrap Icons' },
        [el('i', { class: 'bi bi-grid-3x3-gap', 'aria-hidden': 'true' }), ' ', el('span', { class: 'sr-only', text: 'Wybierz ikonę' })]);
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
        const found = q ? icons.filter((n) => n.includes(q)) : icons;
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
        search = el('input', { type: 'search', class: 'picker-search', placeholder: 'Szukaj ikony, np. house, envelope…', 'aria-label': 'Szukaj ikony', autocomplete: 'off' });
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
        custom.value = /^#[0-9a-f]{6}$/i.test(input.value) ? input.value : (brand[0]?.hex || '#c31432');
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

// ── Start + obserwacja nowych pól ────────────────────────────────
function scan(root) {
    if (! root.querySelectorAll) return;
    root.querySelectorAll('input[data-icon-picker]').forEach(enhanceIcon);
    root.querySelectorAll('input[data-color-picker]').forEach(enhanceColor);
}

scan(document);
new MutationObserver((mutations) => {
    for (const m of mutations) {
        for (const n of m.addedNodes) {
            if (n.nodeType !== 1) continue;
            if (n.matches('input[data-icon-picker]')) enhanceIcon(n);
            else if (n.matches('input[data-color-picker]')) enhanceColor(n);
            else scan(n);
        }
    }
}).observe(document.body, { childList: true, subtree: true });
