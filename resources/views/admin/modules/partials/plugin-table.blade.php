{{-- Tabela modułów instalowanych (rozszerzeń z katalogu modules/) z zaznaczaniem wielu wierszy i akcjami masowymi
     Aktywuj / Dezaktywuj zaznaczone. To moduły CAŁEJ instalacji (ModuleManager, tabela plugins), nie jednej witryny.
     Zwykły formularz (bez JS): pola i przyciski wskazują atrybutem form="plugins-bulk-form" formularz dodany przez
     @push('scripts'), a przyciski w wierszu — formularze z partiala plugin-forms (form="plugin-module-form-…"),
     więc tabela może stać wewnątrz innego formularza (Ustawienia). Bez JS „Zaznacz wszystkie” to link (?zaznacz=rozszerzenia).
     Parametry: $cards (PluginModules::cards()), $filterable (bool — filtr/wyszukiwarka z nadrzędnego x-data visible()),
     $returnTo (opcjonalnie 'settings'). --}}
@php
    $filterable = ! empty($filterable);
    $returnTo = $returnTo ?? null;
    $pluginsBulkReady = \Illuminate\Support\Facades\Route::has('admin.moduly.masowo');
    $pluginsStatus = session('plugins_table_status');
    $pluginsError = $errors->getBag('pluginsTable')->first('modules');
    $pluginsPreselected = request('zaznacz') === 'rozszerzenia' ? $cards->keys()->values()->all() : [];
    $selfUrl = $returnTo === 'settings'
        ? route('admin.ustawienia.edit', ['tab' => 'modules', 'zaznacz' => 'rozszerzenia'])
        : route('admin.moduly.index', ['zaznacz' => 'rozszerzenia']);
    $visibleJs = $filterable ? "visible('plugin:' + k)" : 'true';
@endphp

