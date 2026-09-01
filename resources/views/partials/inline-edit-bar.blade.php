{{--
    Pasek wizualnej edycji "na żywo" (alternatywa dla formularza admina).
    Renderowany wewnątrz x-data="inlineContentEditor(...)" — ma dostęp do stanu
    Alpine: editMode, saving, saveSuccess, error, toggleEdit().
--}}
<div class="sticky top-0 z-[9999] border-b border-gray-200 bg-white shadow-[0_4px_16px_rgba(0,0,0,.08)] print:hidden"
    role="region" aria-label="Pasek wizualnej edycji treści" aria-live="polite">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 gap-y-2 px-4 py-2.5">
        <div class="flex min-w-0 flex-1 items-center gap-2 text-sm text-gray-600">
            <template x-if="saveSuccess">
                <span class="flex items-center gap-1.5 font-medium text-green-700">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Zapisano.
                </span>
            </template>
            <template x-if="!saveSuccess && editMode">
                <span class="flex items-center gap-1.5 font-medium text-brand">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    Tryb edycji — kliknij tytuł albo treść i edytuj bezpośrednio na stronie. Zapis następuje po opuszczeniu pola.
                </span>
            </template>
            <template x-if="!saveSuccess && !editMode">
                <span class="flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic-sparkles text-brand" aria-hidden="true"></i>
                    Tryb administratora — możesz edytować tę stronę bezpośrednio.
                </span>
            </template>
        </div>

        <p x-show="error" x-text="error" class="text-sm font-medium text-red-600" role="alert"></p>

        <div class="flex shrink-0 items-center gap-2">
            <span x-show="saving" x-cloak class="text-xs font-medium text-muted">
                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Zapisywanie…
            </span>
            <button type="button" @click="toggleEdit()" :aria-pressed="editMode"
                class="rounded-lg border px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                :class="editMode ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : 'border-brand bg-brand/10 text-brand hover:bg-brand/20'">
                <template x-if="!editMode">
                    <span><i class="fa-solid fa-pen-to-square mr-1" aria-hidden="true"></i>Edytuj tę stronę</span>
                </template>
                <template x-if="editMode">
                    <span><i class="fa-solid fa-eye mr-1" aria-hidden="true"></i>Zakończ edycję</span>
                </template>
            </button>
        </div>
    </div>
</div>
