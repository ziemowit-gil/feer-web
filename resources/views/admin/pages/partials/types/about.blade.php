{{-- Sekcja formularza strony: typ "about" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
<div data-about-fields class="space-y-3 border-t border-gray-100 pt-5 {{ $currentType === 'about' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">O organizacji</p>
    <p class="text-xs text-muted">Rozwiń wybraną sekcję, aby ją wypełnić. Puste sekcje są automatycznie pomijane na stronie.</p>

    <details open class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Kolejność sekcji</summary>
        <div class="border-t border-gray-100 px-4 py-4">
        <p class="mb-3 text-xs text-muted">Zmień kolejność wyświetlania sekcji. Nagłówek (tytuł i motto) zawsze pozostaje na górze; puste sekcje są automatycznie pomijane.</p>
        <ul id="about-section-order-list" class="space-y-2 sm:max-w-md">
            @foreach ($page->orderedAboutSections() as $key)
                <li data-section="{{ $key }}" class="flex items-center justify-between rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                    <span class="font-medium">{{ \App\Models\Page::ABOUT_SECTIONS[$key] ?? $key }}</span>
                    <span class="flex items-center gap-1">
                        <button type="button" data-move="up" class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-gray-200 hover:text-brand" aria-label="Przenieś wyżej">
                            <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                        </button>
                        <button type="button" data-move="down" class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-gray-200 hover:text-brand" aria-label="Przenieś niżej">
                            <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                        </button>
                    </span>
                    <input type="hidden" name="about_section_order[{{ $key }}]" value="0">
                </li>
            @endforeach
        </ul>
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Motto i wstęp</summary>
        <div class="space-y-4 border-t border-gray-100 px-4 py-4">
    <div>
        <label for="about_motto" class="mb-1 block text-sm font-bold">Motto <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="about_motto" name="about_motto" rows="2" placeholder="np. Tworzymy świat bez barier cyfrowych."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('about_motto', $page->about_motto) }}</textarea>
        @error('about_motto') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:w-1/2">
        <label for="about_motto_author" class="mb-1 block text-sm font-bold">Autor motta <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <input type="text" id="about_motto_author" name="about_motto_author" value="{{ old('about_motto_author', $page->about_motto_author) }}"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        @error('about_motto_author') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="about_intro" class="mb-1 block text-sm font-bold">Wstęp</label>
        <textarea id="about_intro" name="about_intro" rows="5" placeholder="Krótkie wprowadzenie o organizacji. Kolejne akapity oddzielaj pustą linią."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('about_intro', $page->about_intro) }}</textarea>
        <p class="mt-1 text-xs text-muted">Tekst wstępu jako zwykłe pole (bez edytora). Wyświetli się obok zdjęć u góry strony.</p>
        @error('about_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Dokumenty i sprawozdania</summary>
        <div class="space-y-4 border-t border-gray-100 px-4 py-4">
    <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50/60 p-4">
        <p class="text-sm font-bold text-ink"><i class="fa-solid fa-folder-open text-muted" aria-hidden="true"></i> Sekcja „Dokumenty i sprawozdania"</p>

        <div>
            <label for="about_documents_intro" class="mb-1 block text-sm font-bold">Wstęp (opis nad listą)</label>
            <textarea id="about_documents_intro" name="about_documents_intro" rows="4" placeholder="Opis nad listą dokumentów, np. dlaczego udostępniacie sprawozdania."
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('about_documents_intro', $page->about_documents_intro) }}</textarea>
            @error('about_documents_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-gray-200 pt-3">
            <p class="text-sm font-bold text-ink">Pliki do pokazania na liście</p>
            <p class="mt-0.5 text-xs text-muted">Wgraj tu tylko wybrane dokumenty (np. najnowsze sprawozdanie, Standardy Ochrony Małoletnich, statut) — reszta zostaje w BIP pod przyciskiem powyżej.</p>
            @if ($page->exists)
                <button type="button" data-goto-files class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light">
                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Dodaj / zarządzaj plikami
                    @if ($page->attachments->isNotEmpty())
                        <span class="rounded-full bg-brand px-1.5 text-xs font-bold text-white">{{ $page->attachments->count() }}</span>
                    @endif
                </button>
            @else
                <p class="mt-1 text-xs font-medium text-amber-700">Zapisz stronę, aby móc wgrać pliki (pojawi się zakładka „Pliki do pobrania").</p>
            @endif
        </div>
    </div>

    @if ($page->exists)
        <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
            Zdjęcia dodaj w zakładce „Galeria". Pierwsze 2–3 zdjęcia pojawią się obok wstępu; pozostałe w sekcji galerii poniżej.
        </p>
    @else
        <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
            Zdjęcia (2–3 obok wstępu + galeria) dodasz po zapisaniu strony — pojawi się zakładka „Galeria".
        </p>
    @endif
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Statystyki (liczby)</summary>
        <div class="border-t border-gray-100 px-4 py-4">
    <div data-repeater>
        <p class="mb-3 text-xs text-muted">np. „12 lat" + „doświadczenia". Puste wiersze są pomijane.</p>
        <div data-repeater-rows class="space-y-2">
            @foreach ($aboutStats as $i => $row)
                <div data-repeater-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                    <input type="text" name="about_stats[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="Wartość, np. 500+" aria-label="Wartość statystyki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <input type="text" name="about_stats[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Etykieta, np. przeszkolonych osób" aria-label="Etykieta statystyki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <button type="button" data-repeater-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń statystykę"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj statystykę</button>
        <template data-repeater-template>
            <div data-repeater-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                <input type="text" name="about_stats[__INDEX__][value]" placeholder="Wartość, np. 500+" aria-label="Wartość statystyki" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <input type="text" name="about_stats[__INDEX__][label]" placeholder="Etykieta, np. przeszkolonych osób" aria-label="Etykieta statystyki" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <button type="button" data-repeater-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń statystykę"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Wartości / kafelki</summary>
        <div class="border-t border-gray-100 px-4 py-4">
    <div data-repeater>
        <p class="mb-3 text-xs text-muted">Ikona (klasa Font Awesome, np. <code>fa-solid fa-heart</code>) + tytuł + opis.</p>
        <div data-repeater-rows class="space-y-2">
            @foreach ($aboutValues as $i => $row)
                <div data-repeater-row class="grid gap-2 sm:grid-cols-[1fr_1fr_2fr_auto]">
                    <input type="text" name="about_values[{{ $i }}][icon]" value="{{ $row['icon'] ?? '' }}" placeholder="fa-solid fa-heart" data-icon-picker data-icon-format="class" aria-label="Ikona wartości {{ $i + 1 }}" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                    <input type="text" name="about_values[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł" aria-label="Tytuł wartości {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <input type="text" name="about_values[{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Krótki opis" aria-label="Opis wartości {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <button type="button" data-repeater-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wartość"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wartość</button>
        <template data-repeater-template>
            <div data-repeater-row class="grid gap-2 sm:grid-cols-[1fr_1fr_2fr_auto]">
                <input type="text" name="about_values[__INDEX__][icon]" placeholder="fa-solid fa-heart" data-icon-picker data-icon-format="class" aria-label="Ikona wartości" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                <input type="text" name="about_values[__INDEX__][title]" placeholder="Tytuł" aria-label="Tytuł wartości" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <input type="text" name="about_values[__INDEX__][text]" placeholder="Krótki opis" aria-label="Opis wartości" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <button type="button" data-repeater-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wartość"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Oś czasu (historia)</summary>
        <div class="border-t border-gray-100 px-4 py-4">
    <div data-repeater>
        <p class="mb-3 text-xs text-muted">Rok / etap + opis, opcjonalny link oraz kolor znacznika na osi. Puste wiersze są pomijane.</p>
        <div data-repeater-rows class="space-y-3">
            @foreach ($aboutTimeline as $i => $row)
                <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid gap-2 sm:grid-cols-[1fr_3fr]">
                        <input type="text" name="about_timeline[{{ $i }}][year]" value="{{ $row['year'] ?? '' }}" placeholder="Rok, np. 2015" aria-label="Rok wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <input type="text" name="about_timeline[{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Opis wydarzenia" aria-label="Opis wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <p class="text-xs font-medium text-muted">Linki zewnętrzne (maks. 3)</p>
                    @for ($l = 1; $l <= 3; $l++)
                        @php $lk = $l === 1 ? '' : $l; @endphp
                        <div class="grid gap-2 sm:grid-cols-[3fr_2fr]">
                            <input type="url" name="about_timeline[{{ $i }}][url{{ $lk }}]" value="{{ $row['url'.$lk] ?? '' }}" placeholder="Link {{ $l }} (URL)" aria-label="Link {{ $l }} wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            <input type="text" name="about_timeline[{{ $i }}][label{{ $lk }}]" value="{{ $row['label'.$lk] ?? '' }}" placeholder="Etykieta linku {{ $l }}" aria-label="Etykieta linku {{ $l }} wpisu osi czasu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        </div>
                    @endfor
                    <div class="flex items-center justify-between gap-2">
                        <label class="flex items-center gap-2 text-xs text-muted">Kolor znacznika
                            <input type="color" name="about_timeline[{{ $i }}][color]" value="{{ $row['color'] ?? $siteSettings->brand_color }}" aria-label="Kolor znacznika na osi czasu {{ $i + 1 }}" class="h-8 w-12 rounded border-gray-300">
                        </label>
                        <div class="flex items-center gap-1"><button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Przenieś wpis wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button><button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Przenieś wpis niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button><button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wpis osi czasu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button></div>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpis</button>
        <template data-repeater-template>
            <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="grid gap-2 sm:grid-cols-[1fr_3fr]">
                    <input type="text" name="about_timeline[__INDEX__][year]" placeholder="Rok, np. 2015" aria-label="Rok wpisu osi czasu" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <input type="text" name="about_timeline[__INDEX__][text]" placeholder="Opis wydarzenia" aria-label="Opis wpisu osi czasu" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <p class="text-xs font-medium text-muted">Linki zewnętrzne (maks. 3)</p>
                @for ($l = 1; $l <= 3; $l++)
                    @php $lk = $l === 1 ? '' : $l; @endphp
                    <div class="grid gap-2 sm:grid-cols-[3fr_2fr]">
                        <input type="url" name="about_timeline[__INDEX__][url{{ $lk }}]" placeholder="Link {{ $l }} (URL)" aria-label="Link {{ $l }} wpisu osi czasu" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        <input type="text" name="about_timeline[__INDEX__][label{{ $lk }}]" placeholder="Etykieta linku {{ $l }}" aria-label="Etykieta linku {{ $l }} wpisu osi czasu" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                    </div>
                @endfor
                <div class="flex items-center justify-between gap-2">
                    <label class="flex items-center gap-2 text-xs text-muted">Kolor znacznika
                        <input type="color" name="about_timeline[__INDEX__][color]" value="{{ $siteSettings->brand_color }}" aria-label="Kolor znacznika na osi czasu" class="h-8 w-12 rounded border-gray-300">
                    </label>
                    <div class="flex items-center gap-1"><button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Przenieś wpis wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button><button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Przenieś wpis niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button><button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wpis osi czasu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button></div>
                </div>
            </div>
        </template>
    </div>
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Nasi partnerzy</summary>
        <div class="border-t border-gray-100 px-4 py-4">
    <div>
        <p class="mb-3 text-xs text-muted">Zaznacz partnerów, których loga pokazać w sekcji „Nasi partnerzy — wspierają nas". Partnerów dodajesz w module <a href="{{ route('admin.partnerzy.index') }}" class="text-brand underline">Partnerzy</a>.</p>
        @php $selectedPartners = array_map('intval', (array) old('about_partner_ids', $page->about_partner_ids ?? [])); @endphp
        @if ($partnerOptions->isEmpty())
            <p class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-muted">Brak partnerów — dodaj ich najpierw w module „Partnerzy".</p>
        @else
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($partnerOptions as $partner)
                    <label class="flex items-center gap-3 rounded border border-gray-200 p-2 text-sm hover:bg-gray-50">
                        <input type="checkbox" name="about_partner_ids[]" value="{{ $partner->id }}"
                            @checked(in_array($partner->id, $selectedPartners, true))
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        @if ($partner->logo_url)
                            <img src="{{ $partner->logo_url }}" alt="" class="h-8 w-16 flex-none object-contain">
                        @endif
                        <span class="min-w-0 truncate font-medium text-ink">{{ $partner->name }}</span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Odnośnik do FAQ</summary>
        <div class="border-t border-gray-100 px-4 py-4">
            <label class="flex items-center gap-2">
                <input type="hidden" name="about_faq_visible" value="0">
                <input type="checkbox" name="about_faq_visible" value="1" {{ old('about_faq_visible', $page->about_faq_visible ?? false) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-sm font-bold">Pokaż odnośnik do FAQ</span>
            </label>
            <p class="mt-1 text-xs text-muted">Na stronie „O organizacji" pojawi się sekcja „Masz pytania?" z przyciskiem prowadzącym do <code>/faq</code>. Kolejność ustawisz w „Kolejność sekcji" (pozycja „Odnośnik do FAQ").</p>
            @error('about_faq_visible') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </details>

    <details class="rounded-lg border border-gray-200">
        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">My w mediach</summary>
        <div class="space-y-4 border-t border-gray-100 px-4 py-4">
            <div>
                <label for="about_press_intro" class="mb-1 block text-sm font-bold">Wstęp</label>
                <textarea id="about_press_intro" name="about_press_intro" rows="3" placeholder="Krótki wstęp nad wzmiankami prasowymi."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('about_press_intro', $page->about_press_intro) }}</textarea>
                @error('about_press_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div data-repeater>
                <p class="mb-3 text-xs text-muted">Wklej link do artykułu — obrazek i tytuł pobiorą się automatycznie ze strony (og:image) przy zapisie. Możesz je nadpisać ręcznie.</p>
                <div data-repeater-rows class="space-y-3">
                    @foreach ($aboutPress as $i => $row)
                        <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            @if (! empty($row['image']))
                                <img src="{{ $row['image'] }}" alt="" class="h-24 w-full max-w-xs rounded object-cover">
                            @endif
                            <input type="url" name="about_press[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="Link do artykułu (URL)" aria-label="Link wzmianki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input type="text" name="about_press[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł (pobierze się automatycznie)" aria-label="Tytuł wzmianki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <input type="text" name="about_press[{{ $i }}][source]" value="{{ $row['source'] ?? '' }}" placeholder="Źródło, np. Gazeta Wyborcza" aria-label="Źródło wzmianki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <input type="text" name="about_press[{{ $i }}][image]" value="{{ $row['image'] ?? '' }}" placeholder="URL obrazka (pobierze się automatycznie)" aria-label="Obrazek wzmianki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            <div class="text-right">
                                <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wzmiankę"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wzmiankę</button>
                <template data-repeater-template>
                    <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <input type="url" name="about_press[__INDEX__][url]" placeholder="Link do artykułu (URL)" aria-label="Link wzmianki" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <input type="text" name="about_press[__INDEX__][title]" placeholder="Tytuł (pobierze się automatycznie)" aria-label="Tytuł wzmianki" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <input type="text" name="about_press[__INDEX__][source]" placeholder="Źródło, np. Gazeta Wyborcza" aria-label="Źródło wzmianki" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <input type="text" name="about_press[__INDEX__][image]" placeholder="URL obrazka (pobierze się automatycznie)" aria-label="Obrazek wzmianki" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        <div class="text-right">
                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wzmiankę"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </details>
</div>
