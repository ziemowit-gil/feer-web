{{--
    Pasek wizualnej edycji „na żywo" (alternatywa dla formularza admina).
    Renderowany wewnątrz x-data="inlineContentEditor(...)" — korzysta ze stanu
    Alpine: editMode, dirty, saving, saveSuccess, error, hasRichFields,
    toggleEdit(), saveAll(), exitEdit().

    Poza edycją pasek jest ledwo widoczny (jedna cienka, wyciszona linia z małym
    linkiem „Edytuj"), żeby nie odciągał uwagi od strony. Dopiero w trybie edycji
    staje się wyraźny i przypięty u góry — potrzebuje wtedy przycisku „Zapisz"
    i miejsca na pasek narzędzi edytora (#inline-editor-toolbar).
--}}
<div role="region" aria-label="Pasek wizualnej edycji treści"
    class="z-[9999] border-b print:hidden"
    :class="editMode
        ? 'sticky top-0 border-gray-200 bg-white shadow-[0_4px_16px_rgba(0,0,0,.08)]'
        : 'border-transparent bg-gray-50/70'">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 gap-y-1 px-4" :class="editMode ? 'py-2' : 'py-1'">
        <div class="flex min-w-0 flex-1 items-center gap-2 text-xs text-gray-600" aria-live="polite">
            <template x-if="saveSuccess">
                <span class="flex items-center gap-1.5 text-sm font-medium text-green-700">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Zapisano.
                </span>
            </template>
            <template x-if="!saveSuccess && editMode && dirty">
                <span class="flex items-center gap-1.5 text-sm font-medium text-amber-700">
                    <i class="fa-solid fa-circle-dot" aria-hidden="true"></i> Niezapisane zmiany — zapisz przyciskiem albo <kbd class="rounded border border-amber-300 bg-amber-50 px-1 text-xs">Ctrl+S</kbd>.
                </span>
            </template>
            <template x-if="!saveSuccess && editMode && !dirty">
                <span class="flex items-center gap-1.5 text-sm font-medium text-brand">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    Tryb edycji — kliknij tytuł albo treść i edytuj bezpośrednio na stronie.
                </span>
            </template>
            <template x-if="!saveSuccess && !editMode">
                <span class="flex items-center gap-1.5 text-gray-500">
                    <i class="fa-solid fa-pen-ruler text-[11px]" aria-hidden="true"></i>
                    Edycja na stronie
                </span>
            </template>
        </div>

        <p x-show="error" x-text="error" class="w-full text-sm font-medium text-red-600 sm:w-auto" role="alert"></p>

        <div class="flex shrink-0 items-center gap-2">
            <span x-show="saving" x-cloak class="text-xs font-medium text-muted">
                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Zapisywanie…
            </span>

            <template x-if="editMode">
                <button type="button" @click="saveAll()" :disabled="saving || !dirty"
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-lg bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Zapisz
                </button>
            </template>

            <button type="button" @click="toggleEdit()" :aria-pressed="editMode.toString()"
                class="inline-flex items-center gap-1.5 rounded-md font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                :class="editMode
                    ? 'min-h-10 rounded-lg border border-gray-300 bg-white px-4 text-sm text-ink hover:bg-gray-50'
                    : 'min-h-8 px-2 text-xs text-gray-600 underline-offset-4 hover:text-brand hover:underline'">
                <template x-if="!editMode">
                    <span><i class="fa-solid fa-pen-to-square mr-1" aria-hidden="true"></i>Edytuj<span class="sr-only"> tę stronę</span></span>
                </template>
                <template x-if="editMode">
                    <span><i class="fa-solid fa-xmark mr-1" aria-hidden="true"></i><span x-text="dirty ? 'Odrzuć i zakończ' : 'Zakończ edycję'">Zakończ edycję</span></span>
                </template>
            </button>
        </div>
    </div>

    {{-- Pasek narzędzi TinyMCE (inline, przypięty) — wypełnia się po kliknięciu w treść. --}}
    <div id="inline-editor-toolbar" x-show="editMode && hasRichFields" x-cloak class="inline-editor-toolbar border-t border-gray-100 bg-gray-50"></div>
</div>
