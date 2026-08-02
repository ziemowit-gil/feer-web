@extends('admin.layout')

@section('title', 'Slajder (hero)')

@section('content')

    {{-- Panel: slajd z misją --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-bold text-ink">Misja organizacji jako pierwszy slajd</p>
                <p class="mt-0.5 text-sm text-muted">Gdy włączone, przed właściwymi slajdami wyświetlany jest automatyczny slajd z misją i logotypem organizacji (treść z zakładki Ustawienia → Strona główna).</p>
            </div>
            <form method="POST" action="{{ route('admin.hero.mission-slide') }}" id="mission-slide-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="hero_mission_slide" value="0">
                <label class="flex cursor-pointer items-center gap-2.5">
                    <input type="checkbox" name="hero_mission_slide" value="1"
                        {{ $siteSettings->hero_mission_slide ? 'checked' : '' }}
                        onchange="this.form.submit()"
                        class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand"
                        aria-label="Włącz slajd z misją organizacji">
                    <span class="text-sm font-bold {{ $siteSettings->hero_mission_slide ? 'text-brand' : 'text-muted' }}">
                        {{ $siteSettings->hero_mission_slide ? 'Włączony' : 'Wyłączony' }}
                    </span>
                </label>
            </form>
        </div>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.hero.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj slajd
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($heroSlides as $slide)
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <img src="{{ $slide->image_url }}" alt="" class="h-36 w-full object-cover">
                <div class="p-4">
                    <div class="text-xs font-bold uppercase text-muted">Kolejność: {{ $slide->order }}</div>
                    <h3 class="mt-1 font-bold">{{ $slide->title }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ $slide->text }}</p>

                    <div class="mt-3 flex items-center gap-3 border-t border-gray-100 pt-3">
                        <a href="{{ route('admin.hero.edit', $slide) }}" class="text-sm font-bold text-brand hover:text-brand-dark">Edytuj</a>
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
