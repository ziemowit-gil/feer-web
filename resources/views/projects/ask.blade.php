@extends('layouts.site')

@section('title', 'Zadaj pytanie — '.$project->title.' — '.$siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Działania', 'url' => route('projects.index')],
        ['label' => $project->title, 'url' => route('projects.show', $project)],
        ['label' => 'Zadaj pytanie', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12">
        <p class="text-sm font-bold uppercase tracking-widest text-brand-dark">{{ $project->title }}</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight text-ink md:text-4xl">Zadaj pytanie</h1>
        <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
        <p class="mt-5 text-ink">Napisz, co chcesz wiedzieć o tym działaniu. Wiadomość trafi bezpośrednio do osoby kontaktowej:
            <strong>{{ $project->coordinator_name ?: 'zespołu działania' }}</strong>.</p>

        @if ($errors->any())
            <p class="mt-6 rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-800" role="alert">Popraw zaznaczone pola i wyślij ponownie.</p>
        @endif

        <form method="POST" action="{{ route('projects.ask.store', $project) }}" class="mt-8 space-y-5 rounded-md bg-gray-50 p-6">
            @csrf
            <input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN_FIELD }}" value="{{ $challenge['token'] }}">
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label for="ask-website">Strona WWW</label>
                <input id="ask-website" type="text" name="{{ \App\Support\SpamGuard::HONEYPOT_FIELD }}" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label for="ask-name" class="mb-1 block text-sm font-bold text-ink">Imię i nazwisko <span class="text-red-700" aria-hidden="true">*</span></label>
                <input id="ask-name" name="name" type="text" required aria-required="true" value="{{ old('name') }}" autocomplete="name" maxlength="255"
                    @error('name') aria-invalid="true" aria-describedby="ask-name-err" @enderror class="min-h-11 w-full rounded-md border-gray-500 text-base focus:border-brand-dark focus:ring-brand-dark">
                @error('name')<p id="ask-name-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="ask-email" class="mb-1 block text-sm font-bold text-ink">E-mail <span class="text-red-700" aria-hidden="true">*</span></label>
                    <input id="ask-email" name="email" type="email" required aria-required="true" value="{{ old('email') }}" autocomplete="email" inputmode="email" maxlength="255"
                        @error('email') aria-invalid="true" aria-describedby="ask-email-err" @enderror class="min-h-11 w-full rounded-md border-gray-500 text-base focus:border-brand-dark focus:ring-brand-dark">
                    @error('email')<p id="ask-email-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ask-phone" class="mb-1 block text-sm font-bold text-ink">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input id="ask-phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="30" class="min-h-11 w-full rounded-md border-gray-500 text-base focus:border-brand-dark focus:ring-brand-dark">
                </div>
            </div>
            <div>
                <label for="ask-message" class="mb-1 block text-sm font-bold text-ink">Pytanie <span class="text-red-700" aria-hidden="true">*</span></label>
                <textarea id="ask-message" name="message" rows="5" required aria-required="true" maxlength="5000"
                    @error('message') aria-invalid="true" aria-describedby="ask-message-err" @enderror class="w-full rounded-md border-gray-500 text-base focus:border-brand-dark focus:ring-brand-dark">{{ old('message') }}</textarea>
                @error('message')<p id="ask-message-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="ask-answer" class="mb-1 block text-sm font-bold text-ink">{{ $challenge['question'] }} <span class="text-red-700" aria-hidden="true">*</span></label>
                <input id="ask-answer" type="text" name="{{ \App\Support\SpamGuard::ANSWER_FIELD }}" required aria-required="true" autocomplete="off" inputmode="numeric"
                    class="min-h-11 w-40 rounded-md border-gray-500 text-base focus:border-brand-dark focus:ring-brand-dark">
            </div>
            <label class="flex items-start gap-3 text-sm text-ink">
                <input type="checkbox" name="rodo_consent" value="1" required aria-required="true" @checked(old('rodo_consent')) class="mt-0.5 h-5 w-5 flex-none rounded text-brand-dark focus:ring-brand-dark">
                <span>Wyrażam zgodę na kontakt w sprawie pytania i przetwarzanie moich danych w tym celu.@if (\App\Models\SiteSetting::current()->privacy_url ?? null) <a href="{{ \App\Models\SiteSetting::current()->privacy_url }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Jak przetwarzamy dane</a>.@endif</span>
            </label>
            <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-6 font-bold text-white sm:w-auto hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                Wyślij pytanie <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            </button>
        </form>
    </section>
@endsection
