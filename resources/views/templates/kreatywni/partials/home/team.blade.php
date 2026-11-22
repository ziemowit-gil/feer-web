{{-- Zespół: karty osób ze zdjęciem albo inicjałami — z modułu „Osoby – Kreatywni”, a bez niego z tekstów (team_items). --}}
@php
    $kcTeam = \App\Models\KreatywniPerson::teamRows($texts, 'team_items');
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
            @foreach ($kcTeam as $person)
                <li class="kc-card kc-sec-cream kc-person">
                    @if ($person['photo'])
                        <img src="{{ $person['photo'] }}" alt="{{ $person['alt'] }}" class="kc-avatar" loading="lazy">
                    @else
                        <span class="kc-avatar" aria-hidden="true">{{ $person['initials'] }}</span>
                    @endif
                    <div>
                        <h3>{{ $person['name'] }}</h3>
                        @if ($person['role'] !== '')<p class="kc-role">{{ $person['role'] }}</p>@endif
                        @if ($person['bio'] !== '')<p>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $person['bio']), 320) }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
