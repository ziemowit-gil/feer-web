{{--
    Pasek wizualnej edycji „na żywo" (alternatywa dla formularza admina).
    Renderowany wewnątrz x-data="inlineContentEditor(...)" — korzysta ze stanu
    Alpine: editMode, dirty, saving, saveSuccess, error, hasRichFields,
    toggleEdit(), saveAll(), exitEdit().

    W trybie edycji pola `data-inline-kind="rich"` dostają edytor WYSIWYG
    (TinyMCE inline / CKEditor 5 inline); pasek narzędzi TinyMCE trafia do
    #inline-editor-toolbar, więc jest zawsze pod ręką, przypięty u góry.
--}}
<div class="sticky top-0 z-[9999] border-b border-gray-200 bg-white shadow-[0_4px_16px_rgba(0,0,0,.08)] print:hidden"
    role="region" aria-label="Pasek wizualnej edycji treści">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 gap-y-2 px-4 py-2">
        <div class="flex min-w-0 flex-1 items-center gap-2 text-sm text-gray-600" aria-live="polite">
            <template x-if="saveSuccess">
                <span class="flex items-center gap-1.5 font-medium text-green-700">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Zapisano.
                </span>
            </template>
            <template x-if="!saveSuccess && editMode && dirty">
                <span class="flex items-center gap-1.5 font-medium text-amber-700">
                    <i class="fa-solid fa-circle-dot" aria-hidden="true"></i> Niezapisane zmiany — zapisz przyciskiem albo <kbd class="rounded border border-amber-300 bg-amber-50 px-1 text-xs">Ctrl+S</kbd>.
                </span>
            </template>
            <template x-if="!saveSuccess && editMode && !dirty">
                <span class="flex items-center gap-1.5 font-medium text-brand">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    Tryb edycji — kliknij tytuł albo treść i edytuj bezpośrednio na stronie, z pełnym paskiem narzędzi.
                </span>
            </template>
            <template x-if="!saveSuccess && !editMode">
                <span class="flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic-sparkles text-brand" aria-hidden="true"></i>
                    Tryb administratora — możesz edytować tę stronę bezpośrednio.
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
                class="inline-flex min-h-10 items-center gap-1.5 rounded-lg border px-4 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                :class="editMode ? 'border-gray-300 bg-white text-ink hover:bg-gray-50' : 'border-brand bg-brand/10 text-brand hover:bg-brand/20'">
                <template x-if="!editMode">
                    <span><i class="fa-solid fa-pen-to-square mr-1" aria-hidden="true"></i>Edytuj tę stronę</span>
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
