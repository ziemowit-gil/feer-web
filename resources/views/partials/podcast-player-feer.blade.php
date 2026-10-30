{{--
    Odtwarzacz podcastu w stylu FEER (zwykły CSS, klasy pc-*; niezależny od zbudowanych klas Tailwinda).
    Wymagane: $podcast, $canPlay. Opcjonalnie: $compact (bez tytułu — gdy tytuł jest tuż obok).
    Dostępność: natywny <audio> pod spodem, suwak to <input type="range"> (strzałki, Home/End), przyciski z etykietami,
    czas odtwarzania podawany tekstem; płasko, bez ramek, kontrast ≥ 4,5:1.
--}}
@php $compact = $compact ?? false; $pid = 'pc-'.$podcast->id.'-'.\Illuminate\Support\Str::random(4); @endphp
@once
    <style>
        .pc { border-radius: .75rem; background: #f3f4f6; padding: 1.25rem; color: #1d1d1a; }
        .pc-title { margin: 0 0 .9rem; font-weight: 700; font-size: 1rem; }
        .pc-row { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }
        .pc-btn { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; min-height: 2.75rem; min-width: 2.75rem; border: 0; border-radius: .6rem; background: #e3e7ee; color: #1d1d1a; font-size: .875rem; font-weight: 700; padding: 0 .75rem; cursor: pointer; }
        .pc-btn:hover { background: #d5dbe5; }
        .pc-play { min-width: 3.5rem; min-height: 3.5rem; border-radius: 9999px; background: #1752bf; color: #fff; font-size: 1.25rem; padding: 0; }
        .pc-play:hover { background: #1d1d1a; }
        .pc-btn:focus-visible, .pc-range:focus-visible, .pc-link:focus-visible { outline: 3px solid #1752bf; outline-offset: 2px; }
        .pc-time { font-variant-numeric: tabular-nums; font-size: .9rem; font-weight: 600; margin-left: auto; }
        .pc-range { width: 100%; margin: 1rem 0 0; accent-color: #1752bf; height: 1.5rem; cursor: pointer; }
        .pc-lock { border-radius: .75rem; background: #e8f0ff; padding: 1.25rem; color: #1d1d1a; }
        .pc-lock h3 { margin: 0 0 .35rem; font-size: 1.05rem; font-weight: 800; }
        .pc-lock p { margin: 0 0 1rem; line-height: 1.6; }
        .pc-link { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; border-radius: .6rem; background: #1752bf; color: #fff; font-weight: 700; padding: 0 1.1rem; text-decoration: none; }
        .pc-link:hover { background: #1d1d1a; }
    </style>
@endonce

@if ($canPlay)
    <div class="pc" role="group" aria-label="Odtwarzacz: {{ $podcast->title }}"
        x-data="{
            playing: false, current: 0, duration: 0, rate: 1,
            get a() { return this.$refs.audio },
            toggle() { this.a.paused ? this.a.play() : this.a.pause() },
            skip(s) { this.a.currentTime = Math.max(0, Math.min(this.duration || 0, this.a.currentTime + s)) },
            seek(v) { this.a.currentTime = Number(v) },
            cycle() { const r = [1, 1.25, 1.5, 2]; this.rate = r[(r.indexOf(this.rate) + 1) % r.length]; this.a.playbackRate = this.rate },
            fmt(s) { if (!s || isNaN(s)) return '0:00'; s = Math.floor(s); const h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = String(s % 60).padStart(2, '0'); return h ? h + ':' + String(m).padStart(2, '0') + ':' + x : m + ':' + x },
            spoken(s) { s = Math.floor(s || 0); const m = Math.floor(s / 60), x = s % 60; return m + ' min ' + x + ' s' }
        }">
        <audio x-ref="audio" preload="metadata" src="{{ route('podcasts.stream', $podcast) }}"
            @loadedmetadata="duration = $el.duration" @timeupdate="current = $el.currentTime"
            @play="playing = true" @pause="playing = false" @ended="playing = false"></audio>

        @unless ($compact)<p class="pc-title">{{ $podcast->title }}</p>@endunless

        <div class="pc-row">
            <button type="button" class="pc-btn pc-play" @click="toggle()" :aria-label="playing ? 'Pauza' : 'Odtwórz odcinek'" aria-label="Odtwórz odcinek">
                <i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-play'" aria-hidden="true"></i>
            </button>
            <button type="button" class="pc-btn" @click="skip(-15)" aria-label="Cofnij o 15 sekund"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> 15 s</button>
            <button type="button" class="pc-btn" @click="skip(30)" aria-label="Przewiń o 30 sekund do przodu">30 s <i class="fa-solid fa-rotate-right" aria-hidden="true"></i></button>
            <button type="button" class="pc-btn" @click="cycle()" :aria-label="'Prędkość odtwarzania: ' + rate + 'x. Zmień'" aria-label="Prędkość odtwarzania: 1x. Zmień"><span x-text="rate + '×'">1×</span></button>
            <span class="pc-time" aria-hidden="true"><span x-text="fmt(current)">0:00</span> / <span x-text="fmt(duration)">0:00</span></span>
        </div>

        <label for="{{ $pid }}-seek" class="sr-only">Pozycja odtwarzania</label>
        <input id="{{ $pid }}-seek" type="range" class="pc-range" min="0" step="1" value="0"
            :max="Math.floor(duration) || 0" :value="Math.floor(current)" @input="seek($event.target.value)"
            :aria-valuetext="spoken(current) + ' z ' + spoken(duration)">
    </div>
@else
    <div class="pc-lock" role="group" aria-labelledby="{{ $pid }}-lock">
        <h3 id="{{ $pid }}-lock"><i class="fa-solid fa-lock" aria-hidden="true"></i> Odcinek dla subskrybentów</h3>
        @auth
            <p>Twoje konto nie ma jeszcze dostępu do tego odcinka. Napisz do nas, podpowiemy, jak go uzyskać.</p>
            <a href="{{ url('/kontakt') }}" class="pc-link">Napisz do nas <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        @else
            <p>Zaloguj się, aby posłuchać. Jeśli masz dostęp w ramach subskrypcji, odcinek od razu się odtworzy.</p>
            <a href="{{ route('login') }}" class="pc-link">Zaloguj się, aby słuchać <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        @endauth
    </div>
@endif
