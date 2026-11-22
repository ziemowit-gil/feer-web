{{--
    Dedykowana podstrona „O nas” szablonu „kreatywni” (strona o adresie o-nas / o-nas-2…, włączana z page.blade.php
    pod pasem z tytułem). Sekcje z tekstów App\Support\KreatywniTexts (grupa „about”): misja jako duży cytat,
    #zespol — karty osób (zdjęcie albo inicjały, rola, opis; z modułu „Osoby – Kreatywni”, bez niego z tekstów), #dlaczego — karty „Dlaczego powstała Fundacja” z punktami,
    #historia — historia z napisem „Od 2020 roku”, #dolacz — ciemny pas z zaproszeniem, przyciskiem do kontaktu
    i profilami social media. Sekcja bez treści się nie pokazuje. Kotwice są w mega menu (KreatywniMegaMenu).
    Zmienne: $texts, $kcPageContent (HTML treści strony z edytora albo '').
--}}
@php
    $kcTeam = \App\Models\KreatywniPerson::teamRows($texts, 'about_team');
    $kcWhy = $texts->rows('about_why', 2);
    $kcWhyIcons = ['fa-solid fa-hand-holding-heart', 'fa-solid fa-people-group', 'fa-solid fa-seedling', 'fa-solid fa-brain', 'fa-solid fa-leaf'];
    $kcContactUrl = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    $kcSocial = $siteSettings->socialLinks();
    $kcNet = fn (string $icon) => str_contains($icon, 'facebook') ? 'fb' : (str_contains($icon, 'instagram') ? 'ig' : (str_contains($icon, 'youtube') ? 'yt' : 'other'));
@endphp

@if ($texts->get('about_mission') !== '')
    <section class="kc-sec kc-about-mission-sec" aria-label="Nasza misja">
        <div class="kc-wrap">
            <blockquote class="kc-about-mission">
                @foreach ($texts->paragraphs('about_mission') as $p)
                    <p>{{ $p }}</p>
                @endforeach
            </blockquote>
        </div>
    </section>
@endif

@if ($kcTeam !== [])
    <section id="zespol" class="kc-sec kc-sec-cream" aria-labelledby="kc-about-team">
        <div class="kc-wrap">
            <h2 id="kc-about-team" class="kc-h2 kc-head">{{ $texts->get('about_team_title') }}</h2>
            <ul class="kc-grid kc-grid-2">
                @foreach ($kcTeam as $person)
                    <li class="kc-card kc-about-person">
                        <div class="kc-about-person-head">
                            @if ($person['photo'])
                                <img src="{{ $person['photo'] }}" alt="{{ $person['alt'] }}" class="kc-avatar kc-avatar-lg" loading="lazy">
                            @else
                                <span class="kc-avatar kc-avatar-lg" aria-hidden="true">{{ $person['initials'] }}</span>
                            @endif
                            <div>
                                <h3>{{ $person['name'] }}</h3>
                                @if ($person['role'] !== '')<p class="kc-about-role">{{ $person['role'] }}</p>@endif
                            </div>
                        </div>
                        @foreach (preg_split('/\R\s*\R/u', $person['bio']) ?: [] as $kcBioP)
                            @if (trim($kcBioP) !== '')<p>{{ trim($kcBioP) }}</p>@endif
                        @endforeach
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@if ($kcWhy !== [])
    <section id="dlaczego" class="kc-sec kc-sec-mint" aria-labelledby="kc-about-why">
        <div class="kc-wrap">
            <h2 id="kc-about-why" class="kc-h2 kc-head">{{ $texts->get('about_why_title') }}</h2>
            <ul class="kc-grid kc-grid-3 kc-about-why">
                @foreach ($kcWhy as $i => [$title, $points])
                    @php $kcPoints = array_values(array_filter(array_map('trim', explode(';', $points)), 'strlen')); @endphp
                    <li class="kc-card">
                        <span class="kc-badge {{ $i % 2 ? 'kc-badge-lime' : '' }}" aria-hidden="true"><i class="{{ $kcWhyIcons[$i % count($kcWhyIcons)] }}"></i></span>
                        <h3>{{ $title }}</h3>
                        @if ($kcPoints !== [])
                            <ul class="kc-about-points">
                                @foreach ($kcPoints as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@if ($texts->get('about_story') !== '')
    <section id="historia" class="kc-sec" aria-labelledby="kc-about-story">
        <div class="kc-wrap kc-about-story">
            <div>
                @if ($texts->get('about_story_kicker') !== '')<p class="kc-eyebrow kc-hand">{{ $texts->get('about_story_kicker') }}</p>@endif
                <h2 id="kc-about-story" class="kc-h2">{{ $texts->get('about_story_title') }}</h2>
            </div>
            <div class="kc-about-story-text">
                @foreach ($texts->paragraphs('about_story') as $p)
                    <p>{{ $p }}</p>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($kcPageContent !== '')
    <section class="kc-sec kc-sec-cream" aria-label="Więcej o nas">
        <div class="kc-wrap"><div class="kc-prose prose max-w-none" style="max-width:52rem">{!! $kcPageContent !!}</div></div>
    </section>
@endif

<section id="dolacz" class="kc-sec kc-dark" aria-labelledby="kc-about-join">
    <div class="kc-wrap kc-page-cta-in">
        <div>
            <h2 id="kc-about-join" class="kc-h2">{{ $texts->get('about_join_title') }}</h2>
            @foreach ($texts->paragraphs('about_join_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
        </div>
        <div class="kc-actions">
            @if ($kcContactUrl && $texts->get('about_join_button') !== '')
                <a href="{{ $kcContactUrl }}" class="kc-btn kc-btn-sun"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>{{ $texts->get('about_join_button') }}</a>
            @endif
            @foreach ($kcSocial as [$sUrl, $sIcon, $sLabel])
                <a href="{{ $sUrl }}" class="kc-net-pill" target="_blank" rel="noopener">
                    <span class="kc-net kc-net-{{ $kcNet($sIcon) }}" aria-hidden="true"><i class="{{ $sIcon }}"></i></span>
                    <span>{{ $sLabel }}</span><span class="kc-sr"> (otwiera się w nowej karcie)</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
