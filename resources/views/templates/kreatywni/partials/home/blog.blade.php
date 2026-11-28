{{--
    Blog na stronie głównej (HomeController::kreatywniHome, $blogItems — cztery najnowsze wpisy modułu Blog): kremowy pas
    z kartami jak w aktualnościach (po dwie w rzędzie, na telefonie jedna pod drugą): zdjęcie, data jako płaski kafelek
    kalendarza, tytuł, zajawka. Cała karta jest linkiem, zdjęcie dekoracyjne. Bez wpisów albo przy wyłączonym module
    sekcji nie ma; przełącznik i teksty w Ustawienia → Szablon → Sekcje strony głównej.
--}}
@if (($blogItems ?? collect())->isNotEmpty())
<section id="blog" class="kc-sec kc-sec-cream kc-news-sec kc-blog-sec" aria-labelledby="kc-blog-title">
    <div class="kc-wrap">
        <div class="kc-news-head">
            <div>
                @if ($texts->get('blog_kicker') !== '')
                    <p class="kc-eyebrow kc-hand">{{ $texts->get('blog_kicker') }}</p>
                @endif
                <h2 id="kc-blog-title" class="kc-h2">{{ $texts->get('blog_title') }}</h2>
                @foreach ($texts->paragraphs('blog_text') as $p)
                    <p class="kc-lead">{{ $p }}</p>
                @endforeach
            </div>
            @if (\Illuminate\Support\Facades\Route::has('blog.index'))
                <a href="{{ route('blog.index') }}" class="kc-btn kc-btn-ghost kc-news-all">{{ $texts->get('blog_button') }} <span aria-hidden="true">→</span></a>
            @endif
        </div>

        <ul class="kc-news-layout">
            @foreach ($blogItems as $kcPost)
                @php
                    $kcImage = $kcPost->imageUrl();
                    $kcDate = $kcPost->publishedDate();
                    $kcExcerpt = trim((string) $kcPost->excerpt) ?: $kcPost->metaDescription();
                @endphp
                <li class="kc-news-item">
                    <a href="{{ route('blog.show', $kcPost) }}" class="kc-news-feature">
                        <span class="kc-news-photo" aria-hidden="true">
                            @if ($kcImage)
                                <img src="{{ $kcImage }}" alt="" loading="lazy">
                            @else
                                <i class="fa-solid fa-feather-pointed"></i>
                            @endif
                        </span>
                        @if ($kcDate)
                            <time class="kc-news-date" datetime="{{ $kcDate->toDateString() }}">
                                <span class="kc-news-day" aria-hidden="true">{{ $kcDate->format('j') }}</span>
                                <span class="kc-news-mon" aria-hidden="true">{{ $kcDate->locale('pl')->translatedFormat('M') }}</span>
                                <span class="kc-sr">{{ $kcDate->locale('pl')->translatedFormat('j F Y') }}</span>
                            </time>
                        @endif
                        <span class="kc-news-body">
                            <h3 class="kc-news-name">{{ $kcPost->title }}</h3>
                            @if ($kcExcerpt !== '')
                                <span class="kc-news-text">{{ \Illuminate\Support\Str::limit($kcExcerpt, 160) }}</span>
                            @endif
                            <span class="kc-news-more" aria-hidden="true">Czytaj wpis <span>→</span></span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
