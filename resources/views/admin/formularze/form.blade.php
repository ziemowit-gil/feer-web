@extends('admin.layout')

@section('title', $form->exists ? 'Edytuj formularz' : 'Nowy formularz')

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-bold">Popraw błędy:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" data-no-dirty
        action="{{ $form->exists ? route('admin.formularze.update', $form) : route('admin.formularze.store') }}"
        class="space-y-5"
        x-data="formBuilder({{ Js::from($form->fields ?? []) }}, @js($errors->keys() && collect($errors->keys())->contains(fn ($k) => ! str_starts_with($k, 'fields')) ? 'settings' : 'fields'))"
        @input="dirty = true" @change="dirty = true" @submit="dirty = false"
        @keydown.window="globalKey($event)">

        @csrf
        @if ($form->exists) @method('PUT') @endif

        {{-- ── Pasek edytora: nazwa, stan, zakładki i zapis (przyklejony u góry) ── --}}
        <div class="sticky top-0 z-30 -mx-4 -mt-4 bg-white px-4 pb-0 pt-3 shadow-sm sm:-mx-6 sm:-mt-6 sm:px-6">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <div class="min-w-0 flex-1">
                    <label for="title" class="sr-only">Nazwa formularza (wymagana)</label>
                    <input id="title" name="title" type="text" required
                        value="{{ old('title', $form->title) }}"
                        @input="syncSlug($el.value)"
                        placeholder="Nazwa formularza"
                        class="w-full rounded-md border-0 bg-transparent px-2 py-1 text-xl font-bold text-ink hover:bg-gray-50 focus:bg-gray-50 focus:ring-2 focus:ring-brand-dark">
                    <p class="px-2 text-xs text-muted">
                        <span x-text="inputCount + ' ' + (inputCount === 1 ? 'pole' : ([2,3,4].includes(inputCount % 10) && ! [12,13,14].includes(inputCount % 100) ? 'pola' : 'pól'))"></span>
                        <span x-show="stepCount > 0" x-text="' · ' + (stepCount + 1) + ' kroki'"></span>
                        · <span x-text="dirty ? 'niezapisane zmiany' : 'zapisano'" :class="dirty ? 'font-bold text-ink' : ''"></span>
                    </p>
                    @error('title') <p class="px-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="undo()" :disabled="! history.length" title="Cofnij (Ctrl+Z)"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-md bg-gray-100 px-3 text-sm font-bold text-ink hover:bg-brand-light disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Cofnij
                    </button>
                    @if ($form->exists)
                        <a href="{{ route('formularz.show', $form->slug) }}" target="_blank" rel="noopener"
                            class="inline-flex min-h-10 items-center gap-1.5 rounded-md bg-gray-100 px-3 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Na stronie<span class="sr-only"> (otwiera się w nowej karcie, pokazuje zapisaną wersję)</span>
                        </a>
                        <a href="{{ route('admin.formularze.zgloszenia', $form) }}"
                            class="inline-flex min-h-10 items-center gap-1.5 rounded-md bg-gray-100 px-3 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <i class="fa-solid fa-inbox" aria-hidden="true"></i> Zgłoszenia ({{ $form->submissions()->count() }})
                        </a>
                    @endif
                    <a href="{{ route('admin.formularze.index') }}" class="inline-flex min-h-10 items-center px-2 text-sm text-muted hover:text-brand-dark focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</a>
                    <button type="submit"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-md bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Zapisz
                    </button>
                </div>
            </div>

            <div role="tablist" aria-label="Sekcje formularza" class="mt-2 flex gap-1" @keydown.arrow-right.prevent="nextTab(1)" @keydown.arrow-left.prevent="nextTab(-1)">
                @foreach (['fields' => ['Pola', 'fa-table-list'], 'settings' => ['Ustawienia', 'fa-sliders'], 'share' => ['Udostępnianie', 'fa-share-nodes']] as $tabKey => [$tabLabel, $tabIcon])
                    @continue($tabKey === 'share' && ! $form->exists)
                    <button type="button" role="tab" id="tab-{{ $tabKey }}" aria-controls="panel-{{ $tabKey }}"
                        :aria-selected="tab === '{{ $tabKey }}'" :tabindex="tab === '{{ $tabKey }}' ? 0 : -1"
                        @click="tab = '{{ $tabKey }}'"
                        class="-mb-px inline-flex min-h-11 items-center gap-2 border-b-4 px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark"
                        :class="tab === '{{ $tabKey }}' ? 'border-brand-dark text-ink' : 'border-transparent text-muted hover:text-ink'">
                        <i class="fa-solid {{ $tabIcon }}" aria-hidden="true"></i> {{ $tabLabel }}
                    </button>
                @endforeach
            </div>
        </div>


        <div role="tabpanel" id="panel-fields" aria-labelledby="tab-fields" x-show="tab === 'fields'">
        {{-- Edytor wizualny pól: paleta | podgląd formularza (płótno) | właściwości wybranego pola.
             Dane pól wysyłają ukryte pola na dole — panel właściwości nie ma atrybutów name. --}}
        @php
            $paletteGroups = [
                'Podstawowe' => ['text' => 'fa-font', 'textarea' => 'fa-align-left', 'email' => 'fa-at', 'tel' => 'fa-phone', 'number' => 'fa-hashtag', 'date' => 'fa-calendar', 'time' => 'fa-clock', 'url' => 'fa-link'],
                'Wybór' => ['select' => 'fa-caret-down', 'radio' => 'fa-circle-dot', 'checkboxes' => 'fa-list-check', 'checkbox' => 'fa-square-check', 'rating' => 'fa-star'],
                'Specjalne' => ['file' => 'fa-paperclip', 'consent' => 'fa-shield-halved', 'krs' => 'fa-building-columns'],
                'Układ' => ['heading' => 'fa-heading', 'link' => 'fa-arrow-up-right-from-square', 'step' => 'fa-shoe-prints'],
            ];
            $fieldInput = 'w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark';
            $propLabel = 'mb-1 block text-xs font-bold uppercase tracking-wide text-muted';
        @endphp
        <div class="rounded-md bg-white p-4 sm:p-5">
            <h2 class="sr-only">Pola formularza</h2>
            <p class="mb-4 text-sm text-muted">
                Kliknij typ pola po lewej (albo przeciągnij go na podgląd), potem kliknij pole w podglądzie, aby je ustawić po prawej.
                Skróty na zaznaczonym polu: <kbd class="rounded bg-gray-100 px-1">Alt</kbd>+<kbd class="rounded bg-gray-100 px-1">↑</kbd>/<kbd class="rounded bg-gray-100 px-1">↓</kbd> przesuwa,
                <kbd class="rounded bg-gray-100 px-1">Ctrl</kbd>+<kbd class="rounded bg-gray-100 px-1">D</kbd> powiela, <kbd class="rounded bg-gray-100 px-1">Delete</kbd> usuwa, <kbd class="rounded bg-gray-100 px-1">Ctrl</kbd>+<kbd class="rounded bg-gray-100 px-1">Z</kbd> cofa.
            </p>

            <div class="grid gap-5 lg:grid-cols-[12rem_minmax(0,1fr)_20rem] xl:grid-cols-[13rem_minmax(0,1fr)_22rem]">
                {{-- ── Paleta ─────────────────────────────────────────── --}}
                <nav aria-label="Dodaj pole" class="space-y-4 lg:sticky lg:top-36 lg:max-h-[calc(100vh-10rem)] lg:self-start lg:overflow-y-auto">
                    @foreach ($paletteGroups as $groupLabel => $groupTypes)
                        <div>
                            <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted">{{ $groupLabel }}</p>
                            <ul class="grid grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-1" role="list">
                                @foreach ($groupTypes as $type => $icon)
                                    @continue(! isset($types[$type]))
                                    <li>
                                        <button type="button" draggable="true"
                                            @click="addField('{{ $type }}')"
                                            @dragstart="dragStart($event, 'new:{{ $type }}')" @dragend="dragEnd()"
                                            title="{{ $types[$type] }}"
                                            class="flex min-h-10 w-full cursor-grab items-center gap-2 rounded-md bg-gray-100 px-3 py-1.5 text-left text-sm font-medium text-ink hover:bg-brand-light hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            <i class="fa-solid {{ $icon }} w-4 shrink-0 text-center text-brand-dark" aria-hidden="true"></i>
                                            <span class="truncate">{{ \Illuminate\Support\Str::before($types[$type], ' (') }}</span>
                                            <span class="sr-only">— dodaj pole</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>

                {{-- ── Płótno: podgląd formularza ─────────────────────── --}}
                <div class="min-w-0">
                    <div id="fields-list" class="min-h-48 space-y-1 rounded-md bg-gray-50 p-3 sm:p-5"
                        @keydown="canvasKey($event)"
                        @dragover.prevent="dragOverEnd($event)" @drop.prevent="drop()"
                        aria-label="Podgląd formularza" role="region">
                        <template x-for="(field, index) in fields" :key="field._id">
                            <div>
                                <div x-show="dropIndex === index" class="my-1 h-1 rounded bg-brand-dark" aria-hidden="true"></div>
                                <div class="group relative rounded-md p-3 transition"
                                    :class="selectedId === field._id ? 'bg-brand-light ring-2 ring-brand-dark' : 'bg-white hover:bg-brand-light/60'"
                                    draggable="true"
                                    @dragstart="dragStart($event, 'move:' + field._id)" @dragend="dragEnd()"
                                    @dragover.prevent.stop="dragOverItem($event, index)" @drop.prevent.stop="drop()">

                                    {{-- Przycisk wyboru pola zakrywa podgląd (podgląd jest tylko obrazkiem — inert) --}}
                                    <button type="button" class="absolute inset-0 z-10 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                        @click="select(field._id)" :aria-pressed="selectedId === field._id"
                                        :aria-label="'Edytuj pole ' + (index + 1) + ': ' + (field.label || '(bez etykiety)') + ' — ' + typeName(field.type)"></button>

                                    <div inert aria-hidden="true" class="pointer-events-none pr-24">
                                        {{-- Nagłówek sekcji / nowy krok --}}
                                        <template x-if="field.type === 'heading'">
                                            <div><p class="text-lg font-bold text-ink" x-text="field.label || 'Nagłówek sekcji'"></p><p class="text-sm text-muted" x-show="field.help_text" x-text="field.help_text"></p></div>
                                        </template>
                                        <template x-if="field.type === 'step'">
                                            <div class="flex items-center gap-2 text-sm font-bold text-brand-dark"><i class="fa-solid fa-shoe-prints"></i> <span x-text="'Krok: ' + (field.label || 'bez tytułu')"></span><span class="h-0.5 flex-1 bg-brand-dark/40"></span></div>
                                        </template>

                                        <template x-if="field.type === 'link'">
                                            <div class="rounded-md bg-brand-light p-3">
                                                <p class="whitespace-pre-line text-sm text-ink" x-show="field.help_text" x-text="field.help_text"></p>
                                                <span class="mt-2 inline-flex min-h-9 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white"><span x-text="field.label || 'Przejdź dalej'"></span> <i class="fa-solid" :class="field.new_tab ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right'"></i></span>
                                                <p class="mt-1 break-all text-xs text-muted" x-text="field.url ? '→ ' + field.url : '→ (ustaw adres)'"></p>
                                            </div>
                                        </template>

                                        {{-- Zgoda / checkbox --}}
                                        <template x-if="['checkbox', 'consent'].includes(field.type)">
                                            <label class="flex items-start gap-2 text-sm text-ink"><input type="checkbox" class="mt-0.5 rounded border-gray-400"><span><span x-text="field.label || 'Treść zgody'"></span><span x-show="field.type === 'consent'"> Szczegóły w <u>Polityce prywatności</u>.</span><span x-show="field.required" class="ml-0.5 font-bold text-red-600">*</span></span></label>
                                        </template>

                                        {{-- Pozostałe: etykieta + kontrolka --}}
                                        <template x-if="! ['heading', 'step', 'link', 'checkbox', 'consent'].includes(field.type)">
                                            <div>
                                                <p class="text-sm font-semibold text-ink"><span x-text="field.label || '(bez etykiety)'"></span><span x-show="field.required" class="ml-0.5 font-bold text-red-600">*</span></p>
                                                <p class="text-xs text-muted" x-show="field.help_text" x-text="field.help_text"></p>
                                                <div class="mt-1.5">
                                                    <template x-if="field.type === 'textarea'"><div class="h-16 rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-400" x-text="field.placeholder"></div></template>
                                                    <template x-if="field.type === 'select'"><div class="flex h-9 items-center justify-between rounded border border-gray-300 bg-white px-2 text-sm text-gray-500"><span x-text="optionList(field)[0] || '— wybierz —'"></span><i class="fa-solid fa-caret-down"></i></div></template>
                                                    <template x-if="['radio', 'checkboxes'].includes(field.type)">
                                                        <div class="space-y-1">
                                                            <template x-for="opt in (optionList(field).length ? optionList(field) : ['Opcja 1', 'Opcja 2'])" :key="opt">
                                                                <p class="flex items-center gap-2 text-sm text-ink"><span class="inline-block h-4 w-4 border border-gray-400 bg-white" :class="field.type === 'radio' ? 'rounded-full' : 'rounded-sm'"></span><span x-text="opt"></span></p>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="field.type === 'rating'"><div class="flex gap-1.5"><template x-for="n in 5" :key="n"><span class="flex h-8 w-8 items-center justify-center rounded border-2 border-gray-300 bg-white text-sm font-bold text-ink" x-text="n"></span></template></div></template>
                                                    <template x-if="field.type === 'file'"><div class="flex h-9 items-center gap-2 rounded border border-dashed border-gray-400 bg-white px-2 text-sm text-gray-500"><i class="fa-solid fa-paperclip"></i> <span x-text="'Wybierz plik (' + (field.accept || '{{ \App\Models\FormDefinition::FILE_DEFAULT_ACCEPT }}') + ')'"></span></div></template>
                                                    <template x-if="field.type === 'krs'"><div class="flex gap-2"><div class="flex h-9 w-40 items-center rounded border border-gray-300 bg-white px-2 font-mono text-sm text-gray-400">0000123456</div><span class="flex h-9 items-center rounded bg-ink px-3 text-xs font-bold text-white">Pobierz dane z KRS</span></div></template>
                                                    <template x-if="['text', 'email', 'tel', 'number', 'date', 'time', 'url'].includes(field.type)"><div class="flex h-9 items-center rounded border border-gray-300 bg-white px-2 text-sm text-gray-400" x-text="field.placeholder || sampleFor(field.type)"></div></template>
                                                </div>
                                            </div>
                                        </template>

                                        <p class="mt-1.5 flex flex-wrap gap-x-3 text-xs text-muted">
                                            <span x-show="field.show_if_uid"><i class="fa-solid fa-code-branch mr-1"></i>pokazywane warunkowo</span>
                                            <span x-show="field.krs_fill"><i class="fa-solid fa-building-columns mr-1"></i>uzupełniane z KRS</span>
                                        </p>
                                    </div>

                                    {{-- Narzędzia pola (nad przyciskiem wyboru) --}}
                                    <div class="absolute right-2 top-2 z-20 flex gap-0.5">
                                        <button type="button" @click="moveUp(index)" :disabled="index === 0" :aria-label="'Przesuń pole „' + field.label + '” w górę'"
                                            class="rounded p-1.5 text-muted hover:bg-white hover:text-brand-dark disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-arrow-up text-xs" aria-hidden="true"></i></button>
                                        <button type="button" @click="moveDown(index)" :disabled="index === fields.length - 1" :aria-label="'Przesuń pole „' + field.label + '” w dół'"
                                            class="rounded p-1.5 text-muted hover:bg-white hover:text-brand-dark disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i></button>
                                        <button type="button" @click="duplicateField(index)" :aria-label="'Powiel pole „' + field.label + '”'"
                                            class="rounded p-1.5 text-muted hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-regular fa-copy text-xs" aria-hidden="true"></i></button>
                                        <button type="button" @click="removeField(index)" :aria-label="'Usuń pole „' + field.label + '”'"
                                            class="rounded p-1.5 text-muted hover:bg-white hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600"><i class="fa-solid fa-trash-can text-xs" aria-hidden="true"></i></button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="dropIndex === fields.length && fields.length" class="my-1 h-1 rounded bg-brand-dark" aria-hidden="true"></div>

                        <template x-if="fields.length === 0">
                            <p class="rounded-md border-2 border-dashed border-gray-300 py-10 text-center text-sm text-muted">
                                Formularz jest pusty — kliknij typ pola w palecie albo przeciągnij go tutaj.
                            </p>
                        </template>
                        <div class="mt-3 flex justify-end" x-show="fields.some(f => ! ['heading', 'step', 'link'].includes(f.type))" aria-hidden="true">
                            <span class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white">Wyślij zgłoszenie <i class="fa-solid fa-paper-plane"></i></span>
                        </div>
                    </div>
                    <p class="sr-only" role="status" aria-live="polite" x-text="announce"></p>
                </div>

                {{-- ── Właściwości wybranego pola ─────────────────────── --}}
                <aside aria-labelledby="field-props-heading" class="lg:sticky lg:top-36 lg:max-h-[calc(100vh-10rem)] lg:self-start lg:overflow-y-auto">
                    <div class="rounded-md bg-gray-50 p-4">
                        <h3 id="field-props-heading" class="mb-3 text-sm font-bold text-ink">
                            <i class="fa-solid fa-sliders mr-1 text-brand-dark" aria-hidden="true"></i>
                            <span x-text="sel ? 'Ustawienia pola ' + (selIndex + 1) : 'Ustawienia pola'"></span>
                        </h3>
                        <p x-show="! sel" class="text-sm text-muted">Kliknij pole w podglądzie, aby je edytować.</p>

                        <template x-if="sel">
                            <div class="space-y-3">
                                <div>
                                    <label for="prop-label" class="{{ $propLabel }}" x-text="sel.type === 'consent' || sel.type === 'checkbox' ? 'Treść zgody / etykieta' : (sel.type === 'step' ? 'Tytuł kroku' : (sel.type === 'heading' ? 'Nagłówek' : (sel.type === 'link' ? 'Napis na przycisku' : 'Etykieta pola')))">Etykieta pola</label>
                                    <textarea x-show="['consent', 'checkbox'].includes(sel.type)" id="prop-label-long" rows="3" x-model="sel.label" class="{{ $fieldInput }}" aria-label="Treść zgody"></textarea>
                                    <input x-show="! ['consent', 'checkbox'].includes(sel.type)" id="prop-label" type="text" x-model="sel.label" placeholder="np. Imię i nazwisko" class="{{ $fieldInput }}">
                                </div>
                                <div>
                                    <label for="prop-type" class="{{ $propLabel }}">Typ pola</label>
                                    <select id="prop-type" x-model="sel.type" class="{{ $fieldInput }}">
                                        @foreach ($types as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="! ['heading', 'step', 'link', 'checkbox', 'consent', 'radio', 'checkboxes', 'rating', 'file'].includes(sel.type)">
                                    <label for="prop-placeholder" class="{{ $propLabel }}">Tekst w pustym polu</label>
                                    <input id="prop-placeholder" type="text" x-model="sel.placeholder" class="{{ $fieldInput }}">
                                </div>
                                <div>
                                    <label for="prop-help" class="{{ $propLabel }}" x-text="sel.type === 'heading' ? 'Opis sekcji' : (sel.type === 'step' ? 'Opis kroku' : (sel.type === 'link' ? 'Tekst nad przyciskiem' : 'Tekst pomocy pod etykietą'))">Tekst pomocy</label>
                                    <textarea x-show="sel.type === 'link'" id="prop-help-long" rows="4" x-model="sel.help_text" class="{{ $fieldInput }}" aria-label="Tekst nad przyciskiem"></textarea>
                                    <input x-show="sel.type !== 'link'" id="prop-help" type="text" x-model="sel.help_text" class="{{ $fieldInput }}">
                                </div>
                                <div x-show="['select', 'radio', 'checkboxes'].includes(sel.type)">
                                    <label for="prop-options" class="{{ $propLabel }}">Odpowiedzi — każda w osobnym wierszu</label>
                                    <textarea id="prop-options" rows="4" :value="optionList(sel).join('\n')" @input="sel.options = $event.target.value.split('\n').map(o => o.replace(/\s*,\s*/g, ' ').trim()).filter(Boolean).join(', ')"
                                        placeholder="Tak&#10;Nie&#10;Nie wiem" class="{{ $fieldInput }}"></textarea>
                                </div>
                                <div x-show="sel.type === 'file'" class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <label for="prop-accept" class="{{ $propLabel }}">Typy plików</label>
                                        <input id="prop-accept" type="text" x-model="sel.accept" placeholder="{{ \App\Models\FormDefinition::FILE_DEFAULT_ACCEPT }}" class="{{ $fieldInput }} font-mono">
                                    </div>
                                    <div>
                                        <label for="prop-maxmb" class="{{ $propLabel }}">Maks. MB</label>
                                        <input id="prop-maxmb" type="number" min="1" max="{{ \App\Models\FormDefinition::FILE_MAX_MB }}" x-model="sel.max_mb" placeholder="10" class="{{ $fieldInput }}">
                                    </div>
                                </div>
                                <div x-show="sel.type === 'link'" class="space-y-2">
                                    <div>
                                        <label for="prop-url" class="{{ $propLabel }}">Adres po kliknięciu</label>
                                        <input id="prop-url" type="text" inputmode="url" x-model="sel.url" placeholder="https://… lub /strona" class="{{ $fieldInput }}"
                                            :aria-invalid="sel.url && ! /^(https?:\/\/\S+|\/(?!\/)\S*)$/i.test(sel.url)">
                                        <p class="mt-1 text-xs" :class="sel.url && ! /^(https?:\/\/\S+|\/(?!\/)\S*)$/i.test(sel.url) ? 'font-bold text-red-700' : 'text-muted'">Pełny adres (https://…) albo ścieżka w serwisie (/kontakt).</p>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-ink">
                                        <input type="checkbox" x-model="sel.new_tab" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Otwórz w nowej karcie
                                    </label>
                                </div>
                                <p x-show="sel.type === 'rating'" class="text-xs text-muted">Odpowiedź: przyciski 1–5.</p>
                                <p x-show="sel.type === 'step'" class="text-xs text-muted">Od tego miejsca zaczyna się kolejny krok — tytuł widać na pasku postępu.</p>
                                <p x-show="sel.type === 'krs'" class="text-xs text-muted">Dane pobierają się z rejestru KRS. W innych polach wybierz „Uzupełnij z KRS”.</p>

                                <label x-show="! ['heading', 'step', 'link'].includes(sel.type)" class="flex items-center gap-2 text-sm font-medium text-ink">
                                    <input type="checkbox" x-model="sel.required" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Pole wymagane
                                </label>

                                <div x-show="hasKrs && krsFillable.includes(sel.type)">
                                    <label for="prop-krs" class="{{ $propLabel }}">Uzupełnij z KRS</label>
                                    <select id="prop-krs" x-model="sel.krs_fill" class="{{ $fieldInput }}">
                                        <option value="">— nie uzupełniaj —</option>
                                        @foreach (\App\Services\KrsClient::FILL_KEYS as $krsKey => $krsLabel)
                                            <option value="{{ $krsKey }}">{{ $krsLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Logika warunkowa --}}
                                <div x-show="sel.type !== 'step' && conditionSources(selIndex).length" class="rounded bg-white p-3">
                                    <label class="flex items-center gap-2 text-sm font-medium text-ink">
                                        <input type="checkbox" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark"
                                            :checked="!! sel.show_if_uid"
                                            @change="sel.show_if_uid = $event.target.checked ? (conditionSources(selIndex)[0]?.uid || '') : ''; sel.show_if_value = ''">
                                        Pokaż tylko, gdy…
                                    </label>
                                    <div x-show="sel.show_if_uid" class="mt-2 space-y-2">
                                        <div>
                                            <label for="prop-cond" class="{{ $propLabel }}">pole</label>
                                            <select id="prop-cond" x-model="sel.show_if_uid" @change="sel.show_if_value = ''" class="{{ $fieldInput }}">
                                                <template x-for="src in conditionSources(selIndex)" :key="src.uid">
                                                    <option :value="src.uid" x-text="src.label || '(bez etykiety)'" :selected="src.uid === sel.show_if_uid"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="prop-condv" class="{{ $propLabel }}">ma odpowiedź</label>
                                            <template x-if="conditionChoices(sel.show_if_uid) !== null">
                                                <select id="prop-condv" x-model="sel.show_if_value" class="{{ $fieldInput }}">
                                                    <option value="">— dowolną (pole wypełnione) —</option>
                                                    <template x-for="opt in conditionChoices(sel.show_if_uid)" :key="opt.value">
                                                        <option :value="opt.value" x-text="opt.label" :selected="opt.value === sel.show_if_value"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="conditionChoices(sel.show_if_uid) === null">
                                                <input id="prop-condv" type="text" x-model="sel.show_if_value" placeholder="puste = gdy pole jest wypełnione" class="{{ $fieldInput }}">
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2 pt-1">
                                    <button type="button" @click="duplicateField(selIndex)" class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-white px-3 text-xs font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-regular fa-copy" aria-hidden="true"></i> Powiel</button>
                                    <button type="button" @click="removeField(selIndex)" class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-white px-3 text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Usuń pole</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </aside>
            </div>

            {{-- Dane pól do zapisu (kolejność = kolejność w podglądzie) --}}
            <div hidden>
                <template x-for="(f, i) in fields" :key="'h' + f._id">
                    <div>
                        <input type="hidden" :name="'fields[' + i + '][label]'" :value="f.label">
                        <input type="hidden" :name="'fields[' + i + '][type]'" :value="f.type">
                        <input type="hidden" :name="'fields[' + i + '][uid]'" :value="f.uid">
                        <input type="hidden" :name="'fields[' + i + '][required]'" :value="f.required ? '1' : '0'">
                        <input type="hidden" :name="'fields[' + i + '][placeholder]'" :value="f.placeholder || ''">
                        <input type="hidden" :name="'fields[' + i + '][help_text]'" :value="f.help_text || ''">
                        <input type="hidden" :name="'fields[' + i + '][options]'" :value="f.options || ''">
                        <input type="hidden" :name="'fields[' + i + '][accept]'" :value="f.accept || ''">
                        <input type="hidden" :name="'fields[' + i + '][max_mb]'" :value="f.max_mb || ''">
                        <input type="hidden" :name="'fields[' + i + '][show_if_uid]'" :value="f.show_if_uid || ''">
                        <input type="hidden" :name="'fields[' + i + '][show_if_value]'" :value="f.show_if_value || ''">
                        <input type="hidden" :name="'fields[' + i + '][url]'" :value="f.type === 'link' ? (f.url || '') : ''">
                        <input type="hidden" :name="'fields[' + i + '][new_tab]'" :value="f.type === 'link' && f.new_tab ? '1' : '0'">
                        <input type="hidden" :name="'fields[' + i + '][krs_fill]'" :value="krsFillable.includes(f.type) ? (f.krs_fill || '') : ''">
                    </div>
                </template>
            </div>
        </div>
        </div>

        <div role="tabpanel" id="panel-settings" aria-labelledby="tab-settings" x-show="tab === 'settings'" x-cloak class="space-y-5">
        {{-- Podstawowe dane --}}
        <fieldset class="space-y-5 p-6 rounded-md bg-white">
            <legend class="px-2 text-sm font-bold text-ink">Podstawowe informacje</legend>

            <div class="grid grid-cols-1 gap-5">
                <div>
                    <label for="slug" class="mb-1 block text-sm font-bold">
                        Identyfikator URL <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <div class="flex items-center gap-1">
                        <span class="shrink-0 text-xs text-muted">/formularz/</span>
                        <input id="slug" name="slug" type="text" required
                            value="{{ old('slug', $form->slug) }}"
                            x-ref="slug"
                            pattern="[a-z0-9\-]+"
                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-bold">Opis (widoczny dla edytorów)</label>
                <textarea id="description" name="description" rows="2"
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('description', $form->description) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1"
                    {{ old('is_active', $form->is_active ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <label for="is_active" class="text-sm font-medium">Formularz aktywny (publiczny)</label>
            </div>
        </fieldset>

        {{-- Ustawienia --}}
        <fieldset class="space-y-4 p-6 rounded-md bg-white">
            <legend class="px-2 text-sm font-bold text-ink">Ustawienia formularza</legend>

            <div>
                <label for="confirmation_message" class="mb-1 block text-sm font-bold">
                    Wiadomość po wysłaniu
                </label>
                <textarea id="confirmation_message" name="settings[confirmation_message]" rows="2"
                    placeholder="Dziękujemy! Twoje zgłoszenie zostało przyjęte."
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('settings.confirmation_message', $form->settings['confirmation_message'] ?? '') }}</textarea>
            </div>

            <div>
                <label for="submit_label" class="mb-1 block text-sm font-bold">Napis na przycisku wysyłania <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input id="submit_label" name="settings[submit_label]" type="text" maxlength="60"
                    value="{{ old('settings.submit_label', $form->settings['submit_label'] ?? '') }}" placeholder="Wyślij zgłoszenie"
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
            </div>

            <div>
                <label for="redirect_url" class="mb-1 block text-sm font-bold">Po wysłaniu przejdź na adres <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input id="redirect_url" name="settings[redirect_url]" type="text" inputmode="url"
                    value="{{ old('settings.redirect_url', $form->settings['redirect_url'] ?? '') }}"
                    placeholder="np. /dziekujemy albo https://…" aria-describedby="redirect_url_help"
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                <p id="redirect_url_help" class="mt-1 text-xs text-muted">Puste = po wysłaniu osoba zostaje na stronie i widzi wiadomość powyżej. Ustawiony adres = przejście na inną stronę (np. podziękowanie, płatność).</p>
                @error('settings.redirect_url') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            {{-- Doręczenie zgłoszeń: e-mail / API / oba --}}
            @php $delivery = old('settings.delivery', $form->delivery()); @endphp
            <fieldset class="space-y-4 rounded-md bg-gray-50 p-4" x-data="{ delivery: @js($delivery) }">
                <legend class="px-1 text-sm font-bold text-ink"><i class="fa-solid fa-paper-plane mr-1 text-brand-dark" aria-hidden="true"></i> Gdzie trafiają zgłoszenia</legend>
                <p class="text-xs text-muted">Każde zgłoszenie jest zawsze zapisywane w panelu (Zgłoszenia). Tu wybierasz, dokąd ma być dodatkowo przesłane.</p>
                <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Gdzie trafiają zgłoszenia">
                    @foreach (\App\Models\FormDefinition::DELIVERY as $value => $label)
                        <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-solid border-gray-300 bg-white px-3 text-sm has-[:checked]:border-brand-dark has-[:checked]:bg-brand-light has-[:checked]:font-bold">
                            <input type="radio" name="settings[delivery]" value="{{ $value }}" x-model="delivery" @checked($delivery === $value)
                                class="border-gray-500 text-brand-dark focus:ring-brand-dark">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div x-show="delivery !== 'email'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="webhook_url" class="mb-1 block text-sm font-bold">Adres API <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input id="webhook_url" name="settings[webhook_url]" type="url" inputmode="url" autocomplete="off"
                            value="{{ old('settings.webhook_url', $form->settings['webhook_url'] ?? '') }}" placeholder="https://api.przyklad.pl/zgloszenia"
                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p class="mt-1 text-xs text-muted">Zgłoszenie idzie jako JSON (POST): formularz, numer, pola z etykietą, wartością i tekstem.</p>
                        @error('settings.webhook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="webhook_secret" class="mb-1 block text-sm font-bold">Sekret do podpisu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input id="webhook_secret" name="settings[webhook_secret]" type="password" autocomplete="new-password" value=""
                            placeholder="{{ filled($form->settings['webhook_secret'] ?? null) ? '•••••••• (zapisany — wpisz nowy, aby zmienić)' : 'np. długi losowy ciąg' }}"
                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p class="mt-1 text-xs text-muted">Podpis HMAC SHA-256 treści w nagłówku <code>X-Form-Signature: sha256=…</code> — odbiorca sprawdzi, że zgłoszenie jest od nas.</p>
                    </div>
                </div>
                <p x-show="delivery === 'api'" class="text-xs text-muted">Powiadomienia e-mail poniżej nie będą wysyłane.</p>
            </fieldset>

            {{-- Terminy i limity --}}
            @php
                $fs = $form->settings ?? [];
                $dtl = fn ($v) => filled($v) ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\\TH:i') : '';
                $avail = $form->exists ? $form->availability() : null;
            @endphp
            <fieldset class="space-y-4 rounded-md bg-gray-50 p-4">
                <legend class="px-1 text-sm font-bold text-ink"><i class="fa-solid fa-calendar-check mr-1 text-brand-dark" aria-hidden="true"></i> Terminy i limity</legend>
                @if ($avail)
                    <p class="text-sm {{ $avail['open'] ? 'text-ink' : 'font-bold text-red-700' }}" role="status">
                        {{ $avail['open'] ? 'Formularz przyjmuje zgłoszenia.' : 'Zamknięty: '.$avail['message'] }}
                        @if ($avail['remaining'] !== null) Pozostało miejsc: {{ $avail['remaining'] }}. @endif
                    </p>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="open_from" class="mb-1 block text-sm font-bold">Zbieranie od</label>
                        <input id="open_from" name="settings[open_from]" type="datetime-local" value="{{ old('settings.open_from', $dtl($fs['open_from'] ?? null)) }}"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                    <div>
                        <label for="open_until" class="mb-1 block text-sm font-bold">Zbieranie do</label>
                        <input id="open_until" name="settings[open_until]" type="datetime-local" value="{{ old('settings.open_until', $dtl($fs['open_until'] ?? null)) }}"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                    <div>
                        <label for="max_submissions" class="mb-1 block text-sm font-bold">Limit zgłoszeń</label>
                        <input id="max_submissions" name="settings[max_submissions]" type="number" min="1" value="{{ old('settings.max_submissions', $fs['max_submissions'] ?? '') }}" placeholder="bez limitu"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="settings[show_remaining]" value="0">
                    <input type="checkbox" name="settings[show_remaining]" value="1" @checked(old('settings.show_remaining', $fs['show_remaining'] ?? false))
                        class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                    Pokazuj nad formularzem liczbę wolnych miejsc (przy ustawionym limicie) i termin końca
                </label>
                <div>
                    <label for="closed_message" class="mb-1 block text-sm font-bold">Komunikat po zamknięciu</label>
                    <textarea id="closed_message" name="settings[closed_message]" rows="2" placeholder="np. Zapisy zakończone. Kolejna edycja szkolenia jesienią — zapisz się na newsletter."
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('settings.closed_message', $fs['closed_message'] ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-muted">Pokazywany zamiast formularza po terminie albo po wyczerpaniu limitu. Puste = komunikat domyślny.</p>
                </div>
            </fieldset>

            {{-- Powiadomienia e-mail o zgłoszeniach --}}
            @php
                $siteCfg = \App\Models\SiteSetting::current();
                $graphReady = $siteCfg->msGraphConfigured();
                $formMailer = old('settings.mailer', $form->settings['mailer'] ?? '');
                $inheritedLabel = $siteCfg->formsMailer() === 'msgraph'
                    ? 'Microsoft 365 (Graph API)'
                    : (\App\Models\SiteSetting::MAIL_TRANSPORTS[$siteCfg->mail_transport ?? 'default'] ?? 'domyślny tryb wysyłki');
            @endphp
            <fieldset class="space-y-4 p-4 rounded-md bg-gray-50">
                <legend class="px-1 text-sm font-bold text-ink"><i class="fa-solid fa-envelope mr-1 text-brand-dark" aria-hidden="true"></i> Powiadomienia e-mail o zgłoszeniach</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="notification_email" class="mb-1 block text-sm font-bold">Odbiorcy powiadomień</label>
                        <input id="notification_email" name="settings[notification_email]" type="text" inputmode="email" autocomplete="off"
                            value="{{ old('settings.notification_email', $form->settings['notification_email'] ?? '') }}"
                            placeholder="np. biuro@feer.org.pl, sekretariat@feer.org.pl"
                            aria-describedby="notification_email_help"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p id="notification_email_help" class="mt-1 text-xs text-muted">Kilka adresów rozdziel przecinkami. Puste = brak powiadomień.</p>
                        @error('settings.notification_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="notification_cc" class="mb-1 block text-sm font-bold">Kopia (DW)</label>
                        <input id="notification_cc" name="settings[notification_cc]" type="text" inputmode="email" autocomplete="off"
                            value="{{ old('settings.notification_cc', $form->settings['notification_cc'] ?? '') }}"
                            placeholder="opcjonalnie"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        @error('settings.notification_cc')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="notification_subject" class="mb-1 block text-sm font-bold">Temat wiadomości</label>
                        <input id="notification_subject" name="settings[notification_subject]" type="text"
                            value="{{ old('settings.notification_subject', $form->settings['notification_subject'] ?? '') }}"
                            placeholder="Nowe zgłoszenie: {tytul}"
                            aria-describedby="notification_subject_help"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p id="notification_subject_help" class="mt-1 text-xs text-muted">Znaczniki: <code>{tytul}</code> — nazwa formularza, <code>{id}</code> — numer zgłoszenia.</p>
                    </div>
                    <div>
                        <label for="form_mailer" class="mb-1 block text-sm font-bold">Sposób wysyłki</label>
                        <select id="form_mailer" name="settings[mailer]" aria-describedby="form_mailer_help"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <option value="" @selected($formMailer === '')>Dziedzicz z ustawień ({{ $inheritedLabel }})</option>
                            <option value="msgraph" @selected($formMailer === 'msgraph')>Microsoft 365 (Graph API){{ $graphReady ? '' : ' — nieskonfigurowany' }}</option>
                            <option value="default" @selected($formMailer === 'default')>Domyślny tryb wysyłki serwisu</option>
                        </select>
                        <p id="form_mailer_help" class="mt-1 text-xs text-muted">
                            Formularze domyślnie wysyłają przez Microsoft Graph, gdy jest skonfigurowany w
                            <a href="{{ route('admin.ustawienia.edit', ['tab' => 'mail']) }}" class="text-brand-dark underline-offset-2 hover:underline">Ustawienia → Poczta</a>.
                            @unless ($graphReady) <span class="text-amber-700">Graph nie jest jeszcze skonfigurowany — do czasu uzupełnienia danych używany jest domyślny tryb.</span> @endunless
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <input type="hidden" name="settings[reply_to_submitter]" value="0">
                        <input type="checkbox" name="settings[reply_to_submitter]" value="1"
                            @checked(old('settings.reply_to_submitter', $form->settings['reply_to_submitter'] ?? true))
                            class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>
                            <span class="block text-sm font-bold">Odpowiedz-do: adres zgłaszającego</span>
                            <span class="block text-xs text-muted">Jeśli formularz ma pole „Adres e-mail”, odpowiedź na powiadomienie trafi wprost do tej osoby.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <input type="hidden" name="settings[send_copy_to_submitter]" value="0">
                        <input type="checkbox" name="settings[send_copy_to_submitter]" value="1"
                            @checked(old('settings.send_copy_to_submitter', $form->settings['send_copy_to_submitter'] ?? false))
                            class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>
                            <span class="block text-sm font-bold">Wyślij kopię zgłaszającemu</span>
                            <span class="block text-xs text-muted">Potwierdzenie z kopią przesłanych danych na adres z pola „Adres e-mail”.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <label for="submitter_copy_message" class="mb-1 block text-sm font-bold">Treść wstępu w kopii dla zgłaszającego</label>
                    <textarea id="submitter_copy_message" name="settings[submitter_copy_message]" rows="2"
                        placeholder="Dziękujemy za wypełnienie formularza. Poniżej kopia przesłanych danych."
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('settings.submitter_copy_message', $form->settings['submitter_copy_message'] ?? '') }}</textarea>
                </div>
            </fieldset>

            {{-- Ochrona CleanTalk: globalnie w Ustawienia → Logowanie i integracje; tu wyłączenie dla jednego formularza --}}
            @if (\App\Support\CleanTalkGuard::enabled())
                <label class="mt-4 flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                    <input type="hidden" name="settings[cleantalk_disabled]" value="0">
                    <input type="checkbox" name="settings[cleantalk_disabled]" value="1"
                        @checked(old('settings.cleantalk_disabled', $form->settings['cleantalk_disabled'] ?? false))
                        class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                    <span>
                        <span class="block text-sm font-bold">Nie sprawdzaj tego formularza w CleanTalk</span>
                        <span class="block text-xs text-muted">Domyślnie zgłoszenia są oceniane przez CleanTalk (włączone w ustawieniach). Zaznacz, jeśli formularz jest wewnętrzny albo nie powinien wysyłać danych do zewnętrznej usługi. Lokalna ochrona antyspamowa działa nadal.</span>
                    </span>
                </label>
            @endif

            {{-- Podpięcie do CRM w SZO --}}
            <div class="mt-4 border-t border-gray-200 pt-4">
                <label for="szo_form_slug" class="mb-1 block text-sm font-bold">
                    Przekazuj zgłoszenia do CRM (SZO)
                </label>

                @if (empty($szoForms))
                    <p class="text-sm text-gray-600">
                        Integracja z SZO jest wyłączona albo SZO nie odpowiada. Ustaw
                        <code>SZO_ENABLED</code>, <code>SZO_URL</code> i <code>SZO_TOKEN</code>
                        w pliku <code>.env</code>. Do czasu włączenia zgłoszenia zapisują się
                        tylko tutaj.
                    </p>
                    <input type="hidden" name="settings[szo_form_slug]"
                        value="{{ old('settings.szo_form_slug', $form->settings['szo_form_slug'] ?? '') }}">
                @else
                    <select id="szo_form_slug" name="settings[szo_form_slug]"
                        class="w-full max-w-sm rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <option value="">— nie przekazuj —</option>
                        @foreach ($szoForms as $szoForm)
                            <option value="{{ $szoForm['slug'] }}"
                                @selected(old('settings.szo_form_slug', $form->settings['szo_form_slug'] ?? '') === $szoForm['slug'])>
                                {{ $szoForm['title'] }} ({{ $szoForm['slug'] }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-600">
                        Zgłoszenie założy kontakt w CRM, trafi do Skrzynki CRM jako wiadomość
                        przychodząca i utworzy działanie „odpowiedzieć". Pola dopasowywane są po
                        nazwach — sprawdź, czy etykiety pól odpowiadają polom formularza w SZO.
                    </p>
                @endif

                @error('settings.szo_form_slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </fieldset>

        </div>

        @if ($form->exists)
        <div role="tabpanel" id="panel-share" aria-labelledby="tab-share" x-show="tab === 'share'" x-cloak>
        {{-- Osadzanie formularza --}}
            <div class="rounded-md bg-brand-light p-4 text-sm">
                <p class="mb-2 font-bold text-ink">
                    <i class="fa-solid fa-code mr-1" aria-hidden="true"></i>
                    Osadzanie formularza
                </p>
                <p class="mb-2 text-ink">Wklej shortcode w dowolne miejsce treści strony lub aktualności:</p>
                <div class="flex items-center gap-2">
                    <code id="form-shortcode" class="flex-1 rounded bg-white px-3 py-1.5 font-mono text-ink ">[formularz:{{ $form->slug }}]</code>
                    <button type="button"
                        onclick="navigator.clipboard.writeText('[formularz:{{ $form->slug }}]').then(() => { this.textContent='Skopiowano!'; setTimeout(() => this.textContent='Kopiuj', 2000) })"
                        class="rounded bg-brand-dark px-3 py-1.5 text-xs font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        Kopiuj
                    </button>
                </div>
                <p class="mt-2 text-xs text-ink">
                    Bezpośredni link: <a href="{{ route('formularz.show', $form->slug) }}" target="_blank" rel="noopener" class="underline hover:no-underline">{{ route('formularz.show', $form->slug) }}</a>
                </p>
            </div>

        </div>
        @endif

    </form>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formBuilder', (initialFields, initialTab = 'fields') => ({
                tab: initialTab,
                dirty: false,
                history: [],
                get inputCount() { return this.fields.filter(f => ! ['heading', 'step', 'link'].includes(f.type)).length; },
                get stepCount() { return this.fields.filter(f => f.type === 'step').length; },

                init() {
                    // Ostrzeżenie przy wyjściu ze strony z niezapisanymi zmianami.
                    window.addEventListener('beforeunload', e => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });
                },

                nextTab(dir) {
                    const tabs = [...this.$root.querySelectorAll('[role=tab]')];
                    const i = tabs.findIndex(t => t.getAttribute('aria-selected') === 'true');
                    const next = tabs[(i + dir + tabs.length) % tabs.length];
                    next.click(); next.focus();
                },

                // Historia do „Cofnij” — zapis stanu przed każdą zmianą struktury (dodanie, usunięcie, przesunięcie).
                snapshot() {
                    this.history.push(JSON.stringify(this.fields));
                    if (this.history.length > 40) this.history.shift();
                    this.dirty = true;
                },
                undo() {
                    if (! this.history.length) return;
                    this.fields = JSON.parse(this.history.pop());
                    if (! this.fields.some(f => f._id === this.selectedId)) this.selectedId = null;
                    this.announce = 'Cofnięto ostatnią zmianę.';
                },

                // Skróty na zaznaczonym polu (fokus w podglądzie).
                canvasKey(e) {
                    if (this.selIndex < 0) return;
                    const i = this.selIndex;
                    if (e.altKey && e.key === 'ArrowUp') { e.preventDefault(); this.moveUp(i); this.refocus(); }
                    else if (e.altKey && e.key === 'ArrowDown') { e.preventDefault(); this.moveDown(i); this.refocus(); }
                    else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') { e.preventDefault(); this.duplicateField(i); this.refocus(); }
                    else if (e.key === 'Delete') { e.preventDefault(); this.removeField(i); }
                    else if (e.key === 'Escape') { this.selectedId = null; }
                },
                refocus() {
                    this.$nextTick(() => document.querySelector('#fields-list [aria-pressed=true]')?.focus());
                },
                globalKey(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && ! e.shiftKey && this.tab === 'fields'
                        && ! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
                        e.preventDefault(); this.undo();
                    }
                },

                krsFillable: @json(\App\Models\FormDefinition::KRS_FILLABLE_TYPES),
                get hasKrs() { return this.fields.some(f => f.type === 'krs'); },
                fields: (initialFields || []).map((f, i) => ({ show_if_uid: '', show_if_value: '', krs_fill: '', url: '', new_tab: false, ...f, krs_fill: f.krs_fill || '', uid: f.uid || Math.random().toString(36).slice(2, 10), _id: i })),
                _nextId: (initialFields || []).length,

                // Pola, od których może zależeć pole nr index: wcześniejsze pola z wartością.
                conditionSources(index) {
                    return this.fields.slice(0, index).filter(f => ! ['heading', 'step', 'link', 'file'].includes(f.type));
                },

                // Lista możliwych odpowiedzi pola źródłowego; null = dowolny tekst.
                conditionChoices(uid) {
                    const src = this.fields.find(f => f.uid === uid);
                    if (! src) return null;
                    if (['select', 'radio', 'checkboxes'].includes(src.type)) {
                        return String(src.options || '').split(',').map(o => o.trim()).filter(Boolean).map(o => ({ value: o, label: o }));
                    }
                    if (['checkbox', 'consent'].includes(src.type)) return [{ value: '1', label: 'zaznaczone' }];
                    if (src.type === 'rating') return ['1', '2', '3', '4', '5'].map(v => ({ value: v, label: v + '/5' }));
                    return null;
                },

                selectedId: null,
                dragData: null,
                dropIndex: null,
                announce: '',
                typeNames: @json(collect($types)->map(fn ($l) => \Illuminate\Support\Str::before($l, ' ('))),
                defaultLabels: {
                    text: 'Pytanie', textarea: 'Twoja wiadomość', email: 'Adres e-mail', tel: 'Numer telefonu', number: 'Liczba',
                    date: 'Data', time: 'Godzina', url: 'Adres strony internetowej', select: 'Wybierz z listy', radio: 'Wybierz jedną odpowiedź',
                    checkboxes: 'Zaznacz odpowiedzi', checkbox: 'Potwierdzam', rating: 'Jak oceniasz…?', file: 'Załącznik', krs: 'Numer KRS',
                    consent: 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu obsługi zgłoszenia.', heading: 'Nowa sekcja', step: 'Nowy krok', link: 'Przejdź dalej',
                },

                get selIndex() { return this.fields.findIndex(f => f._id === this.selectedId); },
                get sel() { return this.fields[this.selIndex] || null; },
                typeName(type) { return this.typeNames[type] || type; },
                optionList(f) { return String(f.options || '').split(',').map(o => o.trim()).filter(Boolean); },
                sampleFor(type) {
                    return { email: 'jan.kowalski@example.org', tel: '500 000 000', number: '0', date: 'dd.mm.rrrr', time: 'gg:mm', url: 'https://' }[type] || '';
                },

                newField(type) {
                    return {
                        _id: this._nextId++, uid: Math.random().toString(36).slice(2, 10),
                        show_if_uid: '', show_if_value: '', label: this.defaultLabels[type] || '', type: type,
                        placeholder: '', options: ['select', 'radio', 'checkboxes'].includes(type) ? 'Opcja 1, Opcja 2' : '',
                        help_text: type === 'link' ? 'Krótki opis, dokąd prowadzi przycisk.' : '', accept: '', max_mb: '', krs_fill: '', url: '', new_tab: false, required: type === 'consent',
                    };
                },

                // Nowe pole trafia pod zaznaczone (albo na koniec) i od razu jest zaznaczone do edycji.
                addField(type, at = null) {
                    this.snapshot();
                    const field = this.newField(type);
                    const pos = at !== null ? at : (this.selIndex >= 0 ? this.selIndex + 1 : this.fields.length);
                    this.fields.splice(pos, 0, field);
                    this.select(field._id, true);
                    this.announce = 'Dodano pole: ' + this.typeName(type) + ' (pozycja ' + (pos + 1) + ').';
                },

                select(id, focus = false) {
                    this.selectedId = id;
                    if (! focus && window.innerWidth < 1024) this.$nextTick(() => document.getElementById('field-props-heading')?.scrollIntoView({ block: 'start' }));
                    if (focus) this.$nextTick(() => document.getElementById(['consent', 'checkbox'].includes(this.sel?.type) ? 'prop-label-long' : 'prop-label')?.focus());
                },

                duplicateField(index) {
                    this.snapshot();
                    const copy = { ...JSON.parse(JSON.stringify(this.fields[index])), _id: this._nextId++, uid: Math.random().toString(36).slice(2, 10) };
                    copy.label = copy.label ? copy.label + ' (kopia)' : '';
                    this.fields.splice(index + 1, 0, copy);
                    this.select(copy._id);
                    this.announce = 'Powielono pole.';
                },

                removeField(index) {
                    const f = this.fields[index];
                    if (! f) return;
                    this.snapshot();
                    this.fields.splice(index, 1);
                    // Warunki wskazujące na usunięte pole przestają obowiązywać.
                    this.fields.forEach(x => { if (x.show_if_uid === f.uid) { x.show_if_uid = ''; x.show_if_value = ''; } });
                    if (this.selectedId === f._id) this.selectedId = (this.fields[index] || this.fields[index - 1] || {})._id ?? null;
                    this.announce = 'Usunięto pole „' + (f.label || 'bez etykiety') + '”. Ctrl+Z cofa.';
                },

                move(from, to) {
                    if (to < 0 || to >= this.fields.length || from === to) return;
                    this.snapshot();
                    const [f] = this.fields.splice(from, 1);
                    this.fields.splice(to, 0, f);
                    this.announce = 'Pole „' + (f.label || 'bez etykiety') + '” na pozycji ' + (to + 1) + ' z ' + this.fields.length + '.';
                },
                moveUp(index) { this.move(index, index - 1); },
                moveDown(index) { this.move(index, index + 1); },

                // ── Przeciąganie: z palety (new:typ) albo w obrębie podglądu (move:_id) ──
                dragStart(e, data) {
                    this.dragData = data;
                    e.dataTransfer.effectAllowed = data.startsWith('new:') ? 'copy' : 'move';
                    e.dataTransfer.setData('text/plain', data);
                },
                dragEnd() { this.dragData = null; this.dropIndex = null; },
                dragOverItem(e, index) {
                    if (! this.dragData) return;
                    const r = e.currentTarget.getBoundingClientRect();
                    this.dropIndex = e.clientY > r.top + r.height / 2 ? index + 1 : index;
                },
                dragOverEnd(e) {
                    if (this.dragData && this.dropIndex === null) this.dropIndex = this.fields.length;
                },
                drop() {
                    const data = this.dragData, at = this.dropIndex ?? this.fields.length;
                    this.dragEnd();
                    if (! data) return;
                    if (data.startsWith('new:')) { this.addField(data.slice(4), at); return; }
                    const from = this.fields.findIndex(f => String(f._id) === data.slice(5));
                    if (from < 0) return;
                    this.move(from, from < at ? at - 1 : at);
                },

                syncSlug(title) {
                    // Tylko jeśli slug jest pusty lub identyczny z poprzednim tytułem (nowy formularz)
                    if (!this.$refs.slug || (this.$refs.slug.dataset.userEdited)) return;
                    this.$refs.slug.value = title
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[̀-ͯ]/g, '')
                        .replace(/ą/g, 'a').replace(/ć/g, 'c').replace(/ę/g, 'e')
                        .replace(/ł/g, 'l').replace(/ń/g, 'n').replace(/ó/g, 'o')
                        .replace(/ś/g, 's').replace(/ź/g, 'z').replace(/ż/g, 'z')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .slice(0, 100);
                },
            }));
        });

        // Oznacz slug jako ręcznie edytowany — wtedy nie nadpisujemy go przy zmianie tytułu.
        document.addEventListener('DOMContentLoaded', () => {
            const slugInput = document.getElementById('slug');
            if (slugInput) {
                slugInput.addEventListener('input', () => {
                    slugInput.dataset.userEdited = '1';
                });
                @if ($form->exists)
                    slugInput.dataset.userEdited = '1';
                @endif
            }
        });
    </script>
@endsection
