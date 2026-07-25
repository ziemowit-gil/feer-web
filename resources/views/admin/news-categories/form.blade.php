@extends('admin.layout')

@section('title', $newsCategory->exists ? 'Edytuj kategorię newsów' : 'Nowa kategoria newsów')

@section('content')
    <form method="POST" action="{{ $newsCategory->exists ? route('admin.kategorie-newsow.update', $newsCategory) : route('admin.kategorie-newsow.store') }}"
        x-data="{ useBrandColor: {{ old('color', $newsCategory->color) ? 'false' : 'true' }} }"
        class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($newsCategory->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $newsCategory->name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="slug" class="mb-1 block text-sm font-bold">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $newsCategory->slug) }}" placeholder="zostanie wygenerowany z nazwy"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $newsCategory->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <div>
            <label class="mb-1 block text-sm font-bold">Kolor odznaki</label>
            <label class="mb-2 flex items-center gap-2 text-sm">
                <input type="checkbox" x-model="useBrandColor" class="rounded border-gray-300 text-brand focus:ring-brand">
                Użyj domyślnego koloru marki (z Ustawień strony)
            </label>

            <div x-show="!useBrandColor" x-cloak class="flex items-center gap-3">
                <input type="color" id="color" name="color" x-bind:disabled="useBrandColor" value="{{ old('color', $newsCategory->color ?: $newsCategory->badgeColor()) }}"
                    class="h-10 w-16 rounded border-gray-300">
                <input type="text" value="{{ old('color', $newsCategory->color ?: $newsCategory->badgeColor()) }}"
                    oninput="document.getElementById('color').value = this.value"
                    class="w-32 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
            </div>
            <p class="mt-1 text-xs text-muted">Widoczny jako tło odznaki kategorii na kartach aktualności.</p>
            @error('color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.kategorie-newsow.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
