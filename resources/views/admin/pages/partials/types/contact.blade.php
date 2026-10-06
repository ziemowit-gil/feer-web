{{-- Sekcja formularza strony: typ "contact" — dedykowane opcje strony kontaktowej (zapisywane w ustawieniach witryny). --}}
@php
    $cs = $siteSettings;
    $contactFieldClass = 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand';
@endphp
<div data-contact-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'contact' ? '' : 'hidden' }}">
    <div>
        <p class="text-sm font-bold uppercase tracking-wide text-muted">Strona kontaktowa</p>
        <p class="mt-1 text-xs text-muted">Te opcje dotyczą strony <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a> i są wspólne dla całej witryny (te same co w Ustawieniach → Kontakt). Treść, zdjęcie i szablon wizualny nie są tu potrzebne — strona ma stały układ.</p>
    </div>

    <fieldset>
        <legend class="mb-2 text-sm font-bold">Wygląd strony</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (\App\Models\SiteSetting::CONTACT_LAYOUTS as $clValue => $clLabel)
                @continue($cs->isOptionBlocked('contact_layouts', $clValue) && $cs->contactLayoutValue() !== $clValue)
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition has-[:checked]:border-brand has-[:checked]:bg-brand-light">
                    <input type="radio" name="contact_layout" value="{{ $clValue }}" @checked(old('contact_layout', $cs->contactLayoutValue()) === $clValue)
                        class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm leading-snug">{{ $clLabel }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <div>
        <label for="editor-contact_intro" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(opcjonalnie)</span></label>
        @include('admin.partials.editor', ['name' => 'contact_intro', 'value' => old('contact_intro', $cs->contact_intro)])
        @error('contact_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="contact_address" class="mb-1 block text-sm font-bold">Adres (ulica, numer)</label>
            <input type="text" id="contact_address" name="contact_address" value="{{ old('contact_address', $cs->contact_address) }}" class="{{ $contactFieldClass }}">
            @error('contact_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="contact_city" class="mb-1 block text-sm font-bold">Kod i miejscowość</label>
            <input type="text" id="contact_city" name="contact_city" value="{{ old('contact_city', $cs->contact_city) }}" class="{{ $contactFieldClass }}">
            @error('contact_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="contact_email" class="mb-1 block text-sm font-bold">E-mail</label>
            <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $cs->contact_email) }}" class="{{ $contactFieldClass }}">
            @error('contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="contact_phone" class="mb-1 block text-sm font-bold">Telefon</label>
            <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $cs->contact_phone) }}" class="{{ $contactFieldClass }}">
            @error('contact_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="contact_office_hours" class="mb-1 block text-sm font-bold">Godziny pracy</label>
            <input type="text" id="contact_office_hours" name="contact_office_hours" value="{{ old('contact_office_hours', $cs->contact_office_hours) }}" class="{{ $contactFieldClass }}">
            @error('contact_office_hours') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex flex-wrap gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm">
        <a href="{{ route('admin.ustawienia.edit', ['tab' => 'contact']) }}" class="inline-flex items-center gap-2 font-bold text-brand underline-offset-4 hover:underline">
            <i class="fa-solid fa-gear" aria-hidden="true"></i> Więcej opcji: adres biura, przesyłki, rachunki, spotkania
        </a>
        <a href="{{ route('admin.wiadomosci-kontaktowe.index') }}" class="inline-flex items-center gap-2 font-bold text-brand underline-offset-4 hover:underline">
            <i class="fa-solid fa-inbox" aria-hidden="true"></i> Wiadomości z formularza
        </a>
    </div>
</div>
