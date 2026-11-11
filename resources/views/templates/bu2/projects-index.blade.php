{{--
    Lista działań (moduł Działania) w szablonie „bu2” jako strona „Projekty”: tytuł = nazwa pozycji w Menu nawigacji
    prowadzącej do listy (bez niej „Projekty”), wstęp z Ustawienia → Treści (projects_intro), potem dwie grupy:
    „Projekty obecnie realizowane lub kontynuowane” (w trakcie i planowane) i „Projekty zrealizowane” (zakończone).
    Każdy projekt to szare pole z linkiem do jego strony.
--}}
@extends('templates.bu2.layout')

@php
    $bu2Node = \App\Support\Bu2Menu::currentNode();
    $bu2Title = $bu2Node['label'] ?? 'Projekty';
@endphp

@section('title', $bu2Title . ' — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [['label' => $bu2Title, 'url' => null]]])
@endsection

@section('bu2_main')
    <article>
        <h1 class="bu2-title">{{ $bu2Title }}</h1>
        @if (filled($siteSettings->projects_intro))
            <div class="bu2-body prose bu2-intro">{!! $siteSettings->projects_intro !!}</div>
        @endif

        @foreach ([['Projekty obecnie realizowane lub kontynuowane', $currentProjects, 'bu2-pr-now'], ['Projekty zrealizowane', $completedProjects, 'bu2-pr-done']] as [$heading, $list, $hid])
            @if ($list->isNotEmpty())
                <section class="bu2-group" aria-labelledby="{{ $hid }}">
                    <h2 id="{{ $hid }}" class="bu2-group-title">{{ $heading }}</h2>
                    <ul class="bu2-boxes" role="list">
                        @foreach ($list as $project)
                            <li><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach

        @if ($currentProjects->isEmpty() && $completedProjects->isEmpty())
            <p class="bu2-body">Nie ma jeszcze opublikowanych projektów.</p>
        @endif

        @include('templates.bu2.partials.tools')
    </article>
@endsection