<div id="tabela-rozszerzen" class="scroll-mt-24"
    x-data="{
        psel: @js($pluginsPreselected),
        pnote: '',
        pluginKeys: @js($cards->keys()->values()),
        pshown() { return this.pluginKeys.filter((k) => {{ $visibleJs }}); },
        get allPluginsChecked() { const v = this.pshown(); return v.length > 0 && v.every((k) => this.psel.includes(k)); },
        get somePluginsChecked() { return ! this.allPluginsChecked && this.pshown().some((k) => this.psel.includes(k)); },
        toggleAllPlugins(on) { const v = this.pshown(); this.psel = on ? [...new Set([...this.psel, ...v])] : this.psel.filter((k) => ! v.includes(k)); },
        pguard(e) { if (this.psel.length === 0) { e.preventDefault(); this.pnote = 'Zaznacz co najmniej jeden moduł, a potem wybierz akcję.'; return false; } this.pnote = ''; return true; },
        async pdeactivate(e) {
            if (! this.pguard(e)) return;
            e.preventDefault();
            const msg = 'Dezaktywować zaznaczone moduły (' + this.psel.length + ')? Ich funkcje przestaną działać we wszystkich witrynach.';
            const ok = window.Alpine?.store('confirm') ? await Alpine.store('confirm').ask(msg) : window.confirm(msg);
            if (! ok) return;
            const form = document.getElementById('plugins-bulk-form');
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'action'; input.value = 'deactivate';
            form.appendChild(input);
            form.submit();
        },
    }">

    <p class="mb-3 flex items-start gap-2 rounded-md bg-gray-50 p-3 text-sm text-ink">
        <i class="fa-solid fa-circle-info mt-0.5 text-brand-dark" aria-hidden="true"></i>
        <span><strong>To moduły całej instalacji</strong> — aktywacja i dezaktywacja działa we wszystkich witrynach naraz i od razu, bez przycisku „Zapisz”. Wyłączenie modułu tylko w tej witrynie robisz w tabeli „Moduły witryny” (np. Newsletter).</span>
    </p>

    @if ($pluginsStatus)
        <p id="plugins-table-status" role="status" tabindex="-1" x-init="$nextTick(() => $el.focus())"
            class="mb-4 flex items-start gap-3 rounded-md bg-brand-light p-4 text-sm text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
            <i class="fa-solid fa-circle-check mt-0.5 text-brand-dark" aria-hidden="true"></i>
            <span>{{ $pluginsStatus }}</span>
        </p>
    @endif
    @if ($pluginsError)
        <p id="plugins-table-error" role="alert" tabindex="-1" x-init="$nextTick(() => $el.focus())"
            class="mb-4 flex items-start gap-3 rounded-md bg-red-50 p-4 text-sm font-bold text-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-800">
            <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ $pluginsError }}</span>
        </p>
    @endif

    @if ($pluginsBulkReady)
        <div class="mb-3 flex flex-col gap-3 rounded-md bg-gray-50 p-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink" x-cloak aria-live="polite">
                Zaznaczono: <strong x-text="psel.length">{{ count($pluginsPreselected) }}</strong>
                <span x-text="psel.length === 1 ? 'moduł' : ((psel.length % 10 >= 2 && psel.length % 10 <= 4 && (psel.length % 100 < 12 || psel.length % 100 > 14)) ? 'moduły' : 'modułów')">modułów</span>
            </p>
            <div role="group" aria-label="Akcje dla zaznaczonych modułów instalowanych" class="flex flex-wrap gap-2">
                <button type="submit" form="plugins-bulk-form" name="action" value="activate" @click="pguard($event)"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-play" aria-hidden="true"></i> Aktywuj zaznaczone
                </button>
                <button type="submit" form="plugins-bulk-form" name="action" value="deactivate" @click="pdeactivate($event)"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-4 text-sm font-bold text-ink ring-1 ring-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-pause" aria-hidden="true"></i> Dezaktywuj zaznaczone
                </button>
            </div>
        </div>
        <p x-show="pnote !== ''" x-cloak role="alert" class="mb-3 text-sm font-bold text-red-800" x-text="pnote"></p>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <caption class="sr-only">Moduły instalowane (całej instalacji): zaznacz wiersze i wybierz akcję albo użyj przycisku w wierszu</caption>
            <thead>
                <tr class="border-b-2 border-gray-300">
                    <th scope="col" class="w-14 p-0 align-middle">
                        @if ($pluginsBulkReady)
                            <label x-cloak class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                <input type="checkbox" :checked="allPluginsChecked" :indeterminate="somePluginsChecked"
                                    @change="toggleAllPlugins($event.target.checked)"
                                    class="h-5 w-5 rounded border-gray-600 text-brand-dark focus:ring-brand-dark">
                                <span class="sr-only">Zaznacz wszystkie widoczne moduły instalowane</span>
                            </label>
                            <noscript>
                                <a href="{{ $selfUrl }}#tabela-rozszerzen" class="inline-flex min-h-11 items-center px-1 text-xs font-bold text-brand-dark underline">Zaznacz wszystkie</a>
                            </noscript>
                        @else
                            <span class="sr-only">Zaznaczenie</span>
                        @endif
                    </th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Moduł</th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Stan</th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Wersja</th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Akcja</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cards as $card)
                    @php
                        $manifest = $card['manifest'];
                        $id = $card['id'];
                        $status = $card['status'];
                        $formId = 'plugin-module-form-'.$id;
                        $titleId = 'plugin-module-'.$id.'-title';
                    @endphp
                    <tr class="border-b border-gray-200" data-plugin-row="{{ $id }}"
                        @if ($filterable) x-show="visible(@js('plugin:'.$id))" @endif
                        :class="psel.includes(@js($id)) ? 'bg-brand-light' : ''">
                        <td class="p-0 align-top">
                            @if ($pluginsBulkReady)
                                <label class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                    <input type="checkbox" form="plugins-bulk-form" name="modules[]" value="{{ $id }}" x-model="psel"
                                        @checked(in_array($id, $pluginsPreselected, true))
                                        class="h-5 w-5 rounded border-gray-600 text-brand-dark focus:ring-brand-dark">
                                    <span class="sr-only">Zaznacz moduł: {{ $manifest->name }}</span>
                                </label>
                            @endif
                        </td>
                        <th scope="row" class="px-3 py-3 align-top font-normal">
                            <span class="flex items-start gap-3">
                                <i class="fa-solid {{ $card['icon'] }} mt-0.5 w-5 flex-none text-center text-base text-brand-dark" aria-hidden="true"></i>
                                <span class="min-w-0">
                                    <span id="{{ $titleId }}" class="block font-bold text-ink">{{ $manifest->name }}</span>
                                    @if ($manifest->description)
                                        <span class="mt-1 block text-sm text-muted">{{ $manifest->description }}</span>
                                    @endif
                                    @if ($manifest->requires)
                                        <span class="mt-1 block text-xs text-muted">Wymaga: {{ implode(', ', $manifest->requires) }}</span>
                                    @endif
                                </span>
                            </span>
                        </th>
                        <td class="whitespace-nowrap px-3 py-3 align-top text-ink">
                            <span class="inline-flex items-center gap-2 {{ $status === 'active' ? 'font-bold' : '' }}">
                                <i class="fa-solid {{ $status === 'active' ? 'fa-circle-check text-brand-dark' : ($status === 'inactive' ? 'fa-circle-pause text-muted' : 'fa-circle-down text-muted') }}" aria-hidden="true"></i>
                                {{ $card['status_label'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 align-top text-ink">v{{ $manifest->version }}</td>
                        <td class="whitespace-nowrap px-3 py-2 align-top">
                            @php
                                [$rowLabel, $rowIcon, $rowClass] = match ($status) {
                                    'active' => ['Dezaktywuj', 'fa-pause', 'bg-white text-ink ring-1 ring-gray-500 hover:bg-gray-100'],
                                    'inactive' => ['Aktywuj', 'fa-play', 'bg-brand-dark text-white hover:bg-ink'],
                                    default => ['Zainstaluj', 'fa-download', 'bg-brand-dark text-white hover:bg-ink'],
                                };
                            @endphp
                            <button type="submit" form="{{ $formId }}"
                                class="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 {{ $rowClass }}">
                                <i class="fa-solid {{ $rowIcon }}" aria-hidden="true"></i>
                                {{ $rowLabel }}<span class="sr-only">&nbsp;moduł {{ $manifest->name }}</span>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($pluginsBulkReady)
    @push('scripts')
        <form id="plugins-bulk-form" method="POST" action="{{ route('admin.moduly.masowo') }}" class="hidden">
            @csrf
            @if ($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
            @endif
        </form>
    @endpush
@endif
