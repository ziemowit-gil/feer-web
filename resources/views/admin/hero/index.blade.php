@extends('admin.layout')

@section('title', 'Slidery')

@section('content')

    @include('admin.hero.partials.mission-panel', ['slideCount' => $heroSlides->count()])

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.hero.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj slajd
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($heroSlides as $slide)
            <div class="overflow-hidden rounded-md bg-white">
                <img src="{{ $slide->image_url }}" alt="" class="h-36 w-full object-cover">
                <div class="p-4">
                    <div class="text-xs font-bold uppercase text-muted">Kolejność: {{ $slide->order }}</div>
                    <h3 class="mt-1 font-bold">{{ $slide->title }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ $slide->text }}</p>

                    <div class="mt-3 flex items-center gap-3 border-t border-gray-100 pt-3">
                        <a href="{{ route('admin.hero.edit', $slide) }}" class="text-sm font-bold text-brand-dark hover:text-ink">Edytuj</a>
                        <form method="POST" action="{{ route('admin.hero.destroy', $slide) }}" onsubmit="return confirm('Usunąć ten slajd?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-bold text-muted hover:text-red-600">Usuń</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">Brak slajdów. Dodaj pierwszy powyżej.</p>
        @endforelse
    </div>
@endsection
