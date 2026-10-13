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
