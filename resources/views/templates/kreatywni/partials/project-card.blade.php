{{--
    Karta działania w szablonie „kreatywni” (sekcja „Nasze działania” na stronie głównej i lista /dzialania): cała karta
    jest linkiem, zdjęcie dekoracyjne (alt="", nazwę niesie tytuł), bez zdjęcia — miętowe pole z ikoną. Tytuł jako
    nagłówek o poziomie $kcHeading (h3 w sekcji pod H2, h2 na liście pod H1). Kontrast: zieleń #1b4a07 i tekst
    #1d1d1a na mięcie i na żółci po najechaniu > 7:1.
--}}
@php
    $kcHeading = $kcHeading ?? 'h3';
    $kcExcerpt = trim(strip_tags((string) ($project->excerpt ?: $project->meta_description)));
@endphp
<a href="{{ route('projects.show', $project) }}" class="kc-card kc-child-card kc-project-card">
    <span class="kc-project-img" aria-hidden="true">
        @if ($project->image_url)
            <img src="{{ $project->image_url }}" alt="" loading="lazy">
        @else
            <i class="fa-solid fa-seedling"></i>
        @endif
    </span>
    <span class="kc-project-body">
        <{{ $kcHeading }} class="kc-child-title">{{ $project->title }}</{{ $kcHeading }}>
        @if ($kcExcerpt !== '')<span class="kc-child-text">{{ \Illuminate\Support\Str::limit($kcExcerpt, 180) }}</span>@endif
        <span class="kc-child-more" aria-hidden="true">Czytaj dalej →</span>
    </span>
</a>
