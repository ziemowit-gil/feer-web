@extends('admin.layout')

@section('title', 'Slajder (hero)')

@section('content')

    {{-- Panel: slajd z misją --}}
    @php $missionImg = $siteSettings->missionSlideImageUrl(); @endphp
    <div class="mb-6 rounded-lg border border-gray-200 bg-white"
         x-data="{ bg: '{{ $siteSettings->hero_mission_bg ?? 'brand' }}' }">

        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-5 py-4">
            <div>
                <p class="font-bold text-ink">Misja organizacji jako pierwszy slajd</p>
                <p class="mt-0.5 text-sm text-muted">Wyświetlany jako pierwszy slajd przed właściwymi slajdami. Treść misji pochodzi z Ustawień → Strona główna.</p>
            </div>
            <form method="POST" action="{{ route('admin.hero.mission-slide') }}" id="mission-toggle-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="hero_mission_bg" :value="bg">
                <input type="hidden" name="hero_mission_order" value="{{ $siteSettings->hero_mission_order ?? 1 }}">
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

        <form method="POST" action="{{ route('admin.hero.mission-slide') }}" enctype="multipart/form-data" class="px-5 py-4">
            @csrf
            @method('PATCH')
            <input type="hidden" name="hero_mission_slide" value="{{ $siteSettings->hero_mission_slide ? '1' : '0' }}">

            <p class="mb-3 text-sm font-bold text-ink">Tło slajdu</p>
            <div class="flex flex-wrap gap-3">

                {{-- Opcja: kolor marki --}}
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition"
                    :class="bg === 'brand' ? 'border-brand bg-brand-light' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio" name="hero_mission_bg" value="brand"
                        x-model="bg"
                        class="text-brand focus:ring-brand">
                    <span class="inline-block h-5 w-5 rounded-sm bg-brand"></span>
                    <span class="text-sm font-bold">Kolor marki</span>
                </label>

                {{-- Opcja: zdjęcie --}}
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition"
                    :class="bg === 'image' ? 'border-brand bg-brand-light' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio" name="hero_mission_bg" value="image"
                        x-model="bg"
                        class="text-brand focus:ring-brand">
                    <i class="fa-solid fa-image text-gray-400"></i>
                    <span class="text-sm font-bold">Zdjęcie</span>
                </label>
            </div>

            {{-- Upload zdjęcia (widoczny tylko gdy bg=image) --}}
            <div x-show="bg === 'image'" x-cloak class="mt-4 space-y-3">
                @if ($missionImg)
                    <div class="flex items-center gap-3">
                        <img src="{{ $missionImg }}" alt="Aktualne tło slajdu misji" class="h-20 w-36 rounded object-cover">
                        <span class="text-sm text-muted">Aktualne zdjęcie — wgraj nowe, by zastąpić</span>
                    </div>
                @endif
                <div>
                    <label for="hero_mission_image" class="mb-1 block text-sm font-bold text-ink">
                        {{ $missionImg ? 'Zamień zdjęcie' : 'Wgraj zdjęcie tła' }}
                    </label>
                    <input type="file" name="hero_mission_image" id="hero_mission_image"
                        accept="image/*"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Zalecane: min. 1280×500 px, max 4 MB. Napisy będą białe.</p>
                </div>
            </div>

            {{-- Pozycja slajdu misji --}}
            <div class="mt-4">
                <label for="hero_mission_order" class="mb-1 block text-sm font-bold text-ink">
                    Pozycja w slajderze
                </label>
                <div class="flex items-center gap-3">
                    <input type="number" name="hero_mission_order" id="hero_mission_order"
                        min="1" max="{{ $heroSlides->count() + 1 }}"
                        value="{{ $siteSettings->hero_mission_order ?? 1 }}"
                        class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                    <p class="text-sm text-muted">
                        1 = pierwszy slajd, {{ $heroSlides->count() + 1 }} = ostatni
                        @if ($heroSlides->isEmpty())
                            <span class="text-xs">(brak innych slajdów)</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit"
                    class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    Zapisz ustawienia
                </button>
            </div>
        </form>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.hero.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj slajd
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
