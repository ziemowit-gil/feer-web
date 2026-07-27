@extends('admin.layout')

@section('title', $navItem->exists ? 'Edytuj pozycję menu' : 'Nowa pozycja menu')

@section('content')
    <form method="POST" action="{{ $navItem->exists ? route('admin.pozycje-menu.update', $navItem) : route('admin.pozycje-menu.store') }}"
        x-data="{ type: '{{ old('type', $navItem->type ?? 'link') }}', parentId: '{{ old('parent_id', $navItem->parent_id ?? request('parent_id')) }}', location: '{{ old('location', $navItem->location ?? request('location', 'main')) }}', isButton: {{ old('is_button', $navItem->is_button ?? false) ? 'true' : 'false' }} }"
        class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($navItem->exists) @method('PUT') @endif

        <div>
            <label for="location" class="mb-1 block text-sm font-bold">Lokalizacja</label>
            <select id="location" name="location" x-model="location" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @foreach (\App\Models\NavItem::LOCATIONS as $value => $option)
                    <option value="{{ $value }}" {{ old('location', $navItem->location ?? 'main') === $value ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-muted">Stopka wyświetla pozycje zawsze jako zwykłe linki, bez rozwijanych podmenu.</p>
            @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="label" class="mb-1 block text-sm font-bold">Etykieta</label>
            <input type="text" id="label" name="label" value="{{ old('label', $navItem->label) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="location === 'main'" x-cloak>
            <label for="type" class="mb-1 block text-sm font-bold">Typ pozycji</label>
            <select id="type" name="type" x-model="type" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @foreach (\App\Models\NavItem::TYPES as $value => $option)
                    <option value="{{ $value }}" {{ old('type', $navItem->type ?? 'link') === $value ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-muted" x-show="type === 'volunteering'" x-cloak>
                Pozycja prowadzi automatycznie do listy ogłoszeń o wolontariacie (/wolontariat) i ukrywa się, gdy moduł jest wyłączony. Możesz wyróżnić ją jako przycisk (CTA) poniżej.
            </p>
        </div>

        <div x-show="type === 'link' || location === 'footer'" x-cloak>
            @if ($pages->isNotEmpty())
                <label for="page_picker" class="mb-1 block text-sm font-bold">Wybierz stronę <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <select id="page_picker" class="mb-2 w-full rounded border-gray-300 focus:border-brand focus:ring-brand"
                    onchange="if (this.value) { document.getElementById('url').value = this.value; } this.selectedIndex = 0;">
                    <option value="">— wybierz z listy stron —</option>
                    @foreach ($pages as $page)
                        <option value="/{{ $page->slug }}">{{ $page->title }}</option>
                    @endforeach
                </select>
            @endif

            <label for="url" class="mb-1 block text-sm font-bold">Link</label>
            <input type="text" id="url" name="url" value="{{ old('url', $navItem->url) }}" placeholder="np. /polityka-prywatnosci, #kontakt lub https://..."
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Wybór strony powyżej uzupełni to pole — możesz też wpisać dowolny adres ręcznie.</p>
            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="type === 'link' && location === 'main'" x-cloak>
            <label for="parent_id" class="mb-1 block text-sm font-bold">Podpozycja rozwijanego menu</label>
            <select id="parent_id" name="parent_id" x-model="parentId" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <option value="">— pozycja główna (na pasku menu) —</option>
                @foreach ($parentOptions as $option)
                    <option value="{{ $option->id }}" {{ (string) old('parent_id', $navItem->parent_id ?? request('parent_id')) === (string) $option->id ? 'selected' : '' }}>{{ $option->label }}</option>
                @endforeach
            </select>
            @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="module" class="mb-1 block text-sm font-bold">Widoczna tylko gdy moduł włączony</label>
            <select id="module" name="module" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <option value="">— zawsze widoczna —</option>
                @foreach (\App\Models\SiteSetting::MODULES as $value => $option)
                    <option value="{{ $value }}" {{ old('module', $navItem->module) === $value ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            @error('module') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2" x-show="(type === 'link' || type === 'volunteering') && parentId === ''" x-cloak>
            <input type="checkbox" name="is_button" value="1" x-model="isButton" {{ old('is_button', $navItem->is_button ?? false) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Wyróżnij jako przycisk (CTA)</span>
        </label>

        <div x-show="(type === 'link' || type === 'volunteering') && parentId === '' && isButton" x-cloak>
            @php $buttonColor = old('button_color', $navItem->button_color); @endphp
            <label for="button_color" class="mb-1 block text-sm font-bold">Kolor przycisku <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <div class="flex items-center gap-3" x-data="{ color: '{{ $buttonColor ?: '#2563eb' }}', enabled: {{ $buttonColor ? 'true' : 'false' }} }">
                <input type="hidden" name="button_color" :value="enabled ? color : ''">
                <input type="color" x-model="color" :disabled="!enabled" aria-label="Wybierz kolor przycisku"
                    class="h-10 w-14 flex-none cursor-pointer rounded border border-gray-300 disabled:opacity-40">
                <input type="text" x-model="color" :disabled="!enabled" aria-label="Kod koloru (hex)"
                    placeholder="#2563eb" pattern="#[0-9a-fA-F]{6}"
                    class="w-40 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand disabled:bg-gray-100 disabled:text-muted">
                <label class="flex items-center gap-2 text-sm text-muted">
                    <input type="checkbox" x-model="enabled" class="rounded border-gray-300 text-brand focus:ring-brand">
                    Własny kolor
                </label>
            </div>
            <p class="mt-1 text-xs text-muted">Kolor tła przycisku. Tekst automatycznie dobiera czerń lub biel dla kontrastu (WCAG). Wyłącz „Własny kolor”, aby użyć koloru marki.</p>
            @error('button_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2" x-show="type === 'dropdown' || type === 'projects' || type === 'pages'" x-cloak>
            <input type="checkbox" name="is_transparent_dropdown" value="1" {{ old('is_transparent_dropdown', $navItem->is_transparent_dropdown ?? false) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Przezroczyste tło rozwijanego panelu</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $navItem->is_active ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Widoczna w menu</span>
        </label>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $navItem->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.pozycje-menu.index', ['location' => old('location', $navItem->location ?? 'main')]) }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
