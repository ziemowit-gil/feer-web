@extends('admin.layout')

@section('title', $page->exists ? 'Edytuj stronę' : 'Nowa strona')

@section('content')
    @php
        $currentType = old('type', $page->type ?? 'standard');
        $scheduleItems = old('schedule_items', $page->schedule_items ?? []);
        $scheduleItems = is_array($scheduleItems) ? array_values($scheduleItems) : [];
        $hasProject = (bool) old('project_id', $page->project_id);

        $aboutStats = array_values((array) old('about_stats', $page->about_stats ?? []));
        $aboutTimeline = array_values((array) old('about_timeline', $page->about_timeline ?? []));
        $aboutValues = array_values((array) old('about_values', $page->about_values ?? []));
        $aboutTeam = array_values((array) old('about_team', $page->about_team ?? []));
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
            @if ($page->exists)
                <button type="button" data-ftab-btn="pliki" role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Pliki do pobrania
                    @if ($page->attachments->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs">{{ $page->attachments->count() }}</span>
                    @endif
                </button>
                <button type="button" data-ftab-btn="galeria" role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-images" aria-hidden="true"></i> Galeria
                    @if ($page->images->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs">{{ $page->images->count() }}</span>
                    @endif
                </button>
            @endif
        </div>

        <form method="POST" action="{{ $page->exists ? route('admin.podstrony.update', $page) : route('admin.podstrony.store') }}" class="space-y-6">
            @csrf
            @if ($page->exists) @method('PUT') @endif

            {{-- ============================ TREŚĆ ============================ --}}
            <div data-ftab-panel="tresc" class="space-y-6">
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

                    <div>
                        <label class="mb-1 block text-sm font-bold">Treść</label>
                        @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $page->content)])
                        @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ============================ TYP I UKŁAD ============================ --}}
            <div data-ftab-panel="typ" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div class="sm:w-1/2">
                        <label for="type" class="mb-1 block text-sm font-bold">Typ strony</label>
                        <select id="type" name="type" data-page-type-select class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @foreach (\App\Models\Page::TYPES as $value => $label)
                                <option value="{{ $value }}" {{ $currentType === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">„Wydarzenie” dodaje pola o terminie, miejscu i rejestracji. „Harmonogram zajęć / spotkań” dodaje tabelę terminów oraz miejsce na informację o zmianie. Każdy typ ma inny układ na stronie.</p>
                        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                            <p class="mt-1 text-xs text-muted">Jeśli podasz link, na stronie wydarzenia pojawi się przycisk „Zarejestruj się”.</p>
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
                                    <span class="block text-sm font-bold text-ink">Wyświetlaj komunikat „Harmonogram jeszcze nie został opublikowany”</span>
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
                            <p class="mb-3 text-xs text-muted">Dodaj kolejne terminy (data, godzina, miejsce). Zaznacz „Termin zmieniony”, aby wyróżnić wpis, który uległ zmianie.</p>

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
                                <i class="fa-solid fa-plus"></i> Dodaj termin
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
                            <p class="mt-1 text-xs text-muted">Tekst wstępu jako zwykłe pole (bez edytora). Wyświetli się obok zdjęć u góry strony. Pole „Treść” (edytor) możesz zostawić puste.</p>
                            @error('about_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                            </div>
                        </details>

                        <details class="rounded-lg border border-gray-200">
                            <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Dokumenty i sprawozdania</summary>
                            <div class="space-y-4 border-t border-gray-100 px-4 py-4">
                        <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                            <p class="text-sm font-bold text-ink"><i class="fa-solid fa-folder-open text-muted" aria-hidden="true"></i> Sekcja „Dokumenty i sprawozdania”</p>

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
                                    <p class="mt-1 text-xs font-medium text-amber-700">Zapisz stronę, aby móc wgrać pliki (pojawi się zakładka „Pliki do pobrania”).</p>
                                @endif
                            </div>
                        </div>

                        @if ($page->exists)
                            <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                                Zdjęcia dodaj w zakładce „Galeria”. Pierwsze 2–3 zdjęcia pojawią się obok wstępu; pozostałe w sekcji galerii poniżej.
                            </p>
                        @else
                            <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                                Zdjęcia (2–3 obok wstępu + galeria) dodasz po zapisaniu strony — pojawi się zakładka „Galeria”.
                            </p>
                        @endif
                            </div>
                        </details>

                        <details class="rounded-lg border border-gray-200">
                            <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Statystyki (liczby)</summary>
                            <div class="border-t border-gray-100 px-4 py-4">
                        <div data-repeater>
                            <p class="mb-3 text-xs text-muted">np. „12 lat” + „doświadczenia”. Puste wiersze są pomijane.</p>
                            <div data-repeater-rows class="space-y-2">
                                @foreach ($aboutStats as $i => $row)
                                    <div data-repeater-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                                        <input type="text" name="about_stats[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="Wartość, np. 500+" aria-label="Wartość statystyki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        <input type="text" name="about_stats[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Etykieta, np. przeszkolonych osób" aria-label="Etykieta statystyki {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        <button type="button" data-repeater-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń statystykę"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus"></i> Dodaj statystykę</button>
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
                            <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus"></i> Dodaj wartość</button>
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
                                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wpis osi czasu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus"></i> Dodaj wpis</button>
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
                                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń wpis osi czasu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                            </div>
                        </details>

                        <details class="rounded-lg border border-gray-200">
                            <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-bold text-ink hover:bg-gray-50">Zespół</summary>
                            <div class="border-t border-gray-100 px-4 py-4">
                        <div data-repeater>
                            <p class="mb-3 text-xs text-muted">Każda osoba: imię i nazwisko, „Co robi w FEER", „Trochę o mnie" oraz opcjonalnie linki do social media. Zdjęcie — wklej adres URL obrazu z Multimediów; puste = inicjały.</p>
                            <div data-repeater-rows class="space-y-3">
                                @foreach ($aboutTeam as $i => $row)
                                    <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <input type="text" name="about_team[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Imię i nazwisko" aria-label="Imię członka zespołu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                            <input type="text" name="about_team[{{ $i }}][role]" value="{{ $row['role'] ?? '' }}" placeholder="Co robi w FEER" aria-label="Co robi w FEER — członek zespołu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        </div>
                                        <input type="text" name="about_team[{{ $i }}][photo]" value="{{ $row['photo'] ?? '' }}" placeholder="URL zdjęcia (opcjonalnie)" aria-label="Zdjęcie członka zespołu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                        <textarea name="about_team[{{ $i }}][bio]" rows="2" placeholder="Trochę o mnie" aria-label="Trochę o mnie — członek zespołu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $row['bio'] ?? '' }}</textarea>
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <input type="url" name="about_team[{{ $i }}][facebook]" value="{{ $row['facebook'] ?? '' }}" placeholder="Facebook (URL)" aria-label="Facebook — członek zespołu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            <input type="url" name="about_team[{{ $i }}][instagram]" value="{{ $row['instagram'] ?? '' }}" placeholder="Instagram (URL)" aria-label="Instagram — członek zespołu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                            <input type="url" name="about_team[{{ $i }}][linkedin]" value="{{ $row['linkedin'] ?? '' }}" placeholder="LinkedIn (URL)" aria-label="LinkedIn — członek zespołu {{ $i + 1 }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                        </div>
                                        <div class="text-right">
                                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń członka zespołu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus"></i> Dodaj osobę</button>
                            <template data-repeater-template>
                                <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <input type="text" name="about_team[__INDEX__][name]" placeholder="Imię i nazwisko" aria-label="Imię członka zespołu" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        <input type="text" name="about_team[__INDEX__][role]" placeholder="Co robi w FEER" aria-label="Co robi w FEER" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <input type="text" name="about_team[__INDEX__][photo]" placeholder="URL zdjęcia (opcjonalnie)" aria-label="Zdjęcie członka zespołu" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    <textarea name="about_team[__INDEX__][bio]" rows="2" placeholder="Trochę o mnie" aria-label="Trochę o mnie" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></textarea>
                                    <div class="grid gap-2 sm:grid-cols-3">
                                        <input type="url" name="about_team[__INDEX__][facebook]" placeholder="Facebook (URL)" aria-label="Facebook" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                        <input type="url" name="about_team[__INDEX__][instagram]" placeholder="Instagram (URL)" aria-label="Instagram" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                        <input type="url" name="about_team[__INDEX__][linkedin]" placeholder="LinkedIn (URL)" aria-label="LinkedIn" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    </div>
                                    <div class="text-right">
                                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń członka zespołu"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
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
                            <p class="mb-3 text-xs text-muted">Zaznacz partnerów, których loga pokazać w sekcji „Nasi partnerzy — wspierają nas”. Partnerów dodajesz w module <a href="{{ route('admin.partnerzy.index') }}" class="text-brand underline">Partnerzy</a>.</p>
                            @php $selectedPartners = array_map('intval', (array) old('about_partner_ids', $page->about_partner_ids ?? [])); @endphp
                            @if ($partnerOptions->isEmpty())
                                <p class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-muted">Brak partnerów — dodaj ich najpierw w module „Partnerzy”.</p>
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
                    </div>

                    {{-- FAQ — pytania i odpowiedzi --}}
                    <div data-faq-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'faq' ? '' : 'hidden' }}">
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">FAQ — pytania i odpowiedzi</p>

                        <div>
                            <label for="faq_intro" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="faq_intro" name="faq_intro" rows="3" placeholder="Krótkie wprowadzenie nad listą pytań."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('faq_intro', $page->faq_intro) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Wyświetli się nad listą pytań. Pole „Treść” (edytor) możesz zostawić puste.</p>
                            @error('faq_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div data-repeater>
                            <p class="mb-1 text-sm font-bold">Pytania i odpowiedzi</p>
                            <p class="mb-3 text-xs text-muted">Każda para tworzy zwijany element (akordeon) na stronie. Puste wiersze są pomijane; kolejność odpowiada kolejności na liście.</p>
                            <div data-repeater-rows class="space-y-3">
                                @foreach ($faqItems as $i => $row)
                                    <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <input type="text" name="faq_items[{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="Pytanie" aria-label="Pytanie {{ $i + 1 }}"
                                            class="w-full rounded border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand">
                                        <textarea name="faq_items[{{ $i }}][answer]" rows="3" placeholder="Odpowiedź" aria-label="Odpowiedź {{ $i + 1 }}"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $row['answer'] ?? '' }}</textarea>
                                        <div class="text-right">
                                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-2 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń pytanie {{ $i + 1 }}"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-repeater-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus"></i> Dodaj pytanie</button>
                            <template data-repeater-template>
                                <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <input type="text" name="faq_items[__INDEX__][question]" placeholder="Pytanie" aria-label="Pytanie"
                                        class="w-full rounded border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand">
                                    <textarea name="faq_items[__INDEX__][answer]" rows="3" placeholder="Odpowiedź" aria-label="Odpowiedź"
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
                            <p class="mt-1 text-xs text-muted">Puste = przycisk poprowadzi do ogólnego adresu BIP z „Ustawienia → Media i BIP”. W polu „Treść” (edytor) możesz dodać własny opis nad komunikatem.</p>
                            @error('bip_move_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bip_move_note" class="mb-1 block text-sm font-bold">Dodatkowa informacja <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="bip_move_note" name="bip_move_note" rows="3" placeholder="np. W BIP znajdziesz sprawozdania, statut i dokumenty formalne fundacji."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('bip_move_note', $page->bip_move_note) }}</textarea>
                            @error('bip_move_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ==================== PUBLIKACJA I POWIĄZANIA ==================== --}}
            <div data-ftab-panel="ustawienia" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="lg:col-span-2">
                            <label for="parent_id" class="mb-1 block text-sm font-bold">Nadrzędna strona</label>
                            <select id="parent_id" name="parent_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="">— brak (strona główna) —</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}" {{ (int) old('parent_id', $page->parent_id) === $option->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Strony z tym samym rodzicem tworzą wspólne, osobne podmenu widoczne na stronie.</p>
                            @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-2">
                            <label for="project_id" class="mb-1 block text-sm font-bold">Powiąż z projektem</label>
                            <select id="project_id" name="project_id" data-project-select class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="">— brak —</option>
                                @foreach ($projectOptions as $option)
                                    <option value="{{ $option->id }}" {{ (int) old('project_id', $page->project_id) === $option->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Jeśli wybierzesz projekt, ta strona stanie się stroną tego projektu — zachowa własny adres, a dodatkowo pojawi się na stronie projektu.</p>
                            @error('project_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-4 {{ $hasProject ? '' : 'hidden' }}" data-project-display-wrap>
                            <label for="project_display" class="mb-1 block text-sm font-bold">Jak pokazać na stronie projektu</label>
                            <select id="project_display" name="project_display" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand sm:w-2/3">
                                @foreach (\App\Models\Page::PROJECT_DISPLAYS as $value => $label)
                                    <option value="{{ $value }}" {{ old('project_display', $page->project_display ?? 'link') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">Strona zawsze ma własny adres. Tu wybierasz, jak jej treść pojawia się na stronie projektu: jako sam odnośnik, jako zakładka albo jako sekcja w treści.</p>
                            @error('project_display') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                            <input type="number" id="order" name="order" min="0" value="{{ old('order', $page->order) }}"
                                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>

                        <div class="flex flex-col justify-center gap-2 lg:col-span-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">Opublikowana</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="show_in_menu" value="1" {{ old('show_in_menu', $page->show_in_menu ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">Dodaj do menu</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_system" value="1" {{ old('is_system', $page->is_system ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">Strona systemowa</span>
                            </label>

                            @if (auth()->user()->isAdmin())
                                <label class="flex items-center gap-2">
                                    <input type="hidden" name="is_locked" value="0">
                                    <input type="checkbox" name="is_locked" value="1" {{ old('is_locked', $page->is_locked ?? false) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-brand focus:ring-brand">
                                    <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-lock text-muted" aria-hidden="true"></i> Zablokuj treść do edycji przez innych</span>
                                </label>
                            @endif

                            <label class="flex items-center gap-2 {{ $currentType === 'about' ? 'hidden' : '' }}" data-gallery-toggle>
                                <input type="hidden" name="show_gallery" value="0">
                                <input type="checkbox" name="show_gallery" value="1" {{ old('show_gallery', $page->show_gallery ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">Pokaż galerię zdjęć</span>
                            </label>
                        </div>
                        <p class="text-xs text-muted {{ $currentType === 'about' ? 'hidden' : '' }}" data-gallery-toggle>„Pokaż galerię zdjęć” wyświetla na stronie zdjęcia dodane w zakładce „Galeria”. Typ „O organizacji” ma własną, osobną galerię (sterowaną kolejnością sekcji), więc ten przełącznik go nie dotyczy.</p>
                    </div>
                    <p class="text-xs text-muted">„Dodaj do menu” dotyczy tylko stron głównych (bez rodzica i bez projektu) — widoczne w głównej nawigacji strony.</p>
                    <p class="text-xs text-muted">„Strona systemowa” to wymagana strona serwisu (np. deklaracja dostępności, polityka prywatności, mapa strony) — oznaczonej w ten sposób strony nie można usunąć.</p>
                    @if (auth()->user()->isAdmin())
                        <p class="text-xs text-muted">„Zablokuj treść do edycji przez innych” — po zaznaczeniu tylko administrator może edytować, klonować lub usunąć tę stronę. Flagę ustawia i zdejmuje wyłącznie administrator.</p>
                    @endif
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

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
                <a href="{{ route('admin.podstrony.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
            </div>
        </form>

        @if ($page->exists)
            <div data-ftab-panel="pliki" class="hidden">
                @include('admin.partials.attachments', [
                    'attachments' => $page->attachments,
                    'storeRoute' => route('admin.podstrony.pliki.store', $page),
                ])
            </div>
            <div data-ftab-panel="galeria" class="hidden">
                @include('admin.partials.page-images', ['page' => $page])
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
            if (typeSelect) {
                typeSelect.addEventListener('change', function () {
                    if (eventFields) eventFields.classList.toggle('hidden', typeSelect.value !== 'event');
                    if (scheduleFields) scheduleFields.classList.toggle('hidden', typeSelect.value !== 'schedule');
                    if (aboutFields) aboutFields.classList.toggle('hidden', typeSelect.value !== 'about');
                    if (faqFields) faqFields.classList.toggle('hidden', typeSelect.value !== 'faq');
                    if (bipMoveFields) bipMoveFields.classList.toggle('hidden', typeSelect.value !== 'bip_move');
                    // Galeria „O organizacji” jest osobna — ukryj generyczny przełącznik dla tego typu.
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
                rep.addEventListener('click', function (e) {
                    const remove = e.target.closest('[data-repeater-remove]');
                    if (remove) {
                        const row = remove.closest('[data-repeater-row]');
                        if (row) row.remove();
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
@endsection
