@extends('admin.layout')

@section('title', $offer->exists ? 'Edytuj ofertę – Kreatywni' : 'Nowa oferta – Kreatywni')

{{--
    Formularz oferty z modułu „Mikrooferta – Kreatywni”: karta cenowa (nazwa, cena, jednostka, dopisek, korzyści,
    przycisk) i warsztaty w grupach („Tytuł | grupa”) w wyglądzie pigułek albo zwykłej listy, widoczność.
--}}
@section('content')
    @php
        $field = 'w-full rounded-lg border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $label = 'mb-1 block text-sm font-bold text-ink';
        $hint = 'mt-1 text-xs text-muted';
        $style = old('items_style', $offer->itemsStyle());
        $invalid = fn (string $name) => $errors->has($name) ? 'aria-invalid=true' : '';
    @endphp

    <div class="max-w-3xl">
        <a href="{{ route('admin.kreatywni-oferty.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy ofert
        </a>

        @if ($errors->any())
            <div role="alert" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-800">
                <p class="font-bold">Popraw formularz:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $offer->exists ? route('admin.kreatywni-oferty.update', $offer) : route('admin.kreatywni-oferty.store') }}"
              class="mt-3 space-y-6 rounded-md bg-white p-6">
            @csrf
            @if ($offer->exists) @method('PUT') @endif

            <fieldset class="space-y-4">
                <legend class="text-base font-bold text-ink">Karta cenowa</legend>

                <div>
                    <label for="ko-name" class="{{ $label }}">Nazwa oferty <span class="text-red-700" aria-hidden="true">*</span></label>
                    <input id="ko-name" name="name" type="text" required maxlength="255" value="{{ old('name', $offer->name) }}" class="{{ $field }}" autocomplete="off" aria-describedby="ko-name-hint" {{ $invalid('name') }}>
                    <p id="ko-name-hint" class="{{ $hint }}">Np. „Warsztaty mobilne”. Pokazuje się na górze karty.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ko-price" class="{{ $label }}">Cena</label>
                        <input id="ko-price" name="price" type="text" maxlength="100" value="{{ old('price', $offer->price) }}" class="{{ $field }}" aria-describedby="ko-price-hint" {{ $invalid('price') }}>
                        <p id="ko-price-hint" class="{{ $hint }}">Np. „od 30 zł”. Duży napis na karcie.</p>
                    </div>
                    <div>
                        <label for="ko-unit" class="{{ $label }}">Jednostka</label>
                        <input id="ko-unit" name="price_unit" type="text" maxlength="100" value="{{ old('price_unit', $offer->price_unit) }}" class="{{ $field }}" aria-describedby="ko-unit-hint" {{ $invalid('price_unit') }}>
                        <p id="ko-unit-hint" class="{{ $hint }}">Np. „za osobę”.</p>
                    </div>
                </div>

                <div>
                    <label for="ko-note" class="{{ $label }}">Dopisek przy cenie</label>
                    <input id="ko-note" name="price_note" type="text" maxlength="255" value="{{ old('price_note', $offer->price_note) }}" class="{{ $field }}" aria-describedby="ko-note-hint" {{ $invalid('price_note') }}>
                    <p id="ko-note-hint" class="{{ $hint }}">Np. „zależna od liczby osób oraz lokalizacji”.</p>
                </div>

                <div>
                    <label for="ko-features" class="{{ $label }}">Korzyści</label>
                    <textarea id="ko-features" name="features" rows="5" maxlength="4000" class="{{ $field }}" aria-describedby="ko-features-hint" {{ $invalid('features') }}>{{ old('features', $offer->features) }}</textarea>
                    <p id="ko-features-hint" class="{{ $hint }}">Jedna korzyść w wierszu, np. „Dojazd do Twojej placówki”.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ko-button" class="{{ $label }}">Napis na przycisku</label>
                        <input id="ko-button" name="button_label" type="text" maxlength="100" value="{{ old('button_label', $offer->button_label) }}" class="{{ $field }}" aria-describedby="ko-button-hint" {{ $invalid('button_label') }}>
                        <p id="ko-button-hint" class="{{ $hint }}">Np. „Zamów warsztaty”. Puste pole = karta bez przycisku.</p>
                    </div>
                    <div>
                        <label for="ko-url" class="{{ $label }}">Adres przycisku</label>
                        <input id="ko-url" name="button_url" type="text" maxlength="500" inputmode="url" value="{{ old('button_url', $offer->button_url) }}" class="{{ $field }}" aria-describedby="ko-url-hint" {{ $invalid('button_url') }}>
                        <p id="ko-url-hint" class="{{ $hint }}">Puste pole = strona kontaktu. Strona w witrynie: „/oferta”, inna strona: „https://…”, telefon: „tel:…”.</p>
                    </div>
                </div>
                <p class="text-xs text-muted">Pod przyciskiem karta pokazuje telefon z Ustawienia → Kontakt.</p>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="text-base font-bold text-ink">Warsztaty</legend>

                <div>
                    <label for="ko-items" class="{{ $label }}">Lista warsztatów</label>
                    <textarea id="ko-items" name="items" rows="{{ min(18, max(6, substr_count((string) old('items', $offer->items), "\n") + 2)) }}" maxlength="8000" class="{{ $field }}" aria-describedby="ko-items-hint" {{ $invalid('items') }}>{{ old('items', $offer->items) }}</textarea>
                    <p id="ko-items-hint" class="{{ $hint }}">Jeden warsztat w wierszu: „Tytuł | grupa”, np. „Dekorowanie bombek | Boże Narodzenie”. Warsztaty z tą samą grupą pokazują się razem pod jej nazwą, w kolejności z listy; bez grupy trafiają do grupy „Warsztaty”.</p>
                </div>

                <fieldset>
                    <legend class="{{ $label }}">Wygląd warsztatów</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach (\App\Support\KreatywniTexts::OFFER_STYLES as $key => [$name, $desc])
                            <label class="flex min-h-11 cursor-pointer items-start gap-2 rounded border border-gray-300 bg-white p-2">
                                <input type="radio" name="items_style" value="{{ $key }}" class="mt-1 border-gray-500 text-brand-dark focus:ring-brand-dark" @checked($style === $key)>
                                <span>
                                    <span class="block text-sm font-bold text-ink">{{ $name }}</span>
                                    <span class="block text-xs text-muted">{{ $desc }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </fieldset>

            <label class="flex min-h-11 items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $offer->is_active ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Pokazuj na stronie</span> <span class="text-muted">— odznacz, żeby ukryć ofertę bez usuwania</span></span>
            </label>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ $offer->exists ? 'Zapisz' : 'Dodaj ofertę' }}
                </button>
                <a href="{{ route('admin.kreatywni-oferty.index') }}" class="inline-flex min-h-11 items-center rounded bg-gray-100 px-5 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
