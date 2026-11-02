// Testy czystej logiki edytora blokowego (resources/js/block-editor-state.js) — `node --test tests/js`.
// Uruchamia je też PHPUnit (BlockEditorStateLogicTest), gdy `node` jest w PATH.
import test from 'node:test';
import assert from 'node:assert/strict';
import { blockPath, mergeSavedSettings, nearestBlockId } from '../../resources/js/block-editor-state.js';

const p = (id, inner) => (inner ? { id, type: 'columns', inner } : { id, type: 'paragraph' });
const ids = (list) => list.map((b) => b.id);

test('blockPath: pozycja na najwyższym poziomie i w zagnieżdżeniu, null dla nieistniejącego bloku', () => {
    const tree = [p('a'), p('cols', [p('c1', [p('x'), p('y')]), p('c2', [p('z')])]), p('b')];

    assert.deepEqual(blockPath(tree, 'a'), [0]);
    assert.deepEqual(blockPath(tree, 'b'), [2]);
    assert.deepEqual(blockPath(tree, 'y'), [1, 0, 1]);
    assert.deepEqual(blockPath(tree, 'z'), [1, 1, 0]);
    assert.equal(blockPath(tree, 'nie-ma'), null);
    assert.equal(blockPath([], 'a'), null);
});

test('nearestBlockId: cofnięty podział akapitu (Enter) wraca do akapitu sprzed podziału, nie na początek strony', () => {
    const before = [p('p1'), p('p2'), p('nowy'), p('p4')];
    const after = [p('p1'), p('p2'), p('p4')];

    assert.equal(nearestBlockId(before, after, [2]), 'p2');
});

test('nearestBlockId: cofnięte wstawienie na końcu i na początku listy', () => {
    assert.equal(nearestBlockId([p('p1'), p('p2'), p('nowy')], [p('p1'), p('p2')], [2]), 'p2');
    // Na początku nie ma poprzedniego bloku — zostaje pierwszy.
    assert.equal(nearestBlockId([p('nowy'), p('p1'), p('p2')], [p('p1'), p('p2')], [0]), 'p1');
});

test('nearestBlockId: cofnięta zmiana typu (ta sama liczba bloków) wraca na tę samą pozycję', () => {
    const before = [p('p1'), p('naglowek'), p('p3')];
    const after = [p('p1'), p('akapit'), p('p3')];

    assert.equal(nearestBlockId(before, after, [1]), 'akapit');
});

test('nearestBlockId: cofnięte wklejenie kilku bloków wraca do bloku sprzed wklejenia', () => {
    const before = [p('p1'), p('a'), p('b'), p('c'), p('p2')];
    const after = [p('p1'), p('p2')];

    assert.equal(nearestBlockId(before, after, [3]), 'p1');
});

test('nearestBlockId: w kolumnie schodzi przez te same bloki i wraca do poprzedniego akapitu w tej kolumnie', () => {
    const before = [p('cols', [p('c1', [p('x'), p('y')]), p('c2', [p('z')])])];
    const after = [p('cols', [p('c1', [p('x')]), p('c2', [p('z')])])];

    assert.equal(nearestBlockId(before, after, [0, 0, 1]), 'x');
});

test('nearestBlockId: gdy znika cały kontener, zostaje poprzedni blok na najwyższym poziomie', () => {
    const before = [p('p1'), p('cols', [p('c1', [p('x')])])];
    const after = [p('p1')];

    assert.equal(nearestBlockId(before, after, [1, 0, 0]), 'p1');
});

test('nearestBlockId: po zmianie kontenera schodzi na tę samą pozycję w nowym kontenerze i przycina indeks do jego długości', () => {
    // Pozycja w nowym kontenerze liczy się od zera — liczba bloków w starym nie ma wpływu.
    const replaced = nearestBlockId(
        [p('A', [p('a1'), p('a2'), p('a3'), p('a4'), p('a5')]), p('B')],
        [p('C', [p('c1'), p('c2'), p('c3')]), p('B')],
        [0, 2],
    );
    assert.equal(replaced, 'c3');
    // Ścieżka dłuższa niż nowe drzewo: schodzimy tak głęboko, jak się da, i przycinamy indeksy.
    const shorter = nearestBlockId(
        [p('p1'), p('cols', [p('c1', [p('x'), p('y'), p('z')])])],
        [p('cols2', [p('c9', [p('q')])])],
        [1, 0, 2],
    );
    assert.equal(shorter, 'q');
});

