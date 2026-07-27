@extends('admin.layout')

@section('title', $ad->exists ? 'Edytuj ogłoszenie o wolontariacie' : 'Nowe ogłoszenie o wolontariacie')

@php
    $oldArrayText = function (string $key, $model) {
        $val = old($key);
        if ($val !== null) {
            return is_array($val) ? implode("\n", $val) : $val;
        }
        return implode("\n", (array) ($model ?? []));
    };
    $tasksText = $oldArrayText('q_tasks', $ad->q_tasks);
    $benefitsText = $oldArrayText('q_benefits', $ad->q_benefits);
    $action = $ad->exists ? route('admin.wolontariat.update', $ad) : route('admin.wolontariat.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-3xl space-y-6"
        x-data="{
            f: {
                q1: @js(old('q_beneficiaries', $ad->q_beneficiaries) ?? ''),
                q2: @js($tasksText),
                q3: @js(old('q_schedule', $ad->q_schedule) ?? ''),
                q4: @js(old('q_time_commitment', $ad->q_time_commitment) ?? ''),
                q5: @js($benefitsText),
                q6: @js(old('q_how_to_apply', $ad->q_how_to_apply) ?? ''),
            },
            get done() { return Object.values(this.f).filter(v => (v || '').trim().length > 0).length; },
        }">
        @csrf
        @if ($ad->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold">Popraw poniższe pola:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Licznik kompletności 6 pytań --}}
        <div class="sticky top-0 z-10 flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white px-5 py-3 shadow-sm">
            <div>
                <p class="text-sm font-bold text-ink">Kompletność ogłoszenia (zasada 6 pytań)</p>
                <p class="text-xs text-muted">Dobre ogłoszenie odpowiada na wszystkie 6 pytań, których szuka kandydat.</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex gap-1" aria-hidden="true">
                    <template x-for="i in 6" :key="i">
                        <span class="h-2.5 w-2.5 rounded-full" :class="i <= done ? 'bg-brand' : 'bg-gray-200'"></span>
                    </template>
                </div>
                <span class="text-sm font-bold" :class="done === 6 ? 'text-green-600' : 'text-muted'" x-text="done + '/6'"></span>
            </div>
        </div>

        {{-- Podstawy --}}
        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <div>
                <label for="title" class="mb-1 block text-sm font-bold">Tytuł ogłoszenia (rola) <span aria-hidden="true" class="text-red-600">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $ad->title) }}" required placeholder="np. Wolontariusz/ka w Klubie Cyfrowym FEER"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Konkretna rola, nie „Wolontariat w FEER".</p>
            </div>
            <div>
                <label for="lead" class="mb-1 block text-sm font-bold">Krótki wstęp (1–2 zdania) <span aria-hidden="true" class="text-red-600">*</span></label>
                <textarea id="lead" name="lead" rows="2" required maxlength="300" placeholder="Jedno–dwa konkretne zdania, dla kogo i po co jest to ogłoszenie."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('lead', $ad->lead) }}</textarea>
            </div>
        </div>

        {{-- Pytanie 1 --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">1. Cel wolontariatu</legend>
            <label for="q_beneficiaries" class="mb-1 block text-xs text-muted">Po co jest ten wolontariat i komu realnie pomaga — sens zaangażowania, nie tylko lista beneficjentów.</label>
            <textarea id="q_beneficiaries" name="q_beneficiaries" rows="3" required x-model="f.q1"
                placeholder="np. „Pomagasz osobom z niepełnosprawnością wzroku samodzielnie korzystać ze smartfona — to realnie zwiększa ich niezależność."
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('q_beneficiaries', $ad->q_beneficiaries) }}</textarea>
        </fieldset>

        {{-- Pytanie 2 --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">2. Na czym będzie polegał wolontariat?</legend>
            <label for="q_tasks" class="mb-1 block text-xs text-muted">Konkretne zadania — jedno w wierszu.</label>
            <textarea id="q_tasks" name="q_tasks" rows="5" required x-model="f.q2"
                placeholder="Prowadzenie indywidualnych spotkań ze smartfonem&#10;Przygotowanie prostych materiałów instruktażowych&#10;Pomoc podczas warsztatów grupowych"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ $tasksText }}</textarea>
        </fieldset>

        {{-- Pytanie 3 --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">3. Kiedy i gdzie odbywają się działania?</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="q_mode" class="mb-1 block text-sm font-bold">Tryb <span aria-hidden="true" class="text-red-600">*</span></label>
                    <select id="q_mode" name="q_mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach (\App\Models\VolunteerAd::MODES as $value => $label)
                            <option value="{{ $value }}" {{ old('q_mode', $ad->q_mode) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="q_location" class="mb-1 block text-sm font-bold">Lokalizacja</label>
                    <input type="text" id="q_location" name="q_location" value="{{ old('q_location', $ad->q_location) }}" placeholder="np. Nowy Sącz, ul. Barbackiego 28"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Wymagane, chyba że wolontariat jest w pełni zdalny.</p>
                </div>
            </div>
            <div>
                <label for="q_schedule" class="mb-1 block text-sm font-bold">Harmonogram <span aria-hidden="true" class="text-red-600">*</span></label>
                <input type="text" id="q_schedule" name="q_schedule" value="{{ old('q_schedule', $ad->q_schedule) }}" required x-model="f.q3"
                    placeholder="np. Wtorki i czwartki 16:00–18:00, od października 2026"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
        </fieldset>

        {{-- Pytanie 4 --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">4. Ile czasu zajmuje zaangażowanie?</legend>
            <label for="q_time_commitment" class="sr-only">Ile czasu</label>
            <input type="text" id="q_time_commitment" name="q_time_commitment" value="{{ old('q_time_commitment', $ad->q_time_commitment) }}" required x-model="f.q4"
                placeholder="np. 4–6 godzin tygodniowo, współpraca min. 3 miesiące"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        </fieldset>

        {{-- Pytanie 5 --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">5. Co zyska wolontariusz?</legend>
            <label for="q_benefits" class="mb-1 block text-xs text-muted">Konkretne korzyści — jedna w wierszu.</label>
            <textarea id="q_benefits" name="q_benefits" rows="5" required x-model="f.q5"
                placeholder="Zaświadczenie o wolontariacie&#10;Szkolenie z dostępności cyfrowej i WCAG&#10;Realne doświadczenie w pracy z użytkownikami&#10;Sieć kontaktów w sektorze NGO"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ $benefitsText }}</textarea>
        </fieldset>

        {{-- Pytanie 6 --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-brand">6. Jak można się zgłosić?</legend>
            <div>
                <label for="q_how_to_apply" class="mb-1 block text-sm font-bold">Instrukcja zgłoszenia <span aria-hidden="true" class="text-red-600">*</span></label>
                <textarea id="q_how_to_apply" name="q_how_to_apply" rows="3" required x-model="f.q6"
                    placeholder="np. Wypełnij krótki formularz — odpowiemy w ciągu 3 dni i zaprosimy na rozmowę."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('q_how_to_apply', $ad->q_how_to_apply) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="application_url" class="mb-1 block text-sm font-bold">Link do formularza zgłoszeniowego</label>
                    <input type="url" id="application_url" name="application_url" value="{{ old('application_url', $ad->application_url) }}" placeholder="https://..."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Zewnętrzny formularz (Typeform, Google Forms) lub Wasz moduł zgłoszeń. Możesz zmieniać sam link bez ruszania treści.</p>
                </div>
                <div>
                    <label for="application_cta_label" class="mb-1 block text-sm font-bold">Napis na przycisku</label>
                    <input type="text" id="application_cta_label" name="application_cta_label" value="{{ old('application_cta_label', $ad->application_cta_label ?: 'Zgłoś się') }}" maxlength="60"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact_name" class="mb-1 block text-sm font-bold">Osoba kontaktowa <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $ad->contact_name) }}" placeholder="np. Anna Kowalska, koordynatorka wolontariatu"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label for="contact_email" class="mb-1 block text-sm font-bold">E-mail kontaktowy <span class="font-normal text-muted">(zapasowo, gdy brak linku)</span></label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $ad->contact_email) }}" placeholder="np. wolontariat@feer.org.pl"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
            </div>
        </fieldset>

        {{-- Publikacja i powiązania --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="mb-4">
                <h2 class="text-base font-bold text-ink">Publikacja i powiązania</h2>
                <p class="mt-1 text-xs text-muted">Widoczność ogłoszenia, termin naboru, kolejność na liście i schemat kolorów spójny z projektami.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="audience" class="mb-1 block text-sm font-bold">Schemat kolorów</label>
                <select id="audience" name="audience" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @foreach ($siteSettings->audienceOptions() as $value => $label)
                        <option value="{{ $value }}" {{ old('audience', $ad->audience ?: 'brand') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="closes_at" class="mb-1 block text-sm font-bold">Termin zgłoszeń</label>
                <input type="date" id="closes_at" name="closes_at" value="{{ old('closes_at', $ad->closes_at?->format('Y-m-d')) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Po tej dacie ogłoszenie znika ze strony.</p>
            </div>
            <div>
                <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                <input type="number" id="order" name="order" value="{{ old('order', $ad->order ?? 0) }}" min="0"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <label class="flex items-center gap-2 sm:col-span-3">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $ad->is_published) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-sm font-bold">Opublikowane (widoczne na stronie)</span>
            </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.wolontariat.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
        </div>
    </form>
@endsection
