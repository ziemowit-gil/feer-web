{{--
    Odtwarzacz podcastu (Alpine.js).
    Wymagane $podcast, $canPlay.
    Jeśli $canPlay=false i $podcast->is_premium — pokazuje blokadę.
--}}

@if ($canPlay)
    <div
        x-data="{
            playing: false,
            loading: false,
            currentTime: 0,
            duration: 0,
            progress: 0,
            rate: 1,
            audio: null,

            init() {
                this.audio = new Audio('{{ route('podcasts.stream', $podcast) }}');
                this.audio.preload = 'metadata';
                this.audio.addEventListener('loadedmetadata', () => { this.duration = this.audio.duration; });
                this.audio.addEventListener('timeupdate', () => {
                    this.currentTime = this.audio.currentTime;
                    this.progress = this.duration ? (this.currentTime / this.duration) * 100 : 0;
                });
                this.audio.addEventListener('ended', () => { this.playing = false; });
                this.audio.addEventListener('waiting', () => { this.loading = true; });
                this.audio.addEventListener('playing', () => { this.loading = false; });
            },

            toggle() {
                if (this.audio.paused) {
                    this.audio.play();
                    this.playing = true;
                } else {
                    this.audio.pause();
                    this.playing = false;
                }
            },

            seek(e) {
                if (!this.duration) return;
                const rect = e.currentTarget.getBoundingClientRect();
                const ratio = (e.clientX - rect.left) / rect.width;
                this.audio.currentTime = ratio * this.duration;
            },

            skip(sec) {
                this.audio.currentTime = Math.max(0, Math.min(this.duration, this.audio.currentTime + sec));
            },

            cycleRate() {
                const rates = [1, 1.25, 1.5, 2];
                const idx = rates.indexOf(this.rate);
                this.rate = rates[(idx + 1) % rates.length];
                this.audio.playbackRate = this.rate;
            },

            fmt(sec) {
                if (!sec || isNaN(sec)) return '0:00';
                const m = Math.floor(sec / 60);
                const s = Math.floor(sec % 60).toString().padStart(2, '0');
                return m + ':' + s;
            }
        }"
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
        role="region"
        aria-label="Odtwarzacz podcastu">

        {{-- Tytuł odcinka --}}
        <p class="mb-4 font-semibold text-ink">{{ $podcast->title }}</p>

        {{-- Pasek postępu --}}
        <div class="mb-3">
            <div
                class="relative h-2 cursor-pointer rounded-full bg-gray-200"
                @click="seek($event)"
                role="slider"
                :aria-valuenow="Math.round(currentTime)"
                :aria-valuemax="Math.round(duration)"
                aria-valuemin="0"
                aria-label="Postęp odtwarzania">
                <div class="h-full rounded-full bg-brand transition-all" :style="`width:${progress}%`"></div>
            </div>
            <div class="mt-1 flex justify-between text-xs text-muted">
                <span x-text="fmt(currentTime)">0:00</span>
                <span x-text="fmt(duration)">0:00</span>
            </div>
        </div>

        {{-- Przyciski sterowania --}}
        <div class="flex items-center justify-center gap-3">
            <button
                type="button"
                @click="skip(-10)"
                class="rounded-full p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-2 focus-visible:outline-brand"
                aria-label="Cofnij 10 sekund">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </button>

            <button
                type="button"
                @click="toggle()"
                :aria-pressed="playing.toString()"
                :aria-label="playing ? 'Pauza' : 'Odtwórz'"
                class="flex h-12 w-12 items-center justify-center rounded-full bg-brand text-white shadow hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                <svg x-show="!playing && !loading" class="h-6 w-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.84A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.27l9.344-5.891a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z"/>
                </svg>
                <svg x-show="playing && !loading" x-cloak class="h-6 w-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.75 3a.75.75 0 0 0-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 0 0 .75-.75V3.75A.75.75 0 0 0 7.25 3h-1.5ZM12.75 3a.75.75 0 0 0-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 0 0 .75-.75V3.75a.75.75 0 0 0-.75-.75h-1.5Z"/>
                </svg>
                <svg x-show="loading" x-cloak class="h-5 w-5 animate-spin" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </button>

            <button
                type="button"
                @click="skip(30)"
                class="rounded-full p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-2 focus-visible:outline-brand"
                aria-label="Przeskocz 30 sekund">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811V8.69ZM12.75 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061a1.125 1.125 0 0 1-1.683-.977V8.69Z"/>
                </svg>
            </button>

            <button
                type="button"
                @click="cycleRate()"
                class="min-w-[3rem] rounded border border-gray-300 px-2 py-1 text-xs font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-brand"
                aria-label="Prędkość odtwarzania">
                <span x-text="rate + 'x'">1x</span>
            </button>
        </div>
    </div>
@else
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-center" role="region" aria-label="Dostęp ograniczony">
        <svg class="mx-auto mb-3 h-8 w-8 text-amber-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 1a.75.75 0 0 1 .67.415l2.302 4.667 5.145.748a.75.75 0 0 1 .416 1.279l-3.724 3.629.879 5.122a.75.75 0 0 1-1.088.79L10 15.547l-4.6 2.419a.75.75 0 0 1-1.088-.79l.879-5.122L1.467 8.11a.75.75 0 0 1 .416-1.279l5.145-.748L9.33 1.415A.75.75 0 0 1 10 1Z" clip-rule="evenodd"/>
        </svg>
        <p class="mb-1 font-semibold text-amber-800">Podcast premium</p>
        <p class="mb-4 text-sm text-amber-700">Ten odcinek jest dostępny tylko dla subskrybentów lub po zakupie dostępu jednorazowego.</p>
        @auth
            <a href="{{ route('podcasts.index') }}"
                class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                Kup dostęp
            </a>
        @else
            <a href="{{ route('login') }}"
                class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                Zaloguj się, aby słuchać
            </a>
        @endauth
    </div>
@endif
