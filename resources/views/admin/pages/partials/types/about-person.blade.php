{{-- Sekcja formularza strony: typ "about-person" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- O organizacji — osoba --}}
@php $personSocialValues = old('person_social', $page->person_social ?? []); @endphp
<div data-about-person-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'about_person' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">O organizacji — osoba</p>
    <p class="text-xs text-muted">Tytuł strony to imię i nazwisko osoby. Zdjęcie profilowe dodaj w sekcji „Zdjęcie w treści" na zakładce Treść. Slug generowany automatycznie: {org}/osoba/{imie-nazwisko}. Strony osób nie mają podstron i nie pojawiają się samodzielnie w menu.</p>

    <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4">
        <input type="hidden" name="is_featured" value="0">
        <label class="flex cursor-pointer items-center gap-2.5 text-sm">
            <input type="checkbox" name="is_featured" value="1"
                {{ old('is_featured', $page->is_featured) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="font-semibold text-ink">Fundator — wprowadzenie</span>
        </label>
        <p class="mt-1.5 pl-6 text-xs text-muted">Zaznacz dla fundatora/ki — sekcja „Słowo od Fundatora" pojawi się na stronie O organizacji.</p>

        <div class="mt-4 border-t border-amber-200 pt-4">
            <label for="founder_quote" class="mb-1 block text-xs font-bold text-ink">Słowo od Fundatora — treść cytatu</label>
            <p class="mb-2 text-xs text-muted">Niezależne od biografii osoby. Pojawi się jako cytat w sekcji na stronie O organizacji.</p>
            <textarea id="founder_quote" name="founder_quote" rows="5"
                placeholder="Wpisz cytat lub krótkie słowo od fundatora…"
                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('founder_quote', $page->founder_quote ?? '') }}</textarea>
            @error('founder_quote') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-4 border-t border-amber-200 pt-4">
            <p class="mb-2 text-xs font-bold text-ink">Zdjęcie do sekcji „Słowo od Fundatora"</p>
            <p class="mb-3 text-xs text-muted">Osobna fotografia do tej sekcji (np. szeroka, reportażowa). Jeśli puste — użyte zostanie zdjęcie profilowe z zakładki Treść.</p>

            @if (filled(old('founder_image', $page->founder_image ?? null)))
                <div class="mb-3 flex items-center gap-3">
                    <img src="{{ old('founder_image', $page->founder_image) }}" alt=""
                        class="h-20 w-32 rounded object-cover" loading="lazy">
                    <label class="flex items-center gap-1.5 text-xs text-red-600">
                        <input type="checkbox" name="remove_founder_image" value="1"
                            class="rounded border-gray-300 text-red-500 focus:ring-red-400">
                        Usuń zdjęcie
                    </label>
                </div>
            @endif

            <input type="file" name="founder_image_file" accept="image/*"
                class="block w-full text-xs text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-amber-100 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-amber-700 hover:file:bg-amber-200">

            <div class="mt-2">
                <label for="founder_image_alt" class="mb-1 block text-xs font-bold">Tekst alternatywny <span class="font-normal text-muted">(dostępność)</span></label>
                <input type="text" id="founder_image_alt" name="founder_image_alt"
                    value="{{ old('founder_image_alt', $page->founder_image_alt ?? '') }}"
                    placeholder="np. Jan Kowalski podczas konferencji"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </div>

    <div>
        <label for="person_member_label" class="mb-2 block text-sm font-bold">Etykieta członkostwa <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <div class="mb-2 flex flex-wrap gap-2">
            @foreach (['Członkini zespołu FEER', 'Członek zespołu FEER', 'Wolontariuszka', 'Wolontariusz', 'Współpracowniczka', 'Współpracownik'] as $lbl)
                <button type="button" onclick="document.getElementById('person_member_label').value='{{ $lbl }}'"
                    class="rounded border border-gray-300 px-3 py-1 text-xs hover:border-brand hover:text-brand">{{ $lbl }}</button>
            @endforeach
        </div>
        <input type="text" id="person_member_label" name="person_member_label"
            value="{{ old('person_member_label', $page->person_member_label) }}"
            placeholder="np. Członkini zespołu FEER"
            maxlength="60"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        @error('person_member_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="person_phone" class="mb-1 block text-sm font-bold">Nr telefonu <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="tel" id="person_phone" name="person_phone"
                value="{{ old('person_phone', $page->person_phone) }}"
                placeholder="+48 000 000 000"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('person_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="person_email" class="mb-1 block text-sm font-bold">E-mail kontaktowy <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="email" id="person_email" name="person_email"
                value="{{ old('person_email', $page->person_email) }}"
                placeholder="osoba@feer.org.pl"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('person_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="person_role" class="mb-1 block text-sm font-bold">Co robi w FEER / stanowisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <input type="text" id="person_role" name="person_role"
            value="{{ old('person_role', $page->person_role) }}"
            placeholder="np. Koordynatorka projektów, wolontariusz…"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        @error('person_role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div x-data="{
        tags: @json(old('person_department', $page->person_department ?? [])),
        input: '',
        add() {
            const v = this.input.trim();
            if (v && !this.tags.includes(v)) this.tags.push(v);
            this.input = '';
        },
        remove(t) { this.tags = this.tags.filter(x => x !== t); }
    }">
        <label class="mb-1 block text-sm font-bold">Działy / sekcje <span class="font-normal text-muted">(opcjonalnie, można dodać kilka)</span></label>
        <div class="flex flex-wrap gap-1.5 rounded border border-gray-300 bg-white p-2 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
            <template x-for="t in tags" :key="t">
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-light px-2.5 py-0.5 text-xs font-bold text-brand-dark">
                    <span x-text="t"></span>
                    <button type="button" @click="remove(t)" class="text-brand hover:text-red-600" :aria-label="'Usuń ' + t">&times;</button>
                    <input type="hidden" :name="'person_department[]'" :value="t">
                </span>
            </template>
            <input type="text" x-model="input"
                @keydown.enter.prevent="add()"
                @keydown.comma.prevent="add()"
                placeholder="Wpisz dział i naciśnij Enter…"
                class="min-w-[180px] flex-1 border-0 p-0 text-sm focus:ring-0">
        </div>
        <p class="mt-1 text-xs text-muted">np. Zarząd, Biuro, Wolontariat — Enter lub przecinek dodaje dział</p>
        @error('person_department') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="person_bio" class="mb-1 block text-sm font-bold">Krótkie o mnie <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="person_bio" name="person_bio" rows="4"
            placeholder="Kilka zdań — pojawi się wyróżnione na stronie osoby."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('person_bio', $page->person_bio) }}</textarea>
        @error('person_bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <p class="mb-3 text-sm font-bold">Social media <span class="font-normal text-muted">(opcjonalnie)</span></p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="person_social_facebook" class="mb-1 block text-xs font-bold text-muted">Facebook</label>
                <input type="url" id="person_social_facebook" name="person_social[facebook]"
                    value="{{ $personSocialValues['facebook'] ?? '' }}" placeholder="https://facebook.com/…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="person_social_instagram" class="mb-1 block text-xs font-bold text-muted">Instagram</label>
                <input type="url" id="person_social_instagram" name="person_social[instagram]"
                    value="{{ $personSocialValues['instagram'] ?? '' }}" placeholder="https://instagram.com/…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="person_social_linkedin" class="mb-1 block text-xs font-bold text-muted">LinkedIn</label>
                <input type="url" id="person_social_linkedin" name="person_social[linkedin]"
                    value="{{ $personSocialValues['linkedin'] ?? '' }}" placeholder="https://linkedin.com/in/…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="person_social_website" class="mb-1 block text-xs font-bold text-muted">Strona internetowa</label>
                <input type="url" id="person_social_website" name="person_social[website]"
                    value="{{ $personSocialValues['website'] ?? '' }}" placeholder="https://…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </div>
</div>
