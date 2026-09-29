{{-- Sekcja formularza strony: typ "event" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
<div data-event-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'event' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Szczegóły wydarzenia</p>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="event_mode" class="mb-1 block text-sm font-bold">Forma</label>
            <select id="event_mode" name="event_mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <option value="">— wybierz —</option>
                @foreach (\App\Models\Page::EVENT_MODES as $value => $label)
                    <option value="{{ $value }}" {{ old('event_mode', $page->event_mode) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('event_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="event_when" class="mb-1 block text-sm font-bold">Kiedy</label>
            <input type="text" id="event_when" name="event_when" value="{{ old('event_when', $page->event_when) }}" placeholder="np. 12 marca 2026, 18:00"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('event_when') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="event_location" class="mb-1 block text-sm font-bold">Gdzie</label>
        <input type="text" id="event_location" name="event_location" value="{{ old('event_location', $page->event_location) }}" placeholder="np. ul. Barbackiego 28, Nowy Sącz — lub nazwa platformy webinaru"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        @error('event_location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="event_how_to_join" class="mb-1 block text-sm font-bold">Jak dołączyć</label>
        <textarea id="event_how_to_join" name="event_how_to_join" rows="3" placeholder="Instrukcja dołączenia — np. link do spotkania, dojazd, wymagania"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('event_how_to_join', $page->event_how_to_join) }}</textarea>
        @error('event_how_to_join') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="event_registration_url" class="mb-1 block text-sm font-bold">Link do rejestracji</label>
        <input type="url" id="event_registration_url" name="event_registration_url" value="{{ old('event_registration_url', $page->event_registration_url) }}" placeholder="https://..."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        <p class="mt-1 text-xs text-muted">Jeśli podasz link, na stronie wydarzenia pojawi się przycisk „Zarejestruj się".</p>
        @error('event_registration_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
