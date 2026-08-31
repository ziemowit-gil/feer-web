@extends('layouts.site')

@section('title', 'Dołącz do nas — ' . $siteSettings->site_name)
@section('meta_description', 'Dołącz do ' . $siteSettings->site_name . ' — poznaj korzyści członkostwa i potrzebne dokumenty.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Dołącz do nas', 'url' => null],
    ]])
@endsection

@section('content')
    {{-- Hero --}}
    <section class="bg-white">
        <div class="mx-auto max-w-[900px] px-4 py-12 text-center lg:py-16">
            <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Dołącz do nas</p>
            <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
                Razem możemy więcej
            </h1>
            <p class="mx-auto max-w-2xl text-base leading-relaxed text-muted">
                {{ $siteSettings->site_name }} zrzesza organizacje pozarządowe działające na rzecz Krakowa i jego
                mieszkańców. Jeśli Twoja organizacja podziela naszą misję, zapraszamy do dołączenia.
            </p>
        </div>
    </section>

    {{-- Dlaczego warto --}}
    <section class="border-t border-gray-100 bg-gray-50 py-16">
        <div class="mx-auto max-w-[1400px] px-4">
            <h2 class="mb-10 text-center text-2xl font-extrabold tracking-tight text-ink">Dlaczego warto?</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($benefits as $i => $b)
                    @php $color = $siteSettings->brandColorN(($i % 4) + 1); @endphp
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <span class="mb-3 flex h-11 w-11 items-center justify-center rounded-lg" style="background:{{ $color }}1a">
                            <i class="fa-solid {{ $b['icon'] }} text-lg" style="color:{{ $color }}" aria-hidden="true"></i>
                        </span>
                        <h3 class="mb-1.5 font-bold text-ink">{{ $b['title'] }}</h3>
                        <p class="text-sm leading-relaxed text-muted">{{ $b['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Jak dołączyć --}}
    <section class="border-t border-gray-100 py-16">
        <div class="mx-auto max-w-[900px] px-4">
            <h2 class="mb-10 text-center text-2xl font-extrabold tracking-tight text-ink">Jak dołączyć?</h2>
            <ol class="space-y-6">
                @php
                    $steps = [
                        ['title' => 'Wypełnij deklarację członkostwa', 'text' => 'Pobierz i wypełnij deklarację członkostwa w imieniu swojej organizacji.'],
                        ['title' => 'Uzyskaj uchwałę zarządu', 'text' => 'Zarząd Twojej organizacji podejmuje uchwałę o przystąpieniu do federacji.'],
                        ['title' => 'Prześlij dokumenty', 'text' => 'Wyślij komplet dokumentów (deklarację, uchwałę i statut) na adres biuro@krafos.pl.'],
                    ];
                @endphp
                @foreach ($steps as $i => $step)
                    @php $color = $siteSettings->brandColorN(($i % 4) + 1); @endphp
                    <li class="flex gap-4">
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-sm font-extrabold text-white" style="background:{{ $color }}">
                            {{ $i + 1 }}
                        </span>
                        <div>
                            <h3 class="font-bold text-ink">{{ $step['title'] }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-muted">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Potrzebne dokumenty --}}
    <section class="border-t border-gray-100 bg-gray-50 py-16">
        <div class="mx-auto max-w-[900px] px-4">
            <h2 class="mb-6 text-2xl font-extrabold tracking-tight text-ink">Potrzebne dokumenty</h2>
            @if ($documents->isNotEmpty())
                @include('partials.attachments-list', ['attachments' => $documents])
            @else
                <p class="text-muted">Dokumenty do pobrania pojawią się tutaj wkrótce.</p>
            @endif
        </div>
    </section>

    {{-- Formularz zgłoszeniowy --}}
    <section class="border-t border-gray-100 py-16">
        <div class="mx-auto max-w-[700px] px-4">
            <h2 class="mb-2 text-2xl font-extrabold tracking-tight text-ink">Zgłoś swoją organizację</h2>
            <p class="mb-8 text-sm leading-relaxed text-muted">
                Wypełnij formularz i dołącz skany wypełnionych dokumentów (deklaracja, uchwała, statut) —
                PDF, JPG lub PNG, maks. 8 MB na plik.
            </p>

            @if (session('application_sent'))
                <div class="mb-6 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="alert">
                    <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('application_sent') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('federation.join.submit') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label for="organization_name" class="mb-1.5 block text-sm font-bold text-ink">Nazwa organizacji</label>
                    <input type="text" id="organization_name" name="organization_name" required value="{{ old('organization_name') }}"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_name" class="mb-1.5 block text-sm font-bold text-ink">Osoba zgłaszająca</label>
                        <input type="text" id="contact_name" name="contact_name" required value="{{ old('contact_name') }}"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-bold text-ink">E-mail</label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>

                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-bold text-ink">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>

                <div>
                    <label for="message" class="mb-1.5 block text-sm font-bold text-ink">Wiadomość <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <textarea id="message" name="message" rows="3"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                </div>

                <div>
                    <label for="documents" class="mb-1.5 block text-sm font-bold text-ink">Skany dokumentów</label>
                    <input type="file" id="documents" name="documents[]" multiple required accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full rounded border border-gray-300 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white">
                    <p class="mt-1 text-xs text-muted">Możesz wybrać kilka plików naraz (Ctrl/Cmd + klik).</p>
                </div>

                <label class="flex items-start gap-2 text-sm text-muted">
                    <input type="checkbox" name="privacy" required class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span>Wyrażam zgodę na przetwarzanie moich danych osobowych w celu rozpatrzenia zgłoszenia.</span>
                </label>

                <button type="submit"
                    class="rounded-md bg-brand px-6 py-3 text-sm font-extrabold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Wyślij zgłoszenie
                </button>
            </form>
        </div>
    </section>

    @include('templates.federation.partials.home.cta-banner')
@endsection
