@extends('admin.layout')

@section('title', 'Galeria')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.galeria.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj zdjęcie
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @forelse ($galleryImages as $image)
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <img src="{{ $image->image_url }}" alt="" class="h-32 w-full object-cover">
                <div class="p-3">
                    <div class="text-xs font-bold uppercase text-muted">Kolejność: {{ $image->order }}</div>
                    <p class="mt-1 truncate text-sm">{{ $image->caption ?: '—' }}</p>

                    <div class="mt-3 flex items-center gap-3 border-t border-gray-100 pt-3">
                        <a href="{{ route('admin.galeria.edit', $image) }}" class="text-sm font-bold text-brand hover:text-brand-dark">Edytuj</a>
                        <form method="POST" action="{{ route('admin.galeria.destroy', $image) }}" onsubmit="return confirm('Usunąć to zdjęcie?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-bold text-muted hover:text-red-600">Usuń</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">Brak zdjęć. Dodaj pierwsze powyżej.</p>
        @endforelse
    </div>
@endsection
