{{-- Formularz kategorii Wiem FEER (dodawanie i edycja). Zmienne: $category, $action, $method. --}}
<form method="POST" action="{{ $action }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-5">
    @csrf
    @if ($method) @method($method) @endif
    <div>
        <label for="cat-name" class="mb-1 block text-sm font-bold">Nazwa</label>
        <input type="text" id="cat-name" name="name" value="{{ old('name', $category->name) }}" required maxlength="120" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="cat-slug" class="mb-1 block text-sm font-bold">Adres (slug) <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <input type="text" id="cat-slug" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="120" placeholder="z nazwy" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        @error('slug') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('_slug') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="cat-description" class="mb-1 block text-sm font-bold">Opis <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="cat-description" name="description" rows="2" maxlength="1000" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('description', $category->description) }}</textarea>
    </div>
    <div>
        <label for="cat-order" class="mb-1 block text-sm font-bold">Kolejność</label>
        <input type="number" id="cat-order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-32 rounded border-gray-300 focus:border-brand focus:ring-brand">
    </div>
    <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
</form>