test('nearestBlockId: puste drzewo albo brak ścieżki daje null (bez skoku na początek strony)', () => {
    assert.equal(nearestBlockId([p('a')], [], [0]), null);
    assert.equal(nearestBlockId([p('a')], [p('b')], null), null);
    assert.equal(nearestBlockId([p('a')], [p('b')], []), null);
});

test('nearestBlockId: zawsze zwraca id istniejące w drzewie po zmianie', () => {
    const before = [p('a'), p('b'), p('cols', [p('c1', [p('x'), p('y'), p('z')]), p('c2', [p('w')])]), p('e')];
    const afters = [
        [p('a')],
        [p('a'), p('b'), p('cols', [p('c1', [p('x')]), p('c2', [p('w')])])],
        [p('b'), p('cols', [p('c9', [p('q')])])],
        [p('a'), p('b'), p('e'), p('f'), p('g'), p('h')],
    ];
    const known = (list) => list.flatMap((b) => [b.id, ...(b.inner ? known(b.inner) : [])]);

    for (const after of afters) {
        for (const id of ['a', 'b', 'cols', 'c1', 'x', 'y', 'z', 'c2', 'w', 'e']) {
            const found = nearestBlockId(before, after, blockPath(before, id));
            assert.ok(found === null || known(after).includes(found), `${id} → ${found} w ${JSON.stringify(ids(after))}`);
        }
    }
});

test('mergeSavedSettings: bez zmian w trakcie zapisu bierzemy wszystko z odpowiedzi serwera', () => {
    const sent = { excerpt: 'Lead', slug: 'Stary adres', is_published: true };
    const server = { excerpt: 'Lead', slug: 'stary-adres', is_published: true, pending_approval: false };
    const merged = mergeSavedSettings(server, sent, { ...sent });

    assert.equal(merged.edited, false);
    assert.deepEqual(merged.settings, server);
});

test('mergeSavedSettings: pole zmienione w trakcie zapisu zostaje lokalne, reszta z serwera', () => {
    const sent = { excerpt: 'Lead', slug: 'Stary adres', is_published: false };
    const local = { excerpt: 'Lead i dopisek w trakcie zapisu', slug: 'Stary adres', is_published: false };
    const server = { excerpt: 'Lead', slug: 'stary-adres', is_published: false, pending_approval: true };
    const merged = mergeSavedSettings(server, sent, local);

    assert.equal(merged.edited, true);
    assert.equal(merged.settings.excerpt, 'Lead i dopisek w trakcie zapisu');
    assert.equal(merged.settings.slug, 'stary-adres');
    assert.equal(merged.settings.pending_approval, true);
});

test('mergeSavedSettings: przełącznik publikacji zmieniony w trakcie zapisu nie jest cofany odpowiedzią', () => {
    const sent = { is_published: false, show_in_menu: true };
    const local = { is_published: true, show_in_menu: true };
    const server = { is_published: false, show_in_menu: true };

    assert.equal(mergeSavedSettings(server, sent, local).settings.is_published, true);
});

test('mergeSavedSettings: zmiana cofnięta w trakcie zapisu (wartość jak wysłana) nie liczy się jako edycja', () => {
    const sent = { excerpt: 'Lead' };

    assert.equal(mergeSavedSettings({ excerpt: 'Lead' }, sent, { excerpt: 'Lead' }).edited, false);
});

test('mergeSavedSettings: porównuje wartości strukturalnie, a bez ustawień lokalnych zwraca odpowiedź', () => {
    const sent = { tags: [1, 2], order: 0 };

    assert.equal(mergeSavedSettings({ tags: [1, 2], order: 0 }, sent, { tags: [1, 2], order: 0 }).edited, false);
    assert.equal(mergeSavedSettings({ tags: [1, 2], order: 0 }, sent, { tags: [1, 2, 3], order: 0 }).edited, true);
    assert.deepEqual(mergeSavedSettings({ a: 1 }, null, null), { settings: { a: 1 }, edited: false });
});
