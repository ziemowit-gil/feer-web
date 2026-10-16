@extends('admin.layout')

@section('title', $heroSlide->exists ? 'Edytuj slajd' : 'Nowy slajd')

@section('content')
    <form method="POST" action="{{ $heroSlide->exists ? route('admin.hero.update', $heroSlide) : route('admin.hero.store') }}"
        enctype="multipart/form-data" class="max-w-2xl space-y-5 p-6 rounded-md bg-white">
        @csrf
        @if ($heroSlide->exists) @method('PUT') @endif
        @if ($heroSlide->slider_id)<input type="hidden" name="slider_id" value="{{ $heroSlide->slider_id }}">@endif

        @include('admin.partials.library-image-picker', [
            'name' => 'image',
            'label' => 'Zdjęcie slajdu',
            'current' => $heroSlide->exists ? $heroSlide->image_url : null,
            'required' => false,
            'help' => 'Wgraj plik z dysku albo wybierz zdjęcie wgrane wcześniej do biblioteki plików.',
        ])

        @include('admin.partials.unsplash-picker', ['initialQuery' => $heroSlide->title, 'titleFieldId' => 'title'])

        <div>
            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
            <input type="text" id="title" name="title" value="{{ old('title', $heroSlide->title) }}" required
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="text" class="mb-1 block text-sm font-bold">Opis</label>
            <textarea id="text" name="text" rows="3"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">{{ old('text', $heroSlide->text) }}</textarea>
            @error('text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="button_label" class="mb-1 block text-sm font-bold">Etykieta przycisku</label>
                <input type="text" id="button_label" name="button_label" value="{{ old('button_label', $heroSlide->button_label) }}" placeholder="np. Dowiedz się więcej"
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                @error('button_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="button_url" class="mb-1 block text-sm font-bold">Link przycisku</label>
                <input type="text" id="button_url" name="button_url" value="{{ old('button_url', $heroSlide->button_url) }}" placeholder="np. /projekty lub https://..."
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                @error('button_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="-mt-3 text-xs text-muted">Zostaw puste, jeśli slajd nie ma mieć przycisku.</p>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                <input type="number" id="order" name="order" min="0" value="{{ old('order', $heroSlide->order) }}"
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            </div>

            <div>
                <label for="duration" class="mb-1 block text-sm font-bold">Czas wyświetlania (s)</label>
                <input type="number" id="duration" name="duration" min="1" max="60"
                    value="{{ old('duration', $heroSlide->duration) }}" placeholder="6"
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                <p class="mt-1 text-xs text-muted">Domyślnie 6 sekund. Zostaw puste, by użyć domyślnej.</p>
                @error('duration') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Termin wyświetlania: poza nim slajd znika ze strony sam (np. nabór, wydarzenie). --}}
        <fieldset class="rounded-md bg-gray-50 p-4">
            <legend class="px-1 text-sm font-bold">Widoczny w terminie <span class="font-normal text-muted">(opcjonalnie)</span></legend>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="starts_at" class="mb-1 block text-sm font-bold">Od</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $heroSlide->starts_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                    @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ends_at" class="mb-1 block text-sm font-bold">Do</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $heroSlide->ends_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                    @error('ends_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="mt-2 text-xs text-muted">Puste pola = slajd widoczny zawsze. Przed datą „Od” i po dacie „Do” slajd nie pokazuje się na stronie, ale zostaje w sliderze.</p>
        </fieldset>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ $heroSlide->slider_id && Route::has('admin.slidery.show') ? route('admin.slidery.show', $heroSlide->slider_id) : route('admin.hero.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
