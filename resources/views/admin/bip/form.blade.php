@extends('admin.layout')

@section('title', $document->exists ? 'Edytuj dokument BIP — ' . $document->title : 'Nowy dokument BIP')

@section('content')
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Popraw poniższe błędy:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div role="status" class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST"
        action="{{ $document->exists ? route('admin.bip-dokumenty.update', $document) : route('admin.bip-dokumenty.store') }}"
        enctype="multipart/form-data"
        x-data="{ slugManual: {{ $document->exists ? 'true' : 'false' }} }"
        class="space-y-6">
        @csrf
        @if ($document->exists) @method('PUT') @endif

        <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-5">

            {{-- Tytuł i slug --}}
            <div>
                <label for="title" class="mb-1 block text-sm font-bold">Tytuł <span class="text-red-500" aria-hidden="true">*</span></label>
                <input type="text" id="title" name="title" required maxlength="255"
                    value="{{ old('title', $document->title) }}"
                    @input="if (!slugManual) { document.getElementById('slug').value = $event.target.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'') }"
                    class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
            </div>

            <div>
                <label for="slug" class="mb-1 block text-sm font-bold">
                    Adres URL (slug)
                    @if (! $document->exists)
                        <span class="ml-1 text-xs font-normal text-muted">— zostanie wygenerowany z tytułu</span>
                    @endif
                </label>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted">/bip/</span>
                    <input type="text" id="slug" name="slug" maxlength="255"
                        value="{{ old('slug', $document->slug) }}"
                        @input="slugManual = true"
                        class="flex-1 rounded border-gray-300 font-mono text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                </div>
                @if ($document->exists)
                    <p class="mt-1 text-xs text-muted">
                        Zmiana adresu URL spowoduje uszkodzenie istniejących odsyłaczy zewnętrznych.
                    </p>
                @endif
            </div>

            {{-- Kategoria --}}
            <div>
                <label for="category" class="mb-1 block text-sm font-bold">Kategoria BIP <span class="text-red-500" aria-hidden="true">*</span></label>
                <select id="category" name="category" required
                    class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                    @foreach (\App\Models\BipDocument::CATEGORIES as $value => $label)
                        <option value="{{ $value }}" {{ old('category', $document->category ?? 'other') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Skrót / opis --}}
            <div>
                <label for="summary" class="mb-1 block text-sm font-bold">Skrót / opis</label>
                <textarea id="summary" name="summary" rows="3" maxlength="1000"
                    class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">{{ old('summary', $document->summary) }}</textarea>
                <p class="mt-1 text-xs text-muted">Krótki opis widoczny na liście dokumentów BIP (max. 1000 znaków).</p>
            </div>

            {{-- Kolejność i widoczność --}}
            <div class="flex flex-wrap items-end gap-6">
                <div>
                    <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                    <input type="number" id="order" name="order" min="0" value="{{ old('order', $document->order ?? 0) }}"
                        class="w-24 rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                    <p class="mt-1 text-xs text-muted">Mniejsza liczba = wyżej w kategorii.</p>
                </div>
                <label class="flex items-center gap-2 pb-2">
                    <input type="checkbox" name="is_published" value="1"
                        {{ old('is_published', $document->is_published ?? true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
                    <span class="text-sm font-bold">Widoczny na stronie BIP</span>
                </label>
            </div>
        </div>

        {{-- Treść --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-bold text-ink">Treść dokumentu</h2>
            @include('admin.partials.editor', [
                'name' => 'content',
                'value' => old('content', $document->content),
                'revisionable' => $document->exists ? ['type' => 'bip_document', 'id' => $document->id] : [],
            ])
        </div>

        {{-- Pliki załączone --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Pliki do pobrania</legend>

            @if ($document->exists && $document->attachedFiles()->isNotEmpty())
                <ul class="mb-4 space-y-1">
                    @foreach ($document->attachedFiles() as $media)
                        <li class="flex items-center justify-between gap-3 rounded border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 text-brand hover:text-brand-dark">
                                <i class="fa-solid {{ $document->fileIcon($media) }}" aria-hidden="true"></i>
                                {{ $media->file_name }}
                                <span class="text-xs text-muted">({{ $media->human_readable_size }})</span>
                            </a>
                            <label class="flex items-center gap-1.5 text-xs text-muted">
                                <input type="checkbox" name="remove_files[]" value="{{ $media->id }}"
                                    class="rounded border-gray-300 text-red-600 focus-visible:ring-2 focus-visible:ring-red-600">
                                Usuń
                            </label>
                        </li>
                    @endforeach
                </ul>
            @endif

            <label for="files" class="mb-1 block text-sm font-bold">
                {{ ($document->exists && $document->attachedFiles()->isNotEmpty()) ? 'Dodaj kolejne pliki' : 'Dodaj pliki' }}
            </label>
            <input type="file" id="files" name="files[]" multiple
                accept=".pdf,.doc,.docx,.odt,.xls,.xlsx,.ods,.csv,.zip,.jpg,.jpeg,.png,.webp"
                class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            <p class="mt-1 text-xs text-muted">
                Dopuszczalne formaty: PDF, Word, Excel, ODS, CSV, ZIP, obrazy. Każdy plik do 10 MB.
            </p>
        </fieldset>

        {{-- Metadane BIP (informacja o autorze i datach — wymóg ustawowy) --}}
        @if ($document->exists)
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-muted space-y-1">
                <p class="font-bold text-ink mb-2">Metadane BIP (wymagane przepisami)</p>
                <p>
                    <span class="font-semibold">Wprowadził/-a:</span>
                    {{ $document->creator?->name ?? '—' }}
                </p>
                <p>
                    <span class="font-semibold">Data dodania:</span>
                    {{ $document->created_at?->format('d.m.Y H:i') ?? '—' }}
                </p>
                <p>
                    <span class="font-semibold">Ostatnia zmiana:</span>
                    {{ $document->updated_at?->format('d.m.Y H:i') ?? '—' }}
                    @if ($document->updater)
                        przez <strong>{{ $document->updater->name }}</strong>
                    @endif
                </p>
                <p class="mt-2">
                    <a href="{{ route('admin.historia.index', ['type' => 'bip_document', 'id' => $document->id]) }}"
                        class="inline-flex items-center gap-1.5 text-brand hover:text-brand-dark">
                        <i class="fa-solid fa-clock-rotate-left text-xs" aria-hidden="true"></i>
                        Historia wszystkich zmian
                    </a>
                </p>
            </div>
        @endif

        <div class="flex items-center gap-3 border-t border-gray-100 pt-2">
            <button type="submit"
                class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Zapisz
            </button>
            <a href="{{ route('admin.bip-dokumenty.index') }}"
                class="rounded text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                Anuluj
            </a>
        </div>
    </form>
@endsection
