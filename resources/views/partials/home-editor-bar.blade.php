{{--
    Pasek edytora układu strony głównej.
    Renderowany wyłącznie wewnątrz x-data="homepageEditor(...)" — ma dostęp do
    stanu Alpine (editMode, saving, saveSuccess, error, hasChanges()).
    Widoczny tylko dla administratorów (warunek w home.blade.php).
--}}
<div
    class="fixed inset-x-0 bottom-0 z-[9999] border-t border-gray-200 bg-white shadow-[0_-4px_16px_rgba(0,0,0,.08)] print:hidden"
    role="region"
    aria-label="Panel edytora strony głównej"
    aria-live="polite"
>
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">

        {{-- Ikona + komunikat statusu --}}
        <div class="flex min-w-0 flex-1 items-center gap-2 text-sm text-gray-600">
            <template x-if="saveSuccess">
                <span class="flex items-center gap-1.5 font-medium text-green-700">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Zapisano! Odświeżanie strony…
                </span>
            </template>
            <template x-if="!saveSuccess && editMode">
                <span class="flex items-center gap-1.5 font-medium text-brand">
                    <i class="fa-solid fa-grip-dots-vertical" aria-hidden="true"></i>
                    Tryb edycji — przeciągaj sekcje lub użyj strzałek, by zmienić kolejność.
                </span>
            </template>
            <template x-if="!saveSuccess && !editMode">
                <span class="flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic-sparkles text-brand" aria-hidden="true"></i>
                    Tryb administratora — możesz edytować układ strony głównej.
                </span>
            </template>
        </div>

        {{-- Komunikat błędu --}}
        <p
            x-show="error"
            x-text="error"
            class="text-sm font-medium text-red-600"
            role="alert"
        ></p>

        {{-- Przyciski akcji --}}
        <div class="flex shrink-0 items-center gap-2">

            {{-- Odrzuć zmiany (tylko w trybie edycji, gdy są niezapisane zmiany) --}}
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

            {{-- Zapisz układ (tylko w trybie edycji) --}}
            <button
                x-show="editMode"
                x-cloak
                type="button"
                @click="save()"
                :disabled="saving || !hasChanges()"
                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-green-700 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600"
                :aria-label="saving ? 'Zapisywanie układu…' : 'Zapisz nowy układ strony'"
            >
                <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i>
                <span x-text="saving ? 'Zapisywanie…' : 'Zapisz układ'"></span>
            </button>

            {{-- Przycisk przełączania trybu edycji --}}
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
                    <span><i class="fa-solid fa-pen-ruler mr-1" aria-hidden="true"></i>Edytuj układ</span>
                </template>
                <template x-if="editMode">
                    <span><i class="fa-solid fa-eye mr-1" aria-hidden="true"></i>Podgląd</span>
                </template>
            </button>

        </div>
    </div>
</div>

{{-- Odsunięcie treści strony od dołu, żeby pasek nie zasłaniał ostatniej sekcji --}}
<div aria-hidden="true" class="h-16 print:hidden"></div>
