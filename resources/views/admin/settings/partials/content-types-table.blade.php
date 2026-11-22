{{-- Ustawienia → Moduły: tabela „Typy treści i układy” (blocked_options) z zaznaczaniem wielu wierszy i akcjami
     masowymi Udostępnij / Ukryj. Jak tabela modułów: zwykły formularz poza głównym (form="options-bulk-form"),
     bez JS „Zaznacz wszystkie” to link (?zaznacz=typy). Opcje z UNBLOCKABLE_OPTIONS są zawsze dostępne (bez pola
     wyboru). W każdej grupie musi zostać co najmniej jedna dostępna opcja (SiteModulesController::options).
     Wymaga: $optionGroups, $optionAvailable (closure grupa, wartość → bool). --}}
@php
    $optionsBulkReady = \Illuminate\Support\Facades\Route::has('admin.ustawienia.typy-tresci.masowo');
    $optionsStatus = session('options_table_status');
    $optionsError = $errors->getBag('optionsTable')->first('options');
    $selectableOptions = [];
    foreach ($optionGroups as $group => $options) {
        foreach (array_keys($options) as $value) {
            if (! in_array((string) $value, \App\Models\SiteSetting::UNBLOCKABLE_OPTIONS[$group] ?? [], true)) {
                $selectableOptions[] = "opt:{$group}:{$value}";
            }
        }
    }
    $optionsPreselected = request('zaznacz') === 'typy' ? $selectableOptions : [];
@endphp

