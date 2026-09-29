{{-- Sekcja formularza strony: typ "hub" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- Panel współpracownika: hero + wstęp + kafelki linków do systemów --}}
@php $hubLinks = array_values((array) old('hub_links', $page->hub_links ?? [])); @endphp
<div data-hub-fields class="space-y-5 border-t border-gray-100 pt-5 {{ in_array($currentType, ['internal_hub', 'links_hub'], true) ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Kafelki i linki</p>

    <div>
        <label class="mb-1 block text-sm font-bold">Obraz hero (u góry panelu)</label>
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-2">
            @if (! empty($page->hub_hero))
                <img src="{{ $page->hub_hero }}" alt="" class="h-14 w-24 shrink-0 rounded object-cover">
            @else
                <span class="flex h-14 w-24 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-400" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
            @endif
            <div class="min-w-0 flex-1 space-y-1">
                <input type="file" name="hub_hero_file" accept="image/*" aria-label="Wgraj obraz hero"
                    class="block w-full cursor-pointer text-xs text-muted file:mr-2 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-3 file:py-1 file:text-xs file:font-bold file:text-white hover:file:bg-brand-dark">
                <input type="text" name="hub_hero" value="{{ old('hub_hero', $page->hub_hero) }}" placeholder="…albo wklej URL obrazu" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
            </div>
        </div>
    </div>

    <div>
        <label for="hub_intro" class="mb-1 block text-sm font-bold">Wstęp</label>
        <textarea id="hub_intro" name="hub_intro" rows="2" placeholder="np. Zbiór linków do systemów i narzędzi dla współpracowników FEER."
            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('hub_intro', $page->hub_intro) }}</textarea>
    </div>

    <div data-repeater>
        <p class="mb-2 text-sm font-bold">Kafelki linków do systemów</p>
        <div data-repeater-rows class="space-y-3">
            @foreach ($hubLinks as $i => $row)
                <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid gap-2 sm:grid-cols-[2fr_3fr]">
                        <input type="text" name="hub_links[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Tytuł kafelka" aria-label="Tytuł kafelka {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <input type="text" name="hub_links[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="Adres (URL lub /sciezka)" aria-label="Adres linku {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <input type="text" name="hub_links[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" placeholder="Krótki opis pod tytułem" aria-label="Opis kafelka {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                    <div class="grid gap-2 sm:grid-cols-3">
                        <input type="text" name="hub_links[{{ $i }}][icon]" value="{{ $row['icon'] ?? '' }}" placeholder="fa-solid fa-handshake" data-icon-picker data-icon-format="class" aria-label="Ikona {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        <select name="hub_links[{{ $i }}][color]" aria-label="Kolor kafelka {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            @foreach (['blue' => 'Niebieski', 'dark' => 'Ciemny (grafitowy)', 'green' => 'Zielony', 'purple' => 'Fioletowy', 'orange' => 'Pomarańczowy', 'red' => 'Czerwony'] as $val => $lbl)
                                <option value="{{ $val }}" {{ ($row['color'] ?? 'blue') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="hub_links[{{ $i }}][cta_label]" value="{{ $row['cta_label'] ?? '' }}" placeholder="Tekst przycisku, np. Dowiedz się więcej" aria-label="Tekst przycisku {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                    </div>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń link"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kafelek</button>
        <template data-repeater-template>
            <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="grid gap-2 sm:grid-cols-[2fr_3fr]">
                    <input type="text" name="hub_links[__INDEX__][label]" placeholder="Tytuł kafelka" aria-label="Tytuł kafelka" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <input type="text" name="hub_links[__INDEX__][url]" placeholder="Adres (URL lub /sciezka)" aria-label="Adres linku" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <input type="text" name="hub_links[__INDEX__][description]" placeholder="Krótki opis pod tytułem" aria-label="Opis kafelka" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                <div class="grid gap-2 sm:grid-cols-3">
                    <input type="text" name="hub_links[__INDEX__][icon]" placeholder="fa-solid fa-handshake" data-icon-picker data-icon-format="class" aria-label="Ikona" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                    <select name="hub_links[__INDEX__][color]" aria-label="Kolor kafelka" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        <option value="blue">Niebieski</option>
                        <option value="dark">Ciemny (grafitowy)</option>
                        <option value="green">Zielony</option>
                        <option value="purple">Fioletowy</option>
                        <option value="orange">Pomarańczowy</option>
                        <option value="red">Czerwony</option>
                    </select>
                    <input type="text" name="hub_links[__INDEX__][cta_label]" placeholder="Tekst przycisku" aria-label="Tekst przycisku" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                </div>
                <div class="flex items-center justify-end gap-1">
                    <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                    <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                    <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń link"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                </div>
            </div>
        </template>
    </div>
</div>
