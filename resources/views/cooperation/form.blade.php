@extends('layouts.site')

@php
    $formTitle    = $cd['form_title'] ?? 'Formularz współpracy';
    $formSubtitle = $cd['form_subtitle'] ?? 'Wypełnij poniższe pola — skontaktujemy się z Tobą w ciągu 2 dni roboczych.';

    $defaultSectors = [
        ['title' => 'Biznes'],
        ['title' => 'Samorząd i instytucje'],
        ['title' => 'Nauka i edukacja'],
        ['title' => 'Inne NGO'],
    ];
    $defaultForms = [
        ['title' => 'Partnerstwo strategiczne'],
        ['title' => 'Sponsoring'],
        ['title' => 'Wolontariat kompetencyjny'],
        ['title' => 'Koalicje i sieci'],
    ];

    $sectors = collect($cd['sectors'] ?? $defaultSectors)->filter(fn ($s) => filled($s['title'] ?? null));
    $forms   = collect($cd['forms']   ?? $defaultForms)->filter(fn ($f) => filled($f['title'] ?? null));
@endphp

@section('title', $formTitle . ' — ' . $siteSettings->site_name)
@section('meta_description', $formSubtitle)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $page->title, 'url' => route('page.show', $page)],
        ['label' => 'Formularz', 'url' => null],
    ]])
@endsection

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12">

    @if (session('sent'))
        <div class="mb-8 flex items-start gap-4 rounded-2xl border border-green-200 bg-green-50 p-6" role="alert" aria-live="polite">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600" aria-hidden="true">
                <i class="fa-solid fa-check text-lg"></i>
            </span>
            <div>
                <p class="font-bold text-green-800">Zgłoszenie wysłane</p>
                <p class="mt-1 text-sm text-green-700">{{ session('sent') }}</p>
            </div>
        </div>
    @endif

    {{-- Nagłówek --}}
    <div class="mb-10">
        <a href="{{ route('page.show', $page) }}"
           class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:text-brand-dark focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            {{ $page->title }}
        </a>
        <h1 class="text-3xl font-bold text-ink">{{ $formTitle }}</h1>
        @if ($formSubtitle)
            <p class="mt-3 text-muted">{{ $formSubtitle }}</p>
        @endif
    </div>

    {{-- Formularz --}}
    <form method="POST" action="{{ route('cooperation.form.store', $page) }}" novalidate
          x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
                <p class="mb-2 text-sm font-bold text-red-700">Popraw poniższe błędy:</p>
                <ul class="list-inside list-disc space-y-1 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-8">

            {{-- Dane kontaktowe --}}
            <fieldset>
                <legend class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Dane kontaktowe</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-bold text-ink">
                            Imię i nazwisko <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                               autocomplete="name" required
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand @error('name') border-red-400 @enderror"
                               aria-required="true" aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}">
                        @error('name')
                            <p id="name-error" class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-bold text-ink">
                            E-mail <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               autocomplete="email" required
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand @error('email') border-red-400 @enderror"
                               aria-required="true" aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}">
                        @error('email')
                            <p id="email-error" class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="organization" class="mb-1 block text-sm font-bold text-ink">
                            Organizacja / firma <span class="font-normal text-muted">(opcjonalnie)</span>
                        </label>
                        <input type="text" id="organization" name="organization" value="{{ old('organization') }}"
                               autocomplete="organization"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
            </fieldset>

            {{-- Sektor --}}
            @if ($sectors->isNotEmpty())
            <fieldset>
                <legend class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Skąd piszesz?</legend>
                <div class="flex flex-wrap gap-3" role="group">
                    @foreach ($sectors as $idx => $sector)
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-ink shadow-sm transition hover:border-brand has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand has-[:checked]:shadow-none">
                            <input type="radio" name="sector" value="{{ $sector['title'] }}"
                                   {{ old('sector') === $sector['title'] ? 'checked' : '' }}
                                   class="sr-only">
                            @if (filled($sector['icon'] ?? null))
                                <i class="{{ $sector['icon'] }} text-xs" aria-hidden="true"></i>
                            @endif
                            {{ $sector['title'] }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @endif

            {{-- Formy współpracy --}}
            @if ($forms->isNotEmpty())
            <fieldset>
                <legend class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">
                    Co Cię interesuje? <span class="font-normal normal-case text-muted">(możesz wybrać kilka)</span>
                </legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($forms as $form)
                        <label class="inline-flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-brand has-[:checked]:border-brand has-[:checked]:bg-brand-light">
                            <input type="checkbox" name="cooperation_types[]" value="{{ $form['title'] }}"
                                   {{ in_array($form['title'], old('cooperation_types', [])) ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2 text-sm font-bold text-ink">
                                    @if (filled($form['icon'] ?? null))
                                        <i class="{{ $form['icon'] }} text-brand" aria-hidden="true"></i>
                                    @endif
                                    {{ $form['title'] }}
                                </span>
                                @if (filled($form['text'] ?? null))
                                    <span class="mt-0.5 block text-xs text-muted">{{ $form['text'] }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @endif

            {{-- Wiadomość --}}
            <div>
                <label for="message" class="mb-1 block text-sm font-bold text-ink">
                    Krótki opis <span class="font-normal text-muted">(opcjonalnie)</span>
                </label>
                <textarea id="message" name="message" rows="4"
                          placeholder="Co chcesz osiągnąć? Jakie masz zasoby lub potrzeby?"
                          class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                <p class="mt-1 text-xs text-muted">Maksymalnie 2000 znaków.</p>
            </div>

            {{-- RODO --}}
            <div>
                <label class="inline-flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="privacy" value="1"
                           {{ old('privacy') ? 'checked' : '' }}
                           required
                           class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand @error('privacy') border-red-400 @enderror"
                           aria-required="true">
                    <span class="text-sm text-muted">
                        Wyrażam zgodę na przetwarzanie podanych danych osobowych przez {{ $siteSettings->site_name }}
                        w celu odpowiedzi na zgłoszenie. Dane nie będą przekazywane osobom trzecim.
                        <span class="text-red-500" aria-hidden="true">*</span>
                    </span>
                </label>
                @error('privacy')
                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Przycisk --}}
            <div class="flex items-center gap-4">
                <button type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand px-7 py-3.5 font-bold text-white shadow-sm transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-60">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Wyślij zgłoszenie
                </button>
                <p class="text-xs text-muted">
                    <span class="text-red-500" aria-hidden="true">*</span> Pola wymagane
                </p>
            </div>

        </div>
    </form>
</div>
@endsection
