{{--
    Jak wesprzeć: tekst z numerem KRS i numerem konta (gdy są w Ustawieniach) oraz karty sposobów wsparcia
    (Tytuł | opis | opcjonalny adres).
--}}
@php
    $kcWays = $texts->rows('support_items', 3);
    $kcWayIcons = ['fa-solid fa-percent', 'fa-solid fa-hand-holding-heart', 'fa-solid fa-palette', 'fa-solid fa-handshake'];
    $kcKrs = trim((string) $siteSettings->krs_number);
    $kcAccount = trim((string) $siteSettings->bank_account_number);
@endphp
@if ($kcWays !== [] || $texts->get('support_text') !== '')
<section id="wsparcie" class="kc-sec kc-sec-cream" aria-labelledby="kc-support-title">
    <div class="kc-wrap kc-support">
        <div>
            <h2 id="kc-support-title" class="kc-h2">{{ $texts->get('support_title') }}</h2>
            @foreach ($texts->paragraphs('support_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
            @if ($kcKrs !== '')
                <p class="kc-lead">Numer KRS do zeznania PIT: <span class="kc-krs">{{ $kcKrs }}</span></p>
            @endif
            @if ($kcAccount !== '')
                <p class="kc-lead">Numer konta do darowizn: <span class="kc-krs">{{ $kcAccount }}</span></p>
            @endif
        </div>
        @if ($kcWays !== [])
            <ul class="kc-grid">
                @foreach ($kcWays as $i => [$title, $text, $link])
                    @php $kcHref = \App\Support\KreatywniTexts::href($link); @endphp
                    <li class="kc-card kc-support-card">
                        <span class="kc-badge kc-badge-lime" aria-hidden="true" style="margin-bottom:0"><i class="{{ $kcWayIcons[$i % count($kcWayIcons)] }}"></i></span>
                        <div>
                            <h3>@if ($kcHref)<a href="{{ $kcHref }}">{{ $title }}</a>@else{{ $title }}@endif</h3>
                            @if ($text !== '')<p>{{ $text }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
