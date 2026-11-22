{{-- Ustawienia → Moduły: tabela modułów witryny z zaznaczaniem wielu wierszy i akcjami masowymi.
     Zwykły formularz (bez JS): pola i przyciski wskazują atrybutem form="modules-bulk-form" formularz dodany
     przez @push('scripts') poza głównym formularzem ustawień (HTML nie pozwala zagnieżdżać <form>).
     Bez JS „Zaznacz wszystkie” to link (?zaznacz=moduly); z JS — pole w nagłówku, licznik i filtr z nadrzędnego
     x-data (visible(), anyVisible()). Zmiana zapisuje się od razu (SiteModulesController::modules).
     Wymaga: $moduleGroups, $defaultEnabledModules. --}}
@php
    $modulesBulkReady = \Illuminate\Support\Facades\Route::has('admin.ustawienia.moduly.masowo');
    $modulesStatus = session('modules_table_status');
    $modulesError = $errors->getBag('modulesTable')->first('modules');
    $modulesPreselected = request('zaznacz') === 'moduly' ? array_keys(\App\Models\SiteSetting::MODULES) : [];
    $pluginManager = app(\App\Modules\ModuleManager::class);
@endphp

<section id="tabela-modulow" class="mb-8 scroll-mt-24" aria-labelledby="modules-table-caption"
    x-data="{
        sel: @js($modulesPreselected),
        note: '',
        moduleKeys: @js(array_keys(\App\Models\SiteSetting::MODULES)),
        shown() { return this.moduleKeys.filter((k) => visible(k)); },
        get allShownChecked() { const v = this.shown(); return v.length > 0 && v.every((k) => this.sel.includes(k)); },
        get someShownChecked() { return ! this.allShownChecked && this.shown().some((k) => this.sel.includes(k)); },
        toggleAll(on) { const v = this.shown(); this.sel = on ? [...new Set([...this.sel, ...v])] : this.sel.filter((k) => ! v.includes(k)); },
        guard(e) { if (this.sel.length === 0) { e.preventDefault(); this.note = 'Zaznacz co najmniej jeden moduł, a potem wybierz akcję.'; } else { this.note = ''; } },
    }">

    @if ($modulesStatus)
        <p id="modules-table-status" role="status" tabindex="-1" x-init="$nextTick(() => $el.focus())"
            class="mb-4 flex items-start gap-3 rounded-md bg-brand-light p-4 text-sm text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
            <i class="fa-solid fa-circle-check mt-0.5 text-brand-dark" aria-hidden="true"></i>
            <span>{{ $modulesStatus }}</span>
        </p>
    @endif
    @if ($modulesError)
        <p id="modules-table-error" role="alert" tabindex="-1" x-init="$nextTick(() => $el.focus())"
            class="mb-4 flex items-start gap-3 rounded-md bg-red-50 p-4 text-sm font-bold text-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-800">
            <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ $modulesError }}</span>
        </p>
    @endif

    @if ($modulesBulkReady)
        {{-- Pasek akcji masowych --}}
        <div class="mb-3 flex flex-col gap-3 rounded-md bg-gray-50 p-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink" x-cloak aria-live="polite">
                Zaznaczono: <strong x-text="sel.length">{{ count($modulesPreselected) }}</strong>
                <span x-text="sel.length === 1 ? 'moduł' : ((sel.length % 10 >= 2 && sel.length % 10 <= 4 && (sel.length % 100 < 12 || sel.length % 100 > 14)) ? 'moduły' : 'modułów')">modułów</span>
            </p>
            <div role="group" aria-label="Akcje dla zaznaczonych modułów" class="flex flex-wrap gap-2">
                <button type="submit" form="modules-bulk-form" name="action" value="enable" @click="guard($event)"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-toggle-on" aria-hidden="true"></i> Włącz zaznaczone
                </button>
                <button type="submit" form="modules-bulk-form" name="action" value="disable" @click="guard($event)"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-4 text-sm font-bold text-ink ring-1 ring-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-toggle-off" aria-hidden="true"></i> Wyłącz zaznaczone
                </button>
            </div>
        </div>
        <p x-show="note !== ''" x-cloak role="alert" class="mb-3 text-sm font-bold text-red-800" x-text="note"></p>
    @else
        <p class="mb-3 rounded-md bg-gray-50 p-4 text-sm text-ink">Akcje masowe będą dostępne po wdrożeniu nowej wersji panelu (odświeżeniu tras).</p>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <caption id="modules-table-caption" class="mb-2 text-left text-base font-bold text-ink">
                Moduły witryny
                <span class="block text-sm font-normal text-muted">Zaznacz moduły i wybierz akcję albo użyj przycisku w wierszu. Zmiana działa od razu, bez „Zapisz”.</span>
            </caption>
            <thead>
                <tr class="border-b-2 border-gray-300">
                    <th scope="col" class="w-14 p-0 align-middle">
                        @if ($modulesBulkReady)
                            <label x-cloak class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                <input type="checkbox" :checked="allShownChecked" :indeterminate="someShownChecked"
                                    @change="toggleAll($event.target.checked)"
                                    class="h-5 w-5 rounded border-gray-600 text-brand-dark focus:ring-brand-dark">
                                <span class="sr-only">Zaznacz wszystkie widoczne moduły</span>
                            </label>
                            <noscript>
                                <a href="{{ route('admin.ustawienia.edit', ['tab' => 'modules', 'zaznacz' => 'moduly']) }}#tabela-modulow"
                                    class="inline-flex min-h-11 items-center px-1 text-xs font-bold text-brand-dark underline">Zaznacz wszystkie</a>
                            </noscript>
                        @else
                            <span class="sr-only">Zaznaczenie</span>
                        @endif
                    </th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Moduł</th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Stan</th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink"><span class="sr-only">Akcja w wierszu</span><span aria-hidden="true">Akcja</span></th>
                </tr>
            </thead>
            @foreach ($moduleGroups as $groupKey => $group)
                <tbody x-show="anyVisible(@js(array_keys($group['modules'])))">
                    <tr>
                        <th scope="rowgroup" colspan="4" id="modules-group-{{ $groupKey }}" class="bg-gray-50 px-3 py-2 text-sm font-bold text-ink">{{ $group['label'] }}</th>
                    </tr>
                    @foreach ($group['modules'] as $key => $module)
                        @php
                            $isOn = in_array($key, $defaultEnabledModules, true);
                            $pluginInactive = $pluginManager->get($key) !== null && ! $pluginManager->isActive($key);
                        @endphp
                        <tr x-show="visible(@js($key))" class="border-b border-gray-200" data-module-row="{{ $key }}"
                            :class="sel.includes(@js($key)) ? 'bg-brand-light' : ''">
                            <td class="p-0 align-top">
                                @if ($modulesBulkReady)
                                    <label class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                        <input type="checkbox" form="modules-bulk-form" name="modules[]" value="{{ $key }}" x-model="sel"
                                            @checked(in_array($key, $modulesPreselected, true))
                                            class="h-5 w-5 rounded border-gray-600 text-brand-dark focus:ring-brand-dark">
                                        <span class="sr-only">Zaznacz moduł: {{ $module['label'] }}</span>
                                    </label>
                                @endif
                            </td>
                            <th scope="row" class="px-3 py-3 align-top font-normal">
                                <span class="flex items-start gap-3">
                                    <i class="fa-solid {{ $module['icon'] }} mt-0.5 w-5 flex-none text-center text-base text-brand-dark" aria-hidden="true"></i>
                                    <span class="min-w-0">
                                        <span class="block font-bold text-ink">{{ $module['label'] }}</span>
                                        @if ($module['desc'] !== '')
                                            <span id="module-desc-{{ $key }}" class="mt-1 block text-sm text-muted">{{ $module['desc'] }}@unless (\App\Support\Edition::allows($key)) <span class="font-bold text-ink">Wersja płatna — w darmowej tylko podgląd dotychczasowych treści.</span>@endunless</span>
                                        @endif
                                        @if ($pluginInactive)
                                            <span class="mt-1 block text-sm font-bold text-ink">Rozszerzenie jest nieaktywne w Modułach systemu — włącz je tam, żeby moduł działał.</span>
                                        @endif
                                    </span>
                                </span>
                            </th>
                            <td class="whitespace-nowrap px-3 py-3 align-top text-ink">
                                @if ($isOn)
                                    <span class="inline-flex items-center gap-2 font-bold"><i class="fa-solid fa-circle-check text-brand-dark" aria-hidden="true"></i> Włączony</span>
                                @else
                                    <span class="inline-flex items-center gap-2"><i class="fa-regular fa-circle text-muted" aria-hidden="true"></i> Wyłączony</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                @if ($modulesBulkReady)
                                    <button type="submit" form="modules-bulk-form" name="row_action" value="{{ $isOn ? 'disable' : 'enable' }}|{{ $key }}"
                                        class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 {{ $isOn ? 'bg-white text-ink ring-1 ring-gray-500 hover:bg-gray-100' : 'bg-brand-dark text-white hover:bg-ink' }}">
                                        {{ $isOn ? 'Wyłącz' : 'Włącz' }}<span class="sr-only">&nbsp;moduł {{ $module['label'] }}</span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            @endforeach
        </table>
    </div>
</section>

@if ($modulesBulkReady)
    @push('scripts')
        <form id="modules-bulk-form" method="POST" action="{{ route('admin.ustawienia.moduly.masowo') }}" class="hidden">
            @csrf
        </form>
    @endpush
@endif
