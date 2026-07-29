@extends('admin.layout')

@section('title', $page->exists ? 'Edytuj landing page' : 'Nowy landing page')

@php
    $inp = 'w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand';
    $speakers = old('speakers', $page->speakers ?? []);
    $benefits = old('benefits', $page->benefits ?? []);
    $agenda = old('agenda', $page->agenda ?? []);
    $order = old('section_order', $page->orderedSections());
    // Opcje listy edytujemy jako tekst po przecinku; klucz nadaje kontroler.
    $formFields = collect(old('form_fields', $page->form_fields ?? []))->map(fn ($f) => [
        'label' => $f['label'] ?? '',
        'type' => $f['type'] ?? 'text',
        'required' => ! empty($f['required']),
        'options' => is_array($f['options'] ?? null) ? implode(', ', $f['options']) : ($f['options'] ?? ''),
    ])->values()->all();
@endphp

@section('content')
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $page->exists ? route('admin.lp.update', $page) : route('admin.lp.store') }}"
        x-data="{
            speakers: {{ Js::from($speakers) }},
            benefits: {{ Js::from($benefits) }},
            agenda: {{ Js::from($agenda) }},
            fields: {{ Js::from($formFields) }},
            order: {{ Js::from($order) }},
            labels: { speakers: 'Prelegenci', benefits: 'Korzyści', agenda: 'Agenda' },
            move(i, d) { const n = i + d; if (n < 0 || n >= this.order.length) return; [this.order[i], this.order[n]] = [this.order[n], this.order[i]]; },
            picker: { open: false, loaded: false, images: [], targetId: null },
            openPicker(id) {
                this.picker.targetId = id;
                this.picker.open = true;
                if (this.picker.loaded) return;
                fetch({{ Js::from(route('admin.multimedia.images')) }}, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json()).then(imgs => { this.picker.images = imgs; this.picker.loaded = true; }).catch(() => {});
            },
            choosePicker(url) {
                const el = document.getElementById(this.picker.targetId);
                if (el) { el.value = url; el.dispatchEvent(new Event('input', { bubbles: true })); }
                this.picker.open = false;
            }
        }"
        @keydown.escape.window="picker.open = false"
        class="max-w-3xl space-y-6">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        {{-- Podstawy --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Podstawy</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="title" class="mb-1 block text-sm font-bold">Tytuł (wewnętrzny)</label>
                    <input id="title" name="title" value="{{ old('title', $page->title) }}" required class="{{ $inp }}">
                </div>
                <div>
                    <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres /lp/…)</label>
                    <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" required pattern="[a-z0-9\-]+" placeholder="webinar-dostepnosc" class="{{ $inp }} font-mono">
                </div>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
                <span class="text-sm font-bold">Opublikowany</span>
            </label>
        </fieldset>

        {{-- Hero --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Sekcja Hero</legend>
            <div>
                <label for="hero_eyebrow" class="mb-1 block text-sm font-bold">Nadtytuł <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input id="hero_eyebrow" name="hero_eyebrow" value="{{ old('hero_eyebrow', $page->hero_eyebrow) }}" placeholder="Bezpłatny webinar" class="{{ $inp }}">
            </div>
            <div>
                <label for="hero_title" class="mb-1 block text-sm font-bold">Nagłówek H1</label>
                <input id="hero_title" name="hero_title" value="{{ old('hero_title', $page->hero_title) }}" required class="{{ $inp }}">
            </div>
            <div>
                <label for="hero_lead" class="mb-1 block text-sm font-bold">Lead (opis)</label>
                <textarea id="hero_lead" name="hero_lead" rows="3" class="{{ $inp }}">{{ old('hero_lead', $page->hero_lead) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="event_start" class="mb-1 block text-sm font-bold">Termin</label>
                    <input id="event_start" name="event_start" type="datetime-local" value="{{ old('event_start', $page->event_start?->format('Y-m-d\TH:i')) }}" class="{{ $inp }}">
                </div>
                <div>
                    <label for="event_location" class="mb-1 block text-sm font-bold">Miejsce</label>
                    <input id="event_location" name="event_location" value="{{ old('event_location', $page->event_location) }}" placeholder="Online / Zoom" class="{{ $inp }}">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="hero_cta_label" class="mb-1 block text-sm font-bold">Tekst przycisku</label>
                    <input id="hero_cta_label" name="hero_cta_label" value="{{ old('hero_cta_label', $page->hero_cta_label ?? 'Zarejestruj się') }}" class="{{ $inp }}">
                </div>
                <div class="sm:col-span-2">
                    <label for="hero_cta_url" class="mb-1 block text-sm font-bold">Własny link przycisku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input id="hero_cta_url" name="hero_cta_url" value="{{ old('hero_cta_url', $page->hero_cta_url) }}" placeholder="https://… (zewnętrzny system zapisu)" class="{{ $inp }}">
                    <p class="mt-1 text-xs text-muted">Puste = przycisk przewija do formularza na stronie. Podany adres = przycisk prowadzi do zewnętrznego systemu zapisu.</p>
                </div>
                <div>
                    <label for="hero_image_url" class="mb-1 block text-sm font-bold">Obrazek hero <span class="font-normal text-muted">(z biblioteki mediów)</span></label>
                    <div class="flex items-center gap-3">
                        <input id="hero_image_url" x-ref="heroImg" name="hero_image_url" value="{{ old('hero_image_url', $page->hero_image_url) }}" placeholder="/storage/…" class="{{ $inp }}">
                        <button type="button" @click="openPicker('hero_image_url')" class="flex-none rounded border border-gray-300 px-3 py-2 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-photo-film" aria-hidden="true"></i> Wybierz
                        </button>
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- Kolejność sekcji --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Kolejność sekcji</legend>
            <ul class="space-y-2">
                <template x-for="(key, i) in order" :key="key">
                    <li class="flex items-center justify-between rounded border border-gray-200 px-3 py-2">
                        <span class="font-medium text-ink" x-text="labels[key]"></span>
                        <span class="flex gap-1">
                            <input type="hidden" name="section_order[]" :value="key">
                            <button type="button" @click="move(i, -1)" :disabled="i === 0" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-arrow-up" aria-hidden="true"></i><span class="sr-only">W górę</span>
                            </button>
                            <button type="button" @click="move(i, 1)" :disabled="i === order.length - 1" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-arrow-down" aria-hidden="true"></i><span class="sr-only">W dół</span>
                            </button>
                        </span>
                    </li>
                </template>
            </ul>
        </fieldset>

        {{-- Prelegenci --}}
        <fieldset class="space-y-3 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Prelegenci</legend>
            <template x-for="(s, i) in speakers" :key="i">
                <div class="grid gap-2 rounded border border-gray-200 p-3 sm:grid-cols-2">
                    <input :name="`speakers[${i}][name]`" x-model="s.name" placeholder="Imię i nazwisko" class="{{ $inp }}">
                    <input :name="`speakers[${i}][role]`" x-model="s.role" placeholder="Rola / tytuł" class="{{ $inp }}">
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input :id="`speaker_photo_${i}`" :name="`speakers[${i}][photo]`" x-model="s.photo" placeholder="URL zdjęcia (z biblioteki)" class="{{ $inp }}">
                        <button type="button" @click="openPicker(`speaker_photo_${i}`)" class="flex-none rounded border border-gray-300 px-3 py-2 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-photo-film" aria-hidden="true"></i> Wybierz
                        </button>
                    </div>
                    <textarea :name="`speakers[${i}][bio]`" x-model="s.bio" rows="2" placeholder="Bio" class="{{ $inp }} sm:col-span-2"></textarea>
                    <button type="button" @click="speakers.splice(i, 1)" class="justify-self-start rounded text-sm text-red-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 sm:col-span-2">Usuń prelegenta</button>
                </div>
            </template>
            <button type="button" @click="speakers.push({ name: '', role: '', bio: '', photo: '' })" class="rounded border border-dashed border-gray-300 px-3 py-2 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj prelegenta
            </button>
        </fieldset>

        {{-- Korzyści --}}
        <fieldset class="space-y-3 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Korzyści (bloki z ikonami)</legend>
            <template x-for="(b, i) in benefits" :key="i">
                <div class="grid gap-2 rounded border border-gray-200 p-3 sm:grid-cols-2">
                    <input :name="`benefits[${i}][icon]`" x-model="b.icon" placeholder="np. fa-solid fa-check" class="{{ $inp }} font-mono">
                    <input :name="`benefits[${i}][title]`" x-model="b.title" placeholder="Tytuł" class="{{ $inp }}">
                    <textarea :name="`benefits[${i}][text]`" x-model="b.text" rows="2" placeholder="Opis" class="{{ $inp }} sm:col-span-2"></textarea>
                    <button type="button" @click="benefits.splice(i, 1)" class="justify-self-start rounded text-sm text-red-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 sm:col-span-2">Usuń korzyść</button>
                </div>
            </template>
            <button type="button" @click="benefits.push({ icon: 'fa-solid fa-star', title: '', text: '' })" class="rounded border border-dashed border-gray-300 px-3 py-2 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj korzyść
            </button>
        </fieldset>

        {{-- Agenda --}}
        <fieldset class="space-y-3 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Agenda</legend>
            <template x-for="(a, i) in agenda" :key="i">
                <div class="grid gap-2 rounded border border-gray-200 p-3 sm:grid-cols-3">
                    <input :name="`agenda[${i}][time]`" x-model="a.time" placeholder="np. 10:00" class="{{ $inp }}">
                    <input :name="`agenda[${i}][title]`" x-model="a.title" placeholder="Punkt agendy" class="{{ $inp }} sm:col-span-2">
                    <textarea :name="`agenda[${i}][desc]`" x-model="a.desc" rows="2" placeholder="Opis" class="{{ $inp }} sm:col-span-3"></textarea>
                    <button type="button" @click="agenda.splice(i, 1)" class="justify-self-start rounded text-sm text-red-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 sm:col-span-3">Usuń punkt</button>
                </div>
            </template>
            <button type="button" @click="agenda.push({ time: '', title: '', desc: '' })" class="rounded border border-dashed border-gray-300 px-3 py-2 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj punkt agendy
            </button>
        </fieldset>

        {{-- Formularz --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Sekcja rejestracji</legend>
            <div>
                <label for="form_title" class="mb-1 block text-sm font-bold">Nagłówek formularza</label>
                <input id="form_title" name="form_title" value="{{ old('form_title', $page->form_title ?? 'Zapisz się na webinar') }}" class="{{ $inp }}">
            </div>
            <div>
                <label for="form_intro" class="mb-1 block text-sm font-bold">Tekst wstępny</label>
                <textarea id="form_intro" name="form_intro" rows="2" class="{{ $inp }}">{{ old('form_intro', $page->form_intro) }}</textarea>
            </div>
            <div>
                <label for="form_consent_label" class="mb-1 block text-sm font-bold">Treść zgody (RODO)</label>
                <textarea id="form_consent_label" name="form_consent_label" rows="2" class="{{ $inp }}">{{ old('form_consent_label', $page->form_consent_label) }}</textarea>
            </div>
            <div>
                <label for="form_success" class="mb-1 block text-sm font-bold">Komunikat po zapisaniu</label>
                <textarea id="form_success" name="form_success" rows="2" class="{{ $inp }}">{{ old('form_success', $page->form_success) }}</textarea>
            </div>

            {{-- Dodatkowe pola formularza (przekazywane do zewnętrznego API) --}}
            <div class="border-t border-gray-100 pt-4">
                <p class="mb-1 text-sm font-bold text-ink">Dodatkowe pola formularza</p>
                <p class="mb-3 text-xs text-muted">Trafiają do zgłoszenia (kolumna <code>extra</code>) i są przekazywane do systemu zapisu przez API.</p>
                <div class="space-y-3">
                    <template x-for="(f, i) in fields" :key="i">
                        <div class="grid gap-2 rounded border border-gray-200 p-3 sm:grid-cols-2">
                            <input :name="`form_fields[${i}][label]`" x-model="f.label" placeholder="Etykieta pola (np. Organizacja)" class="{{ $inp }}">
                            <select :name="`form_fields[${i}][type]`" x-model="f.type" class="{{ $inp }}">
                                @foreach (\App\Models\LandingPage::FIELD_TYPES as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                            <input :name="`form_fields[${i}][options]`" x-model="f.options" x-show="f.type === 'select'" x-cloak placeholder="Opcje po przecinku: Tak, Nie, Może" class="{{ $inp }} sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm text-muted">
                                <input type="checkbox" :name="`form_fields[${i}][required]`" x-model="f.required" value="1" class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
                                Pole wymagane
                            </label>
                            <button type="button" @click="fields.splice(i, 1)" class="justify-self-end rounded text-sm text-red-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">Usuń pole</button>
                        </div>
                    </template>
                    <button type="button" @click="fields.push({ label: '', type: 'text', required: false, options: '' })" class="rounded border border-dashed border-gray-300 px-3 py-2 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pole
                    </button>
                </div>
            </div>
        </fieldset>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.lp.index') }}" class="rounded text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</a>
        </div>

        {{-- Modal wyboru obrazu z biblioteki mediów --}}
        <div x-show="picker.open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4" style="display: none">
            <div class="fixed inset-0 bg-ink/60" @click="picker.open = false" aria-hidden="true"></div>
            <div role="dialog" aria-modal="true" aria-label="Wybierz obraz z biblioteki mediów" class="relative z-10 my-6 w-full max-w-3xl rounded-lg border border-gray-200 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h2 class="font-bold text-ink">Biblioteka mediów</h2>
                    <button type="button" @click="picker.open = false" class="rounded p-2 text-muted hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="sr-only">Zamknij</span>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-4">
                    <p x-show="!picker.loaded" class="py-8 text-center text-muted">Ładowanie…</p>
                    <p x-show="picker.loaded && picker.images.length === 0" x-cloak class="py-8 text-center text-muted">Brak obrazów w bibliotece.</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        <template x-for="img in picker.images" :key="img.id">
                            <button type="button" @click="choosePicker(img.url)" :title="img.file_name"
                                class="group overflow-hidden rounded-lg border border-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <img :src="img.url" :alt="img.alt" loading="lazy" class="aspect-square w-full object-cover transition group-hover:opacity-80">
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
