@extends('admin.layout')

@section('title', $event->exists ? 'Edytuj wydarzenie' : 'Nowe wydarzenie')

@php
    $action = $event->exists ? route('admin.wydarzenia.update', $event) : route('admin.wydarzenia.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-3xl space-y-6"
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
                <div x-show="mode !== 'zdalnie'" x-cloak>
                    <label for="location" class="mb-1 block text-sm font-bold">Miejsce</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $event->location) }}" maxlength="255" placeholder="np. Nowy Sącz, ul. Barbackiego 28"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Wymagane, chyba że wydarzenie jest w pełni zdalne.</p>
                </div>
            </div>
            <div x-show="mode !== 'stacjonarnie'" x-cloak>
                <label for="online_url" class="mb-1 block text-sm font-bold">Link do spotkania online <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="url" id="online_url" name="online_url" value="{{ old('online_url', $event->online_url) }}" maxlength="500" placeholder="https://..."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
        </fieldset>

        {{-- Zapisy --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">Zapisy i kontakt</legend>
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
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.wydarzenia.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
        </div>
    </form>
@endsection
