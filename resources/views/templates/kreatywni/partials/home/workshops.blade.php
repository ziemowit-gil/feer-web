{{--
    Najbliższe warsztaty na stronie głównej szablonu „kreatywni” (sekcja „workshops” z KreatywniTexts): trzy najbliższe
    widoczne terminy z modułu „Warsztaty – Kreatywni” jako zwarte karty (partials/workshop-card bez opisu; nazwa prowadzi
    do karty na stronie kalendarza) i przycisk do strony typu „Kalendarz warsztatów”. Sekcji nie ma, gdy moduł jest
    wyłączony, brak tabeli, brak opublikowanej strony z kalendarzem albo brak nadchodzących terminów.
    Zmienne: $texts, $siteSettings.
--}}
@php
    $kcWsOn = $siteSettings->isModuleEnabled('workshops') && \App\Models\KreatywniWorkshop::supported();
    $kcCalPage = $kcWsOn
        ? \App\Models\Page::forCurrentSite()->where('type', 'kreatywni_workshops')->where('is_published', true)->orderBy('id')->get()->first(fn ($p) => $p->isLiveNow())
        : null;
    $kcHomeWs = $kcCalPage
        ? \App\Models\KreatywniWorkshop::forCurrentSite()->where('is_active', true)->upcoming()->orderBy('starts_at')->with('media')->limit(3)->get()
        : collect();
    $kcCalUrl = $kcCalPage?->publicUrl();
@endphp
@if ($kcHomeWs->isNotEmpty())
    @include('templates.kreatywni.partials.workshops-css')
    <section id="warsztaty" class="kc-sec kc-sec-cream" aria-labelledby="kc-ws-home-title">
        <div class="kc-wrap">
            <div class="kc-news-head">
                <div>
                    @if ($texts->get('workshops_kicker') !== '')
                        <p class="kc-eyebrow kc-hand">{{ $texts->get('workshops_kicker') }}</p>
                    @endif
                    <h2 id="kc-ws-home-title" class="kc-h2">{{ $texts->get('workshops_title') }}</h2>
                    @foreach ($texts->paragraphs('workshops_text') as $p)
                        <p class="kc-lead">{{ $p }}</p>
                    @endforeach
                </div>
                @if ($texts->get('workshops_button') !== '')
                    <a href="{{ $kcCalUrl }}" class="kc-btn kc-btn-ghost kc-news-all">{{ $texts->get('workshops_button') }} <span aria-hidden="true">→</span></a>
                @endif
            </div>
            <ul class="kc-ws-list is-3">
                @foreach ($kcHomeWs as $kcW)
                    @include('templates.kreatywni.partials.workshop-card', ['kcH' => 3, 'kcCompact' => true, 'kcLink' => $kcCalUrl.'#warsztat-'.$kcW->id, 'kcContact' => null])
                @endforeach
            </ul>
        </div>
    </section>
@endif
