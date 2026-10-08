{{--
    Wybór jednej opcji w oknie dialogowym (pop-up): podsumowanie aktualnego wyboru + przycisk „Zmień", a po kliknięciu
    okno z kartami (ikona, nazwa, opis), opcjonalnymi grupami i wyszukiwarką. Wartość trafia do prawdziwego pola formularza
    (<select> albo grupa radio), więc reszta formularza i jego skrypty działają bez zmian.

    Parametry:
      $pickerId  — unikalny identyfikator,
      $title     — tytuł okna i etykieta pola,
      $options   — [klucz => ['name' =>, 'desc' =>, 'icon' =>]],
      $groups    — opcjonalnie [nazwa grupy => [klucze]],
      $current   — aktualny klucz,
      $carrier   — ['select' => 'id pola'] albo ['radio' => 'name pola'].
    Dostępność: role="dialog" + aria-modal, fokus w oknie (pętla Tab), Esc zamyka i oddaje fokus przyciskowi, karty to role="radio".
--}}
@php
    $groups ??= null;
    $groups = $groups ?: ['' => array_keys($options)];
    $cfg = ['id' => $pickerId, 'current' => $current, 'carrier' => $carrier, 'count' => count($options)];
@endphp
<div x-data="modalPicker(@js($cfg))" @keydown.escape.window="if (open) close()" class="space-y-1">
    <p class="text-sm font-bold text-ink" id="{{ $pickerId }}-label">{{ $title }}</p>

    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-3">
        @foreach ($options as $key => $o)
            <div x-show="cur === '{{ $key }}'" @if ($key !== $current) x-cloak @endif class="flex min-w-0 flex-1 items-center gap-3">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-brand-light text-brand-dark" aria-hidden="true"><i class="fa-solid {{ $o['icon'] }}"></i></span>
                <span class="min-w-0">
                    <span class="block text-sm font-bold text-ink">{{ $o['name'] }}</span>
                    @if (($o['desc'] ?? '') !== '')<span class="block text-xs leading-snug text-muted">{{ $o['desc'] }}</span>@endif
                </span>
            </div>
        @endforeach
        <button type="button" x-ref="opener" @click="show()" aria-haspopup="dialog" :aria-expanded="open.toString()"
            class="inline-flex min-h-10 flex-none items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            <i class="fa-solid fa-pen" aria-hidden="true"></i>Zmień<span class="sr-only"> — {{ $title }}</span>
        </button>
    </div>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[10000] flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-8" @click.self="close()">
            <div role="dialog" aria-modal="true" aria-labelledby="{{ $pickerId }}-title" x-ref="panel" @keydown.tab="trap($event)"
                class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
                    <h2 id="{{ $pickerId }}-title" class="text-lg font-bold text-ink">{{ $title }}</h2>
                    <button type="button" @click="close()" class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Zamknij okno">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="space-y-5 px-6 py-5">
                    @if (count($options) > 6)
                        <div>
                            <label for="{{ $pickerId }}-q" class="sr-only">Szukaj</label>
                            <input id="{{ $pickerId }}-q" type="search" x-ref="search" x-model="q" placeholder="Szukaj…" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    @endif
                    @if (count($groups) > 2 && array_key_first($groups) !== '')
                        {{-- Filtr grup: jedna grupa albo wszystkie --}}
                        <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filtr grup">
                            <button type="button" @click="g = ''" :aria-pressed="(g === '').toString()"
                                class="rounded-full border px-3 py-1 text-xs font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                                :class="g === '' ? 'border-ink bg-ink text-white' : 'border-gray-300 bg-white text-ink hover:bg-gray-50'">Wszystkie</button>
                            @foreach ($groups as $groupName => $keys)
                                <button type="button" @click="g = @js($groupName)" :aria-pressed="(g === @js($groupName)).toString()"
                                    class="rounded-full border px-3 py-1 text-xs font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                                    :class="g === @js($groupName) ? 'border-ink bg-ink text-white' : 'border-gray-300 bg-white text-ink hover:bg-gray-50'">{{ $groupName }} <span class="font-normal">({{ count(array_filter($keys, fn ($k) => isset($options[$k]))) }})</span></button>
                            @endforeach
                        </div>
                    @endif
                    @foreach ($groups as $groupName => $keys)
                        <fieldset class="min-w-0" role="radiogroup" aria-label="{{ $groupName ?: $title }}" x-show="g === '' || g === @js($groupName)">
                            @if ($groupName !== '')<legend class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">{{ $groupName }}</legend>@endif
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($keys as $key)
                                    @continue(! isset($options[$key]))
                                    @php $o = $options[$key]; @endphp
                                    <button type="button" role="radio" :aria-checked="(cur === '{{ $key }}').toString()" @click="pick('{{ $key }}')"
                                        x-show="match(@js($o['name'].' '.($o['desc'] ?? '')))"
                                        class="flex items-start gap-3 rounded-lg border-2 p-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                                        :class="cur === '{{ $key }}' ? 'border-brand bg-brand-light' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'">
                                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-md bg-gray-100 text-brand-dark" aria-hidden="true"><i class="fa-solid {{ $o['icon'] }}"></i></span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-bold text-ink">{{ $o['name'] }} <i x-show="cur === '{{ $key }}'" class="fa-solid fa-check ml-1 text-brand-dark" aria-hidden="true"></i></span>
                                            @if (($o['desc'] ?? '') !== '')<span class="mt-0.5 block text-xs leading-snug text-muted">{{ $o['desc'] }}</span>@endif
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            </div>
        </div>
    </template>
</div>

@once
    <script>
        function modalPicker(cfg) {
            return {
                open: false, q: '', g: '', cur: cfg.current,
                show() { this.open = true; this.$nextTick(() => { (this.$refs.search || this.$refs.panel.querySelector('[aria-checked="true"]') || this.$refs.panel.querySelector('button')).focus(); }); },
                close() { this.open = false; this.q = ''; this.$nextTick(() => this.$refs.opener.focus()); },
                match(text) { const t = this.q.trim().toLowerCase(); return t === '' || text.toLowerCase().includes(t); },
                pick(key) {
                    this.cur = key;
                    let el = null;
                    if (cfg.carrier.select) {
                        el = document.getElementById(cfg.carrier.select);
                        if (el) { el.value = key; }
                    } else if (cfg.carrier.radio) {
                        el = document.querySelector('input[type=radio][name="' + cfg.carrier.radio + '"][value="' + key + '"]');
                        if (el) { el.checked = true; }
                    }
                    if (el) { el.dispatchEvent(new Event('change', { bubbles: true })); }
                    this.close();
                },
                trap(e) {
                    const f = Array.from(this.$refs.panel.querySelectorAll('button, input')).filter(n => n.offsetParent !== null);
                    if (!f.length) return;
                    const first = f[0], last = f[f.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                },
            };
        }
    </script>
@endonce
