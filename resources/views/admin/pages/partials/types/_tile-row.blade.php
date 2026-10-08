{{--
    Jeden kafelek w edytorze „Kafelki" (wspólny dla wierszy zapisanych i szablonu nowego wiersza).
    Zmienne: $i — indeks albo „__INDEX__", $tile — tablica wartości (może być pusta).
    Podgląd na żywo (Alpine): kolor, negatyw, pasek i etykieta; paleta brandbooka jednym kliknięciem; segmenty zamiast małych radio.
--}}
@php
    $t = $tile ?? [];
    $seg = 'flex cursor-pointer items-center justify-center rounded-md border px-3 py-1.5 text-xs font-bold transition focus-within:ring-2 focus-within:ring-brand has-[:checked]:border-brand has-[:checked]:bg-brand has-[:checked]:text-white border-gray-300 bg-white text-ink hover:bg-gray-50';
    $swatches = \App\Support\ThemePalette::swatches();
    $n = "tiles[{$i}]";
@endphp
<div data-repeater-row class="rounded-xl border border-gray-200 bg-white"
    x-data="{ label: @js($t['label'] ?? ''), color: @js($t['color'] ?? ''), neg: @js((bool) ($t['is_negative'] ?? false)), strip: @js((bool) ($t['strip'] ?? false)), cols: @js((int) ($t['cols'] ?? 1)),
              bg() { return /^#[0-9a-fA-F]{6}$/.test(this.color) ? this.color : '{{ \App\Support\ThemePalette::colors()[0] }}'; },
              fg() { const h = this.bg(); const v = [1,3,5].map(k => parseInt(h.slice(k,k+2),16)/255).map(x => x <= .03928 ? x/12.92 : Math.pow((x+.055)/1.055,2.4)); const L = .2126*v[0]+.7152*v[1]+.0722*v[2]; return L > .5 ? '#1d1d1a' : '#ffffff'; } }">

    {{-- Podgląd kafla --}}
    <div class="flex items-center justify-between gap-3 rounded-t-xl bg-gray-50 px-4 py-3">
        <div class="min-w-0 flex-1" aria-hidden="true">
            <div class="flex items-center gap-3 rounded-md px-4" :class="strip ? 'min-h-10 py-2' : 'min-h-16 py-3'"
                :style="neg ? ('background:' + bg() + ';color:' + fg()) : ('border:2px solid ' + bg() + ';background:#fff;color:#1d1d1a')">
                <span class="truncate text-sm font-bold" x-text="label || 'Podgląd kafelka'"></span>
            </div>
        </div>
        <div class="flex flex-none items-center gap-1">
            <button type="button" data-repeater-move="up" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń kafelek wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
            <button type="button" data-repeater-move="down" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń kafelek niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
            <button type="button" data-repeater-remove class="flex h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600" aria-label="Usuń kafelek"><i class="fa-solid fa-trash" aria-hidden="true"></i>Usuń</button>
        </div>
    </div>

    <div class="space-y-4 p-4">
        <div class="grid gap-3 sm:grid-cols-[2fr_3fr]">
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Etykieta</label>
                <input type="text" name="{{ $n }}[label]" x-model="label" placeholder="np. Panel kursanta" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Adres</label>
                <input type="text" name="{{ $n }}[url]" value="{{ $t['url'] ?? '' }}" placeholder="URL albo /sciezka" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Ikona (Bootstrap Icons)</label>
                <input type="text" name="{{ $n }}[icon]" value="{{ $t['icon'] ?? '' }}" placeholder="np. bi-rocket-takeoff" data-icon-picker data-icon-format="name" class="w-full rounded-lg border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Kolor</label>
                <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Kolory z brandbooka">
                    @foreach ($swatches as $hex => $name)
                        <button type="button" title="{{ $name }}" @click="color = '{{ $hex }}'" :aria-pressed="(color.toLowerCase() === '{{ $hex }}').toString()"
                            class="h-8 w-8 rounded-md border-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" style="background: {{ $hex }}"
                            :class="color.toLowerCase() === '{{ $hex }}' ? 'border-ink' : 'border-white ring-1 ring-gray-300'"><span class="sr-only">{{ $name }}</span></button>
                    @endforeach
                    <input type="text" name="{{ $n }}[color]" x-model="color" placeholder="#hex" maxlength="7" data-color-picker aria-label="Własny kolor (hex)" class="w-24 rounded-lg border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                </div>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-muted">Zdjęcie w tle <span class="font-normal">(opcjonalnie, adres URL — szablon FEER przyciemnia je pod tekstem)</span></label>
            <input type="url" name="{{ $n }}[image]" value="{{ $t['image'] ?? '' }}" placeholder="https://…/zdjecie.jpg" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
        </div>

        <div class="flex flex-wrap items-end gap-x-6 gap-y-3 border-t border-gray-100 pt-4">
            <fieldset>
                <legend class="mb-1 text-xs font-bold text-muted">Szerokość (kolumny)</legend>
                <div class="flex gap-1.5" role="radiogroup">
                    @foreach ([1, 2, 3] as $cv)
                        <label class="{{ $seg }}"><input type="radio" name="{{ $n }}[cols]" value="{{ $cv }}" x-model.number="cols" @checked((int) ($t['cols'] ?? 1) === $cv) class="sr-only">{{ $cv }}</label>
                    @endforeach
                </div>
            </fieldset>
            <fieldset>
                <legend class="mb-1 text-xs font-bold text-muted">Układ</legend>
                <div class="flex gap-1.5" role="radiogroup">
                    <label class="{{ $seg }}"><input type="radio" name="{{ $n }}[strip]" value="0" @click="strip = false" @checked(! ($t['strip'] ?? false)) class="sr-only">Karta</label>
                    <label class="{{ $seg }}"><input type="radio" name="{{ $n }}[strip]" value="1" @click="strip = true" @checked($t['strip'] ?? false) class="sr-only">Pasek</label>
                </div>
            </fieldset>
            <label class="flex cursor-pointer items-center gap-2 pb-1.5">
                <input type="hidden" name="{{ $n }}[is_negative]" value="0">
                <input type="checkbox" name="{{ $n }}[is_negative]" value="1" x-model="neg" @checked($t['is_negative'] ?? false) class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-sm font-bold text-ink">Negatyw <span class="font-normal text-muted">(wypełnione tło)</span></span>
            </label>
        </div>
    </div>
</div>
