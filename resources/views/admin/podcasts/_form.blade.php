@php $podcast ??= null; @endphp

@if ($errors->any())
    <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Sekcja: podstawowe informacje --}}
<div class="space-y-5 p-6 rounded-md bg-white">
    <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Informacje o odcinku</h2>

    <div class="grid gap-5 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="title" class="mb-1 block text-sm font-bold">Tytuł <span aria-hidden="true" class="text-red-500">*</span></label>
            <input type="text" id="title" name="title" value="{{ old('title', $podcast?->title) }}" required
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"
                placeholder="np. Empatia w szkole – rozmowa z pedagogiem">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="episode_number" class="mb-1 block text-sm font-bold">Nr odcinka</label>
            <input type="text" id="episode_number" name="episode_number" value="{{ old('episode_number', $podcast?->episode_number) }}"
                placeholder="np. 12 lub S2E05"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('episode_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
        <div class="flex items-center gap-2">
            <span class="shrink-0 text-sm text-muted">/podcasty/</span>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $podcast?->slug) }}"
                placeholder="zostanie wygenerowany z tytułu"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
        </div>
        @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="mb-1 block text-sm font-bold">Opis odcinka</label>
        <textarea id="description" name="description" rows="5"
            class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"
            placeholder="O czym jest ten odcinek? Kto gości? Jakie tematy poruszacie?">{{ old('description', $podcast?->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Sekcja: publikacja --}}
<div class="space-y-5 p-6 rounded-md bg-white">
    <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Publikacja</h2>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="published_at" class="mb-1 block text-sm font-bold">Data i godzina publikacji</label>
            <input type="datetime-local" id="published_at" name="published_at"
                value="{{ old('published_at', $podcast?->published_at?->format('Y-m-d\TH:i')) }}"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('published_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-3 pt-1 sm:pt-7">
            <label class="flex cursor-pointer items-center gap-3">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1"
                    class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"
                    {{ old('is_published', $podcast?->is_published) ? 'checked' : '' }}>
                <span class="text-sm font-bold">Opublikowany</span>
            </label>

            <label class="flex cursor-pointer items-start gap-3">
                <input type="hidden" name="is_premium" value="0">
                <input type="checkbox" name="is_premium" value="1"
                    class="mt-0.5 h-4 w-4 rounded border-gray-500 text-amber-500 focus:ring-amber-400"
                    {{ old('is_premium', $podcast?->is_premium) ? 'checked' : '' }}>
                <span class="text-sm font-bold">Premium
                    <span class="block font-normal text-muted">Wymaga aktywnej subskrypcji lub zakupu odcinka.</span>
                </span>
            </label>
        </div>
    </div>
</div>

{{-- Sekcja: multimedia --}}
<div class="space-y-5 p-6 rounded-md bg-white">
    <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Multimedia</h2>

    {{-- Plik audio --}}
    <div>
        <label for="audio" class="mb-1 block text-sm font-bold">Plik audio</label>

        @if ($podcast?->getFirstMedia('audio'))
            @php $audioMedia = $podcast->getFirstMedia('audio'); @endphp
            <div class="mb-3 flex items-center gap-3 px-4 py-3 rounded-md bg-gray-50">
                <i class="fa-solid fa-headphones text-lg text-brand-dark" aria-hidden="true"></i>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-ink">{{ $audioMedia->file_name }}</p>
                    <p class="text-xs text-muted">{{ number_format($audioMedia->size / 1048576, 1) }} MB</p>
                </div>
                <a href="{{ $podcast->getFirstMediaUrl('audio') }}" target="_blank" rel="noopener"
                    class="shrink-0 text-xs text-brand-dark hover:text-ink">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Odsłuchaj
                </a>
            </div>
        @endif

        <input type="file" id="audio" name="audio" accept="audio/*"
            class="block w-full cursor-pointer text-sm text-muted
                   file:mr-3 file:cursor-pointer file:rounded file:border-0
                   file:bg-brand-dark file:px-4 file:py-2 file:text-sm file:font-bold file:text-white
                   hover:file:bg-brand-dark">
        <p class="mt-1.5 text-xs text-muted">
            {{ $podcast?->getFirstMedia('audio') ? 'Wybierz nowy plik, aby zastąpić obecny.' : 'Maks. 200 MB.' }}
            Formaty: MP3, M4A, OGG, WAV.
        </p>
        @error('audio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Okładka --}}
    <div>
        <label for="cover" class="mb-1 block text-sm font-bold">Okładka odcinka</label>

        @if ($podcast?->getFirstMediaUrl('cover'))
            <div class="mb-3 flex items-center gap-3">
                <img src="{{ $podcast->getFirstMediaUrl('cover') }}"
                    alt="Aktualna okładka: {{ $podcast->title }}"
                    class="h-20 w-20 rounded-lg border border-gray-200 object-cover shadow-sm">
                <p class="text-xs text-muted">Aktualna okładka. Wybierz plik poniżej, aby ją zmienić.</p>
            </div>
        @endif

        <input type="file" id="cover" name="cover" accept="image/*"
            class="block w-full cursor-pointer text-sm text-muted
                   file:mr-3 file:cursor-pointer file:rounded file:border-0
                   file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-bold file:text-ink
                   hover:file:bg-gray-200">
        <p class="mt-1.5 text-xs text-muted">Maks. 4 MB. Format JPG, PNG lub WebP. Zalecany kwadrat (np. 1400×1400 px).</p>
        @error('cover') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
