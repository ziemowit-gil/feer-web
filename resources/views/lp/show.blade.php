@extends('layouts.site')

@section('title', $page->title . ' — ' . $siteSettings->site_name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->hero_lead ?? $page->hero_title), 155))

@section('content')
    {{-- ============================ HERO ============================ --}}
    <header class="bg-brand text-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 md:grid-cols-2 md:items-center md:py-24">
            <div>
                @if ($page->hero_eyebrow)
                    <p class="mb-3 inline-block rounded-full bg-white/15 px-3 py-1 text-sm font-bold uppercase tracking-wide">{{ $page->hero_eyebrow }}</p>
                @endif
                <h1 class="text-3xl font-bold leading-tight md:text-5xl">{{ $page->hero_title }}</h1>
                @if ($page->hero_lead)
                    <p class="mt-4 max-w-xl text-lg text-white/90">{{ $page->hero_lead }}</p>
                @endif

                @if ($page->event_start || $page->event_location)
                    <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-white/90">
                        @if ($page->event_start)
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
                                <dt class="sr-only">Termin</dt>
                                <dd class="font-bold">{{ $page->eventLabel() }}</dd>
                            </div>
                        @endif
                        @if ($page->event_location)
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                <dt class="sr-only">Miejsce</dt>
                                <dd class="font-bold">{{ $page->event_location }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif

                @php($ctaExternal = filled($page->hero_cta_url))
                <a href="{{ $ctaExternal ? $page->hero_cta_url : '#rejestracja' }}" @if ($ctaExternal) target="_blank" rel="noopener" @endif
                    class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-base font-bold text-brand shadow-lg transition-transform hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand">
                    {{ $page->hero_cta_label ?: 'Zarejestruj się' }}
                    <i class="fa-solid {{ $ctaExternal ? 'fa-arrow-up-right-from-square' : 'fa-arrow-down' }}" aria-hidden="true"></i>
                </a>
            </div>

            @if ($page->hero_image_url)
                <div class="order-first md:order-last">
                    <img src="{{ $page->hero_image_url }}" alt="" class="mx-auto w-full max-w-md rounded-2xl object-cover shadow-2xl">
                </div>
            @endif
        </div>
    </header>

    {{-- =================== SEKCJE ŚRODKOWE (kolejność z CMS) =================== --}}
    @foreach ($page->orderedSections() as $section)
        @php($rows = $page->items($section))
        @continue(empty($rows))

        @if ($section === 'speakers')
            <section aria-labelledby="lp-prelegenci" class="bg-white">
                <div class="mx-auto max-w-6xl px-4 py-16">
                    <h2 id="lp-prelegenci" class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Prelegenci</h2>
                    <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($rows as $s)
                            <li class="rounded-2xl border border-gray-200 bg-gray-50 p-6 text-center">
                                @if (!empty($s['photo']))
                                    <img src="{{ $s['photo'] }}" alt="{{ $s['name'] ?? '' }}" class="mx-auto mb-4 h-28 w-28 rounded-full object-cover">
                                @else
                                    <span class="mx-auto mb-4 flex h-28 w-28 items-center justify-center rounded-full bg-brand-light text-3xl font-bold text-brand" aria-hidden="true">{{ mb_substr($s['name'] ?? '?', 0, 1) }}</span>
                                @endif
                                <p class="text-lg font-bold text-ink">{{ $s['name'] ?? '' }}</p>
                                @if (!empty($s['role']))
                                    <p class="text-sm font-bold text-brand">{{ $s['role'] }}</p>
                                @endif
                                @if (!empty($s['bio']))
                                    <p class="mt-2 text-sm text-muted">{{ $s['bio'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @elseif ($section === 'benefits')
            <section aria-labelledby="lp-korzysci" class="bg-gray-50">
                <div class="mx-auto max-w-6xl px-4 py-16">
                    <h2 id="lp-korzysci" class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Co zyskasz</h2>
                    <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($rows as $b)
                            <li class="rounded-2xl border border-gray-200 bg-white p-6">
                                <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-light text-xl text-brand" aria-hidden="true">
                                    <i class="{{ $b['icon'] ?? 'fa-solid fa-star' }}"></i>
                                </span>
                                <p class="text-lg font-bold text-ink">{{ $b['title'] ?? '' }}</p>
                                @if (!empty($b['text']))
                                    <p class="mt-2 text-sm text-muted">{{ $b['text'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @elseif ($section === 'agenda')
            <section aria-labelledby="lp-agenda" class="bg-white">
                <div class="mx-auto max-w-3xl px-4 py-16">
                    <h2 id="lp-agenda" class="mb-10 text-center text-2xl font-bold text-ink md:text-3xl">Agenda</h2>
                    <ol class="space-y-4">
                        @foreach ($rows as $a)
                            <li class="flex flex-col gap-1 rounded-2xl border border-gray-200 bg-gray-50 p-5 sm:flex-row sm:gap-5">
                                @if (!empty($a['time']))
                                    <span class="flex-none font-bold text-brand sm:w-28">{{ $a['time'] }}</span>
                                @endif
                                <span>
                                    <span class="block font-bold text-ink">{{ $a['title'] ?? '' }}</span>
                                    @if (!empty($a['desc']))
                                        <span class="mt-1 block text-sm text-muted">{{ $a['desc'] }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif
    @endforeach

    {{-- ============================ FORMULARZ ============================ --}}
    <section id="rejestracja" aria-labelledby="lp-form-title" class="bg-brand-light">
        <div class="mx-auto max-w-xl px-4 py-16">
            <h2 id="lp-form-title" class="mb-2 text-center text-2xl font-bold text-ink md:text-3xl">{{ $page->form_title ?: 'Zapisz się na webinar' }}</h2>
            @if ($page->form_intro)
                <p class="mb-8 text-center text-muted">{{ $page->form_intro }}</p>
            @endif

            @php($extraInit = collect($page->formFields())->mapWithKeys(fn ($f) => [$f['key'] => $f['type'] === 'checkbox' ? false : ''])->all())
            <div x-data="lpRegister(@js(route('lp.register', $page->slug)), @js(csrf_token()), @js($extraInit))"
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">

                {{-- Sukces --}}
                <div x-show="state === 'success'" x-cloak role="status" class="py-6 text-center">
                    <i class="fa-solid fa-circle-check mb-3 text-4xl text-brand" aria-hidden="true"></i>
                    <p class="text-lg font-bold text-ink" x-text="message"></p>
                </div>

                <form x-show="state !== 'success'" @submit.prevent="submit" novalidate class="space-y-4">
                    {{-- Błąd ogólny --}}
                    <p x-show="state === 'error'" x-cloak role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" x-text="message"></p>

                    <div>
                        <label for="lp-name" class="mb-1 block text-sm font-bold text-ink">Imię i nazwisko</label>
                        <input id="lp-name" name="name" type="text" x-model="form.name" required autocomplete="name"
                            class="w-full rounded-lg border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                        <p x-show="errors.name" x-cloak class="mt-1 text-sm text-red-700" x-text="errors.name"></p>
                    </div>

                    <div>
                        <label for="lp-email" class="mb-1 block text-sm font-bold text-ink">Adres e-mail</label>
                        <input id="lp-email" name="email" type="email" x-model="form.email" required autocomplete="email"
                            class="w-full rounded-lg border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                        <p x-show="errors.email" x-cloak class="mt-1 text-sm text-red-700" x-text="errors.email"></p>
                    </div>

                    <div>
                        <label for="lp-phone" class="mb-1 block text-sm font-bold text-ink">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input id="lp-phone" name="phone" type="tel" x-model="form.phone" autocomplete="tel"
                            class="w-full rounded-lg border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                    </div>

                    {{-- Dodatkowe pola zdefiniowane w panelu (przekazywane do API) --}}
                    @foreach ($page->formFields() as $f)
                        @php($fkey = $f['key'])
                        @if ($f['type'] === 'checkbox')
                            <label class="flex items-start gap-2 text-sm text-muted">
                                <input type="checkbox" x-model="form.extra['{{ $fkey }}']" class="mt-0.5 rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
                                <span>{{ $f['label'] }}@if ($f['required'])<span class="text-red-600" aria-hidden="true">*</span>@endif</span>
                            </label>
                        @else
                            <div>
                                <label for="lp-x-{{ $fkey }}" class="mb-1 block text-sm font-bold text-ink">{{ $f['label'] }}@if ($f['required'])<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
                                @if ($f['type'] === 'select')
                                    <select id="lp-x-{{ $fkey }}" x-model="form.extra['{{ $fkey }}']" @if ($f['required']) required @endif
                                        class="w-full rounded-lg border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                                        <option value="">— wybierz —</option>
                                        @foreach (($f['options'] ?? []) as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="lp-x-{{ $fkey }}" type="{{ $f['type'] }}" x-model="form.extra['{{ $fkey }}']" @if ($f['required']) required @endif
                                        class="w-full rounded-lg border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                                @endif
                            </div>
                        @endif
                        <p x-show="errors['extra.{{ $fkey }}']" x-cloak class="mt-1 text-sm text-red-700" x-text="errors['extra.{{ $fkey }}']"></p>
                    @endforeach

                    <label class="flex items-start gap-2 text-sm text-muted">
                        <input type="checkbox" name="consent" x-model="form.consent" value="1" class="mt-0.5 rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
                        <span>{{ $page->form_consent_label ?: 'Wyrażam zgodę na przetwarzanie moich danych w celu rejestracji na webinar (RODO).' }}</span>
                    </label>
                    <p x-show="errors.consent" x-cloak class="text-sm text-red-700" x-text="errors.consent"></p>

                    <button type="submit" :disabled="state === 'loading'"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand px-6 py-3 text-base font-bold text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-solid fa-spinner fa-spin" x-show="state === 'loading'" x-cloak aria-hidden="true"></i>
                        <span x-text="state === 'loading' ? 'Wysyłanie…' : '{{ $page->hero_cta_label ?: 'Zarejestruj się' }}'"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('lpRegister', (endpoint, csrf, extraInit = {}) => ({
                state: 'idle', // idle | loading | success | error
                message: '',
                form: { name: '', email: '', phone: '', consent: false, extra: { ...extraInit } },
                errors: {},

                async submit() {
                    this.state = 'loading';
                    this.errors = {};
                    try {
                        const res = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ ...this.form, consent: this.form.consent ? 1 : 0 }),
                        });

                        if (res.ok) {
                            const data = await res.json();
                            this.message = data.message;
                            this.state = 'success';
                            return;
                        }

                        if (res.status === 422) {
                            const data = await res.json();
                            Object.entries(data.errors || {}).forEach(([k, v]) => { this.errors[k] = v[0]; });
                            this.message = 'Popraw zaznaczone pola i spróbuj ponownie.';
                        } else {
                            this.message = 'Coś poszło nie tak. Spróbuj ponownie za chwilę.';
                        }
                        this.state = 'error';
                    } catch (e) {
                        this.message = 'Brak połączenia. Sprawdź internet i spróbuj ponownie.';
                        this.state = 'error';
                    }
                },
            }));
        });
    </script>
@endsection
