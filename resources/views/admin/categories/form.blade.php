@extends('admin.layout')

@section('title', $category->exists ? 'Edytuj kategorię' : 'Nowa kategoria')

@section('content')
    <form method="POST" action="{{ $category->exists ? route('admin.kategorie.update', $category) : route('admin.kategorie.store') }}" class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted">/kategoria/</span>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="zostanie wygenerowany z nazwy"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $category->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.kategorie.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
