{{--
    Pasek zakładek w kolorze marki — wspólny dla stron korzystających z układu
    zakładkowego. Musi stać wewnątrz elementu z x-data="sectionTabs([...])".

    Zmienne: $tabItems — lista ['id' => ..., 'label' => ...].

    Dostępność: role tablist/tab, aria-selected i aria-controls, roving tabindex,
    obsługa strzałek oraz Home/End. Biel na kolorze marki daje 6:1 kontrastu,
    aktywna zakładka (białe tło, ciemny tekst) 17:1 — powyżej progu 4,5:1.
--}}
@php $tabItems = $tabItems ?? []; @endphp

@if (count($tabItems) > 1)
    <div class="border-t border-white/20 bg-brand">
        <div class="mx-auto max-w-6xl px-4">
            <div role="tablist" aria-label="{{ $tabsLabel ?? 'Sekcje strony' }}" class="flex flex-wrap">
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
                        class="px-5 py-4 text-sm font-bold uppercase tracking-wide transition focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-white"
                        :class="tab === '{{ $tabItem['id'] }}' ? 'bg-white text-ink' : 'text-white hover:bg-white/15'">
                        {{ $tabItem['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
@endif
