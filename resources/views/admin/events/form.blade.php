@extends('admin.layout')

@section('title', $event->exists ? 'Edytuj wydarzenie' : 'Nowe wydarzenie')

@php
    $action = $event->exists ? route('admin.wydarzenia.update', $event) : route('admin.wydarzenia.store');
@endphp

@section('content')
    @include('admin.partials.template-panel', [
        'templateType'   => 'event',
        'templateFields' => ['type', 'mode', 'location', 'online_url', 'audience', 'registration_cta_label', 'contact_email', 'price_info'],
    ])

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-4 max-w-3xl space-y-6"
        x-data="{ mode: @js(old('mode', $event->mode ?: 'stacjonarnie')) }">
        @csrf
        @if ($event->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold">Popraw poniższe pola:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Podstawy --}}
        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <div>
                <label for="title" class="mb-1 block text-sm font-bold">Tytuł wydarzenia <span aria-hidden="true" class="text-red-600">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $event->title) }}" required maxlength="160" placeholder="np. Szkolenie z dostępności cyfrowej (WCAG) dla NGO"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="lead" class="mb-1 block text-sm font-bold">Krótki opis (1–2 zdania) <span aria-hidden="true" class="text-red-600">*</span></label>
                <textarea id="lead" name="lead" rows="2" required maxlength="300" placeholder="Dla kogo jest to wydarzenie i co uczestnik z niego wyniesie."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('lead', $event->lead) }}</textarea>
            </div>
            <div>
                <label for="description" class="mb-1 block text-sm font-bold">Pełny opis <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <textarea id="description" name="description" rows="6" maxlength="10000" placeholder="Program, agenda, dla kogo, co zapewniamy…"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('description', $event->description) }}</textarea>
                <p class="mt-1 text-xs text-muted">Zwykły tekst — akapity i przejścia do nowej linii zostaną zachowane.</p>
            </div>
        </div>

        {{-- Korzyści --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6"
            x-data="{ enabled: @js(old('show_benefits', $event->show_benefits ?? false)) }">
            <legend class="px-2 text-sm font-bold text-brand">Korzyści — co zyskasz? <span class="font-normal text-muted">(opcjonalnie)</span></legend>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="show_benefits" value="1"
                    x-model="enabled"
                    {{ old('show_benefits', $event->show_benefits) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand"
                    aria-expanded="enabled" aria-controls="benefits-fields">
                <span class="text-sm font-bold">Pokaż sekcję „Co zyskasz?" na stronie</span>
            </label>

            <div id="benefits-fields" x-show="enabled" x-cloak>
                <label for="benefits" class="mb-1 block text-sm font-bold">Lista korzyści</label>
                <textarea id="benefits" name="benefits" rows="6" maxlength="5000"
                    placeholder="Każda linia to osobna korzyść, np.:&#10;Poznasz standardy WCAG 2.2&#10;Otrzymasz gotowe szablony do pracy&#10;Uzyskasz certyfikat ukończenia"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('benefits', $event->benefits) }}</textarea>
                <p class="mt-1 text-xs text-muted">Każda linia tekstu wyświetli się jako osobny punkt na stronie.</p>
            </div>
        </fieldset>

        {{-- Rodzaj i termin --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">Rodzaj i termin</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="type" class="mb-1 block text-sm font-bold">Rodzaj <span aria-hidden="true" class="text-red-600">*</span></label>
                    <select id="type" name="type" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach (\App\Models\Event::TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('type', $event->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="price_info" class="mb-1 block text-sm font-bold">Koszt <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="price_info" name="price_info" value="{{ old('price_info', $event->price_info) }}" maxlength="100" placeholder="np. Bezpłatne / 50 zł"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="starts_at" class="mb-1 block text-sm font-bold">Rozpoczęcie <span aria-hidden="true" class="text-red-600">*</span></label>
                    <input type="datetime-local" id="starts_at" name="starts_at" required
                        value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label for="ends_at" class="mb-1 block text-sm font-bold">Zakończenie <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="datetime-local" id="ends_at" name="ends_at"
                        value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
            </div>
        </fieldset>

        {{-- Powtarzanie --}}
        @if (! $event->isInstance())
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6"
            x-data="{
                enabled: @js(old('recurrence_type', $event->recurrence_type) !== null),
            }">
            <legend class="px-2 text-sm font-bold text-brand">Powtarzanie <span class="font-normal text-muted">(opcjonalnie)</span></legend>

            @if ($event->isSeries())
                <p class="rounded bg-blue-50 px-3 py-2 text-xs text-blue-800">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    Seria &middot; {{ $event->instances()->count() }} {{ $event->instances()->count() === 1 ? 'instancja' : ($event->instances()->count() < 5 ? 'instancje' : 'instancji') }}.
                    Zapisanie formularza <strong>wygeneruje instancje od nowa</strong>.
                </p>
            @endif

            <label class="flex items-center gap-2">
                <input type="checkbox" x-model="enabled"
                    class="rounded border-gray-300 text-brand focus:ring-brand"
                    aria-expanded="enabled" aria-controls="recurrence-fields">
                <span class="text-sm font-bold">Ustaw powtarzanie</span>
            </label>

            <div id="recurrence-fields" x-show="enabled" x-cloak class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="recurrence_type" class="mb-1 block text-sm font-bold">Częstotliwość</label>
                    <select id="recurrence_type" :name="enabled ? 'recurrence_type' : null"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach (\App\Models\Event::RECURRENCE_TYPES as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('recurrence_type', $event->recurrence_type) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="recurrence_ends_at" class="mb-1 block text-sm font-bold">Zakończ serię dnia <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="date" id="recurrence_ends_at" :name="enabled ? 'recurrence_ends_at' : null"
                        value="{{ old('recurrence_ends_at', $event->recurrence_ends_at?->format('Y-m-d')) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Gdy puste: maks. 12 mies. (co tydzień/2 tyg.) lub 3 lata (co mies./rok). Maks. 52 instancje.</p>
                </div>
            </div>
        </fieldset>
        @else
            {{-- Informacja dla instancji serii --}}
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <i class="fa-solid fa-link" aria-hidden="true"></i>
                To jest <strong>instancja serii</strong> — edytujesz tylko to wystąpienie.
                <a href="{{ route('admin.wydarzenia.edit', $event->parent) }}" class="ml-1 font-bold underline hover:text-blue-900">
                    Przejdź do serii ({{ $event->parent->title }})
                </a>
            </div>
        @endif

        {{-- Miejsce / tryb --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">Miejsce</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="mode" class="mb-1 block text-sm font-bold">Tryb <span aria-hidden="true" class="text-red-600">*</span></label>
                    <select id="mode" name="mode" x-model="mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach (\App\Models\Event::MODES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="mode !== 'zdalnie'" x-cloak
                    x-data="{
                        lat: @js(old('latitude', $event->latitude)),
                        lng: @js(old('longitude', $event->longitude)),
                        map: null, marker: null,
                        initMap() {
                            if (this.map) return;
                            this.$nextTick(() => {
                                const startLat = this.lat || 50.0647;
                                const startLng = this.lng || 19.9450;
                                this.map = L.map(this.$refs.mapEl).setView([startLat, startLng], this.lat ? 14 : 6);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '© <a href=\'https://www.openstreetmap.org/copyright\'>OpenStreetMap</a>'
                                }).addTo(this.map);
                                if (this.lat && this.lng) {
                                    this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);
                                    this.marker.on('dragend', e => {
                                        const p = e.target.getLatLng();
                                        this.lat = p.lat.toFixed(7);
                                        this.lng = p.lng.toFixed(7);
                                    });
                                }
                                this.map.on('click', e => {
                                    this.lat = e.latlng.lat.toFixed(7);
                                    this.lng = e.latlng.lng.toFixed(7);
                                    if (this.marker) { this.marker.setLatLng(e.latlng); }
                                    else {
                                        this.marker = L.marker(e.latlng, { draggable: true }).addTo(this.map);
                                        this.marker.on('dragend', ev => {
                                            const p = ev.target.getLatLng();
                                            this.lat = p.lat.toFixed(7);
                                            this.lng = p.lng.toFixed(7);
                                        });
                                    }
                                });
                            });
                        },
                        clearPin() { this.lat = null; this.lng = null; if (this.marker) { this.marker.remove(); this.marker = null; } }
                    }">
                    <label for="location" class="mb-1 block text-sm font-bold">Miejsce</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $event->location) }}" maxlength="255" placeholder="np. Nowy Sącz, ul. Barbackiego 28"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Wymagane, chyba że wydarzenie jest w pełni zdalne.</p>

                    {{-- Mapa —pin lokalizacji --}}
                    <div class="mt-3">
                        <div class="mb-1 flex items-center justify-between">
                            <p class="text-xs font-bold text-muted">
                                Pin na mapie <span class="font-normal">(opcjonalnie — kliknij mapę, aby ustawić)</span>
                            </p>
                            <button type="button" x-show="lat" @click="clearPin()"
                                class="text-xs text-muted hover:text-red-600" aria-label="Usuń pin">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Usuń pin
                            </button>
                        </div>

                        <div x-ref="mapEl" class="h-56 w-full overflow-hidden rounded-lg border border-gray-200"
                            x-init="$watch('$el.isConnected', v => v && initMap())"
                            @click="if (!map) initMap()"></div>

                        <p x-show="lat" class="mt-1 text-xs text-muted">
                            <span x-text="lat"></span>, <span x-text="lng"></span>
                        </p>
                        <p x-show="!lat" class="mt-1 text-xs text-muted">Brak pinu — kliknij mapę, aby dodać.</p>
                    </div>

                    <input type="hidden" name="latitude" :value="lat">
                    <input type="hidden" name="longitude" :value="lng">
                </div>
            </div>
            <div x-show="mode !== 'stacjonarnie'" x-cloak>
                <label for="online_url" class="mb-1 block text-sm font-bold">Link do spotkania online <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="url" id="online_url" name="online_url" value="{{ old('online_url', $event->online_url) }}" maxlength="500" placeholder="https://..."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
        </fieldset>

        {{-- Zapisy --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6"
            x-data="{ hide: @js(old('hide_registration', $event->hide_registration ?? false)) }">
            <legend class="px-2 text-sm font-bold text-brand">Zapisy i kontakt</legend>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="hide_registration" value="1"
                    x-model="hide"
                    {{ old('hide_registration', $event->hide_registration) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-sm font-bold">Nie pokazuj przycisku zapisów na stronie</span>
            </label>

            <div x-show="!hide" x-cloak class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="registration_url" class="mb-1 block text-sm font-bold">Link do zapisów</label>
                        <input type="url" id="registration_url" name="registration_url" value="{{ old('registration_url', $event->registration_url) }}" maxlength="500" placeholder="https://..."
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Zewnętrzny formularz (Typeform, Google Forms). Gdy pusty, przycisk zapisu użyje e-maila poniżej.</p>
                    </div>
                    <div>
                        <label for="registration_cta_label" class="mb-1 block text-sm font-bold">Napis na przycisku</label>
                        <input type="text" id="registration_cta_label" name="registration_cta_label" value="{{ old('registration_cta_label', $event->registration_cta_label ?: 'Zapisz się') }}" maxlength="60"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                </div>
                <div>
                    <label for="contact_email" class="mb-1 block text-sm font-bold">E-mail kontaktowy <span class="font-normal text-muted">(zapasowo, gdy brak linku)</span></label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $event->contact_email) }}" maxlength="255" placeholder="np. szkolenia@feer.org.pl"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
            </div>
        </fieldset>

        {{-- Osoba prowadząca --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6"
            x-data="{ preview: @js($event->facilitatorPhotoUrl()) }">
            <legend class="px-2 text-sm font-bold text-brand">Osoba prowadząca <span class="font-normal text-muted">(opcjonalnie)</span></legend>

            <div class="flex flex-wrap items-start gap-5">
                <div class="flex flex-col items-center gap-2">
                    <span class="h-24 w-24 overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200">
                        <template x-if="preview">
                            <img :src="preview" alt="Podgląd zdjęcia osoby prowadzącej" class="h-full w-full object-cover">
                        </template>
                        <template x-if="! preview">
                            <span class="flex h-full w-full items-center justify-center text-gray-300">
                                <i class="fa-solid fa-user text-3xl" aria-hidden="true"></i>
                            </span>
                        </template>
                    </span>
                    @if ($event->facilitatorPhotoUrl())
                        <label class="flex items-center gap-1.5 text-xs text-muted">
                            <input type="checkbox" name="remove_facilitator_photo" value="1" class="rounded border-gray-300 text-brand focus:ring-brand"
                                @change="if ($event.target.checked) preview = null">
                            Usuń zdjęcie
                        </label>
                    @endif
                </div>

                <div class="min-w-[16rem] flex-1 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="facilitator_name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
                            <input type="text" id="facilitator_name" name="facilitator_name" value="{{ old('facilitator_name', $event->facilitator_name) }}" maxlength="160" placeholder="np. Anna Kowalska"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label for="facilitator_role" class="mb-1 block text-sm font-bold">Rola / tytuł</label>
                            <input type="text" id="facilitator_role" name="facilitator_role" value="{{ old('facilitator_role', $event->facilitator_role) }}" maxlength="160" placeholder="np. trenerka dostępności cyfrowej"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div>
                        <label for="facilitator_photo" class="mb-1 block text-sm font-bold">Zdjęcie osoby prowadzącej</label>
                        <input type="file" id="facilitator_photo" name="facilitator_photo" accept="image/*"
                            @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)"
                            class="w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                        <p class="mt-1 text-xs text-muted">Kwadratowe zdjęcie wygląda najlepiej (kadrowane do koła). Maks. 4 MB.</p>
                    </div>
                </div>
            </div>

            <div>
                <label for="facilitator_bio" class="mb-1 block text-sm font-bold">Bio</label>
                <textarea id="facilitator_bio" name="facilitator_bio" rows="4" maxlength="2000" placeholder="Kilka zdań o doświadczeniu i tym, co uczestnicy zyskają dzięki osobie prowadzącej."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('facilitator_bio', $event->facilitator_bio) }}</textarea>
            </div>

            <div class="space-y-3">
                <p class="text-sm font-bold">Linki i social media <span class="font-normal text-muted">(opcjonalnie)</span></p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex items-center gap-2">
                        <span class="w-5 flex-none text-center text-muted"><i class="fa-solid fa-globe" aria-hidden="true"></i></span>
                        <div class="flex-1">
                            <label for="facilitator_website" class="mb-0.5 block text-xs font-bold">Strona WWW</label>
                            <input type="url" id="facilitator_website" name="facilitator_website"
                                value="{{ old('facilitator_website', $event->facilitator_website) }}"
                                maxlength="500" placeholder="https://..."
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-5 flex-none text-center" style="color:#0a66c2"><i class="fa-brands fa-linkedin" aria-hidden="true"></i></span>
                        <div class="flex-1">
                            <label for="facilitator_linkedin" class="mb-0.5 block text-xs font-bold">LinkedIn</label>
                            <input type="url" id="facilitator_linkedin" name="facilitator_linkedin"
                                value="{{ old('facilitator_linkedin', $event->facilitator_linkedin) }}"
                                maxlength="500" placeholder="https://linkedin.com/in/..."
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-5 flex-none text-center" style="color:#1877f2"><i class="fa-brands fa-facebook" aria-hidden="true"></i></span>
                        <div class="flex-1">
                            <label for="facilitator_facebook" class="mb-0.5 block text-xs font-bold">Facebook</label>
                            <input type="url" id="facilitator_facebook" name="facilitator_facebook"
                                value="{{ old('facilitator_facebook', $event->facilitator_facebook) }}"
                                maxlength="500" placeholder="https://facebook.com/..."
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-5 flex-none text-center" style="color:#e1306c"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                        <div class="flex-1">
                            <label for="facilitator_instagram" class="mb-0.5 block text-xs font-bold">Instagram</label>
                            <input type="url" id="facilitator_instagram" name="facilitator_instagram"
                                value="{{ old('facilitator_instagram', $event->facilitator_instagram) }}"
                                maxlength="500" placeholder="https://instagram.com/..."
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-5 flex-none text-center text-ink"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></span>
                        <div class="flex-1">
                            <label for="facilitator_twitter" class="mb-0.5 block text-xs font-bold">X / Twitter</label>
                            <input type="url" id="facilitator_twitter" name="facilitator_twitter"
                                value="{{ old('facilitator_twitter', $event->facilitator_twitter) }}"
                                maxlength="500" placeholder="https://x.com/..."
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- FAQ szkolenia --}}
        @php
            $faqsInit = old('faqs', $event->exists
                ? $event->faqs->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])->values()->all()
                : []);
            $globalFaqOptions = ($allFaqs ?? collect())->map(fn ($f) => [
                'question' => $f->question,
                'answer' => $f->answer,
                'category' => $f->category,
                'is_published' => (bool) $f->is_published,
            ])->values()->all();
        @endphp
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6"
            x-data="{
                faqs: {{ \Illuminate\Support\Js::from(array_values($faqsInit)) }},
                globalFaqs: {{ \Illuminate\Support\Js::from($globalFaqOptions) }},
                pickerOpen: false,
                isAdded(f) { return this.faqs.some((x) => (x.question || '').trim() === f.question.trim()); },
                addGlobal(f) { if (!this.isAdded(f)) this.faqs.push({ question: f.question, answer: f.answer }); },
            }">
            <legend class="px-2 text-sm font-bold text-brand">FAQ — najczęstsze pytania <span class="font-normal text-muted">(opcjonalnie)</span></legend>
            <p class="text-xs text-muted">Pytania i odpowiedzi pojawią się jako rozwijana lista na stronie wydarzenia.</p>

            <template x-for="(faq, i) in faqs" :key="i">
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wide text-muted" x-text="'Pytanie ' + (i + 1)"></span>
                        <button type="button" @click="faqs.splice(i, 1)" class="text-xs font-bold text-red-600 hover:underline">Usuń</button>
                    </div>
                    <input type="text" :name="`faqs[${i}][question]`" x-model="faq.question" maxlength="255" placeholder="np. Czy otrzymam zaświadczenie?"
                        class="mb-2 w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <textarea :name="`faqs[${i}][answer]`" x-model="faq.answer" rows="3" maxlength="2000" placeholder="Odpowiedź na pytanie."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></textarea>
                </div>
            </template>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="faqs.push({ question: '', answer: '' })"
                    class="inline-flex items-center gap-2 rounded border border-dashed border-gray-300 px-4 py-2 text-sm font-bold text-brand hover:border-brand hover:bg-brand-light">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie
                </button>

                <template x-if="globalFaqs.length">
                    <button type="button" @click="pickerOpen = !pickerOpen" :aria-expanded="pickerOpen" aria-controls="global-faq-picker"
                        class="inline-flex items-center gap-2 rounded border border-dashed border-gray-300 px-4 py-2 text-sm font-bold text-brand hover:border-brand hover:bg-brand-light">
                        <i class="fa-solid fa-list-check" aria-hidden="true"></i> Dodaj z globalnego FAQ
                    </button>
                </template>
            </div>

            <template x-if="globalFaqs.length">
                <div id="global-faq-picker" x-show="pickerOpen" x-cloak @keydown.escape="pickerOpen = false"
                    class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <p class="text-xs text-muted">Wybierz pytania z <a href="{{ route('admin.faq.index') }}" class="text-brand underline">globalnego FAQ</a> — treść zostanie skopiowana do listy powyżej i możesz ją dowolnie zmienić dla tego wydarzenia.</p>
                        <button type="button" @click="pickerOpen = false" class="flex-none text-xs font-bold text-muted hover:text-ink">Zamknij</button>
                    </div>
                    <ul class="max-h-56 space-y-1 overflow-auto rounded border border-gray-200 bg-white p-2">
                        <template x-for="(f, gi) in globalFaqs" :key="gi">
                            <li class="flex items-start justify-between gap-3 rounded px-2 py-1.5 hover:bg-gray-50">
                                <span class="text-sm">
                                    <span class="font-medium text-ink" x-text="f.question"></span>
                                    <span class="ml-1 text-xs text-muted" x-show="f.category" x-text="'(' + f.category + ')'"></span>
                                    <span class="ml-1 text-xs font-bold text-amber-600" x-show="!f.is_published">szkic</span>
                                </span>
                                <button type="button" @click="addGlobal(f)" :disabled="isAdded(f)"
                                    class="flex-none rounded px-2 py-1 text-xs font-bold text-brand hover:bg-brand-light disabled:cursor-default disabled:text-muted disabled:hover:bg-transparent">
                                    <span x-show="!isAdded(f)"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj</span>
                                    <span x-show="isAdded(f)"><i class="fa-solid fa-check" aria-hidden="true"></i> Dodane</span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </fieldset>

        {{-- Publikacja --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="mb-4">
                <h2 class="text-base font-bold text-ink">Publikacja</h2>
                <p class="mt-1 text-xs text-muted">Widoczność, schemat kolorów i kolejność. Wydarzenie znika z listy po upływie terminu.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="audience" class="mb-1 block text-sm font-bold">Schemat kolorów</label>
                    <select id="audience" name="audience" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach ($siteSettings->audienceOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('audience', $event->audience ?: 'brand') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                    <input type="number" id="order" name="order" value="{{ old('order', $event->order ?? 0) }}" min="0"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Przy tym samym terminie — mniejsza liczba wyżej.</p>
                </div>
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $event->is_published) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Opublikowane (widoczne na stronie)</span>
                </label>
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $event->is_featured) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-amber-500 focus:ring-amber-500">
                    <span class="text-sm font-bold"><i class="fa-solid fa-star text-amber-500" aria-hidden="true"></i> Wyróżnione (złota ramka na stronie)</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.wydarzenia.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
        </div>
    </form>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLs=" crossorigin=""></script>
@endpush
