@extends('admin.layout')

@section('title', $material->exists ? 'Edytuj materiał' : 'Nowy materiał')

@section('content')
    <form method="POST" action="{{ $material->exists ? route('admin.materialy-edukacyjne.update', $material) : route('admin.materialy-edukacyjne.store') }}"
        enctype="multipart/form-data"
        x-data="{ type: '{{ old('type', $material->type ?: 'video') }}' }"
        class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($material->exists) @method('PUT') @endif

        <div>
            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
            <input type="text" id="title" name="title" value="{{ old('title', $material->title) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-1 block text-sm font-bold">Opis</label>
            <textarea id="description" name="description" rows="4" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('description', $material->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="target_group" class="mb-1 block text-sm font-bold">Dla kogo</label>
            <input type="text" id="target_group" name="target_group" value="{{ old('target_group', $material->target_group) }}" required
                placeholder="np. Nauczyciele szkół podstawowych"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('target_group') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="type" class="mb-1 block text-sm font-bold">Typ materiału</label>
            <select id="type" name="type" x-model="type" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @foreach (\App\Models\EducationalMaterial::TYPES as $value => $label)
                    <option value="{{ $value }}" {{ old('type', $material->type ?: 'video') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="type === 'video'" x-cloak>
            <label for="video_url" class="mb-1 block text-sm font-bold">Link do nagrania</label>
            <input type="url" id="video_url" name="video_url" value="{{ old('video_url', $material->video_url) }}"
                placeholder="https://youtube.com/..."
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('video_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="type === 'pdf' || type === 'scenariusz'" x-cloak>
            @if ($material->exists && $material->fileUrl)
                <p class="mb-1 text-sm font-bold">Obecny plik</p>
                <a href="{{ $material->fileUrl }}" target="_blank" class="mb-2 inline-block text-sm text-brand hover:text-brand-dark">
                    <i class="fa-solid fa-file-pdf"></i> Pobierz obecny plik
                </a>
            @endif
            <label for="file" class="mb-1 block text-sm font-bold">{{ $material->exists && $material->fileUrl ? 'Zmień plik PDF' : 'Plik PDF' }}</label>
            <input type="file" id="file" name="file" accept="application/pdf"
                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $material->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $material->is_published ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Opublikowany</span>
        </label>

        <label class="flex items-start gap-2">
            <input type="checkbox" name="is_archival" value="1" {{ old('is_archival', $material->is_archival ?? false) ? 'checked' : '' }}
                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Materiał archiwalny
                <span class="block font-normal text-muted">Oznaczony na liście plakietką „z dawien dawna”.</span>
            </span>
        </label>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.materialy-edukacyjne.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
