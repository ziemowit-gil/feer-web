@extends('admin.layout')

@section('title', $person->exists ? 'Edytuj osobę – Kreatywni' : 'Nowa osoba – Kreatywni')

{{-- Formularz osoby z modułu „Osoby – Kreatywni”: imię, rola, opis, zdjęcie z opisem alternatywnym, widoczność. --}}
@section('content')
    @php
        $field = 'w-full rounded-lg border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $label = 'mb-1 block text-sm font-bold text-ink';
        $photo = $person->exists ? $person->photoUrl() : null;
    @endphp

    <div class="max-w-3xl">
        <a href="{{ route('admin.kreatywni-osoby.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy osób
        </a>

        @if ($errors->any())
            <div role="alert" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-800">
                <p class="font-bold">Popraw formularz:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data"
              action="{{ $person->exists ? route('admin.kreatywni-osoby.update', $person) : route('admin.kreatywni-osoby.store') }}"
              class="mt-3 space-y-5 rounded-md bg-white p-6">
            @csrf
            @if ($person->exists) @method('PUT') @endif

            <div>
                <label for="kp-name" class="{{ $label }}">Imię i nazwisko <span class="text-red-700" aria-hidden="true">*</span></label>
                <input id="kp-name" name="name" type="text" required maxlength="255" value="{{ old('name', $person->name) }}" class="{{ $field }}" autocomplete="off" @error('name') aria-invalid="true" @enderror>
            </div>

            <div>
                <label for="kp-role" class="{{ $label }}">Rola</label>
                <input id="kp-role" name="role" type="text" maxlength="255" value="{{ old('role', $person->role) }}" class="{{ $field }}" aria-describedby="kp-role-hint">
                <p id="kp-role-hint" class="mt-1 text-xs text-muted">Np. „Terapeuta zajęciowy, koordynator zajęć”. Pokazuje się pod imieniem.</p>
            </div>

            <div>
                <label for="kp-bio" class="{{ $label }}">Opis</label>
                <textarea id="kp-bio" name="bio" rows="7" maxlength="4000" class="{{ $field }}">{{ old('bio', $person->bio) }}</textarea>
            </div>

            <fieldset class="space-y-3">
                <legend class="{{ $label }}">Zdjęcie</legend>
                @if ($photo)
                    <div class="flex items-center gap-3">
                        <img src="{{ $photo }}" alt="" class="h-20 w-20 rounded-full object-cover">
                        <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                            <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                            Usuń zdjęcie (zamiast niego będą inicjały)
                        </label>
                    </div>
                @endif
                <div>
                    <label for="kp-photo" class="mb-1 block text-xs font-bold text-muted">{{ $photo ? 'Zmień zdjęcie' : 'Dodaj zdjęcie' }} (JPG, PNG lub WEBP, do 5 MB)</label>
                    <input id="kp-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-ink file:mr-3 file:min-h-11 file:rounded file:border-0 file:bg-gray-100 file:px-4 file:font-bold file:text-ink hover:file:bg-brand-light">
                </div>
                <div>
                    <label for="kp-alt" class="mb-1 block text-xs font-bold text-muted">Opis zdjęcia dla czytników ekranu</label>
                    <input id="kp-alt" name="photo_alt" type="text" maxlength="255" value="{{ old('photo_alt', $person->photo_alt) }}" class="{{ $field }}" aria-describedby="kp-alt-hint">
                    <p id="kp-alt-hint" class="mt-1 text-xs text-muted">Puste pole = zdjęcie dekoracyjne (imię jest obok). Opisz, gdy zdjęcie wnosi coś więcej, np. „Anna podczas warsztatów z filcu”.</p>
                </div>
            </fieldset>

            <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $person->is_active ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Pokazuj na stronie</span> <span class="text-muted">— odznacz, żeby ukryć osobę bez usuwania</span></span>
            </label>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ $person->exists ? 'Zapisz' : 'Dodaj osobę' }}
                </button>
                <a href="{{ route('admin.kreatywni-osoby.index') }}" class="inline-flex min-h-11 items-center rounded bg-gray-100 px-5 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
