@extends('layouts.site')

@section('title', 'Organizacje członkowskie — ' . $siteSettings->site_name)
@section('meta_description', 'Poznaj organizacje członkowskie ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Organizacje członkowskie', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Organizacje</p>
        <h1 class="mb-4 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
            Poznaj organizacje członkowskie {{ $siteSettings->site_name }}
        </h1>
        <p class="mb-8 max-w-2xl text-base leading-relaxed text-muted">
            Organizacje zrzeszone w {{ $siteSettings->site_name }} w swojej codziennej działalności na pierwszym
            miejscu stawiają pomoc człowiekowi. Rozwijają też pasje i zainteresowania w środowiskach dzieci i
            młodzieży, jak również wspierają edukację i system pomocy zdrowotnej.
        </p>

        <div class="relative mb-12 flex flex-col items-center gap-5 overflow-hidden rounded-lg p-8 text-center sm:flex-row sm:justify-between sm:text-left"
            style="background:{{ $siteSettings->brandColorN(2) }}">
            <i class="fa-solid fa-people-group pointer-events-none absolute -right-4 -top-4 text-[8rem] text-white/10" aria-hidden="true"></i>
            <div class="relative">
                <p class="text-xl font-extrabold leading-snug text-white">Chcesz dołączyć do federacji?</p>
                <p class="mt-1 text-sm text-white/85">Sprawdź, jakie dokumenty są potrzebne i jak wygląda proces przystąpienia.</p>
            </div>
            <a href="{{ route('federation.join') }}"
                class="relative flex-none rounded-md bg-white px-6 py-3 text-sm font-extrabold text-ink transition hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2"
                style="--tw-ring-offset-color:{{ $siteSettings->brandColorN(2) }}">
                Dołącz do nas
            </a>
        </div>

        <h2 class="mb-6 text-2xl font-extrabold tracking-tight text-ink">Organizacje członkowskie</h2>

        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($organizations as $name)
                <li class="flex items-center gap-3 rounded-md border border-gray-100 bg-white p-4 shadow-sm">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid fa-people-roof text-sm"></i>
                    </span>
                    <span class="text-sm font-semibold leading-snug text-ink">{{ $name }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    @include('templates.federation.partials.home.cta-banner')
@endsection
