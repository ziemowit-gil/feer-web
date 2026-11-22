{{--
    Aktualności tuż pod sliderem (HomeController::kreatywniHome, $newsItems — cztery najnowsze tej witryny): miętowy pas,
    równe karty po dwie w rzędzie (na telefonie jedna pod drugą): zdjęcie, data jako płaski kafelek kalendarza, tytuł,
    zajawka. Cała karta jest linkiem; zdjęcia dekoracyjne (nazwę niesie tytuł), pełna data dla czytników w <time>.
    Bez aktualności albo przy wyłączonym module sekcji nie ma; przełącznik w Ustawienia → Szablon → Sekcje strony głównej.
--}}
@if (($newsItems ?? collect())->isNotEmpty())
@php
    $kcExcerpt = fn ($item) => trim(strip_tags((string) ($item->excerpt ?: $item->meta_description)));
@endphp
<section id="aktualnosci" class="kc-sec kc-sec-mint kc-news-sec" aria-labelledby="kc-news-title">
    <div class="kc-wrap">
        <div class="kc-news-head">
            <div>
                @if ($texts->get('news_kicker') !== '')
                    <p class="kc-eyebrow kc-hand">{{ $texts->get('news_kicker') }}</p>
                @endif
                <h2 id="kc-news-title" class="kc-h2">{{ $texts->get('news_title') }}</h2>
                @foreach ($texts->paragraphs('news_text') as $p)
                    <p class="kc-lead">{{ $p }}</p>
                @endforeach
            </div>
            @if (\Illuminate\Support\Facades\Route::has('news.index'))
                <a href="{{ route('news.index') }}" class="kc-btn kc-btn-ghost kc-news-all">{{ $texts->get('news_button') }} <span aria-hidden="true">→</span></a>
            @endif
        </div>

        <ul class="kc-news-layout">
            @foreach ($newsItems as $item)
                @php $kcImage = $item->imageUrlOrDefault(); @endphp
                <li class="kc-news-item">
                    <a href="{{ route('news.show', $item) }}" class="kc-news-feature">
                        <span class="kc-news-photo" aria-hidden="true">
                            @if ($kcImage)
                                <img src="{{ $kcImage }}" alt="" loading="lazy">
                            @else
                                <i class="fa-solid fa-newspaper"></i>
                            @endif
                        </span>
                        @if ($item->published_at)
                            <time class="kc-news-date" datetime="{{ $item->published_at->toDateString() }}">
                                <span class="kc-news-day" aria-hidden="true">{{ $item->published_at->format('j') }}</span>
                                <span class="kc-news-mon" aria-hidden="true">{{ $item->published_at->translatedFormat('M') }}</span>
                                <span class="kc-sr">{{ $item->published_at->translatedFormat('j F Y') }}</span>
                            </time>
                        @endif
                        <span class="kc-news-body">
                            <h3 class="kc-news-name">{{ $item->title }}</h3>
                            @if ($kcExcerpt($item) !== '')
                                <span class="kc-news-text">{{ \Illuminate\Support\Str::limit($kcExcerpt($item), 160) }}</span>
                            @endif
                            <span class="kc-child-more" aria-hidden="true">Czytaj dalej →</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
