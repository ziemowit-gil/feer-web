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

        <div class="mb-12 grid gap-3 sm:grid-cols-3" role="list" aria-label="Dokumenty organizacyjne">
            @foreach ([
                'Deklaracja członkostwa',
                'Uchwała Zarządu o przystąpieniu',
                'Statut Organizacji',
            ] as $doc)
                <div class="flex items-center gap-3 rounded border border-gray-200 px-4 py-3" role="listitem">
                    <i class="fa-regular fa-file-lines flex-none text-lg text-muted" aria-hidden="true"></i>
                    <span class="text-sm font-bold text-ink">{{ $doc }}</span>
                </div>
            @endforeach
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
