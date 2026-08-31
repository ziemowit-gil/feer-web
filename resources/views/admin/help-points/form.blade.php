@extends('admin.layout')

@section('title', $point->exists ? 'Edytuj punkt pomocy' : 'Nowy punkt pomocy')

@section('content')
    <form method="POST" action="{{ $point->exists ? route('admin.mapa-pomocy.update', $point) : route('admin.mapa-pomocy.store') }}"
        class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($point->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $point->name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="category" class="mb-1 block text-sm font-bold">Kategoria</label>
            <select id="category" name="category" required class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @foreach (\App\Models\HelpPoint::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $point->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="address" class="mb-1 block text-sm font-bold">Adres</label>
            <input type="text" id="address" name="address" value="{{ old('address', $point->address) }}"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="lat" class="mb-1 block text-sm font-bold">Szerokość geogr. (lat)</label>
                <input type="text" inputmode="decimal" id="lat" name="lat" value="{{ old('lat', $point->lat) }}" required placeholder="np. 50.0614"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('lat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="lng" class="mb-1 block text-sm font-bold">Długość geogr. (lng)</label>
                <input type="text" inputmode="decimal" id="lng" name="lng" value="{{ old('lng', $point->lng) }}" required placeholder="np. 19.9366"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('lng') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="-mt-3 text-xs text-muted">Współrzędne można odczytać np. z Google Maps (kliknij prawym przyciskiem na mapie i skopiuj współrzędne).</p>

        <div>
            <label for="phone" class="mb-1 block text-sm font-bold">Telefon (opcjonalnie)</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $point->phone) }}"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="url" class="mb-1 block text-sm font-bold">Link (opcjonalnie)</label>
            <input type="text" id="url" name="url" value="{{ old('url', $point->url) }}" placeholder="np. https://organizacja.pl"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-1 block text-sm font-bold">Opis (opcjonalnie)</label>
            <textarea id="description" name="description" rows="3"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('description', $point->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                <input type="number" id="order" name="order" min="0" value="{{ old('order', $point->order) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-bold">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $point->exists ? $point->is_published : true))
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    Widoczny publicznie
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.mapa-pomocy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
