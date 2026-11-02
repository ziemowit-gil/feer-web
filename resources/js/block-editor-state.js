// Czysta logika edytora blokowego (bez DOM), wydzielona z block-editor.js, żeby dało się ją sprawdzić testem
// `node --test` (tests/js/block-editor-state.test.mjs) — edytora nie uruchamiamy w przeglądarce.

/** Ścieżka indeksów od korzenia do bloku o danym id (np. [2, 1, 0]) albo null, gdy takiego bloku nie ma. */
export function blockPath(blocks, id, prefix = []) {
    for (let i = 0; i < (blocks || []).length; i++) {
        const b = blocks[i];
        if (b.id === id) return [...prefix, i];
        if (b.inner) {
            const found = blockPath(b.inner, id, [...prefix, i]);
            if (found) return found;
        }
    }
    return null;
}

/**
 * Blok, do którego wracamy po cofnięciu albo ponowieniu, gdy zaznaczony blok zniknął (cofnięte wstawienie, podział akapitu
 * klawiszem Enter, zmiana typu, wklejenie). `path` to ścieżka zaznaczonego bloku w drzewie `before` (sprzed podmiany),
 * `after` — drzewo po podmianie. Na każdym poziomie bierzemy tę samą pozycję; gdy w liście ubyło bloków (cofnięte wstawienie),
 * pozycję cofamy o liczbę usuniętych, czyli wracamy do bloku sprzed wstawienia. Indeks jest przycinany do długości listy.
 * Schodzimy głębiej tylko przez ten sam blok co przed zmianą (inaczej poziomy nie są porównywalne). Zwraca id bloku albo null
 * (puste drzewo, brak ścieżki) — wtedy wołający nie powinien przenosić fokusu na początek strony.
 */
export function nearestBlockId(before, after, path) {
    let found = null;
    let listBefore = before;
    let listAfter = after;
    for (const index of path || []) {
        if (!listAfter?.length) break;
        const removed = listBefore ? Math.max(0, listBefore.length - listAfter.length) : 0;
        const at = Math.max(0, Math.min(index - removed, listAfter.length - 1));
        const block = listAfter[at];
        found = block.id;
        listBefore = listBefore && listBefore[index]?.id === block.id ? listBefore[index].inner : null;
        listAfter = block.inner;
    }
    return found;
}

/**
 * Ustawienia (panel boczny) po zapisie. `server` to wartości z odpowiedzi, `sent` — ustawienia w chwili wysłania żądania,
 * `local` — bieżące. Pola, które użytkownik zmienił w trakcie zapisu, zostają lokalne (i „niezapisane”), pozostałe bierzemy
 * z odpowiedzi serwera (adres po normalizacji, oczekiwanie na zatwierdzenie, zdjęcie…). `edited` mówi, czy coś zmieniono w trakcie.
 */
export function mergeSavedSettings(server, sent, local) {
    if (!local) return { settings: server, edited: false };
    const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);
    const settings = { ...server };
    let edited = false;
    for (const key of Object.keys(local)) {
        if (!same(local[key], sent?.[key])) { settings[key] = local[key]; edited = true; }
    }
    return { settings, edited };
}
