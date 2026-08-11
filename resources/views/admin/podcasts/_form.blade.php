@php $podcast ??= null; @endphp

@if ($errors->any())
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label for="title" class="mb-1 block text-sm font-medium text-ink">Tytuł <span aria-hidden="true" class="text-red-500">*</span></label>
    <input type="text" id="title" name="title" value="{{ old('title', $podcast?->title) }}" required
        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
</div>

<div>
    <label for="slug" class="mb-1 block text-sm font-medium text-ink">Slug</label>
    <input type="text" id="slug" name="slug" value="{{ old('slug', $podcast?->slug) }}"
        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
    <p class="mt-1 text-xs text-muted">Pozostaw puste — zostanie wygenerowany automatycznie z tytułu.</p>
</div>

<div>
    <label for="episode_number" class="mb-1 block text-sm font-medium text-ink">Numer odcinka</label>
    <input type="text" id="episode_number" name="episode_number" value="{{ old('episode_number', $podcast?->episode_number) }}"
        placeholder="np. 12 albo S2E05"
        class="w-48 rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
</div>

<div>
    <label for="description" class="mb-1 block text-sm font-medium text-ink">Opis odcinka</label>
    <textarea id="description" name="description" rows="4"
        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">{{ old('description', $podcast?->description) }}</textarea>
</div>

<div>
    <label for="published_at" class="mb-1 block text-sm font-medium text-ink">Data publikacji</label>
    <input type="datetime-local" id="published_at" name="published_at"
        value="{{ old('published_at', $podcast?->published_at?->format('Y-m-d\TH:i')) }}"
        class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
</div>

<div class="flex gap-6">
    <label class="flex cursor-pointer items-center gap-2">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1" class="rounded border-gray-300 text-brand focus:ring-brand"
            {{ old('is_published', $podcast?->is_published) ? 'checked' : '' }}>
        <span class="text-sm font-medium text-ink">Opublikowany</span>
    </label>

    <label class="flex cursor-pointer items-center gap-2">
        <input type="hidden" name="is_premium" value="0">
        <input type="checkbox" name="is_premium" value="1" class="rounded border-gray-300 text-brand focus:ring-brand"
            {{ old('is_premium', $podcast?->is_premium) ? 'checked' : '' }}>
        <span class="text-sm font-medium text-ink">Premium (wymagana subskrypcja)</span>
    </label>
</div>

<div>
    <label for="audio" class="mb-1 block text-sm font-medium text-ink">Plik audio (MP3)</label>
    @if ($podcast?->getFirstMediaUrl('audio'))
        <p class="mb-1 text-xs text-muted">
            Aktualny plik: <a href="{{ $podcast->getFirstMediaUrl('audio') }}" target="_blank" rel="noopener" class="text-brand underline">pobierz</a>
        </p>
    @endif
    <input type="file" id="audio" name="audio" accept="audio/*"
        class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
    <p class="mt-1 text-xs text-muted">Maks. 200 MB. Formaty: MP3, MP4, OGG, WAV, M4A.</p>
</div>

<div>
    <label for="cover" class="mb-1 block text-sm font-medium text-ink">Okładka odcinka</label>
    @if ($podcast?->getFirstMediaUrl('cover'))
        <img src="{{ $podcast->getFirstMediaUrl('cover') }}" alt="" class="mb-2 h-24 w-24 rounded object-cover">
    @endif
    <input type="file" id="cover" name="cover" accept="image/*"
        class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
    <p class="mt-1 text-xs text-muted">Maks. 4 MB. Format JPG, PNG lub WebP.</p>
</div>
