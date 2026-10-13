// Formularze panelu — wspólne zachowania dla wszystkich formularzy edycji:
//
//  • ostrzeżenie przy wyjściu ze strony z niezapisanymi zmianami (beforeunload),
//  • Ctrl/⌘+S zapisuje bieżący formularz (przycisk „Zapisz” zostający w edytorze, jeśli jest).
//
// Formularz edycji = <form method="post"> w <main>, który nie służy tylko do usuwania
// (_method=DELETE) i nie ma atrybutu data-no-dirty. Formularze z własną obsługą
// (ustawienia, kreator formularzy) oznaczamy data-no-dirty.

const isEditForm = (form) => form instanceof HTMLFormElement
    && form.closest('main')
    && (form.getAttribute('method') || 'get').toLowerCase() === 'post'
    && ! form.hasAttribute('data-no-dirty')
    && ! form.closest('[data-no-dirty]')
    && form.querySelector('input[name="_method"][value="DELETE"]') === null
    // Pomijamy małe formularze-akcje (np. sam przycisk „Przesuń wyżej”).
    && form.querySelectorAll('input:not([type=hidden]), textarea, select, [contenteditable="true"]').length > 0;

const dirtyForms = new Set();

const formOf = (el) => (el instanceof Element ? (el.form || el.closest('form')) : null);

document.addEventListener('input', (e) => {
    const form = formOf(e.target);
    if (! form || ! isEditForm(form)) return;
    // Pola pomocnicze (wyszukiwarki w oknach, filtry) mogą się wyłączyć z liczenia.
    if (e.target.closest?.('[data-no-dirty]') || e.target.type === 'search') return;
    dirtyForms.add(form);
}, true);

document.addEventListener('change', (e) => {
    const form = formOf(e.target);
    if (form && isEditForm(form) && ! e.target.closest?.('[data-no-dirty]') && e.target.type !== 'search') dirtyForms.add(form);
}, true);

document.addEventListener('submit', (e) => {
    // Zapis (także przez inny formularz wskazujący ten sam) — zmiany nie przepadną.
    dirtyForms.delete(e.target);
    if (e.submitter?.form) dirtyForms.delete(e.submitter.form);
});

window.addEventListener('beforeunload', (e) => {
    for (const form of dirtyForms) {
        if (document.body.contains(form)) {
            e.preventDefault();
            e.returnValue = '';
            return;
        }
    }
});

// Ctrl/⌘+S: zapis formularza, w którym jest kursor, albo jedynego formularza edycji na stronie.
document.addEventListener('keydown', (e) => {
    if (! (e.ctrlKey || e.metaKey) || e.altKey || e.key.toLowerCase() !== 's') return;
    let form = formOf(document.activeElement);
    if (! form || ! isEditForm(form)) {
        const candidates = [...document.querySelectorAll('main form')].filter(isEditForm);
        form = candidates.find((f) => dirtyForms.has(f)) || (candidates.length === 1 ? candidates[0] : null);
    }
    if (! form) return;
    e.preventDefault();
    // „Zapisz” (zostaje w edytorze) ma pierwszeństwo przed „Zapisz i zamknij”; nigdy wersja robocza.
    const all = [...document.querySelectorAll('button[type=submit], input[type=submit]')]
        .filter((b) => b.form === form && ! b.disabled && b.name !== 'draft');
    const button = all.find((b) => b.name === 'stay') || all[0] || null;
    button ? form.requestSubmit(button) : form.requestSubmit();
});

// ── Objaśnienia pod „?” ─────────────────────────────────────────────
// Dłuższe podpowiedzi pod polami (<p class="text-xs text-muted">) są zwinięte; przy etykiecie pola
// pojawia się przycisk „?”, który je rozwija w tym samym miejscu. Podpowiedź zostaje w DOM-ie,
// więc aria-describedby pól dalej działa. Wyłączenie: data-keep-hint na podpowiedzi albo przodku.
const HINT_MIN_LENGTH = 40;
let hintSeq = 0;

function labelFor(hint) {
    // Etykieta pola: poprzedni brat-etykieta albo pierwsza etykieta w tym samym kontenerze przed podpowiedzią.
    let el = hint.previousElementSibling;
    while (el) {
        if (el.matches('label, p.font-bold, span.font-bold')) return el;
        const inner = el.querySelector?.(':scope > label');
        if (inner) return inner;
        el = el.previousElementSibling;
    }
    const first = hint.parentElement?.firstElementChild;
    if (first && first !== hint && first.matches('label')) return first;
    return null;
}

function enhanceHint(hint) {
    if (hint.dataset.hintReady || hint.closest('[data-keep-hint], [role="alert"], dialog, .picker-popover')) return;
    const text = hint.textContent.replace(/\s+/g, ' ').trim();
    if (text.length < HINT_MIN_LENGTH) return;
    const label = labelFor(hint);
    if (! label || label.querySelector('[data-hint-toggle]') || label.nextElementSibling?.matches?.('[data-hint-toggle]')) return;
    hint.dataset.hintReady = '1';

    hint.id ||= 'hint-' + (++hintSeq);
    hint.hidden = true;
    hint.classList.add('admin-hint-open');

    const topic = label.textContent.replace(/\s+/g, ' ').replace(/\(.*?\)/g, '').trim().slice(0, 60);
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'admin-hint-btn';
    btn.dataset.hintToggle = '';
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-controls', hint.id);
    btn.innerHTML = '<span aria-hidden="true">?</span><span class="sr-only">Pomoc: ' + topic.replace(/[<>&"]/g, '') + '</span>';
    btn.addEventListener('click', () => {
        hint.hidden = ! hint.hidden;
        btn.setAttribute('aria-expanded', String(! hint.hidden));
    });

    if (['block', 'flex', 'grid'].includes(getComputedStyle(label).display) || label.classList.contains('block')) {
        // Etykieta blokowa albo z polem wyboru: etykieta i „?” w jednym wierszu.
        const row = document.createElement('div');
        row.className = 'admin-hint-row';
        label.replaceWith(row);
        row.append(label, btn);
        label.classList.remove('block');
        if (label.classList.contains('mb-1')) { label.classList.remove('mb-1'); row.classList.add('mb-1'); }
    } else {
        label.after(btn);
    }
}

function enhanceHints(root = document) {
    root.querySelectorAll?.('main p.text-xs.text-muted').forEach(enhanceHint);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => enhanceHints());
else enhanceHints();
