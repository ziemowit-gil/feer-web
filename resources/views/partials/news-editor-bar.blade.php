{{--
    Pasek szybkiej edycji aktualności na froncie.
    Renderowany wyłącznie wewnątrz x-data="newsInlineEditor(...)" — ma dostęp do
    stanu Alpine: editMode, form, saving, saveSuccess, error, collapsed, toggleBar().
    Widoczność (czy w ogóle jest w DOM) kontrolowana w news/show.blade.php.
--}}
<div
    class="fixed inset-x-0 top-0 z-[9999] border-b border-gray-200 bg-white shadow-[0_4px_16px_rgba(0,0,0,.08)] print:hidden"
    role="region"
    aria-label="Panel szybkiej edycji aktualności"
    aria-live="polite"
>
    {{-- Stan zwinięty --}}
    <div x-show="collapsed" class="flex items-center justify-between px-4 py-1.5">
        <span class="text-xs font-medium text-gray-500">
            <i class="fa-solid fa-newspaper mr-1 text-brand" aria-hidden="true"></i>Panel admina
        </span>
        <button
            type="button"
            @click="toggleBar()"
            class="flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
            aria-label="Rozwiń pasek administratora"
        >
            Rozwiń <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Stan rozwinięty --}}
    <div x-show="!collapsed">
        <div class="mx-auto flex max-w-2xl flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">

            <div class="flex min-w-0 flex-1 items-center gap-2 text-sm text-gray-600">
                <template x-if="saveSuccess">
                    <span class="flex items-center gap-1.5 font-medium text-green-700">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        Zapisano! Odświeżanie strony…
                    </span>
                </template>
                <template x-if="!saveSuccess && editMode">
                    <span class="flex items-center gap-1.5 font-medium text-brand">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        Tryb edycji — zmień tytuł lub lead poniżej.
                    </span>
                </template>
                <template x-if="!saveSuccess && !editMode">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-newspaper text-brand" aria-hidden="true"></i>
                        Tryb administratora — możesz edytować tę aktualność bez wchodzenia do panelu.
                    </span>
                </template>
            </div>

            <p x-show="error" x-text="error" class="text-sm font-medium text-red-600" role="alert"></p>

            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    @click="toggleBar()"
                    class="rounded-lg border border-gray-200 px-2 py-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Zwiń pasek administratora"
                    title="Zwiń"
                >
                    <i class="fa-solid fa-chevron-up text-sm" aria-hidden="true"></i>
                </button>

                <a
                    href="{{ route('admin.newsy.edit', $news) }}"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    title="Pełna edycja w panelu (treść, zdjęcie, tagi…)"
                >
                    <i class="fa-solid fa-gauge mr-1" aria-hidden="true"></i>Panel
                </a>

                <button
                    x-show="editMode"
                    x-cloak
                    type="button"
                    @click="discard()"
                    :disabled="saving"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                >
                    <i class="fa-solid fa-rotate-left mr-1" aria-hidden="true"></i>Odrzuć
                </button>

                <button
                    x-show="editMode"
                    x-cloak
                    type="button"
                    @click="save()"
                    :disabled="saving || !hasChanges()"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-green-700 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600"
                    :aria-label="saving ? 'Zapisywanie…' : 'Zapisz zmiany'"
                >
                    <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i>
                    <span x-text="saving ? 'Zapisywanie…' : 'Zapisz'"></span>
                </button>

                <button
                    type="button"
                    @click="toggleEdit()"
                    :aria-pressed="editMode"
                    :disabled="saving"
                    class="rounded-lg border px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand disabled:opacity-50"
                    :class="editMode
                        ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                        : 'border-brand bg-brand/10 text-brand hover:bg-brand/20'"
                >
                    <template x-if="!editMode">
                        <span><i class="fa-solid fa-pen-to-square mr-1" aria-hidden="true"></i>Edytuj</span>
                    </template>
                    <template x-if="editMode">
                        <span><i class="fa-solid fa-eye mr-1" aria-hidden="true"></i>Podgląd</span>
                    </template>
                </button>
            </div>
        </div>

        {{-- Dodatkowe pola dostępne tylko w treści strony niewidoczne domyślnie: lead + publikacja --}}
        <div x-show="editMode" x-cloak class="border-t border-gray-100 bg-gray-50 px-4 py-3">
            <div class="mx-auto flex max-w-2xl flex-wrap items-start gap-4">
                <label class="min-w-0 flex-1 text-sm">
                    <span class="mb-1 block font-bold text-gray-700">Lead (krótki opis)</span>
                    <textarea x-model="form.excerpt" rows="2" maxlength="255"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"
                        placeholder="Krótki opis wyświetlany na liście aktualności…"></textarea>
                </label>
                <label class="flex items-center gap-2 pt-6 text-sm font-bold text-gray-700">
                    <input type="checkbox" x-model="form.is_published" class="rounded border-gray-300 text-brand focus:ring-brand">
                    Opublikowany
                </label>
            </div>
        </div>
    </div>
</div>
