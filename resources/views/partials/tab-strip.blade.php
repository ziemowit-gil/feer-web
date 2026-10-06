{{--
    Pasek zakładek w formie kart — wspólny dla stron korzystających z układu zakładkowego
    (kontakt „Instytucjonalny", widok projektu). Musi stać wewnątrz elementu z x-data zawierającym
    tabs / tab / move() / jump().

    Zmienne: $tabItems — lista ['id' => ..., 'label' => ...].

    Wygląd: karty z zaokrąglonymi górnymi rogami na jasnym pasku; aktywna karta ma białe tło (łączy się z treścią
    pod spodem), tekst w kolorze marki i wyraźny pasek na górze; nieaktywne są stonowane. Szerokość bloku
    jest taka sama jak kolumny treści (max-w-6xl).

    Dostępność: role tablist/tab, aria-selected i aria-controls, roving tabindex, strzałki oraz Home/End.
    Aktywna zakładka: ciemny wariant marki na bieli (kontrast ≥ 4,5:1), nieaktywne: text-muted na jasnym tle.
--}}
@php $tabItems = $tabItems ?? []; @endphp

@if (count($tabItems) > 1)
    <div class="border-b border-gray-200 bg-gray-50">
        <div class="mx-auto max-w-6xl px-4">
            <div role="tablist" aria-label="{{ $tabsLabel ?? 'Sekcje strony' }}" class="-mb-px flex flex-wrap gap-1 pt-3">
                @foreach ($tabItems as $tabItem)
                    <button type="button" role="tab"
                        id="tab-{{ $tabItem['id'] }}"
                        aria-controls="panel-{{ $tabItem['id'] }}"
                        :aria-selected="tab === '{{ $tabItem['id'] }}' ? 'true' : 'false'"
                        :tabindex="tab === '{{ $tabItem['id'] }}' ? 0 : -1"
                        @click="tab = '{{ $tabItem['id'] }}'"
                        @keydown.arrow-right.prevent="move(1)"
                        @keydown.arrow-left.prevent="move(-1)"
                        @keydown.home.prevent="jump(tabs[0])"
                        @keydown.end.prevent="jump(tabs[tabs.length - 1])"
                        class="rounded-t-lg border border-b-0 px-5 py-3 text-sm font-bold uppercase tracking-wide transition focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand"
                        :class="tab === '{{ $tabItem['id'] }}'
                            ? 'border-gray-200 border-t-4 border-t-brand bg-white text-brand-dark'
                            : 'border-transparent text-muted hover:bg-white/70 hover:text-ink'">
                        {{ $tabItem['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
@endif
