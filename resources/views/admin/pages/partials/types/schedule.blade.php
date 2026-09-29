{{-- Sekcja formularza strony: typ "schedule" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
<div data-schedule-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'schedule' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Harmonogram</p>

    @php $schedulePending = old('schedule_pending', $page->schedule_pending ?? false); @endphp
    <div class="rounded-lg border {{ $schedulePending ? 'border-amber-300 bg-amber-50' : 'border-gray-200 bg-gray-50' }} p-4" data-schedule-pending-box>
        <label class="flex items-start gap-3">
            <input type="hidden" name="schedule_pending" value="0">
            <input type="checkbox" name="schedule_pending" value="1" {{ $schedulePending ? 'checked' : '' }}
                data-schedule-pending-toggle class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
            <span>
                <span class="block text-sm font-bold text-ink">Wyświetlaj komunikat „Harmonogram jeszcze nie został opublikowany"</span>
                <span class="mt-0.5 block text-xs text-muted">Gdy włączone, zamiast tabeli terminów odwiedzający zobaczą komunikat, że harmonogram nie jest jeszcze gotowy. Terminy poniżej możesz już wpisywać — pojawią się dopiero po wyłączeniu tej opcji.</span>
            </span>
        </label>
    </div>

    <div>
        <label for="schedule_change_notice" class="mb-1 block text-sm font-bold">Informacja o zmianie harmonogramu <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="schedule_change_notice" name="schedule_change_notice" rows="2" placeholder="np. Uwaga: zajęcia z 12 marca przeniesione na 19 marca."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('schedule_change_notice', $page->schedule_change_notice) }}</textarea>
        <p class="mt-1 text-xs text-muted">Jeśli wypełnisz, na górze harmonogramu pojawi się wyróżniony komunikat informujący o zmianie.</p>
        @error('schedule_change_notice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <p class="mb-1 block text-sm font-bold">Terminy</p>
        <p class="mb-3 text-xs text-muted">Dodaj kolejne terminy (data, godzina, miejsce). Zaznacz „Termin zmieniony", aby wyróżnić wpis, który uległ zmianie.</p>

        <div data-schedule-rows class="space-y-3">
            @foreach ($scheduleItems as $i => $item)
                <div data-schedule-row class="grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1fr_1fr_1.5fr_auto]">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-muted">Data</label>
                        <input type="date" name="schedule_items[{{ $i }}][date]" value="{{ $item['date'] ?? '' }}" aria-label="Data terminu {{ $i + 1 }}"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-muted">Godzina</label>
                        <input type="time" name="schedule_items[{{ $i }}][time]" value="{{ $item['time'] ?? '' }}" aria-label="Godzina terminu {{ $i + 1 }}"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-muted">Miejsce / lokalizacja</label>
                        <input type="text" name="schedule_items[{{ $i }}][location]" value="{{ $item['location'] ?? '' }}" placeholder="np. sala 12 / online" aria-label="Miejsce terminu {{ $i + 1 }}"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div class="flex items-end">
                        <button type="button" data-schedule-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" title="Usuń termin" aria-label="Usuń termin {{ $i + 1 }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    <div class="sm:col-span-4">
                        <label class="mb-1 block text-xs font-bold text-muted">Uwaga <span class="font-normal">(opcjonalnie)</span></label>
                        <input type="text" name="schedule_items[{{ $i }}][note]" value="{{ $item['note'] ?? '' }}" placeholder="np. spotkanie organizacyjne" aria-label="Uwaga do terminu {{ $i + 1 }}"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <label class="flex items-center gap-2 sm:col-span-4">
                        <input type="hidden" name="schedule_items[{{ $i }}][changed]" value="0">
                        <input type="checkbox" name="schedule_items[{{ $i }}][changed]" value="1" {{ ! empty($item['changed']) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Termin zmieniony</span>
                    </label>
                </div>
            @endforeach
        </div>

        <button type="button" data-schedule-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj termin
        </button>

        <template data-schedule-template>
            <div data-schedule-row class="grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1fr_1fr_1.5fr_auto]">
                <div>
                    <label class="mb-1 block text-xs font-bold text-muted">Data</label>
                    <input type="date" name="schedule_items[__INDEX__][date]" aria-label="Data terminu"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-muted">Godzina</label>
                    <input type="time" name="schedule_items[__INDEX__][time]" aria-label="Godzina terminu"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-muted">Miejsce / lokalizacja</label>
                    <input type="text" name="schedule_items[__INDEX__][location]" placeholder="np. sala 12 / online" aria-label="Miejsce terminu"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div class="flex items-end">
                    <button type="button" data-schedule-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" title="Usuń termin" aria-label="Usuń termin"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
                <div class="sm:col-span-4">
                    <label class="mb-1 block text-xs font-bold text-muted">Uwaga <span class="font-normal">(opcjonalnie)</span></label>
                    <input type="text" name="schedule_items[__INDEX__][note]" placeholder="np. spotkanie organizacyjne" aria-label="Uwaga do terminu"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <label class="flex items-center gap-2 sm:col-span-4">
                    <input type="hidden" name="schedule_items[__INDEX__][changed]" value="0">
                    <input type="checkbox" name="schedule_items[__INDEX__][changed]" value="1"
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Termin zmieniony</span>
                </label>
            </div>
        </template>
    </div>
</div>
