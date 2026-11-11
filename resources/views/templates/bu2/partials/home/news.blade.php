{{--
    Aktualności na stronie głównej BU2: karuzela kart (3 widoczne na komputerze, przewijana strzałkami i dotykiem),
    na zdjęciu niebieski kwadrat z datą (dzień i skrót miesiąca; czytnik słyszy pełną datę), pod zdjęciem tytuł.
    Każda karta jest zwykłym linkiem, więc z klawiatury przechodzi się po nich Tabem (lista przewija się sama).
--}}
@php
    $newsItems = collect($newsItems ?? []);
    $bu2Months = ['STY', 'LUT', 'MAR', 'KWI', 'MAJ', 'CZE', 'LIP', 'SIE', 'WRZ', 'PAŹ', 'LIS', 'GRU'];
@endphp
@if ($newsItems->isNotEmpty())
    <section class="bu2-sec" aria-labelledby="bu2-news-h">
        <div class="bu2-wrap">
            <h2 id="bu2-news-h" class="bu2-sec-title">Aktualności</h2>
            <div class="bu2-news"
                 x-data="{
                    atStart: true, atEnd: false,
                    upd() { const el = this.$refs.track; this.atStart = el.scrollLeft <= 2; this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 2 },
                    go(d) { const el = this.$refs.track; const card = el.querySelector('li'); const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        el.scrollBy({ left: d * (card ? card.offsetWidth + 16 : el.clientWidth), behavior: calm ? 'auto' : 'smooth' }) },
                 }"
                 x-init="$nextTick(() => upd())" @resize.window.debounce.150ms="upd()">
                <button type="button" class="bu2-news-arrow bu2-news-arrow--prev" @click="go(-1)" :disabled="atStart" aria-controls="bu2-news-track" aria-label="Poprzednie aktualności">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.4 4.6 8 12l7.4 7.4L14 20.8 5.2 12 14 3.2z"/></svg>
                </button>
                <ul id="bu2-news-track" class="bu2-news-track" role="list" x-ref="track" @scroll.debounce.60ms="upd()">
                    @foreach ($newsItems as $item)
                        @php $d = $item->published_at; $img = $item->imageUrlOrDefault(); @endphp
                        <li>
                            <a class="bu2-news-card" href="{{ route('news.show', $item) }}">
                                <span class="bu2-news-img">
                                    @if ($img)<img src="{{ $img }}" alt="" loading="lazy">@endif
                                    @if ($d)
                                        <span class="bu2-news-date" aria-hidden="true"><b>{{ $d->format('d') }}</b><span>{{ $bu2Months[$d->month - 1] }}</span></span>
                                    @endif
                                </span>
                                <h3>{{ $item->title }}@if ($d)<span class="bu2-sr">, <time datetime="{{ $d->toDateString() }}">{{ $d->translatedFormat('j F Y') }}</time></span>@endif</h3>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <button type="button" class="bu2-news-arrow bu2-news-arrow--next" @click="go(1)" :disabled="atEnd" aria-controls="bu2-news-track" aria-label="Następne aktualności">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8.6 19.4 16 12 8.6 4.6 10 3.2l8.8 8.8-8.8 8.8z"/></svg>
                </button>
            </div>
            <p class="bu2-more"><a href="{{ route('news.index') }}">więcej aktualności</a></p>
        </div>
    </section>
@endif
