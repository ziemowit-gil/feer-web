{{-- Misja: H2 z numerowanymi punktami po lewej, tekst i karty obszarów działania po prawej. --}}
@php
    $kcMission = $texts->rows('mission_items');
    $kcAreas = $texts->rows('areas', 2);
    $kcAreaIcons = ['fa-solid fa-heart-pulse', 'fa-solid fa-palette', 'fa-solid fa-people-group', 'fa-solid fa-leaf'];
@endphp
@if ($kcMission !== [] || $kcAreas !== [])
<section id="misja" class="kc-sec" aria-labelledby="kc-mission-title">
    <div class="kc-wrap kc-mission">
        <div>
            <h2 id="kc-mission-title" class="kc-h2">{{ $texts->get('mission_title') }}</h2>
            @if ($kcMission !== [])
                <ol class="kc-steps">
                    @foreach ($kcMission as [$item])
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            @endif
        </div>
        <div>
            @foreach ($texts->paragraphs('mission_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
            @if ($kcAreas !== [])
                <ul class="kc-grid kc-grid-2" style="margin-top:1.5rem">
                    @foreach ($kcAreas as $i => [$title, $text])
                        <li class="kc-card kc-area">
                            <span class="kc-badge" aria-hidden="true"><i class="{{ $kcAreaIcons[$i % count($kcAreaIcons)] }}"></i></span>
                            <h3>{{ $title }}</h3>
                            @if ($text !== '')<p>{{ $text }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
@endif
