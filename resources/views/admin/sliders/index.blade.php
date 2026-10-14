@extends('admin.layout')

@section('title', 'Slidery')

@section('content')
    @php $btn = 'inline-flex min-h-11 items-center gap-2 rounded-md px-4 text-sm font-bold'; @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-bold text-ink">Slidery</h1>
            <p class="mt-1 text-sm text-ink">
                Zestawy slajdów (zdjęcie, tytuł, tekst, przycisk). Każdy slider pokazujesz w jednym albo kilku miejscach:
                na stronie głównej, na blogu albo na wybranych stronach (w edycji strony → Typ i układ → „Slider nad treścią”).
            </p>
        </div>
        <form method="POST" action="{{ route('admin.slidery.store') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <div>
                <label for="slider-name" class="mb-1 block text-sm font-bold text-ink">Nowy slider</label>
                <input id="slider-name" name="name" type="text" required maxlength="120" placeholder="np. Blog, Szkolenia"
                    class="rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
            </div>
            <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink"><i class="fa-solid fa-plus" aria-hidden="true"></i> Utwórz</button>
        </form>
    </div>
    @error('name') <p class="mb-4 text-sm text-red-700">{{ $message }}</p> @enderror

    @if ($sliders->isEmpty())
        <div class="rounded-md bg-white p-10 text-center">
            <p class="font-bold text-ink">Nie ma jeszcze żadnego slidera.</p>
            <p class="mt-1 text-sm text-ink">Utwórz pierwszy — pierwszy slider automatycznie trafia na stronę główną.</p>
        </div>
    @else
        <ul class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" role="list">
            @foreach ($sliders as $slider)
                <li class="flex flex-col rounded-md bg-white">
                    <div class="flex gap-1 overflow-hidden rounded-t-md bg-gray-100 p-2" aria-hidden="true">
                        @forelse ($slider->slides->take(4) as $slide)
                            <span class="h-20 flex-1 overflow-hidden rounded bg-gray-200">@if ($slide->image_url)<img src="{{ $slide->image_url }}" alt="" class="h-full w-full object-cover" loading="lazy">@endif</span>
                        @empty
                            <span class="flex h-20 flex-1 items-center justify-center text-sm text-muted">Brak slajdów</span>
                        @endforelse
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <h2 class="text-lg font-bold text-ink">{{ $slider->name }}</h2>
                        <p class="text-sm text-ink">{{ $slider->slides->count() }} {{ trans_choice('slajd|slajdy|slajdów', $slider->slides->count()) }}</p>
                        <p class="mt-1 text-sm text-ink"><i class="fa-solid fa-location-dot mr-1 text-brand-dark" aria-hidden="true"></i>{{ $slider->placementLabel() }}</p>
                        <div class="mt-auto flex flex-wrap gap-2 pt-4">
                            <a href="{{ route('admin.slidery.show', $slider) }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">Zarządzaj<span class="sr-only"> sliderem {{ $slider->name }}</span></a>
                            <form method="POST" action="{{ route('admin.slidery.kopiuj', $slider) }}"
                                data-confirm="Skopiować slider „{{ $slider->name }}” razem ze wszystkimi slajdami i zdjęciami? Kopia nie będzie nigdzie wyświetlana, dopóki jej nie przypiszesz.">
                                @csrf
                                <button type="submit" class="{{ $btn }} border-2 border-brand-dark bg-white text-brand-dark hover:bg-gray-100"><i class="fa-solid fa-copy" aria-hidden="true"></i> Kopiuj<span class="sr-only"> slider {{ $slider->name }}</span></button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
