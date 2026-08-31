@extends('layouts.site')

@section('title', $page->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: $page->title)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $page->title, 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $cd = $page->cooperation_data ?? [];
        $areas = $cd['areas'] ?? [
            'Wsparcie finansowe i rzeczowe projektów',
            'Wolontariat pracowniczy i kompetencyjny',
            'Wspólne kampanie i wydarzenia',
            'Udostępnianie zasobów i przestrzeni',
        ];
        $form = \App\Models\FormDefinition::where('slug', 'wspolpraca-federacja')->where('is_active', true)->first();
    @endphp

    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-start">

            {{-- Lewa kolumna: informacja --}}
            <div>
                <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Współpraca</p>
                <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
                    {{ $page->title }}
                </h1>
                <div class="max-w-xl space-y-4 text-base leading-relaxed text-muted">
                    @if ($page->content)
                        {!! $page->content !!}
                    @else
                        <p>
                            {{ $siteSettings->site_name }} zaprasza firmy, instytucje i organizacje do współpracy
                            na rzecz Krakowa i jego mieszkańców. Razem możemy zrobić więcej.
                        </p>
                    @endif
                </div>

                <h2 class="mb-3 mt-8 text-lg font-bold text-ink">Obszary współpracy</h2>
                <ul class="space-y-2" role="list">
                    @foreach ($areas as $i => $area)
                        @php $color = $siteSettings->brandColorN(($i % 4) + 1); @endphp
                        <li class="flex items-center gap-3 text-sm text-ink">
                            <span class="flex h-6 w-6 flex-none items-center justify-center rounded" style="background:{{ $color }}1a; color:{{ $color }}" aria-hidden="true">
                                <i class="fa-solid fa-check text-xs"></i>
                            </span>
                            {{ $area }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Prawa kolumna: formularz (moduł Kreator formularzy) --}}
            <div>
                @if ($form)
                    @include('formularz._embed', ['form' => $form])
                @else
                    <div class="rounded-lg border border-gray-200 p-6 text-sm text-muted">
                        Formularz nie jest jeszcze skonfigurowany. Napisz na
                        <a href="mailto:{{ $siteSettings->contact_email }}" class="font-bold text-brand">{{ $siteSettings->contact_email }}</a>.
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
