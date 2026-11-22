{{-- Nasze działania: karty z modułu Działania (HomeController::kreatywniProjects) i przycisk do pełnej listy. Bez działań sekcji nie ma. --}}
@if (($projects ?? collect())->isNotEmpty())
<section id="dzialania" class="kc-sec kc-sec-cream" aria-labelledby="kc-projects-title">
    <div class="kc-wrap">
        <div class="kc-head">
            <h2 id="kc-projects-title" class="kc-h2">{{ $texts->get('projects_title') }}</h2>
            @foreach ($texts->paragraphs('projects_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
        </div>
        <ul class="kc-grid kc-grid-3 kc-projects">
            @foreach ($projects as $project)
                <li>@include('templates.kreatywni.partials.project-card', ['kcHeading' => 'h3'])</li>
            @endforeach
        </ul>
        @if (\Illuminate\Support\Facades\Route::has('projects.index'))
            <div class="kc-more">
                <a href="{{ route('projects.index') }}" class="kc-btn kc-btn-ghost">{{ $texts->get('projects_button') }}</a>
            </div>
        @endif
    </div>
</section>
@endif
