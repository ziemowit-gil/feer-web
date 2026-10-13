@extends('admin.layout')

@section('title', $galleryImage->exists ? 'Edytuj zdjęcie' : 'Nowe zdjęcie')

@section('content')
    <form method="POST" action="{{ $galleryImage->exists ? route('admin.galeria.update', $galleryImage) : route('admin.galeria.store') }}"
        enctype="multipart/form-data" class="max-w-2xl space-y-5 p-6 rounded-md bg-white">
        @csrf
        @if ($galleryImage->exists) @method('PUT') @endif

        @include('admin.partials.library-image-picker', [
            'name' => 'image',
            'label' => 'Zdjęcie',
            'current' => $galleryImage->exists ? $galleryImage->image_url : null,
            'required' => ! $galleryImage->exists,
            'help' => 'Wgraj plik z dysku albo wybierz zdjęcie wgrane wcześniej do biblioteki plików.',
        ])

        <div>
            <label for="caption" class="mb-1 block text-sm font-bold">Podpis</label>
            <input type="text" id="caption" name="caption" value="{{ old('caption', $galleryImage->caption) }}"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $galleryImage->order) }}"
                class="w-28 rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.galeria.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
