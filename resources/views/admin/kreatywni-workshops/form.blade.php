@extends('admin.layout')

@section('title', $workshop->exists ? 'Edytuj warsztat – Kreatywni' : 'Nowy warsztat – Kreatywni')

{{--
    Formularz terminu z modułu „Warsztaty – Kreatywni”: nazwa, miejsce, początek i koniec (czas polski, datetime-local),
    liczba uczestników, opis, grafika ilustracyjna (wgranie albo biblioteka Multimediów) z opisem alternatywnym, widoczność.
    $copyOf — warsztat, z którego kopiujemy dane (bez daty); jego grafika przechodzi, gdy nie wybierzesz nowej.
--}}
@section('content')
    @php
        $field = 'w-full rounded-lg border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $label = 'mb-1 block text-sm font-bold text-ink';
        $hint = 'mt-1 text-xs text-muted';
        $invalid = fn (string $name) => $errors->has($name) ? 'aria-invalid=true' : '';
        $local = fn ($d) => $d ? $d->copy()->timezone(\App\Models\KreatywniWorkshop::TZ)->format('Y-m-d\TH:i') : '';
        $image = $workshop->exists ? $workshop->imageUrl() : $copyOf?->imageUrl();
    @endphp

    <div class="max-w-3xl">
        <a href="{{ route('admin.kreatywni-warsztaty.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy warsztatów
        </a>

        @if ($copyOf)
            <p class="mt-3 rounded-md bg-brand-light px-3 py-2 text-sm text-ink" role="status">
                <i class="fa-solid fa-copy mr-1" aria-hidden="true"></i> Kopia warsztatu „{{ $copyOf->title }}” — ustaw nowy termin. Grafika przejdzie z oryginału, jeśli nie wybierzesz innej.
            </p>
        @endif

        @if ($errors->any())
            <div role="alert" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-800">
                <p class="font-bold">Popraw formularz:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data"
              action="{{ $workshop->exists ? route('admin.kreatywni-warsztaty.update', $workshop) : route('admin.kreatywni-warsztaty.store') }}"
              class="mt-3 space-y-5 rounded-md bg-white p-6">
            @csrf
            @if ($workshop->exists) @method('PUT') @endif
            @if ($copyOf)<input type="hidden" name="copy_of" value="{{ $copyOf->id }}">@endif

            <div>
                <label for="kw-title" class="{{ $label }}">Nazwa warsztatu <span class="text-red-700" aria-hidden="true">*</span></label>
                <input id="kw-title" name="title" type="text" required maxlength="255" value="{{ old('title', $workshop->title) }}" class="{{ $field }}" autocomplete="off" aria-describedby="kw-title-hint" {{ $invalid('title') }}>
                <p id="kw-title-hint" class="{{ $hint }}">Np. „Świece sojowe” albo „Dekorowanie bombek dla rodzin”.</p>
            </div>

            <div>
                <label for="kw-place" class="{{ $label }}">Miejsce</label>
                <input id="kw-place" name="place" type="text" maxlength="255" value="{{ old('place', $workshop->place) }}" class="{{ $field }}" aria-describedby="kw-place-hint" {{ $invalid('place') }}>
                <p id="kw-place-hint" class="{{ $hint }}">Np. „Dom Kultury Podgórze, ul. Krakusa 7, Kraków”.</p>
            </div>

            <fieldset>
                <legend class="{{ $label }}">Termin (czas polski)</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="kw-start" class="mb-1 block text-xs font-bold text-muted">Początek <span class="text-red-700" aria-hidden="true">*</span></label>
                        <input id="kw-start" name="starts_at" type="datetime-local" required value="{{ old('starts_at', $local($workshop->starts_at)) }}" class="{{ $field }}" {{ $invalid('starts_at') }}>
                    </div>
                    <div>
                        <label for="kw-end" class="mb-1 block text-xs font-bold text-muted">Koniec</label>
                        <input id="kw-end" name="ends_at" type="datetime-local" value="{{ old('ends_at', $local($workshop->ends_at)) }}" class="{{ $field }}" aria-describedby="kw-end-hint" {{ $invalid('ends_at') }}>
                        <p id="kw-end-hint" class="{{ $hint }}">Opcjonalnie. Bez końca warsztat znika z nadchodzących następnego dnia.</p>
                    </div>
                </div>
            </fieldset>

            <div>
                <label for="kw-participants" class="{{ $label }}">Liczba uczestników</label>
                <input id="kw-participants" name="participants" type="text" maxlength="100" value="{{ old('participants', $workshop->participants) }}" class="{{ $field }}" aria-describedby="kw-participants-hint" {{ $invalid('participants') }}>
                <p id="kw-participants-hint" class="{{ $hint }}">Np. „do 20 osób”, „10–25 osób” albo „grupa szkolna”.</p>
            </div>

            <div>
                <label for="kw-desc" class="{{ $label }}">Opis</label>
                <textarea id="kw-desc" name="description" rows="6" maxlength="6000" class="{{ $field }}" aria-describedby="kw-desc-hint" {{ $invalid('description') }}>{{ old('description', $workshop->description) }}</textarea>
                <p id="kw-desc-hint" class="{{ $hint }}">Co zrobią uczestnicy, dla kogo są zajęcia, co zabrać. Pusta linia = nowy akapit.</p>
            </div>

            <div class="space-y-3">
                @include('admin.partials.library-image-picker', [
                    'name' => 'image', 'label' => 'Grafika ilustracyjna', 'current' => $image,
                    'help' => 'JPG, PNG lub WEBP, do 8 MB — z dysku albo z biblioteki Multimediów. Najlepiej poziome zdjęcie z warsztatów.',
                    'accept' => 'image/jpeg,image/png,image/webp',
                ])
                @if ($workshop->exists && $image)
                    <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        Usuń grafikę
                    </label>
                @endif
                <div>
                    <label for="kw-alt" class="{{ $label }}">Opis grafiki dla czytników ekranu</label>
                    <input id="kw-alt" name="image_alt" type="text" maxlength="255" value="{{ old('image_alt', $workshop->image_alt) }}" class="{{ $field }}" aria-describedby="kw-alt-hint" {{ $invalid('image_alt') }}>
                    <p id="kw-alt-hint" class="{{ $hint }}">Np. „Dzieci ozdabiają bombki brokatem”. Puste pole = opis z biblioteki, a bez niego grafika dekoracyjna.</p>
                </div>
            </div>

            <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $workshop->is_active ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Pokazuj w kalendarzu</span> <span class="text-muted">— odznacz, żeby ukryć termin bez usuwania</span></span>
            </label>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ $workshop->exists ? 'Zapisz' : 'Dodaj warsztat' }}
                </button>
                <a href="{{ route('admin.kreatywni-warsztaty.index') }}" class="inline-flex min-h-11 items-center rounded bg-gray-100 px-5 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
