{{-- Sekcja formularza strony: typ "internal" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
<div data-internal-fields class="space-y-5 border-t border-gray-100 pt-5 {{ in_array($currentType, ['internal', 'internal_hub'], true) ? '' : 'hidden' }}"
    x-data="{ mode: '{{ old('access_mode', $page->access_mode ?? 'password') }}' }">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Dostęp do strony wewnętrznej</p>
    <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
        Strona jest opublikowana, ale jej treść zobaczą tylko osoby autoryzowane — hasłem lub po zalogowaniu (Microsoft 365 / konto panelu), zależnie od trybu poniżej.
    </p>

    <div>
        <label for="access_mode" class="mb-1 block text-sm font-bold">Tryb dostępu</label>
        <select id="access_mode" name="access_mode" x-model="mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @foreach (\App\Models\Page::ACCESS_MODES as $value => $labelText)
                <option value="{{ $value }}" {{ old('access_mode', $page->access_mode ?? 'password') === $value ? 'selected' : '' }}>{{ $labelText }}</option>
            @endforeach
        </select>
        @error('access_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div x-show="mode === 'password'" x-cloak>
        <label for="access_password" class="mb-1 block text-sm font-bold">Hasło dostępu</label>
        <input type="text" id="access_password" name="access_password" autocomplete="off"
            placeholder="{{ $page->exists && filled($page->access_password) ? 'Ustawione — wpisz nowe, aby zmienić' : 'Wpisz hasło' }}"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        <p class="mt-1 text-xs text-muted">@if ($page->exists && filled($page->access_password)) Puste pole = hasło bez zmian. @else Odwiedzający poda to hasło, aby odblokować stronę. @endif</p>
        @error('access_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <p x-show="mode === 'microsoft'" x-cloak class="text-xs text-muted">
        Treść zobaczą wyłącznie zalogowani użytkownicy panelu (m.in. przez Microsoft 365). Niezalogowani zostaną przekierowani do logowania.
    </p>
</div>
