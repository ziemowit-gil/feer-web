@extends('admin.layout')

@section('title', $offer->exists ? 'Edytuj ogłoszenie o pracę' : 'Nowe ogłoszenie o pracę')

@php
    $oldArrayText = function (string $key, $model) {
        $val = old($key);
        if ($val !== null) return is_array($val) ? implode("\n", $val) : $val;
        return implode("\n", (array) ($model ?? []));
    };
    $dutiesText       = $oldArrayText('duties', $offer->duties);
    $requirementsText = $oldArrayText('requirements', $offer->requirements);
    $benefitsText     = $oldArrayText('benefits', $offer->benefits);
    $action = $offer->exists ? route('admin.praca.update', $offer) : route('admin.praca.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-3xl space-y-6"
        x-data="{ jobType: @js(old('job_type', $offer->job_type ?? 'pelny_etat')) }">
        @csrf
        @if ($offer->exists) @method('PUT') @endif

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
            <h2 class="text-base font-bold text-ink">Podstawowe informacje</h2>
            <div>
                <label for="title" class="mb-1 block text-sm font-bold">Tytuł stanowiska <span aria-hidden="true" class="text-red-600">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $offer->title) }}" required placeholder="np. Specjalistka/Specjalista ds. dostępności cyfrowej"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="lead" class="mb-1 block text-sm font-bold">Krótki opis stanowiska (1–2 zdania) <span aria-hidden="true" class="text-red-600">*</span></label>
                <textarea id="lead" name="lead" rows="2" required maxlength="300" placeholder="Jedno–dwa zdania o tym, czym się zajmujesz i jaki jest sens tej roli."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('lead', $offer->lead) }}</textarea>
            </div>
        </div>

        {{-- Warunki zatrudnienia --}}
        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-base font-bold text-ink">Warunki zatrudnienia</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="job_type" class="mb-1 block text-sm font-bold">Rodzaj umowy <span aria-hidden="true" class="text-red-600">*</span></label>
                    <select id="job_type" name="job_type" x-model="jobType" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach (\App\Models\JobOffer::TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('job_type', $offer->job_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="mode" class="mb-1 block text-sm font-bold">Tryb pracy <span aria-hidden="true" class="text-red-600">*</span></label>
                    <select id="mode" name="mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach (\App\Models\JobOffer::MODES as $value => $label)
                            <option value="{{ $value }}" {{ old('mode', $offer->mode) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="location" class="mb-1 block text-sm font-bold">Lokalizacja</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $offer->location) }}" placeholder="np. Nowy Sącz / cała Polska"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label for="salary_range" class="mb-1 block text-sm font-bold">Wynagrodzenie</label>
                    <input type="text" id="salary_range" name="salary_range" value="{{ old('salary_range', $offer->salary_range) }}" placeholder="np. 3500–5000 PLN brutto/mies."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Zostaw puste, jeśli nie podajesz widełek.</p>
                </div>
            </div>

            {{-- Pola specyficzne dla UOP (pełny etat / pół etatu) --}}
            <div x-show="['pelny_etat','pol_etatu'].includes(jobType)" x-cloak>
                <p class="mb-3 text-xs font-bold text-muted uppercase tracking-wide">Szczegóły umowy o pracę</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="contract_duration_type" class="mb-1 block text-sm font-bold">Czas trwania umowy</label>
                        <select id="contract_duration_type" name="contract_duration_type" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <option value="">— nie podano —</option>
                            @foreach (\App\Models\JobOffer::CONTRACT_DURATION_TYPES as $value => $label)
                                <option value="{{ $value }}" {{ old('contract_duration_type', $offer->contract_duration_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="contract_duration" class="mb-1 block text-sm font-bold">Okres trwania</label>
                        <input type="text" id="contract_duration" name="contract_duration" value="{{ old('contract_duration', $offer->contract_duration) }}" placeholder="np. 6 miesięcy, 1 rok"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Tylko przy czasie określonym.</p>
                    </div>
                    <div>
                        <label for="start_date" class="mb-1 block text-sm font-bold">Termin rozpoczęcia</label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $offer->start_date?->format('Y-m-d')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                </div>
            </div>

            {{-- Pola specyficzne dla zlecenia/dzieła --}}
            <div x-show="jobType === 'uod'" x-cloak>
                <p class="mb-3 text-xs font-bold text-muted uppercase tracking-wide">Szczegóły umowy zlecenia / o dzieło</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="hourly_rate" class="mb-1 block text-sm font-bold">Stawka godzinowa</label>
                        <input type="text" id="hourly_rate" name="hourly_rate" value="{{ old('hourly_rate', $offer->hourly_rate) }}" placeholder="np. 35–45 PLN/h"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label for="start_date_uod" class="mb-1 block text-sm font-bold">Planowany termin rozpoczęcia</label>
                        <input type="date" id="start_date_uod" name="start_date" value="{{ old('start_date', $offer->start_date?->format('Y-m-d')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                </div>
            </div>
        </div>

        {{-- Obowiązki --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">Zakres obowiązków</legend>
            <label for="duties" class="mb-1 block text-xs text-muted">Konkretne zadania — jeden obowiązek w wierszu.</label>
            <textarea id="duties" name="duties" rows="6" required placeholder="Prowadzenie audytów dostępności&#10;Przygotowywanie raportów WCAG&#10;Szkolenia dla partnerów organizacji"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ $dutiesText }}</textarea>
            @error('duties') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        {{-- Wymagania --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">Wymagania</legend>
            <label for="requirements" class="mb-1 block text-xs text-muted">Jedno wymaganie w wierszu. Oddziel obowiązkowe od mile widzianych.</label>
            <textarea id="requirements" name="requirements" rows="5" required placeholder="Znajomość WCAG 2.1 i 2.2&#10;Doświadczenie w testowaniu dostępności&#10;Mile widziane: doświadczenie z czytnikami ekranu"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ $requirementsText }}</textarea>
            @error('requirements') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        {{-- Benefity --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">Co oferujemy <span class="font-normal text-muted">(opcjonalnie)</span></legend>
            <label for="benefits" class="mb-1 block text-xs text-muted">Jeden benefit w wierszu — elastyczność, sprzęt, szkolenia, misja itd.</label>
            <textarea id="benefits" name="benefits" rows="4" placeholder="Praca na rzecz dostępności cyfrowej&#10;Elastyczny czas pracy&#10;Możliwość zdalnej pracy&#10;Rozwój w środowisku NGO"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ $benefitsText }}</textarea>
        </fieldset>

        {{-- Kontakt i aplikowanie --}}
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-base font-bold text-ink">Kontakt i aplikowanie</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="application_url" class="mb-1 block text-sm font-bold">Link do formularza zgłoszeniowego</label>
                    <input type="url" id="application_url" name="application_url" value="{{ old('application_url', $offer->application_url) }}" placeholder="https://..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label for="application_cta_label" class="mb-1 block text-sm font-bold">Napis na przycisku</label>
                    <input type="text" id="application_cta_label" name="application_cta_label" value="{{ old('application_cta_label', $offer->application_cta_label ?: 'Aplikuj') }}" maxlength="60"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label for="contact_name" class="mb-1 block text-sm font-bold">Osoba kontaktowa <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $offer->contact_name) }}" placeholder="np. Anna Kowalska, HR"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label for="contact_email" class="mb-1 block text-sm font-bold">E-mail kontaktowy <span class="font-normal text-muted">(zapasowo)</span></label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $offer->contact_email) }}" placeholder="np. praca@feer.org.pl"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
            </div>
        </div>

        {{-- Publikacja --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-base font-bold text-ink">Publikacja</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="audience" class="mb-1 block text-sm font-bold">Schemat kolorów</label>
                    <select id="audience" name="audience" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach ($siteSettings->audienceOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('audience', $offer->audience ?: 'brand') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="closes_at" class="mb-1 block text-sm font-bold">Termin aplikacji</label>
                    <input type="date" id="closes_at" name="closes_at" value="{{ old('closes_at', $offer->closes_at?->format('Y-m-d')) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Po tej dacie ogłoszenie znika.</p>
                </div>
                <div>
                    <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                    <input type="number" id="order" name="order" value="{{ old('order', $offer->order ?? 0) }}" min="0"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <label class="flex items-center gap-2 sm:col-span-3">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $offer->is_published) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Opublikowane (widoczne na stronie)</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.praca.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
        </div>
    </form>
@endsection