<section id="tabela-typow-tresci" class="mt-10 scroll-mt-24" aria-labelledby="content-options-heading" data-card="typy-tresci" data-card-label="Typy treści i układy"
    x-data="{
        osel: @js($optionsPreselected),
        onote: '',
        optionKeys: @js($selectableOptions),
        oshown() { return this.optionKeys.filter((k) => visible(k)); },
        get allOptionsChecked() { const v = this.oshown(); return v.length > 0 && v.every((k) => this.osel.includes(k)); },
        get someOptionsChecked() { return ! this.allOptionsChecked && this.oshown().some((k) => this.osel.includes(k)); },
        toggleAllOptions(on) { const v = this.oshown(); this.osel = on ? [...new Set([...this.osel, ...v])] : this.osel.filter((k) => ! v.includes(k)); },
        oguard(e) { if (this.osel.length === 0) { e.preventDefault(); this.onote = 'Zaznacz co najmniej jeden typ treści lub układ, a potem wybierz akcję.'; } else { this.onote = ''; } },
    }">
    <h2 id="content-options-heading" class="mb-1 text-lg font-bold text-ink" data-card-title>Typy treści i układy</h2>
    <p class="mb-4 text-sm text-muted">Ukryta opcja znika z list wyboru przy tworzeniu i edycji. Treści, które już jej używają, działają dalej i zachowują swój wybór. W każdej grupie musi zostać co najmniej jedna dostępna opcja. Zmiana działa od razu, bez „Zapisz”.</p>

    @if ($optionsStatus)
        <p id="options-table-status" role="status" tabindex="-1" x-init="$nextTick(() => $el.focus())"
            class="mb-4 flex items-start gap-3 rounded-md bg-brand-light p-4 text-sm text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
            <i class="fa-solid fa-circle-check mt-0.5 text-brand-dark" aria-hidden="true"></i>
            <span>{{ $optionsStatus }}</span>
        </p>
    @endif
    @if ($optionsError)
        <p id="options-table-error" role="alert" tabindex="-1" x-init="$nextTick(() => $el.focus())"
            class="mb-4 flex items-start gap-3 rounded-md bg-red-50 p-4 text-sm font-bold text-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-800">
            <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ $optionsError }}</span>
        </p>
    @endif

    @if ($optionsBulkReady)
        <div class="mb-3 flex flex-col gap-3 rounded-md bg-gray-50 p-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink" x-cloak aria-live="polite">
                Zaznaczono: <strong x-text="osel.length">{{ count($optionsPreselected) }}</strong>
                <span x-text="osel.length === 1 ? 'opcję' : ((osel.length % 10 >= 2 && osel.length % 10 <= 4 && (osel.length % 100 < 12 || osel.length % 100 > 14)) ? 'opcje' : 'opcji')">opcji</span>
            </p>
            <div role="group" aria-label="Akcje dla zaznaczonych typów treści i układów" class="flex flex-wrap gap-2">
                <button type="submit" form="options-bulk-form" name="action" value="show" @click="oguard($event)"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Udostępnij zaznaczone
                </button>
                <button type="submit" form="options-bulk-form" name="action" value="hide" @click="oguard($event)"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-4 text-sm font-bold text-ink ring-1 ring-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Ukryj zaznaczone
                </button>
            </div>
        </div>
        <p x-show="onote !== ''" x-cloak role="alert" class="mb-3 text-sm font-bold text-red-800" x-text="onote"></p>
    @else
        <p class="mb-3 rounded-md bg-gray-50 p-4 text-sm text-ink">Akcje masowe będą dostępne po wdrożeniu nowej wersji panelu (odświeżeniu tras).</p>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <caption class="sr-only">Typy treści i układy: zaznacz wiersze i wybierz akcję albo użyj przycisku w wierszu</caption>
            <thead>
                <tr class="border-b-2 border-gray-300">
                    <th scope="col" class="w-14 p-0 align-middle">
                        @if ($optionsBulkReady)
                            <label x-cloak class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                <input type="checkbox" :checked="allOptionsChecked" :indeterminate="someOptionsChecked"
                                    @change="toggleAllOptions($event.target.checked)"
                                    class="h-5 w-5 rounded border-gray-600 text-brand-dark focus:ring-brand-dark">
                                <span class="sr-only">Zaznacz wszystkie widoczne typy treści i układy</span>
                            </label>
                            <noscript>
                                <a href="{{ route('admin.ustawienia.edit', ['tab' => 'modules', 'zaznacz' => 'typy']) }}#tabela-typow-tresci"
                                    class="inline-flex min-h-11 items-center px-1 text-xs font-bold text-brand-dark underline">Zaznacz wszystkie</a>
                            </noscript>
                        @else
                            <span class="sr-only">Zaznaczenie</span>
                        @endif
                    </th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Typ lub układ</th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Stan</th>
                    <th scope="col" class="px-3 py-2 font-bold text-ink">Akcja</th>
                </tr>
            </thead>
            @foreach ($optionGroups as $group => $options)
                @php
                    $groupLabel = \App\Models\SiteSetting::BLOCKABLE_GROUP_LABELS[$group] ?? $group;
                    $protected = \App\Models\SiteSetting::UNBLOCKABLE_OPTIONS[$group] ?? [];
                    $optionKeys = array_map(fn ($v) => "opt:{$group}:{$v}", array_keys($options));
                @endphp
                <tbody x-show="anyVisible(@js($optionKeys))">
                    <tr>
                        <th scope="rowgroup" colspan="4" id="options-group-{{ $group }}" class="bg-gray-50 px-3 py-2 text-sm font-bold text-ink">{{ $groupLabel }}</th>
                    </tr>
                    @foreach ($options as $value => $label)
                        @php
                            $stateKey = "opt:{$group}:{$value}";
                            $isAvailable = $optionAvailable($group, (string) $value);
                            $isProtected = in_array((string) $value, $protected, true);
                        @endphp
                        <tr x-show="visible(@js($stateKey))" class="border-b border-gray-200" data-option-row="{{ $stateKey }}"
                            :class="osel.includes(@js($stateKey)) ? 'bg-brand-light' : ''">
                            <td class="p-0 align-top">
                                @if ($optionsBulkReady && ! $isProtected)
                                    <label class="flex h-11 w-11 cursor-pointer items-center justify-center">
                                        <input type="checkbox" form="options-bulk-form" name="options[]" value="{{ $stateKey }}"
                                            x-model="osel"
                                            @checked(in_array($stateKey, $optionsPreselected, true))
                                            class="h-5 w-5 rounded border-gray-600 text-brand-dark focus:ring-brand-dark">
                                        <span class="sr-only">Zaznacz: {{ $label }} ({{ $groupLabel }})</span>
                                    </label>
                                @endif
                            </td>
                            <th scope="row" class="px-3 py-3 align-top font-normal">
                                <span class="block font-bold text-ink">{{ $label }}</span>
                                @if ($isProtected)
                                    <span class="mt-1 block text-sm text-muted">Zawsze dostępny — wariant awaryjny, gdy nic innego nie wybrano.</span>
                                @endif
                            </th>
                            <td class="whitespace-nowrap px-3 py-3 align-top text-ink">
                                @if ($isAvailable)
                                    <span class="inline-flex items-center gap-2 font-bold"><i class="fa-solid fa-eye text-brand-dark" aria-hidden="true"></i> Dostępny</span>
                                @else
                                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-eye-slash text-muted" aria-hidden="true"></i> Ukryty</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                @if ($optionsBulkReady && ! $isProtected)
                                    <button type="submit" form="options-bulk-form" name="row_action" value="{{ $isAvailable ? 'hide' : 'show' }}|{{ $group }}|{{ $value }}"
                                        class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 {{ $isAvailable ? 'bg-white text-ink ring-1 ring-gray-500 hover:bg-gray-100' : 'bg-brand-dark text-white hover:bg-ink' }}">
                                        {{ $isAvailable ? 'Ukryj' : 'Udostępnij' }}<span class="sr-only">: {{ $label }} ({{ $groupLabel }})</span>
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

@if ($optionsBulkReady)
    @push('scripts')
        <form id="options-bulk-form" method="POST" action="{{ route('admin.ustawienia.typy-tresci.masowo') }}" class="hidden">
            @csrf
        </form>
    @endpush
@endif
