@extends('admin.layout')

@section('title', $item->exists ? 'Edytuj warsztat w ofercie' : 'Nowy warsztat w ofercie')

{{--
    Formularz warsztatu (karty) oferty z modułu „Mikrooferta – Kreatywni”: nazwa, grupa (z podpowiedziami grup tej oferty),
    krótki opis (karta), opis na podstronie /warsztat/{slug}, cena, grafika z dysku albo z biblioteki z opisem
    alternatywnym, widoczność. Po zapisie wraca do edycji oferty (#warsztaty).
--}}
@section('content')
    @php
        $field = 'w-full rounded-lg border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $label = 'mb-1 block text-sm font-bold text-ink';
        $hint = 'mt-1 text-xs text-muted';
        $invalid = fn (string $name) => $errors->has($name) ? 'aria-invalid=true' : '';
        $image = $item->exists ? $item->imageUrl() : null;
        $backUrl = route('admin.kreatywni-oferty.edit', $offer).'#warsztaty';
    @endphp

    <div class="max-w-3xl">
        <a href="{{ $backUrl }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do oferty „{{ $offer->name }}”
        </a>

        @if ($errors->any())
            <div role="alert" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-800">
                <p class="font-bold">Popraw formularz:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data"
              action="{{ $item->exists ? route('admin.kreatywni-oferty.warsztaty.update', [$offer, $item]) : route('admin.kreatywni-oferty.warsztaty.store', $offer) }}"
              class="mt-3 space-y-5 rounded-md bg-white p-6">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div>
                <label for="ki-title" class="{{ $label }}">Nazwa warsztatu <span class="text-red-700" aria-hidden="true">*</span></label>
                <input id="ki-title" name="title" type="text" required maxlength="255" value="{{ old('title', $item->title) }}" class="{{ $field }}" autocomplete="off" aria-describedby="ki-title-hint" {{ $invalid('title') }}>
                <p id="ki-title-hint" class="{{ $hint }}">
                    Np. „Świece sojowe”.
                    @if ($item->exists && $item->publicUrl())
                        Podstrona: <a href="{{ $item->publicUrl() }}" class="font-bold text-brand-dark underline underline-offset-2">{{ $item->publicUrl() }}</a> (adres się nie zmienia).
                    @else
                        Adres podstrony powstanie z nazwy, np. /warsztat/swiece-sojowe.
                    @endif
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="ki-group" class="{{ $label }}">Grupa</label>
                    <input id="ki-group" name="group" type="text" maxlength="120" list="ki-groups" value="{{ old('group', $item->group) }}" class="{{ $field }}" aria-describedby="ki-group-hint" {{ $invalid('group') }}>
                    <datalist id="ki-groups">@foreach ($groups as $g)<option value="{{ $g }}"></option>@endforeach</datalist>
                    <p id="ki-group-hint" class="{{ $hint }}">Np. „Przez cały rok”, „Boże Narodzenie”. Warsztaty z tą samą grupą są razem pod jej nazwą.</p>
                </div>
                <div>
                    <label for="ki-price" class="{{ $label }}">Cena</label>
                    <input id="ki-price" name="price" type="text" maxlength="100" value="{{ old('price', $item->price) }}" class="{{ $field }}" aria-describedby="ki-price-hint" {{ $invalid('price') }}>
                    <p id="ki-price-hint" class="{{ $hint }}">Opcjonalnie, np. „od 35 zł za osobę”. Bez ceny obowiązuje cena z karty oferty.</p>
                </div>
            </div>

            <div>
                <label for="ki-desc" class="{{ $label }}">Krótki opis</label>
                <textarea id="ki-desc" name="description" rows="3" maxlength="2000" data-text-max="600" class="{{ $field }}" aria-describedby="ki-desc-hint" {{ $invalid('description') }}>{{ old('description', $item->descriptionForEditor()) }}</textarea>
                <p id="ki-desc-hint" class="{{ $hint }}">1–2 zdania na karcie w bloku oferty, np. „Relaksujące sesje tworzenia dekoracyjnych świec z ekologicznych materiałów.” Możesz pogrubić lub pochylić słowa (Ctrl+B, Ctrl+I).</p>
                @include('admin.partials.mini-editor', ['id' => 'ki-desc', 'mode' => 'inline'])
            </div>

            <div>
                <label for="ki-details" class="{{ $label }}">Opis na podstronie warsztatu</label>
                <textarea id="ki-details" name="details" rows="8" maxlength="30000" class="{{ $field }}" aria-describedby="ki-details-hint" {{ $invalid('details') }}>{{ old('details', $item->detailsForEditor()) }}</textarea>
                <p id="ki-details-hint" class="{{ $hint }}">Dłuższy opis: przebieg zajęć, dla kogo, ile trwają, co uczestnicy zabierają do domu. Edytor: pogrubienie, kursywa, śródtytuły, listy i linki. Puste pole — podstrona pokaże krótki opis.</p>
                @include('admin.partials.mini-editor', ['id' => 'ki-details', 'mode' => 'rich'])
            </div>

            <div class="space-y-3">
                @include('admin.partials.library-image-picker', [
                    'name' => 'image', 'label' => 'Grafika', 'current' => $image,
                    'help' => 'JPG, PNG lub WEBP, do 8 MB — z dysku albo z biblioteki Multimediów. Pokazuje się na karcie i na podstronie warsztatu.',
                    'accept' => 'image/jpeg,image/png,image/webp',
                ])
                @if ($image)
                    <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        Usuń grafikę
                    </label>
                @endif
                <div>
                    <label for="ki-alt" class="{{ $label }}">Opis grafiki dla czytników ekranu</label>
                    <input id="ki-alt" name="image_alt" type="text" maxlength="255" value="{{ old('image_alt', $item->image_alt) }}" class="{{ $field }}" aria-describedby="ki-alt-hint" {{ $invalid('image_alt') }}>
                    <p id="ki-alt-hint" class="{{ $hint }}">Np. „Kolorowe świece sojowe w szklanych słoikach”. Puste pole = opis z biblioteki, a bez niego grafika dekoracyjna.</p>
                </div>
            </div>

            <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Pokazuj w ofercie</span> <span class="text-muted">— odznacz, żeby ukryć warsztat (i jego podstronę) bez usuwania</span></span>
            </label>

            @if (\App\Models\KreatywniOfferItem::supportsFaq())
                @include('admin.kreatywni-offers.partials.faq-fields')
            @endif

            @if (\App\Models\KreatywniOfferItem::supportsSeo())
                <div class="-mx-6 border-t border-gray-200">
                    @include('admin.partials.seo-fields', ['model' => $item])
                </div>
            @endif

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ $item->exists ? 'Zapisz' : 'Dodaj warsztat' }}
                </button>
                <a href="{{ $backUrl }}" class="inline-flex min-h-11 items-center rounded bg-gray-100 px-5 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
