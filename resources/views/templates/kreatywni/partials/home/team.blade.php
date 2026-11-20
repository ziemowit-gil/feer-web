{{-- Zespół: karty osób z inicjałami (bez zdjęć — profile i zdjęcia są w social media i na stronie „O nas”). --}}
@php
    $kcTeam = $texts->rows('team_items', 3);
    $kcInitials = fn (string $name) => mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/[\s-]+/u', trim($name)) ?: [], 0, 2))));
@endphp
@if ($kcTeam !== [])
<section id="zespol" class="kc-sec" aria-labelledby="kc-team-title">
    <div class="kc-wrap">
        <div class="kc-head">
            <h2 id="kc-team-title" class="kc-h2">{{ $texts->get('team_title') }}</h2>
            @foreach ($texts->paragraphs('team_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
        </div>
        <ul class="kc-grid kc-grid-2">
            @foreach ($kcTeam as [$name, $role, $bio])
                <li class="kc-card kc-sec-cream kc-person">
                    <span class="kc-avatar" aria-hidden="true">{{ $kcInitials($name) }}</span>
                    <div>
                        <h3>{{ $name }}</h3>
                        @if ($role !== '')<p class="kc-role">{{ $role }}</p>@endif
                        @if ($bio !== '')<p>{{ $bio }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
