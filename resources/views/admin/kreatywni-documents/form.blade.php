@extends('admin.layout')

@section('title', $document->exists ? 'Edytuj dokument – Kreatywni' : 'Nowy dokument – Kreatywni')

{{--
    Formularz dokumentu z modułu „Dokumenty – Kreatywni”: nazwa, opis, plik (pierwszeństwo), strona serwisu albo adres,
    widoczność. Kolejność wyboru celu opisana przy polach.
--}}
@section('content')
    @php
        $field = 'w-full rounded-lg border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $label = 'mb-1 block text-sm font-bold text-ink';
        $hint = 'mt-1 text-xs text-muted';
        $meta = $document->exists ? $document->fileMeta() : null;
        $invalid = fn (string $name) => $errors->has($name) ? 'aria-invalid=true' : '';
        $pageSel = (int) old('page_id', $document->page_id);
        $mimes = strtoupper(implode(', ', \App\Models\KreatywniDocument::FILE_MIMES));
    @endphp

    <div class="max-w-3xl">
        <a href="{{ route('admin.kreatywni-dokumenty.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy dokumentów
        </a>

        @if ($errors->any())
            <div role="alert" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-800">
                <p class="font-bold">Popraw formularz:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data"
              action="{{ $document->exists ? route('admin.kreatywni-dokumenty.update', $document) : route('admin.kreatywni-dokumenty.store') }}"
              class="mt-3 space-y-5 rounded-md bg-white p-6">
            @csrf
            @if ($document->exists) @method('PUT') @endif

            <div>
                <label for="kd-title" class="{{ $label }}">Nazwa dokumentu <span class="text-red-700" aria-hidden="true">*</span></label>
                <input id="kd-title" name="title" type="text" required maxlength="255" value="{{ old('title', $document->title) }}" class="{{ $field }}" autocomplete="off" aria-describedby="kd-title-hint" {{ $invalid('title') }}>
                <p id="kd-title-hint" class="{{ $hint }}">Np. „Statut Fundacji”. To napis linku na stronie „O nas”.</p>
            </div>

            <div>
                <label for="kd-desc" class="{{ $label }}">Krótki opis</label>
                <textarea id="kd-desc" name="description" rows="2" maxlength="500" class="{{ $field }}" aria-describedby="kd-desc-hint" {{ $invalid('description') }}>{{ old('description', $document->description) }}</textarea>
                <p id="kd-desc-hint" class="{{ $hint }}">Opcjonalnie, jedno zdanie pod nazwą, np. „Cele, sposoby działania i władze Fundacji”.</p>
            </div>

            <fieldset class="space-y-4">
                <legend class="text-base font-bold text-ink">Dokąd prowadzi dokument</legend>
                <p class="text-xs text-muted">Wystarczy jedno. Gdy uzupełnisz kilka, liczy się plik, potem strona, na końcu adres.</p>

                <div class="space-y-2">
                    @if ($meta)
                        <p class="text-sm text-ink"><i class="fa-solid fa-file-arrow-down mr-1 text-muted" aria-hidden="true"></i>Obecny plik: <a href="{{ $document->fileUrl() }}" class="font-bold text-brand-dark underline underline-offset-2">pobierz ({{ $meta }})</a></p>
                        <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                            <input type="checkbox" name="remove_file" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                            Usuń plik (dokument poprowadzi wtedy do strony albo adresu)
                        </label>
                    @endif
                    <label for="kd-file" class="{{ $label }}">{{ $meta ? 'Zmień plik' : 'Plik' }}</label>
                    <input id="kd-file" name="file" type="file" accept=".{{ implode(',.', \App\Models\KreatywniDocument::FILE_MIMES) }}" aria-describedby="kd-file-hint" {{ $invalid('file') }}
                           class="block w-full text-sm text-ink file:mr-3 file:min-h-11 file:rounded file:border-0 file:bg-gray-100 file:px-4 file:font-bold file:text-ink hover:file:bg-brand-light">
                    <p id="kd-file-hint" class="{{ $hint }}">{{ $mimes }}, do {{ intdiv(\App\Models\KreatywniDocument::FILE_MAX_KB, 1024) }} MB. Najlepiej dostępny PDF (z tekstem, nie skanem). Na stronie przy nazwie pokaże się typ i rozmiar pliku.</p>
                </div>

                <div>
                    <label for="kd-page" class="{{ $label }}">Strona serwisu</label>
                    <select id="kd-page" name="page_id" class="{{ $field }}" aria-describedby="kd-page-hint" {{ $invalid('page_id') }}>
                        <option value="">— bez strony —</option>
                        @foreach ($pages as $p)
                            <option value="{{ $p->id }}" @selected($pageSel === $p->id)>{{ $p->title }}{{ $p->is_published ? '' : ' (szkic)' }}</option>
                        @endforeach
                    </select>
                    <p id="kd-page-hint" class="{{ $hint }}">Dla dokumentu z pełnym tekstem na stronie, np. „Polityka prywatności”. Szkic strony pokaże się na liście dopiero po opublikowaniu.</p>
                </div>

                <div>
                    <label for="kd-url" class="{{ $label }}">Adres</label>
                    <input id="kd-url" name="url" type="text" maxlength="500" inputmode="url" value="{{ old('url', $document->url) }}" class="{{ $field }}" aria-describedby="kd-url-hint" {{ $invalid('url') }}>
                    <p id="kd-url-hint" class="{{ $hint }}">Np. dokument w innym serwisie: „https://…”, albo strona w tej witrynie: „/rodo”.</p>
                </div>
            </fieldset>

            <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $document->is_active ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Pokazuj na stronie</span> <span class="text-muted">— odznacz, żeby ukryć dokument bez usuwania</span></span>
            </label>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ $document->exists ? 'Zapisz' : 'Dodaj dokument' }}
                </button>
                <a href="{{ route('admin.kreatywni-dokumenty.index') }}" class="inline-flex min-h-11 items-center rounded bg-gray-100 px-5 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
