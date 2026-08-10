@extends('admin.layout')

@section('title', $page->exists
    ? ($isPersonForm ? 'Edytuj osobę' : 'Edytuj stronę')
    : ($isPersonForm ? 'Nowa osoba' : 'Nowa strona')
)

@section('content')
    @if ($isPersonForm)
        <nav class="mb-4 flex items-center gap-1.5 text-sm text-muted" aria-label="Breadcrumb">
            <a href="{{ route('admin.osoby.index') }}" class="text-brand hover:underline">
                <i class="fa-solid fa-users mr-1" aria-hidden="true"></i>Osoby
            </a>
            <span aria-hidden="true">/</span>
            <span class="text-ink">{{ $page->exists ? $page->title : 'Nowa osoba' }}</span>
        </nav>
    @endif
    @php
        $currentType = old('type', $page->type ?? 'standard');
        $scheduleItems = old('schedule_items', $page->schedule_items ?? []);
        $scheduleItems = is_array($scheduleItems) ? array_values($scheduleItems) : [];
        $hasProject = (bool) old('project_id', $page->project_id);

        $aboutStats = array_values((array) old('about_stats', $page->about_stats ?? []));
        $aboutTimeline = array_values((array) old('about_timeline', $page->about_timeline ?? []));
        $aboutValues = array_values((array) old('about_values', $page->about_values ?? []));
        $aboutPress = array_values((array) old('about_press', $page->about_press ?? []));
        $faqItems = array_values((array) old('faq_items', $page->faq_items ?? []));
    @endphp

    <div data-page-form-tabs>
        <div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200" role="tablist">
            <button type="button" data-ftab-btn="tresc" role="tab" aria-selected="true"
                class="-mb-px border-b-2 border-brand px-4 py-2 text-sm font-bold text-brand">
                <i class="fa-solid fa-align-left" aria-hidden="true"></i> Treść
            </button>
            <button type="button" data-ftab-btn="typ" role="tab" aria-selected="false"
                class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> Typ i układ
            </button>
            <button type="button" data-ftab-btn="ustawienia" role="tab" aria-selected="false"
                class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                <i class="fa-solid fa-gear" aria-hidden="true"></i> Publikacja i powiązania
            </button>
            <button type="button" data-ftab-btn="seo" role="tab" aria-selected="false"
                class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> SEO
            </button>
            @if ($page->exists)
                @if ($currentType !== 'wspolpraca')
                <button type="button" data-ftab-btn="pliki" data-wspolpraca-tab role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Pliki do pobrania
                    @if ($page->attachments->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs">{{ $page->attachments->count() }}</span>
                    @endif
                </button>
                <button type="button" data-ftab-btn="galeria" data-wspolpraca-tab role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-images" aria-hidden="true"></i> Galeria
                    @if ($page->images->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs">{{ $page->images->count() }}</span>
                    @endif
                </button>
                <button type="button" data-ftab-btn="etr" data-wspolpraca-tab role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> ETR
                    @if ($page->etr?->is_enabled)
                        <span class="ml-1 rounded-full bg-sky-100 px-1.5 py-0.5 text-xs text-sky-700">aktywna</span>
                    @endif
                </button>
                @endif
                <a href="{{ route('admin.historia.index', ['type' => 'page', 'id' => $page->id]) }}"
                    class="ml-auto -mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historia zmian
                </a>
            @endif
        </div>

        @if ($page->exists)
            @include('admin.partials.edit-lock', ['lockType' => 'page', 'lockId' => $page->id])
        @endif

        <form method="POST" action="{{ $page->exists ? route('admin.podstrony.update', $page) : route('admin.podstrony.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if ($page->exists) @method('PUT') @endif

            {{-- ============================ TREŚĆ ============================ --}}
            <div data-ftab-panel="tresc" class="space-y-6">
                @include('admin.partials.template-panel', [
                    'templateType'   => 'page',
                    'templateFields' => ['content', 'meta_title', 'meta_description'],
                ])

                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
                            <input type="text" id="title" name="title" value="{{ old('title', $page->title) }}" required
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted">/</span>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="zostanie wygenerowany z tytułu"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            </div>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Główny edytor treści — ukryty dla typów bez swobodnej treści
                         („O organizacji" i „Przeniesiono do BIP" mają własne pola). --}}
                    <div data-content-field class="{{ in_array($currentType, ['about', 'bip_move', 'wspolpraca'], true) ? 'hidden' : '' }}">
                        <label class="mb-1 block text-sm font-bold">Treść</label>
                        @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $page->content), 'revisionable' => $page->exists ? ['type' => 'page', 'id' => $page->id] : null])
                        @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Zdjęcie w treści --}}
                    <div>
                        <p class="mb-1 text-sm font-bold">Zdjęcie w treści <span class="font-normal text-muted">(opcjonalne)</span></p>
                        <p class="mb-3 text-xs text-muted">Pojawia się poniżej tytułu, przed główną treścią strony. Ustaw szerokość, by dopasować do układu.</p>
                        <div class="flex items-start gap-4">
                            @if (filled(old('content_image', $page->content_image ?? null)))
                                <img src="{{ old('content_image', $page->content_image) }}" alt=""
                                    class="h-20 w-32 shrink-0 rounded object-cover">
                                <label class="mt-1 flex items-center gap-1.5 text-sm text-red-600">
                                    <input type="checkbox" name="remove_content_image" value="1"
                                        class="rounded border-gray-300 text-brand focus:ring-brand">
                                    Usuń zdjęcie
                                </label>
                            @else
                                <span class="flex h-20 w-32 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-300" aria-hidden="true">
                                    <i class="fa-solid fa-image text-2xl"></i>
                                </span>
                            @endif
                            <div class="min-w-0 flex-1 space-y-2">
                                <input type="file" name="content_image_file" accept="image/*"
                                    aria-label="Wgraj zdjęcie"
                                    class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand-light file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-brand">
                                <input type="text" name="content_image" value="{{ old('content_image', $page->content_image ?? '') }}"
                                    placeholder="…albo wklej URL zdjęcia"
                                    class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="content_image_alt" class="mb-1 block text-xs font-bold">Tekst alternatywny (dostępność)</label>
                                        <input type="text" id="content_image_alt" name="content_image_alt"
                                            value="{{ old('content_image_alt', $page->content_image_alt ?? '') }}"
                                            placeholder="Opisz co przedstawia zdjęcie"
                                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    </div>
                                    <div>
                                        <label for="content_image_width" class="mb-1 block text-xs font-bold">Szerokość</label>
                                        <select id="content_image_width" name="content_image_width"
                                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            <option value="" {{ old('content_image_width', $page->content_image_width ?? '') === '' ? 'selected' : '' }}>Pełna (100%)</option>
                                            <option value="max-w-2xl" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-2xl' ? 'selected' : '' }}>Duże (ok. 672px)</option>
                                            <option value="max-w-xl" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-xl' ? 'selected' : '' }}>Średnie (ok. 576px)</option>
                                            <option value="max-w-lg" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-lg' ? 'selected' : '' }}>Małe (ok. 512px)</option>
                                            <option value="max-w-xs" {{ old('content_image_width', $page->content_image_width ?? '') === 'max-w-xs' ? 'selected' : '' }}>Bardzo małe (ok. 320px)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ TYP I UKŁAD ============================ --}}
            <div data-ftab-panel="typ" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div class="sm:w-1/2">
                        @if ($isPersonForm)
                            <input type="hidden" name="type" value="about_person">
                            <label class="mb-1 block text-sm font-bold">Typ strony</label>
                            <p class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-muted">
                                <i class="fa-solid fa-user mr-1.5 text-brand" aria-hidden="true"></i>
                                Osoba (typ stały — zarządzaj przez moduł <a href="{{ route('admin.osoby.index') }}" class="text-brand underline">Osoby</a>)
                            </p>
                        @else
                            <label for="type" class="mb-1 block text-sm font-bold">Typ strony</label>
                            <select id="type" name="type" data-page-type-select class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @foreach (\App\Models\Page::TYPES as $value => $label)
                                    <option value="{{ $value }}" {{ $currentType === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">„Wydarzenie" dodaje pola o terminie, miejscu i rejestracji. „Harmonogram zajęć / spotkań" dodaje tabelę terminów oraz miejsce na informację o zmianie. Każdy typ ma inny układ na stronie.</p>
                            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @endif
                    </div>

                    <div class="border-t border-gray-100 pt-5 sm:w-1/2">
                        <label for="page_template" class="mb-1 block text-sm font-bold">Szablon wizualny</label>
                        <select id="page_template" name="page_template" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @foreach (\App\Models\Page::TEMPLATES as $value => $label)
                                <option value="{{ $value }}" {{ old('page_template', $page->page_template ?? 'default') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">Zmienia wygląd i układ strony publicznej. Działa dla stron typu „Standardowa", „Wewnętrzna" i „FAQ". Dla pozostałych typów układ jest stały.</p>
                        @error('page_template') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div data-event-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'event' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Szczegóły wydarzenia</p>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="event_mode" class="mb-1 block text-sm font-bold">Forma</label>
                                <select id="event_mode" name="event_mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                    <option value="">— wybierz —</option>
                                    @foreach (\App\Models\Page::EVENT_MODES as $value => $label)
                                        <option value="{{ $value }}" {{ old('event_mode', $page->event_mode) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('event_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="event_when" class="mb-1 block text-sm font-bold">Kiedy</label>
                                <input type="text" id="event_when" name="event_when" value="{{ old('event_when', $page->event_when) }}" placeholder="np. 12 marca 2026, 18:00"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @error('event_when') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="event_location" class="mb-1 block text-sm font-bold">Gdzie</label>
                            <input type="text" id="event_location" name="event_location" value="{{ old('event_location', $page->event_location) }}" placeholder="np. ul. Barbackiego 28, Nowy Sącz — lub nazwa platformy webinaru"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('event_location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="event_how_to_join" class="mb-1 block text-sm font-bold">Jak dołączyć</label>
                            <textarea id="event_how_to_join" name="event_how_to_join" rows="3" placeholder="Instrukcja dołączenia — np. link do spotkania, dojazd, wymagania"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('event_how_to_join', $page->event_how_to_join) }}</textarea>
                            @error('event_how_to_join') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="event_registration_url" class="mb-1 block text-sm font-bold">Link do rejestracji</label>
                            <input type="url" id="event_registration_url" name="event_registration_url" value="{{ old('event_registration_url', $page->event_registration_url) }}" placeholder="https://..."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Jeśli podasz link, na stronie wydarzenia pojawi się przycisk „Zarejestruj się".</p>
                            @error('event_registration_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div data-schedule-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'schedule' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Harmonogram</p>

                        @php $schedulePending = old('schedule_pending', $page->schedule_pending ?? false); @endphp
                        <div class="rounded-lg border {{ $schedulePending ? 'border-amber-300 bg-amber-50' : 'border-gray-200 bg-gray-50' }} p-4" data-schedule-pending-box>
                            <label class="flex items-start gap-3">
                                <input type="hidden" name="schedule_pending" value="0">
                                <input type="checkbox" name="schedule_pending" value="1" {{ $schedulePending ? 'checked' : '' }}
                                    data-schedule-pending-toggle class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                                <span>
                                    <span class="block text-sm font-bold text-ink">Wyświetlaj komunikat „Harmonogram jeszcze nie został opublikowany"</span>
                                    <span class="mt-0.5 block text-xs text-muted">Gdy włączone, zamiast tabeli terminów odwiedzający zobaczą komunikat, że harmonogram nie jest jeszcze gotowy. Terminy poniżej możesz już wpisywać — pojawią się dopiero po wyłączeniu tej opcji.</span>
                                </span>
                            </label>
                        </div>

                        <div>
                            <label for="schedule_change_notice" class="mb-1 block text-sm font-bold">Informacja o zmianie harmonogramu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="schedule_change_notice" name="schedule_change_notice" rows="2" placeholder="np. Uwaga: zajęcia z 12 marca przeniesione na 19 marca."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('schedule_change_notice', $page->schedule_change_notice) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Jeśli wypełnisz, na górze harmonogramu pojawi się wyróżniony komunikat informujący o zmianie.</p>
                            @error('schedule_change_notice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <p class="mb-1 block text-sm font-bold">Terminy</p>
                            <p class="mb-3 text-xs text-muted">Dodaj kolejne terminy (data, godzina, miejsce). Zaznacz „Termin zmieniony", aby wyróżnić wpis, który uległ zmianie.</p>

                            <div data-schedule-rows class="space-y-3">
                                @foreach ($scheduleItems as $i => $item)
                                    <div data-schedule-row class="grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1fr_1fr_1.5fr_auto]">
                                        <div>
                                            <label class="mb-1 block text-xs font-bold text-muted">Data</label>
                                            <input type="date" name="schedule_items[{{ $i }}][date]" value="{{ $item['date'] ?? '' }}" aria-label="Data terminu {{ $i + 1 }}"
                                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-bold text-muted">Godzina</label>
                                            <input type="time" name="schedule_items[{{ $i }}][time]" value="{{ $item['time'] ?? '' }}" aria-label="Godzina terminu {{ $i + 1 }}"
                                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-bold text-muted">Miejsce / lokalizacja</label>
                                            <input type="text" name="schedule_items[{{ $i }}][location]" value="{{ $item['location'] ?? '' }}" placeholder="np. sala 12 / online" aria-label="Miejsce terminu {{ $i + 1 }}"
                                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" data-schedule-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" title="Usuń termin" aria-label="Usuń termin {{ $i + 1 }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                        </div>
                                        <div class="sm:col-span-4">
                                            <label class="mb-1 block text-xs font-bold text-muted">Uwaga <span class="font-normal">(opcjonalnie)</span></label>
                                            <input type="text" name="schedule_items[{{ $i }}][note]" value="{{ $item['note'] ?? '' }}" placeholder="np. spotkanie organizacyjne" aria-label="Uwaga do terminu {{ $i + 1 }}"
                                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        </div>
                                        <label class="flex items-center gap-2 sm:col-span-4">
                                            <input type="hidden" name="schedule_items[{{ $i }}][changed]" value="0">
                                            <input type="checkbox" name="schedule_items[{{ $i }}][changed]" value="1" {{ ! empty($item['changed']) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-brand focus:ring-brand">
                                            <span class="text-sm font-bold">Termin zmieniony</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" data-schedule-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj termin
                            </button>

                            <template data-schedule-template>
                                <div data-schedule-row class="grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1fr_1fr_1.5fr_auto]">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-muted">Data</label>
                                        <input type="date" name="schedule_items[__INDEX__][date]" aria-label="Data terminu"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-muted">Godzina</label>
                                        <input type="time" name="schedule_items[__INDEX__][time]" aria-label="Godzina terminu"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-muted">Miejsce / lokalizacja</label>
                                        <input type="text" name="schedule_items[__INDEX__][location]" placeholder="np. sala 12 / online" aria-label="Miejsce terminu"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" data-schedule-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" title="Usuń termin" aria-label="Usuń termin"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="mb-1 block text-xs font-bold text-muted">Uwaga <span class="font-normal">(opcjonalnie)</span></label>
                                        <input type="text" name="schedule_items[__INDEX__][note]" placeholder="np. spotkanie organizacyjne" aria-label="Uwaga do terminu"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <label class="flex items-center gap-2 sm:col-span-4">
                                        <input type="hidden" name="schedule_items[__INDEX__][changed]" value="0">
                                        <input type="checkbox" name="schedule_items[__INDEX__][changed]" value="1"
                                            class="rounded border-gray-300 text-brand focus:ring-brand">
                                        <span class="text-sm font-bold">Termin zmieniony</span>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>

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
                                        <input type="text" name="about_values[{{ $i }}][icon]" value="{{ $row['icon'] ?? '' }}" placeholder="fa-solid fa-heart" aria-label="Ikona wartości {{ $i + 1 }}" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                                        <input type="text" name="about_values[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł" aria-label="Tytuł wartości {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        <input type="text" name="about_values[{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Krótki opis" aria-label="Opis wartości {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        <button type="button" data-repeater-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wartość"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wartość</button>
                            <template data-repeater-template>
                                <div data-repeater-row class="grid gap-2 sm:grid-cols-[1fr_1fr_2fr_auto]">
                                    <input type="text" name="about_values[__INDEX__][icon]" placeholder="fa-solid fa-heart" aria-label="Ikona wartości" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
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

                    {{-- FAQ — pytania i odpowiedzi --}}
                    <div data-faq-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'faq' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">FAQ — pytania i odpowiedzi</p>

                        <div>
                            <label for="faq_intro" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="faq_intro" name="faq_intro" rows="3" placeholder="Krótkie wprowadzenie nad listą pytań."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('faq_intro', $page->faq_intro) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Wyświetli się nad listą pytań. Pole „Treść" (edytor) możesz zostawić puste.</p>
                            @error('faq_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div data-repeater>
                            <p class="mb-1 text-sm font-bold">Pytania i odpowiedzi</p>
                            <p class="mb-3 text-xs text-muted">Każda para tworzy zwijany element (akordeon) na stronie. W odpowiedzi możesz dodać <strong>linki</strong> (przycisk łańcucha w edytorze) oraz pogrubienia i listy. Puste wiersze są pomijane; kolejność odpowiada kolejności na liście.</p>

                            @php $faqLinkPages = \App\Models\Page::where('is_published', true)->whereNotIn('type', ['internal', 'internal_hub'])->orderBy('title')->get(['slug', 'title']); @endphp
                            @if ($faqLinkPages->isNotEmpty())
                                <div class="mb-3">
                                    <label class="sr-only" for="faq-page-link">Wstaw link do podstrony</label>
                                    <select id="faq-page-link" data-faq-page-link class="rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                        <option value="">Wstaw link do podstrony w serwisie&hellip;</option>
                                        @foreach ($faqLinkPages as $p)
                                            <option value="/{{ $p->slug }}" data-title="{{ $p->title }}">{{ $p->title }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ml-2 text-xs text-muted">Najpierw kliknij w treść odpowiedzi, potem wybierz stronę.</span>
                                </div>
                            @endif
                            <div data-repeater-rows class="space-y-3">
                                @foreach ($faqItems as $i => $row)
                                    <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <input type="text" name="faq_items[{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="Pytanie" aria-label="Pytanie {{ $i + 1 }}"
                                            class="w-full rounded border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand">
                                        <textarea name="faq_items[{{ $i }}][answer]" rows="3" placeholder="Odpowiedź" aria-label="Odpowiedź {{ $i + 1 }}" data-faq-answer
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $row['answer'] ?? '' }}</textarea>
                                        <div class="text-right">
                                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-2 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń pytanie {{ $i + 1 }}"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-repeater-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie</button>
                            <template data-repeater-template>
                                <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <input type="text" name="faq_items[__INDEX__][question]" placeholder="Pytanie" aria-label="Pytanie"
                                        class="w-full rounded border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand">
                                    <textarea name="faq_items[__INDEX__][answer]" rows="3" placeholder="Odpowiedź" aria-label="Odpowiedź" data-faq-answer
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></textarea>
                                    <div class="text-right">
                                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-2 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń pytanie"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Bip-Move — komunikat o przeniesieniu do BIP --}}
                    <div data-bipmove-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'bip_move' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Przeniesiono do BIP</p>
                        <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                            Ten typ wyświetla gotowy komunikat, że treść została przeniesiona do Biuletynu Informacji Publicznej, wraz z oficjalnym logo BIP i wyjaśnieniem oddzielenia warstwy reprezentacyjnej od formalnej. Poniżej możesz doprecyzować link i dodatkową informację.
                        </p>

                        <div>
                            <label for="bip_move_url" class="mb-1 block text-sm font-bold">Bezpośredni link do treści w BIP <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="url" id="bip_move_url" name="bip_move_url" value="{{ old('bip_move_url', $page->bip_move_url) }}"
                                placeholder="https://bip… — puste = ogólny adres BIP z Ustawień"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Puste = przycisk poprowadzi do ogólnego adresu BIP z „Ustawienia → Media i BIP". Dodatkowy opis wpiszesz w polu „Dodatkowa informacja" poniżej.</p>
                            @error('bip_move_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bip_move_note" class="mb-1 block text-sm font-bold">Dodatkowa informacja <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="bip_move_note" name="bip_move_note" rows="3" placeholder="np. W BIP znajdziesz sprawozdania, statut i dokumenty formalne fundacji."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('bip_move_note', $page->bip_move_note) }}</textarea>
                            @error('bip_move_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div data-internal-fields class="space-y-5 border-t border-gray-100 pt-5 {{ in_array($currentType, ['internal', 'internal_hub'], true) ? '' : 'hidden' }}"
                        x-data="{ mode: '{{ old('access_mode', $page->access_mode ?? 'password') }}' }">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Dostęp do strony wewnętrznej</p>
                        <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                            Strona jest opublikowana, ale jej treść zobaczą tylko osoby autoryzowane — hasłem lub po zalogowaniu (Microsoft 365 / konto panelu), zależnie od trybu poniżej.
                        </p>

                        <div>
                            <label for="access_mode" class="mb-1 block text-sm font-bold">Tryb dostępu</label>
                            <select id="access_mode" name="access_mode" x-model="mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @foreach (\App\Models\Page::ACCESS_MODES as $value => $labelText)
                                    <option value="{{ $value }}" {{ old('access_mode', $page->access_mode ?? 'password') === $value ? 'selected' : '' }}>{{ $labelText }}</option>
                                @endforeach
                            </select>
                            @error('access_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="mode === 'password'" x-cloak>
                            <label for="access_password" class="mb-1 block text-sm font-bold">Hasło dostępu</label>
                            <input type="text" id="access_password" name="access_password" autocomplete="off"
                                placeholder="{{ $page->exists && filled($page->access_password) ? 'Ustawione — wpisz nowe, aby zmienić' : 'Wpisz hasło' }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">@if ($page->exists && filled($page->access_password)) Puste pole = hasło bez zmian. @else Odwiedzający poda to hasło, aby odblokować stronę. @endif</p>
                            @error('access_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <p x-show="mode === 'microsoft'" x-cloak class="text-xs text-muted">
                            Treść zobaczą wyłącznie zalogowani użytkownicy panelu (m.in. przez Microsoft 365). Niezalogowani zostaną przekierowani do logowania.
                        </p>
                    </div>

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
                                            <input type="text" name="hub_links[{{ $i }}][icon]" value="{{ $row['icon'] ?? '' }}" placeholder="fa-solid fa-handshake" aria-label="Ikona {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
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
                                        <input type="text" name="hub_links[__INDEX__][icon]" placeholder="fa-solid fa-handshake" aria-label="Ikona" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
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

                    {{-- Instytucja szkoleniowa --}}
                    <div data-training-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'training_institution' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Instytucja szkoleniowa</p>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="training_manager_name" class="mb-1 block text-sm font-bold">Imię i nazwisko kierownika</label>
                                <input type="text" id="training_manager_name" name="training_manager_name"
                                    value="{{ old('training_manager_name', $page->training_manager_name) }}"
                                    placeholder="np. Jan Kowalski"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @error('training_manager_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="training_manager_title" class="mb-1 block text-sm font-bold">Stanowisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="text" id="training_manager_title" name="training_manager_title"
                                    value="{{ old('training_manager_title', $page->training_manager_title) }}"
                                    placeholder="np. Kierownik szkolenia"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @error('training_manager_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="training_ris_number" class="mb-1 block text-sm font-bold">Numer wpisu RIS <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="text" id="training_ris_number" name="training_ris_number"
                                    value="{{ old('training_ris_number', $page->training_ris_number) }}"
                                    placeholder="np. 2.18/00001/2024"
                                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                                @error('training_ris_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="training_bur_number" class="mb-1 block text-sm font-bold">Numer wpisu BUR <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="text" id="training_bur_number" name="training_bur_number"
                                    value="{{ old('training_bur_number', $page->training_bur_number) }}"
                                    placeholder="np. 2.18/00001/2024"
                                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                                @error('training_bur_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="training_extra_info" class="mb-1 block text-sm font-bold">Dodatkowe informacje <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="training_extra_info" name="training_extra_info" rows="4"
                                placeholder="Np. zakres szkoleń, certyfikaty, obszary działalności."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('training_extra_info', $page->training_extra_info) }}</textarea>
                            @error('training_extra_info') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="training_bur_note" class="mb-1 block text-sm font-bold">Wyjaśnienie braku wpisu w BUR <span class="font-normal text-muted">(opcjonalnie — wyróżnione na stronie)</span></label>
                            <textarea id="training_bur_note" name="training_bur_note" rows="3"
                                placeholder="np. Nie figurujemy w BUR, ponieważ…"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('training_bur_note', $page->training_bur_note) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Pojawi się jako wyróżniona ramka z informacją. Wypełnij, jeśli nie jesteście wpisani do BUR i chcecie to wyjaśnić.</p>
                            @error('training_bur_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- „Prezentacja tego, co było" — poprzednik kontynuowany przez FEER --}}
                    <div data-legacy-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'legacy' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Prezentacja tego, co było</p>
                        <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                            Ten typ prezentuje historię i działalność podmiotu poprzedzającego FEER, którego działalność kontynuujemy. Historię i dokonania opisz w polu „Treść" (edytor); możesz też dołączyć galerię zdjęć.
                        </p>
                        <div>
                            <label for="legacy_name" class="mb-1 block text-sm font-bold">Nazwa poprzednika</label>
                            <input type="text" id="legacy_name" name="legacy_name" value="{{ old('legacy_name', $page->legacy_name) }}" placeholder="np. Stowarzyszenie „Dawna Nazwa""
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('legacy_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="legacy_intro" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="legacy_intro" name="legacy_intro" rows="3" placeholder="Krótko: czym był ten podmiot i jak FEER kontynuuje jego działalność."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('legacy_intro', $page->legacy_intro) }}</textarea>
                            @error('legacy_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- ====== MARKA — IDENTYFIKACJA WIZUALNA ====== --}}
                    @php $brandSectionsValue = array_values((array) old('brand_sections', $page->brand_sections ?? [])); @endphp
                    <div data-brand-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'brand_assets' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Identyfikacja wizualna</p>
                        <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                            Strona chroniona indywidualnym loginem i hasłem. Pliki do pobrania wgrywasz w zakładce „Pliki" — tam przypisujesz plik do sekcji zdefiniowanej poniżej.
                        </p>

                        {{-- Link do brandbooka --}}
                        <div>
                            <label for="brand_brandbook_url" class="mb-1 block text-sm font-bold">URL brandbooka <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="url" id="brand_brandbook_url" name="brand_brandbook_url"
                                value="{{ old('brand_brandbook_url', $page->brand_brandbook_url) }}"
                                placeholder="https://…"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Wyświetli się jako przycisk „Pobierz brandbook" na stronie.</p>
                            @error('brand_brandbook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Sekcje plików (repeater) --}}
                        <div>
                            <p class="mb-2 text-sm font-bold">Sekcje plików</p>
                            <p class="mb-3 text-xs text-muted">Każda sekcja to osobna grupa plików na stronie (np. „Logotyp kolorowy", „Bannery"). Dodaj sekcje, a potem przypisz do nich pliki w zakładce „Pliki".</p>

                            <div data-repeater data-repeater-name="brand_sections">
                                <div data-repeater-rows class="space-y-2">
                                    @foreach ($brandSectionsValue as $bsIndex => $bs)
                                        <div data-repeater-row class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 p-2">
                                            <div class="flex flex-1 gap-2">
                                                <div class="flex-1">
                                                    <label class="sr-only">Tytuł sekcji</label>
                                                    <input type="text" name="brand_sections[{{ $bsIndex }}][title]"
                                                        value="{{ old('brand_sections.' . $bsIndex . '.title', $bs['title'] ?? '') }}"
                                                        placeholder="Tytuł sekcji, np. Logotyp — wersja kolorowa"
                                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                                </div>
                                                <div class="w-40">
                                                    <label class="sr-only">Klucz (ID sekcji)</label>
                                                    <input type="text" name="brand_sections[{{ $bsIndex }}][key]"
                                                        value="{{ old('brand_sections.' . $bsIndex . '.key', $bs['key'] ?? '') }}"
                                                        placeholder="klucz-sekcji"
                                                        class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                                                </div>
                                            </div>
                                            <button type="button" data-repeater-remove
                                                class="flex-none text-muted hover:text-red-600" title="Usuń">
                                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                <span class="sr-only">Usuń sekcję</span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <template data-repeater-template>
                                    <div data-repeater-row class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 p-2 mt-2">
                                        <div class="flex flex-1 gap-2">
                                            <div class="flex-1">
                                                <label class="sr-only">Tytuł sekcji</label>
                                                <input type="text" name="brand_sections[__INDEX__][title]"
                                                    placeholder="Tytuł sekcji, np. Logotyp — wersja kolorowa"
                                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"
                                                    data-autoslug-source data-autoslug-target="brand_sections[__INDEX__][key]">
                                            </div>
                                            <div class="w-40">
                                                <label class="sr-only">Klucz (ID sekcji)</label>
                                                <input type="text" name="brand_sections[__INDEX__][key]"
                                                    placeholder="klucz-sekcji"
                                                    class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                                            </div>
                                        </div>
                                        <button type="button" data-repeater-remove
                                            class="flex-none text-muted hover:text-red-600" title="Usuń">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                            <span class="sr-only">Usuń sekcję</span>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" data-repeater-add
                                    class="mt-3 inline-flex items-center gap-1 rounded border border-dashed border-gray-300 bg-white px-3 py-1 text-sm text-muted hover:bg-gray-50">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj sekcję
                                </button>
                            </div>
                        </div>

                        @if ($page->exists && $page->isBrandAssets())
                            <div class="rounded border border-gray-200 bg-gray-50 p-3">
                                <a href="{{ route('admin.podstrony.dostep.index', $page) }}"
                                    class="inline-flex items-center gap-2 font-bold text-brand hover:text-brand-dark hover:underline">
                                    <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
                                    Zarządzaj użytkownikami dostępu ({{ $page->brandAccessUsers()->count() }})
                                    <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                                <p class="mt-1 text-xs text-muted">Generuj loginy i hasła, dezaktywuj konta, eksportuj CSV.</p>
                            </div>
                        @endif
                    </div>

                    {{-- O organizacji — osoba --}}
                    @php $personSocialValues = old('person_social', $page->person_social ?? []); @endphp
                    <div data-about-person-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'about_person' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">O organizacji — osoba</p>
                        <p class="text-xs text-muted">Tytuł strony to imię i nazwisko osoby. Zdjęcie profilowe dodaj w sekcji „Zdjęcie w treści" na zakładce Treść. Slug generowany automatycznie: {org}/osoba/{imie-nazwisko}. Strony osób nie mają podstron i nie pojawiają się samodzielnie w menu.</p>

                        <div>
                            <label for="person_member_label" class="mb-2 block text-sm font-bold">Etykieta członkostwa <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <div class="mb-2 flex flex-wrap gap-2">
                                @foreach (['Członkini zespołu FEER', 'Członek zespołu FEER', 'Wolontariuszka', 'Wolontariusz', 'Współpracowniczka', 'Współpracownik'] as $lbl)
                                    <button type="button" onclick="document.getElementById('person_member_label').value='{{ $lbl }}'"
                                        class="rounded border border-gray-300 px-3 py-1 text-xs hover:border-brand hover:text-brand">{{ $lbl }}</button>
                                @endforeach
                            </div>
                            <input type="text" id="person_member_label" name="person_member_label"
                                value="{{ old('person_member_label', $page->person_member_label) }}"
                                placeholder="np. Członkini zespołu FEER"
                                maxlength="60"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('person_member_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="person_phone" class="mb-1 block text-sm font-bold">Nr telefonu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="tel" id="person_phone" name="person_phone"
                                    value="{{ old('person_phone', $page->person_phone) }}"
                                    placeholder="+48 000 000 000"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @error('person_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="person_email" class="mb-1 block text-sm font-bold">E-mail kontaktowy <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="email" id="person_email" name="person_email"
                                    value="{{ old('person_email', $page->person_email) }}"
                                    placeholder="osoba@feer.org.pl"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @error('person_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="person_role" class="mb-1 block text-sm font-bold">Co robi w FEER / stanowisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="person_role" name="person_role"
                                value="{{ old('person_role', $page->person_role) }}"
                                placeholder="np. Koordynatorka projektów, wolontariusz…"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('person_role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div x-data="{
                            tags: @json(old('person_department', $page->person_department ?? [])),
                            input: '',
                            add() {
                                const v = this.input.trim();
                                if (v && !this.tags.includes(v)) this.tags.push(v);
                                this.input = '';
                            },
                            remove(t) { this.tags = this.tags.filter(x => x !== t); }
                        }">
                            <label class="mb-1 block text-sm font-bold">Działy / sekcje <span class="font-normal text-muted">(opcjonalnie, można dodać kilka)</span></label>
                            <div class="flex flex-wrap gap-1.5 rounded border border-gray-300 bg-white p-2 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                                <template x-for="t in tags" :key="t">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-light px-2.5 py-0.5 text-xs font-bold text-brand-dark">
                                        <span x-text="t"></span>
                                        <button type="button" @click="remove(t)" class="text-brand hover:text-red-600" :aria-label="'Usuń ' + t">&times;</button>
                                        <input type="hidden" :name="'person_department[]'" :value="t">
                                    </span>
                                </template>
                                <input type="text" x-model="input"
                                    @keydown.enter.prevent="add()"
                                    @keydown.comma.prevent="add()"
                                    placeholder="Wpisz dział i naciśnij Enter…"
                                    class="min-w-[180px] flex-1 border-0 p-0 text-sm focus:ring-0">
                            </div>
                            <p class="mt-1 text-xs text-muted">np. Zarząd, Biuro, Wolontariat — Enter lub przecinek dodaje dział</p>
                            @error('person_department') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="person_bio" class="mb-1 block text-sm font-bold">Krótkie o mnie <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="person_bio" name="person_bio" rows="4"
                                placeholder="Kilka zdań — pojawi się wyróżnione na stronie osoby."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('person_bio', $page->person_bio) }}</textarea>
                            @error('person_bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <p class="mb-3 text-sm font-bold">Social media <span class="font-normal text-muted">(opcjonalnie)</span></p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="person_social_facebook" class="mb-1 block text-xs font-bold text-muted">Facebook</label>
                                    <input type="url" id="person_social_facebook" name="person_social[facebook]"
                                        value="{{ $personSocialValues['facebook'] ?? '' }}" placeholder="https://facebook.com/…"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label for="person_social_instagram" class="mb-1 block text-xs font-bold text-muted">Instagram</label>
                                    <input type="url" id="person_social_instagram" name="person_social[instagram]"
                                        value="{{ $personSocialValues['instagram'] ?? '' }}" placeholder="https://instagram.com/…"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label for="person_social_linkedin" class="mb-1 block text-xs font-bold text-muted">LinkedIn</label>
                                    <input type="url" id="person_social_linkedin" name="person_social[linkedin]"
                                        value="{{ $personSocialValues['linkedin'] ?? '' }}" placeholder="https://linkedin.com/in/…"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label for="person_social_website" class="mb-1 block text-xs font-bold text-muted">Strona internetowa</label>
                                    <input type="url" id="person_social_website" name="person_social[website]"
                                        value="{{ $personSocialValues['website'] ?? '' }}" placeholder="https://…"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                    {{-- ======= WSPÓŁPRACA — pola edycji sekcji ======= --}}
                    @php
                        $cd = old('cooperation_data', $page->cooperation_data ?? []);
                        $cdSectors = $cd['sectors'] ?? [
                            ['icon' => 'fa-solid fa-building',    'color' => 'blue',   'title' => 'Biznes',               'text' => '', 'tag1' => 'CSR / ESG', 'tag2' => 'Wolontariat pracowniczy', 'tag3' => 'Wizerunek marki'],
                            ['icon' => 'fa-solid fa-landmark',    'color' => 'green',  'title' => 'Samorząd i instytucje','text' => '', 'tag1' => 'Dialog obywatelski', 'tag2' => 'Polityka społeczna', 'tag3' => 'Aktywizacja lokalna'],
                            ['icon' => 'fa-solid fa-flask',       'color' => 'purple', 'title' => 'Nauka i edukacja',     'text' => '', 'tag1' => 'Innowacje społeczne', 'tag2' => 'Praktyki i badania', 'tag3' => 'Transfer wiedzy'],
                            ['icon' => 'fa-solid fa-people-group','color' => 'orange', 'title' => 'Inne NGO',             'text' => '', 'tag1' => 'Koalicje i synergia', 'tag2' => 'Wymiana zasobów', 'tag3' => 'Wspólny advocacy'],
                        ];
                        $cdForms = $cd['forms'] ?? [
                            ['icon' => 'fa-solid fa-star',                    'title' => 'Partnerstwo strategiczne',  'text' => ''],
                            ['icon' => 'fa-solid fa-circle-dollar-to-slot',   'title' => 'Sponsoring',               'text' => ''],
                            ['icon' => 'fa-solid fa-user-gear',               'title' => 'Wolontariat kompetencyjny','text' => ''],
                            ['icon' => 'fa-solid fa-sitemap',                 'title' => 'Koalicje i sieci',         'text' => ''],
                        ];
                    @endphp
                    <div data-cooperation-fields class="space-y-6 border-t border-gray-100 pt-5 {{ $currentType === 'wspolpraca' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Współpraca — treść sekcji</p>

                        {{-- Hero --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
                            <p class="text-xs font-bold uppercase text-muted">Hero</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Badge (tekst przy ikonie)</label>
                                    <input type="text" name="cooperation_data[hero_badge]" value="{{ $cd['hero_badge'] ?? 'Partnerstwo i współpraca' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Tytuł sekcji (h1)</label>
                                    <p class="text-xs text-muted">Pobierany z pola <strong>Tytuł strony</strong> powyżej.</p>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold">Podtytuł hero</label>
                                <textarea name="cooperation_data[hero_subtitle]" rows="2" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $cd['hero_subtitle'] ?? 'FEER łączy biznes, samorząd, naukę i organizacje pozarządowe wokół wspólnych wartości. Razem działamy skuteczniej, docieramy dalej i tworzymy zmiany, które zostają.' }}</textarea>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Przycisk CTA 1 — etykieta</label>
                                    <input type="text" name="cooperation_data[hero_cta1_label]" value="{{ $cd['hero_cta1_label'] ?? 'Zostań partnerem' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Przycisk CTA 1 — URL</label>
                                    <input type="text" name="cooperation_data[hero_cta1_url]" value="{{ $cd['hero_cta1_url'] ?? '/kontakt' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Przycisk CTA 2 — etykieta</label>
                                    <input type="text" name="cooperation_data[hero_cta2_label]" value="{{ $cd['hero_cta2_label'] ?? 'Poznaj formy współpracy' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                        </div>

                        {{-- Statystyki --}}
                        @php $cdStats = $cd['stats'] ?? [['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>'']]; @endphp
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
                            <p class="text-xs font-bold uppercase text-muted">Pasek liczb (opcjonalne — maks. 4)</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($cdStats as $si => $stat)
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <label class="mb-0.5 block text-xs">Wartość {{ $si + 1 }}</label>
                                        <input type="text" name="cooperation_data[stats][{{ $si }}][value]" value="{{ $stat['value'] ?? '' }}" placeholder="np. 15 lat" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    </div>
                                    <div class="flex-1">
                                        <label class="mb-0.5 block text-xs">Etykieta {{ $si + 1 }}</label>
                                        <input type="text" name="cooperation_data[stats][{{ $si }}][label]" value="{{ $stat['label'] ?? '' }}" placeholder="np. doświadczenia" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Sektory — dynamiczny repeater --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
                            <p class="text-xs font-bold uppercase text-muted">Sekcja: Dlaczego warto (sektory)</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Nagłówek sekcji</label>
                                    <input type="text" name="cooperation_data[sectors_heading]" value="{{ $cd['sectors_heading'] ?? 'Dlaczego warto z nami współpracować?' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Podtytuł sekcji</label>
                                    <input type="text" name="cooperation_data[sectors_subtitle]" value="{{ $cd['sectors_subtitle'] ?? 'Każdy sektor ma inne potrzeby — mamy na to odpowiedź.' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                            <div data-repeater>
                                <div data-repeater-rows class="space-y-3">
                                    @foreach ($cdSectors as $si => $sector)
                                        <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                                            <div class="grid gap-2 sm:grid-cols-3">
                                                <div>
                                                    <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                                                    <input type="text" name="cooperation_data[sectors][{{ $si }}][icon]" value="{{ $sector['icon'] ?? '' }}" placeholder="fa-solid fa-building" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                </div>
                                                <div>
                                                    <label class="mb-0.5 block text-xs">Kolor</label>
                                                    <select name="cooperation_data[sectors][{{ $si }}][color]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                        @foreach (['blue' => 'Niebieski', 'green' => 'Zielony', 'purple' => 'Fioletowy', 'orange' => 'Pomarańczowy', 'brand' => 'Brand'] as $val => $lbl)
                                                            <option value="{{ $val }}" {{ ($sector['color'] ?? 'blue') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-0.5 block text-xs">Tytuł</label>
                                                    <input type="text" name="cooperation_data[sectors][{{ $si }}][title]" value="{{ $sector['title'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs">Treść</label>
                                                <textarea name="cooperation_data[sectors][{{ $si }}][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">{{ $sector['text'] ?? '' }}</textarea>
                                            </div>
                                            <div class="grid gap-2 sm:grid-cols-3">
                                                <div><label class="mb-0.5 block text-xs">Tag 1</label><input type="text" name="cooperation_data[sectors][{{ $si }}][tag1]" value="{{ $sector['tag1'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                                                <div><label class="mb-0.5 block text-xs">Tag 2</label><input type="text" name="cooperation_data[sectors][{{ $si }}][tag2]" value="{{ $sector['tag2'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                                                <div><label class="mb-0.5 block text-xs">Tag 3</label><input type="text" name="cooperation_data[sectors][{{ $si }}][tag3]" value="{{ $sector['tag3'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                                            </div>
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                                <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                                <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń sektor"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj sektor</button>
                                <template data-repeater-template>
                                    <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <div>
                                                <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                                                <input type="text" name="cooperation_data[sectors][__INDEX__][icon]" placeholder="fa-solid fa-building" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs">Kolor</label>
                                                <select name="cooperation_data[sectors][__INDEX__][color]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                    <option value="blue">Niebieski</option>
                                                    <option value="green">Zielony</option>
                                                    <option value="purple">Fioletowy</option>
                                                    <option value="orange">Pomarańczowy</option>
                                                    <option value="brand">Brand</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs">Tytuł</label>
                                                <input type="text" name="cooperation_data[sectors][__INDEX__][title]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-0.5 block text-xs">Treść</label>
                                            <textarea name="cooperation_data[sectors][__INDEX__][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></textarea>
                                        </div>
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <div><label class="mb-0.5 block text-xs">Tag 1</label><input type="text" name="cooperation_data[sectors][__INDEX__][tag1]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                                            <div><label class="mb-0.5 block text-xs">Tag 2</label><input type="text" name="cooperation_data[sectors][__INDEX__][tag2]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                                            <div><label class="mb-0.5 block text-xs">Tag 3</label><input type="text" name="cooperation_data[sectors][__INDEX__][tag3]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                                        </div>
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                            <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń sektor"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Formy współpracy — dynamiczny repeater --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
                            <p class="text-xs font-bold uppercase text-muted">Sekcja: Formy współpracy</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Nagłówek sekcji</label>
                                    <input type="text" name="cooperation_data[forms_heading]" value="{{ $cd['forms_heading'] ?? 'Formy współpracy' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Podtytuł sekcji</label>
                                    <input type="text" name="cooperation_data[forms_subtitle]" value="{{ $cd['forms_subtitle'] ?? 'Wybierz formułę dopasowaną do Twoich możliwości i celów.' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                            <div data-repeater>
                                <div data-repeater-rows class="space-y-3">
                                    @foreach ($cdForms as $fi => $form)
                                        <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                                                    <input type="text" name="cooperation_data[forms][{{ $fi }}][icon]" value="{{ $form['icon'] ?? '' }}" placeholder="fa-solid fa-star" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                </div>
                                                <div>
                                                    <label class="mb-0.5 block text-xs">Tytuł</label>
                                                    <input type="text" name="cooperation_data[forms][{{ $fi }}][title]" value="{{ $form['title'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs">Treść</label>
                                                <textarea name="cooperation_data[forms][{{ $fi }}][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">{{ $form['text'] ?? '' }}</textarea>
                                            </div>
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                                <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                                <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń formę"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj formę</button>
                                <template data-repeater-template>
                                    <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                                                <input type="text" name="cooperation_data[forms][__INDEX__][icon]" placeholder="fa-solid fa-star" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-xs">Tytuł</label>
                                                <input type="text" name="cooperation_data[forms][__INDEX__][title]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-0.5 block text-xs">Treść</label>
                                            <textarea name="cooperation_data[forms][__INDEX__][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></textarea>
                                        </div>
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                            <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń formę"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
                            <p class="text-xs font-bold uppercase text-muted">Sekcja: CTA — Zacznijmy rozmowę</p>
                            <div>
                                <label class="mb-1 block text-xs font-bold">Nagłówek CTA</label>
                                <input type="text" name="cooperation_data[cta_heading]" value="{{ $cd['cta_heading'] ?? 'Zacznijmy rozmowę' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold">Treść CTA</label>
                                <textarea name="cooperation_data[cta_text]" rows="2" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $cd['cta_text'] ?? 'Każda trwała współpraca zaczyna się od jednej wiadomości. Napisz do nas — opowiedz, kim jesteś i co chcesz osiągnąć, a my odpiszemy z propozycją kolejnych kroków.' }}</textarea>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Etykieta przycisku</label>
                                    <input type="text" name="cooperation_data[cta_button_label]" value="{{ $cd['cta_button_label'] ?? 'Napisz do nas' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold">URL przycisku</label>
                                    <input type="text" name="cooperation_data[cta_button_url]" value="{{ $cd['cta_button_url'] ?? '/kontakt' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                        </div>

                        {{-- Strona w budowie --}}
                        <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4">
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                                <input type="hidden" name="cooperation_data[under_construction]" value="0">
                                <input type="checkbox" name="cooperation_data[under_construction]" value="1"
                                    {{ !empty($cd['under_construction']) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="font-semibold text-ink">Strona w budowie</span>
                            </label>
                            <p class="ml-6 mt-1 text-xs text-muted">Gdy zaznaczone, zamiast pełnej treści odwiedzający widzą komunikat „W przygotowaniu".</p>
                        </div>

                        {{-- Formularz zgłoszeniowy --}}
                        <div class="rounded-lg border border-brand/20 bg-brand-light/30 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-ink">Formularz zgłoszeniowy</p>
                                    <p class="text-xs text-muted">Formularz dostępny pod adresem /{slug}/formularz</p>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="hidden" name="cooperation_data[form_enabled]" value="0">
                                    <input type="checkbox" name="cooperation_data[form_enabled]" value="1"
                                        {{ !empty($cd['form_enabled']) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-brand focus:ring-brand">
                                    <span class="font-semibold text-ink">Aktywny</span>
                                </label>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold">Tytuł formularza</label>
                                    <input type="text" name="cooperation_data[form_title]"
                                        value="{{ $cd['form_title'] ?? 'Formularz współpracy' }}"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold">E-mail odbiorcy zgłoszeń</label>
                                    <input type="email" name="cooperation_data[form_recipient]"
                                        value="{{ $cd['form_recipient'] ?? '' }}"
                                        placeholder="Domyślnie: kontaktowy z Ustawień"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-bold">Podtytuł / intro formularza</label>
                                    <input type="text" name="cooperation_data[form_subtitle]"
                                        value="{{ $cd['form_subtitle'] ?? '' }}"
                                        placeholder="np. Wypełnij poniższe pola — odezwiemy się w ciągu 2 dni roboczych."
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-bold">Komunikat po wysłaniu</label>
                                    <input type="text" name="cooperation_data[form_confirmation]"
                                        value="{{ $cd['form_confirmation'] ?? '' }}"
                                        placeholder="np. Dziękujemy! Odezwiemy się wkrótce."
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

            {{-- ==================== PUBLIKACJA I POWIĄZANIA ==================== --}}
            <div data-ftab-panel="ustawienia" class="hidden space-y-6">
                {{-- Karta: widoczność i status --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6"
                    x-data="{ pub: {{ old('is_published', $page->is_published ?? true) ? 'true' : 'false' }} }">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-ink">Widoczność i status</h2>
                        <p class="mt-0.5 text-xs text-muted">Decyduje, czy i jak strona pojawia się w serwisie.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}
                                @change="pub = $event.target.checked"
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Opublikowana</span>
                                <span class="block text-xs text-muted">Strona jest dostępna publicznie.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="is_archived" value="1" {{ old('is_archived', $page->is_archived ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-clock-rotate-left text-muted" aria-hidden="true"></i> Treść archiwalna</span>
                                <span class="block text-xs text-muted">Pokazuje baner, że treść może być nieaktualna (pozostaje w wyszukiwarce).</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="show_in_menu" value="1" {{ old('show_in_menu', $page->show_in_menu ?? true) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Dodaj do menu</span>
                                <span class="block text-xs text-muted">Tylko strony główne (bez rodzica i projektu) trafiają do nawigacji.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="hidden" name="show_side_nav" value="0">
                            <input type="checkbox" name="show_side_nav" value="1" {{ old('show_side_nav', $page->show_side_nav ?? true) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Boczne drzewo nawigacji</span>
                                <span class="block text-xs text-muted">Pokazuje z boku listę podstron w tym dziale. Wyłącz dla stron bez rozbudowanej struktury.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                            <input type="checkbox" name="is_system" value="1" {{ old('is_system', $page->is_system ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Strona systemowa</span>
                                <span class="block text-xs text-muted">Wymagana strona serwisu — nie można jej usunąć.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 {{ $currentType === 'about' ? 'hidden' : '' }}" data-gallery-toggle>
                            <input type="hidden" name="show_gallery" value="0">
                            <input type="checkbox" name="show_gallery" value="1" {{ old('show_gallery', $page->show_gallery ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Pokaż galerię zdjęć</span>
                                <span class="block text-xs text-muted">Wyświetla zdjęcia z zakładki „Galeria".</span>
                            </span>
                        </label>

                        @if (auth()->user()->isAdmin())
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="hidden" name="is_locked" value="0">
                                <input type="checkbox" name="is_locked" value="1" {{ old('is_locked', $page->is_locked ?? false) ? 'checked' : '' }}
                                    class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                                <span>
                                    <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-lock text-brand" aria-hidden="true"></i> Zablokuj do edycji</span>
                                    <span class="block text-xs text-muted">Edytować, klonować i usuwać może tylko administrator.</span>
                                </span>
                            </label>
                        @elseif ($page->is_locked ?? false)
                            <p class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                                <i class="fa-solid fa-lock mt-0.5" aria-hidden="true"></i>
                                <span>Ta strona jest zablokowana do edycji przez administratora.</span>
                            </p>
                        @endif
                    </div>

                    {{-- Harmonogram: data i godzina pierwszego pokazania strony --}}
                    <div x-show="pub" x-cloak class="mt-4 flex flex-wrap items-end gap-4 rounded-lg border border-blue-100 bg-blue-50/50 p-4">
                        <div>
                            <label for="publish_at" class="mb-1 block text-sm font-bold text-ink">
                                <i class="fa-regular fa-clock mr-1 text-blue-400" aria-hidden="true"></i>
                                Opublikuj dopiero od
                                <span class="font-normal text-muted">(opcjonalnie)</span>
                            </label>
                            <input type="datetime-local" id="publish_at" name="publish_at"
                                value="{{ old('publish_at', $page->publish_at?->format('Y-m-d\TH:i')) }}"
                                class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('publish_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <p class="text-xs text-muted">
                            Puste = widoczna natychmiast. Podaj datę, aby strona pojawiła się publicznie dopiero od tej chwili — wcześniej niedostępna dla odwiedzających.
                        </p>
                    </div>
                </div>

                {{-- Karta: powiązania i kolejność --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-ink">Powiązania i kolejność</h2>
                        <p class="mt-0.5 text-xs text-muted">Umiejscowienie strony w strukturze serwisu.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="parent_id" class="mb-1 block text-sm font-bold">Nadrzędna strona</label>
                            <select id="parent_id" name="parent_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="">— brak (strona główna) —</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}" {{ (int) old('parent_id', $page->parent_id ?? request('parent_id')) === $option->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Strony z tym samym rodzicem tworzą wspólne, osobne podmenu.</p>
                            @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="project_id" class="mb-1 block text-sm font-bold">Powiąż z projektem</label>
                            <select id="project_id" name="project_id" data-project-select class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="">— brak —</option>
                                @foreach ($projectOptions as $option)
                                    <option value="{{ $option->id }}" {{ (int) old('project_id', $page->project_id) === $option->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Strona zachowa własny adres, a dodatkowo pojawi się na stronie projektu.</p>
                            @error('project_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2 {{ $hasProject ? '' : 'hidden' }}" data-project-display-wrap>
                            <label for="project_display" class="mb-1 block text-sm font-bold">Jak pokazać na stronie projektu</label>
                            <select id="project_display" name="project_display" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand sm:w-2/3">
                                @foreach (\App\Models\Page::PROJECT_DISPLAYS as $value => $label)
                                    <option value="{{ $value }}" {{ old('project_display', $page->project_display ?? 'link') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Jako sam odnośnik, jako zakładka albo jako sekcja w treści projektu.</p>
                            @error('project_display') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                            <input type="number" id="order" name="order" min="0" value="{{ old('order', $page->order) }}"
                                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Mniejsza liczba = wyżej.</p>
                        </div>
                    </div>
                </div>

                {{-- ==================== DOSTĘPNOŚĆ STRONY ==================== --}}
                @php $currentWip = old('wip_mode', $page->wip_mode); @endphp
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Dostępność strony</p>
                        <p class="mt-1 text-xs text-muted">Tymczasowo wyłącz stronę lub oznacz, że jest w przygotowaniu. Działa niezależnie od statusu publikacji.</p>
                    </div>

                    {{-- Wyłącz stronę --}}
                    <div class="border-t border-gray-100 pt-5">
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="is_disabled" value="1" {{ old('is_disabled', $page->is_disabled ?? false) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand" data-disable-toggle>
                            <span>
                                <span class="block text-sm font-bold">Wyłącz stronę</span>
                                <span class="block text-xs text-muted">Odwiedzający zamiast treści zobaczą pełnoekranowy komunikat, że strona jest tymczasowo niedostępna.</span>
                            </span>
                        </label>
                        <div class="mt-3 sm:pl-6" data-disable-message>
                            <label for="disabled_message" class="mb-1 block text-sm font-bold">Komunikat <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="disabled_message" name="disabled_message" rows="2" placeholder="{{ \App\Models\Page::DEFAULT_DISABLED_MESSAGE }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('disabled_message', $page->disabled_message) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu.</p>
                            @error('disabled_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Strona w przygotowaniu --}}
                    <div class="border-t border-gray-100 pt-5">
                        <p class="text-sm font-bold">Strona w przygotowaniu</p>
                        <p class="mb-3 text-xs text-muted">Oznacz, że trwają prace nad stroną — wybierz, jak poinformować odwiedzających.</p>

                        <div class="space-y-2" data-wip-modes>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="wip_mode" value="" {{ ! $currentWip ? 'checked' : '' }}
                                    class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">Wyłączone <span class="text-muted">— strona działa normalnie</span></span>
                            </label>
                            @foreach (\App\Models\Page::WIP_MODES as $value => $label)
                                <label class="flex items-start gap-2">
                                    <input type="radio" name="wip_mode" value="{{ $value }}" {{ $currentWip === $value ? 'checked' : '' }}
                                        class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('wip_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-3 sm:pl-6 {{ $currentWip ? '' : 'hidden' }}" data-wip-message>
                            <label for="wip_message" class="mb-1 block text-sm font-bold">Treść komunikatu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="wip_message" name="wip_message" rows="2" placeholder="{{ \App\Models\Page::DEFAULT_WIP_NOTICE_MESSAGE }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('wip_message', $page->wip_message) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Zostaw puste, aby użyć domyślnego komunikatu dla wybranego trybu.</p>
                            @error('wip_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ SEO ============================ --}}
            <div data-ftab-panel="seo" class="hidden space-y-6">
                @include('admin.partials.seo-fields', ['model' => $page])
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
                @if ($isPersonForm)
                    <a href="{{ route('admin.osoby.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
                @else
                    <a href="{{ route('admin.podstrony.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
                @endif
            </div>
        </form>

        @if ($page->exists)
            <div data-ftab-panel="pliki" class="hidden">
                @include('admin.partials.attachments', [
                    'attachments' => $page->attachments,
                    'storeRoute' => route('admin.podstrony.pliki.store', $page),
                    'brandSections' => $page->isBrandAssets() ? ($page->brand_sections ?? []) : [],
                ])
            </div>
            <div data-ftab-panel="galeria" class="hidden">
                @include('admin.partials.page-images', ['page' => $page])
            </div>
            <div data-ftab-panel="etr" class="hidden">
                @php $etrModel = $page->etr; @endphp
                <div class="mb-4 rounded-xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800">
                    <strong>Wersja ETR (łatwa do czytania)</strong> — uproszczony tekst dla osób z trudnościami w czytaniu.
                    Gdy włączysz ETR, na tej stronie pojawi się przycisk pozwalający przełączyć się na prostszą wersję.
                    <a href="{{ route('etr.about') }}" target="_blank" class="ml-1 underline hover:text-sky-900">Co to jest ETR? →</a>
                </div>

                <form method="POST" action="{{ route('admin.etr.update', ['type' => 'podstrona', 'id' => $page->id]) }}"
                    class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    @csrf @method('PUT')

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_enabled" value="1"
                            {{ old('is_enabled', $etrModel?->is_enabled ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        <span class="font-bold text-ink">Włącz wersję ETR dla tej strony</span>
                    </label>

                    <div>
                        <label for="etr_title" class="mb-1 block text-sm font-bold">Tytuł (uproszczony) <span class="font-normal text-muted">— opcjonalny, zastąpi oryginalny tytuł w widoku ETR</span></label>
                        <input type="text" id="etr_title" name="etr_title"
                            value="{{ old('etr_title', $etrModel?->etr_title) }}"
                            placeholder="{{ $page->title }}"
                            class="w-full rounded border-gray-300 focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label for="etr_summary" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">— 1–3 zdania prostym językiem</span></label>
                        <textarea id="etr_summary" name="etr_summary" rows="3"
                            placeholder="Krótkie, proste wyjaśnienie o czym jest ta strona."
                            class="w-full rounded border-gray-300 focus:border-sky-500 focus:ring-sky-500">{{ old('etr_summary', $etrModel?->etr_summary) }}</textarea>
                    </div>

                    <div>
                        <label for="etr_content" class="mb-1 block text-sm font-bold">Treść ETR <span class="font-normal text-muted">— prosty tekst, jedno zdanie w akapicie</span></label>
                        <textarea id="etr_content" name="etr_content" rows="12"
                            placeholder="Pisz prostymi słowami.&#10;&#10;Krótkie zdania.&#10;&#10;Jedna myśl — jeden akapit."
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-sky-500 focus:ring-sky-500">{{ old('etr_content', $etrModel?->etr_content) }}</textarea>
                        <p class="mt-1 text-xs text-muted">Puste wiersze tworzą nowe akapity. Nie używaj formatowania HTML.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700">Zapisz ETR</button>
                        @if ($etrModel)
                            <form method="POST" action="{{ route('admin.etr.destroy', ['type' => 'podstrona', 'id' => $page->id]) }}"
                                onsubmit="return confirm('Usuń całą wersję ETR tej strony?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-bold text-red-600 hover:text-red-700">Usuń ETR</button>
                            </form>
                        @endif
                    </div>
                </form>
            </div>
        @endif
    </div>{{-- /page-form-tabs --}}

    <script>
        (function () {
            // --- Type-specific fields (event / schedule) -------------------
            const typeSelect = document.querySelector('[data-page-type-select]');
            const eventFields = document.querySelector('[data-event-fields]');
            const scheduleFields = document.querySelector('[data-schedule-fields]');
            const aboutFields = document.querySelector('[data-about-fields]');
            const faqFields = document.querySelector('[data-faq-fields]');
            const bipMoveFields = document.querySelector('[data-bipmove-fields]');
            const internalFields = document.querySelector('[data-internal-fields]');
            const hubFields = document.querySelector('[data-hub-fields]');
            const legacyFields = document.querySelector('[data-legacy-fields]');
            const trainingFields = document.querySelector('[data-training-fields]');
            const contentField = document.querySelector('[data-content-field]');
            const brandFields = document.querySelector('[data-brand-fields]');
            const aboutPersonFields = document.querySelector('[data-about-person-fields]');
            const cooperationFields = document.querySelector('[data-cooperation-fields]');
            if (typeSelect) {
                typeSelect.addEventListener('change', function () {
                    if (eventFields) eventFields.classList.toggle('hidden', typeSelect.value !== 'event');
                    if (scheduleFields) scheduleFields.classList.toggle('hidden', typeSelect.value !== 'schedule');
                    if (aboutFields) aboutFields.classList.toggle('hidden', typeSelect.value !== 'about');
                    if (faqFields) faqFields.classList.toggle('hidden', typeSelect.value !== 'faq');
                    if (bipMoveFields) bipMoveFields.classList.toggle('hidden', typeSelect.value !== 'bip_move');
                    if (internalFields) internalFields.classList.toggle('hidden', ! ['internal', 'internal_hub'].includes(typeSelect.value));
                    if (hubFields) hubFields.classList.toggle('hidden', ! ['internal_hub', 'links_hub'].includes(typeSelect.value));
                    if (legacyFields) legacyFields.classList.toggle('hidden', typeSelect.value !== 'legacy');
                    if (trainingFields) trainingFields.classList.toggle('hidden', typeSelect.value !== 'training_institution');
                    if (brandFields) brandFields.classList.toggle('hidden', typeSelect.value !== 'brand_assets');
                    if (aboutPersonFields) aboutPersonFields.classList.toggle('hidden', typeSelect.value !== 'about_person');
                    if (cooperationFields) cooperationFields.classList.toggle('hidden', typeSelect.value !== 'wspolpraca');
                    if (contentField) contentField.classList.toggle('hidden', ['about', 'bip_move', 'wspolpraca'].includes(typeSelect.value));
                    document.querySelectorAll('[data-wspolpraca-tab]').forEach(function (btn) {
                        const isWspolpraca = typeSelect.value === 'wspolpraca';
                        btn.classList.toggle('hidden', isWspolpraca);
                        if (isWspolpraca && btn.getAttribute('aria-selected') === 'true') {
                            document.querySelector('[data-ftab-btn="tresc"]')?.click();
                        }
                    });
                    // Galeria „O organizacji" jest osobna — ukryj generyczny przełącznik dla tego typu.
                    document.querySelectorAll('[data-gallery-toggle]').forEach(function (el) {
                        el.classList.toggle('hidden', typeSelect.value === 'about');
                    });
                });
            }

            // --- Generic repeaters (about-page sections) ------------------
            document.querySelectorAll('[data-repeater]').forEach(function (rep) {
                const rows = rep.querySelector('[data-repeater-rows]');
                const template = rep.querySelector('[data-repeater-template]');
                const addBtn = rep.querySelector('[data-repeater-add]');
                if (!rows || !template) return;
                let nextIndex = rows.querySelectorAll('[data-repeater-row]').length;

                if (addBtn) {
                    addBtn.addEventListener('click', function () {
                        const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = html.trim();
                        rows.appendChild(wrapper.firstElementChild);
                    });
                }
                // Przelicz indeksy nazw pól wg kolejności w DOM, aby zapisana
                // kolejność odpowiadała tej na ekranie (po przenoszeniu wierszy).
                const reindex = function () {
                    rows.querySelectorAll('[data-repeater-row]').forEach(function (row, n) {
                        row.querySelectorAll('[name]').forEach(function (el) {
                            el.name = el.name.replace(/^([^\[]+)\[[^\]]*\]/, '$1[' + n + ']');
                        });
                    });
                };

                rep.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-repeater-remove]');
                    if (remove) {
                        const row = remove.closest('[data-repeater-row]');
                        if (row) row.remove();
                        return;
                    }
                    const move = e.target.closest('[data-repeater-move]');
                    if (move) {
                        const row = move.closest('[data-repeater-row]');
                        if (!row) return;
                        if (move.dataset.repeaterMove === 'up' && row.previousElementSibling) {
                            rows.insertBefore(row, row.previousElementSibling);
                        } else if (move.dataset.repeaterMove === 'down' && row.nextElementSibling) {
                            rows.insertBefore(row.nextElementSibling, row);
                        }
                        reindex();
                    }
                });
            });

            // --- "Dodaj / zarządzaj plikami" → przełącz na zakładkę Pliki ---
            const gotoFiles = document.querySelector('[data-goto-files]');
            if (gotoFiles) {
                gotoFiles.addEventListener('click', function () {
                    const btn = document.querySelector('[data-ftab-btn="pliki"]');
                    if (btn) btn.click();
                });
            }

            // --- About section order (move up / down) ---------------------
            const orderList = document.getElementById('about-section-order-list');
            if (orderList) {
                const renumber = function () {
                    [...orderList.children].forEach(function (li, index) {
                        const input = li.querySelector('input[type="hidden"]');
                        if (input) input.value = index;
                    });
                };
                orderList.addEventListener('click', function (event) {
                    const button = event.target.closest('[data-move]');
                    if (!button) return;
                    const li = button.closest('li');
                    const sibling = button.dataset.move === 'up' ? li.previousElementSibling : li.nextElementSibling;
                    if (sibling) {
                        if (button.dataset.move === 'up') {
                            orderList.insertBefore(li, sibling);
                        } else {
                            orderList.insertBefore(sibling, li);
                        }
                        renumber();
                    }
                });
                renumber();
            }

            // --- Repeatable schedule rows ---------------------------------
            if (scheduleFields) {
                const rows = scheduleFields.querySelector('[data-schedule-rows]');
                const template = scheduleFields.querySelector('[data-schedule-template]');
                const addBtn = scheduleFields.querySelector('[data-schedule-add]');
                let nextIndex = rows ? rows.querySelectorAll('[data-schedule-row]').length : 0;

                const addRow = function () {
                    const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    rows.appendChild(wrapper.firstElementChild);
                };

                if (addBtn) addBtn.addEventListener('click', addRow);

                scheduleFields.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-schedule-remove]');
                    if (remove) {
                        const row = remove.closest('[data-schedule-row]');
                        if (row) row.remove();
                    }
                });

                if (rows && rows.querySelectorAll('[data-schedule-row]').length === 0) {
                    addRow();
                }

                // Highlight the "not published yet" box while its toggle is on.
                const pendingToggle = scheduleFields.querySelector('[data-schedule-pending-toggle]');
                const pendingBox = scheduleFields.querySelector('[data-schedule-pending-box]');
                if (pendingToggle && pendingBox) {
                    const syncPending = function () {
                        pendingBox.classList.toggle('border-amber-300', pendingToggle.checked);
                        pendingBox.classList.toggle('bg-amber-50', pendingToggle.checked);
                        pendingBox.classList.toggle('border-gray-200', !pendingToggle.checked);
                        pendingBox.classList.toggle('bg-gray-50', !pendingToggle.checked);
                    };
                    pendingToggle.addEventListener('change', syncPending);
                    syncPending();
                }
            }

            // --- Availability: reveal each message box only when active ----
            const disableToggle = document.querySelector('[data-disable-toggle]');
            const disableMessage = document.querySelector('[data-disable-message]');
            if (disableToggle && disableMessage) {
                const syncDisable = function () { disableMessage.classList.toggle('hidden', !disableToggle.checked); };
                disableToggle.addEventListener('change', syncDisable);
                syncDisable();
            }

            const wipMessage = document.querySelector('[data-wip-message]');
            const wipRadios = document.querySelectorAll('[data-wip-modes] input[name="wip_mode"]');
            if (wipMessage && wipRadios.length) {
                const syncWip = function () {
                    const selected = document.querySelector('[data-wip-modes] input[name="wip_mode"]:checked');
                    wipMessage.classList.toggle('hidden', !selected || selected.value === '');
                };
                wipRadios.forEach(function (r) { r.addEventListener('change', syncWip); });
                syncWip();
            }

            // --- Show the project-display choice only when a project is set -
            const projectSelect = document.querySelector('[data-project-select]');
            const displayWrap = document.querySelector('[data-project-display-wrap]');
            if (projectSelect && displayWrap) {
                projectSelect.addEventListener('change', function () {
                    displayWrap.classList.toggle('hidden', projectSelect.value === '');
                });
            }

            // --- Form tabs -------------------------------------------------
            const wrap = document.querySelector('[data-page-form-tabs]');
            if (!wrap) return;
            const buttons = Array.prototype.slice.call(wrap.querySelectorAll('[data-ftab-btn]'));
            const panels = Array.prototype.slice.call(wrap.querySelectorAll('[data-ftab-panel]'));

            function activate(key) {
                buttons.forEach(function (b) {
                    const active = b.dataset.ftabBtn === key;
                    b.classList.toggle('border-brand', active);
                    b.classList.toggle('text-brand', active);
                    b.classList.toggle('border-transparent', !active);
                    b.classList.toggle('text-muted', !active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    p.classList.toggle('hidden', p.dataset.ftabPanel !== key);
                });
                // A rich editor initialised inside a hidden panel can render
                // blank; toggling it re-lays it out once its tab is visible.
                const shown = panels.find(function (p) { return p.dataset.ftabPanel === key; });
                if (shown && window.tinymce) {
                    shown.querySelectorAll('textarea').forEach(function (ta) {
                        const ed = window.tinymce.get(ta.id);
                        if (ed) { ed.hide(); ed.show(); }
                    });
                }
                window.dispatchEvent(new Event('resize'));
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () { activate(btn.dataset.ftabBtn); });
            });

            // Flag tabs that contain validation errors and jump to the first one.
            let firstErrorKey = null;
            panels.forEach(function (p) {
                if (!p.querySelector('.text-red-600')) return;
                const key = p.dataset.ftabPanel;
                const btn = buttons.find(function (b) { return b.dataset.ftabBtn === key; });
                if (btn && !btn.querySelector('[data-ftab-error]')) {
                    const dot = document.createElement('span');
                    dot.setAttribute('data-ftab-error', '');
                    dot.className = 'ml-1.5 inline-block h-2 w-2 rounded-full bg-red-500 align-middle';
                    btn.appendChild(dot);
                }
                if (!firstErrorKey) firstErrorKey = key;
            });
            if (firstErrorKey) activate(firstErrorKey);
        })();
    </script>

    {{-- Lekki WYSIWYG (TinyMCE) na odpowiedziach FAQ — także dla nowo dodanych wierszy. --}}
    <script>
        (function () {
            const container = document.querySelector('[data-faq-fields]');
            if (!container) return;

            function initOne(ta) {
                if (ta.dataset.mceReady) return;
                ta.dataset.mceReady = '1';
                window.tinymce.init({
                    target: ta,
                    license_key: 'gpl',
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    convert_urls: false,
                    height: 200,
                    plugins: 'link lists autolink',
                    toolbar: 'bold italic | bullist numlist | link unlink | removeformat',
                    link_default_target: '_blank',
                    link_assume_external_targets: true,
                    setup: function (ed) { ed.on('change keyup', function () { ed.save(); }); },
                });
            }

            function initAll() {
                container.querySelectorAll('textarea[data-faq-answer]').forEach(initOne);
            }

            function withTiny(cb) {
                if (window.tinymce) return cb();
                window.__tinymceInitQueue = window.__tinymceInitQueue || [];
                window.__tinymceInitQueue.push(cb);
                if (!window.__tinymceLoading) {
                    window.__tinymceLoading = true;
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
                    s.referrerPolicy = 'origin';
                    s.onload = function () { (window.__tinymceInitQueue || []).forEach(function (f) { f(); }); window.__tinymceInitQueue = []; };
                    document.head.appendChild(s);
                }
            }

            withTiny(function () {
                initAll();
                const rows = container.querySelector('[data-repeater-rows]');
                if (rows && window.MutationObserver) {
                    new MutationObserver(initAll).observe(rows, { childList: true });
                }
            });

            // Wstawianie linku do podstrony w serwisie w aktywne pole odpowiedzi.
            const pageLink = container.querySelector('[data-faq-page-link]');
            if (pageLink) {
                pageLink.addEventListener('change', function () {
                    var url = this.value;
                    var title = this.selectedOptions[0] ? this.selectedOptions[0].dataset.title : url;
                    this.selectedIndex = 0;
                    if (!url || !window.tinymce) return;

                    var ed = window.tinymce.activeEditor;
                    if (ed && ed.targetElm && ed.targetElm.matches && ed.targetElm.matches('textarea[data-faq-answer]')) {
                        ed.insertContent('<a href="' + url + '">' + title + '</a>');
                        ed.save();
                    } else {
                        alert('Najpierw kliknij w treść odpowiedzi, w której chcesz wstawić link.');
                    }
                });
            }

            // Przepisz treść edytorów do textarea tuż przed wysłaniem formularza.
            const form = container.closest('form');
            if (form) form.addEventListener('submit', function () { if (window.tinymce) window.tinymce.triggerSave(); });
        })();

    </script>
@endsection
