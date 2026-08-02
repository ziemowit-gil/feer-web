@extends('admin.layout')

@section('title', $heroSlide->exists ? 'Edytuj slajd' : 'Nowy slajd')

@section('content')
    <form method="POST" action="{{ $heroSlide->exists ? route('admin.hero.update', $heroSlide) : route('admin.hero.store') }}"
        enctype="multipart/form-data" class="max-w-2xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($heroSlide->exists) @method('PUT') @endif

        @if ($heroSlide->exists)
            <div>
                <p class="mb-1 text-sm font-bold">Obecne zdjęcie</p>
                <img src="{{ $heroSlide->image_url }}" alt="{{ $heroSlide->title ?: 'Aktualne zdjęcie slajdu' }}" class="h-32 w-full max-w-xs rounded object-cover">
            </div>
        @endif

        <div>
            <label for="image" class="mb-1 block text-sm font-bold">{{ $heroSlide->exists ? 'Zmień zdjęcie' : 'Zdjęcie' }}</label>
            <input type="file" id="image" name="image" accept="image/*" {{ $heroSlide->exists ? '' : 'required' }}
                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
            <input type="text" id="title" name="title" value="{{ old('title', $heroSlide->title) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="text" class="mb-1 block text-sm font-bold">Opis</label>
            <textarea id="text" name="text" rows="3"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('text', $heroSlide->text) }}</textarea>
            @error('text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="button_label" class="mb-1 block text-sm font-bold">Etykieta przycisku</label>
                <input type="text" id="button_label" name="button_label" value="{{ old('button_label', $heroSlide->button_label) }}" placeholder="np. Dowiedz się więcej"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('button_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="button_url" class="mb-1 block text-sm font-bold">Link przycisku</label>
                <input type="text" id="button_url" name="button_url" value="{{ old('button_url', $heroSlide->button_url) }}" placeholder="np. /projekty lub https://..."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('button_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="-mt-3 text-xs text-muted">Zostaw puste, jeśli slajd nie ma mieć przycisku.</p>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                <input type="number" id="order" name="order" min="0" value="{{ old('order', $heroSlide->order) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>

            <div>
                <label for="duration" class="mb-1 block text-sm font-bold">Czas wyświetlania (s)</label>
                <input type="number" id="duration" name="duration" min="1" max="60"
                    value="{{ old('duration', $heroSlide->duration) }}" placeholder="6"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Domyślnie 6 sekund. Zostaw puste, by użyć domyślnej.</p>
                @error('duration') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.hero.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
