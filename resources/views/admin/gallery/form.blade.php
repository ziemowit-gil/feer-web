@extends('admin.layout')

@section('title', $galleryImage->exists ? 'Edytuj zdjęcie' : 'Nowe zdjęcie')

@section('content')
    <form method="POST" action="{{ $galleryImage->exists ? route('admin.galeria.update', $galleryImage) : route('admin.galeria.store') }}"
        enctype="multipart/form-data" class="max-w-2xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($galleryImage->exists) @method('PUT') @endif

        @if ($galleryImage->exists)
            <div>
                <p class="mb-1 text-sm font-bold">Obecne zdjęcie</p>
                <img src="{{ $galleryImage->image_url }}" alt="{{ $galleryImage->caption ?: 'Aktualne zdjęcie w galerii' }}" class="h-32 w-full max-w-xs rounded object-cover">
            </div>
        @endif

        <div>
            <label for="image" class="mb-1 block text-sm font-bold">{{ $galleryImage->exists ? 'Zmień zdjęcie' : 'Zdjęcie' }}</label>
            <input type="file" id="image" name="image" accept="image/*" {{ $galleryImage->exists ? '' : 'required' }}
                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="caption" class="mb-1 block text-sm font-bold">Podpis</label>
            <input type="text" id="caption" name="caption" value="{{ old('caption', $galleryImage->caption) }}"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $galleryImage->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.galeria.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
