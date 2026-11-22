{{--
    Lista działań w szablonie „kreatywni” (ProjectController::index): kremowy pas z tytułem i wstępem (teksty sekcji
    „Działania” z App\Support\KreatywniTexts), trwające działania jako karty ze zdjęciem, link do zakończonych.
    Karta działania (projects.show) korzysta ze zwykłego widoku z nagłówkiem i stopką szablonu.
--}}
@extends('layouts.site')

@php $kcTitle = $texts->get('projects_title') ?: 'Działania'; @endphp

@section('title', $kcTitle . ' — ' . $siteSettings->site_name)
@section('meta_description', $texts->get('projects_text') ?: 'Działania ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [['label' => $kcTitle, 'url' => null]]])
@endsection

@section('content')
    @include('templates.kreatywni.partials.base-css')
    <div class="kc">
        <section class="kc-page-head" aria-labelledby="kc-page-title">
            <div class="kc-wrap">
                <p class="kc-eyebrow kc-hand">{{ $siteSettings->site_name }}</p>
                <h1 id="kc-page-title">{{ $kcTitle }}</h1>
                @foreach ($texts->paragraphs('projects_text') as $p)
                    <p class="kc-lead">{{ $p }}</p>
                @endforeach
            </div>
        </section>

        <div class="kc-wrap kc-sec">
            @if ($projects->isEmpty())
                <p class="kc-lead">Nie ma jeszcze opublikowanych działań. Zajrzyj tu wkrótce.</p>
            @else
                <ul class="kc-grid kc-grid-3 kc-projects">
                    @foreach ($projects as $project)
                        <li>@include('templates.kreatywni.partials.project-card', ['kcHeading' => 'h2'])</li>
                    @endforeach
                </ul>
            @endif

            @if ($hasArchive && \Illuminate\Support\Facades\Route::has('projects.archive'))
                <div class="kc-more">
                    <a href="{{ route('projects.archive') }}" class="kc-btn kc-btn-ghost"><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Zakończone działania</a>
                </div>
            @endif
        </div>
    </div>
@endsection
