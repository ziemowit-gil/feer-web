{{--
    Aktualności: trzy najnowsze aktualności tej witryny (HomeController::kreatywniHome, $newsItems) w kartach jak działania
    (cała karta jest linkiem, zdjęcie dekoracyjne — nazwę niesie tytuł) i przycisk do pełnej listy. Bez aktualności albo
    przy wyłączonym module sekcji nie ma; przełącznik w Ustawienia → Szablon → Sekcje strony głównej.
--}}
@if (($newsItems ?? collect())->isNotEmpty())
<section id="aktualnosci" class="kc-sec" aria-labelledby="kc-news-title">
    <div class="kc-wrap">
        <div class="kc-head">
            <h2 id="kc-news-title" class="kc-h2">{{ $texts->get('news_title') }}</h2>
            @foreach ($texts->paragraphs('news_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
        </div>
        <ul class="kc-grid kc-grid-3 kc-projects kc-news">
            @foreach ($newsItems as $item)
                @php
                    $kcImage = $item->imageUrlOrDefault();
                    $kcExcerpt = trim(strip_tags((string) ($item->excerpt ?: $item->meta_description)));
                @endphp
                <li>
                    <a href="{{ route('news.show', $item) }}" class="kc-card kc-child-card kc-project-card">
                        <span class="kc-project-img" aria-hidden="true">
                            @if ($kcImage)
                                <img src="{{ $kcImage }}" alt="" loading="lazy">
                            @else
                                <i class="fa-solid fa-newspaper"></i>
                            @endif
                        </span>
                        <span class="kc-project-body">
                            @if ($item->published_at)
                                <time class="kc-child-text" datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->translatedFormat('j F Y') }}</time>
                            @endif
                            <h3 class="kc-child-title">{{ $item->title }}</h3>
                            @if ($kcExcerpt !== '')<span class="kc-child-text">{{ \Illuminate\Support\Str::limit($kcExcerpt, 180) }}</span>@endif
                            <span class="kc-child-more" aria-hidden="true">Czytaj dalej →</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if (\Illuminate\Support\Facades\Route::has('news.index'))
            <div class="kc-more">
                <a href="{{ route('news.index') }}" class="kc-btn kc-btn-ghost">{{ $texts->get('news_button') }}</a>
            </div>
        @endif
    </div>
</section>
@endif
