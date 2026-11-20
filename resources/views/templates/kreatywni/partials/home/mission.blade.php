{{-- Obszary działania: nagłówek, krótki wstęp i cztery lekkie karty (punkty misji są w sliderze na górze). --}}
@php
    $kcAreas = $texts->rows('areas', 2);
    $kcAreaIcons = ['fa-solid fa-heart-pulse', 'fa-solid fa-palette', 'fa-solid fa-people-group', 'fa-solid fa-leaf'];
@endphp
@if ($kcAreas !== [])
<section id="misja" class="kc-sec" aria-labelledby="kc-mission-title">
    <div class="kc-wrap">
        <div class="kc-head">
            <h2 id="kc-mission-title" class="kc-h2">{{ $texts->get('areas_title') }}</h2>
            @foreach ($texts->paragraphs('mission_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
        </div>
        <ul class="kc-grid kc-grid-4">
            @foreach ($kcAreas as $i => [$title, $text])
                <li class="kc-card kc-area">
                    <span class="kc-badge" aria-hidden="true"><i class="{{ $kcAreaIcons[$i % count($kcAreaIcons)] }}"></i></span>
                    <h3>{{ $title }}</h3>
                    @if ($text !== '')<p>{{ $text }}</p>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
