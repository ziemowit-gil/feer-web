{{--
    Tłumaczenie treści na Polski Język Migowy (PJM): przycisk i odtwarzacz nad treścią.
    Użycie: @include('partials.pjm-video', ['url' => $model->pjm_video_url, 'title' => $model->title])
    Ramka z wideo powstaje dopiero po kliknięciu (x-if) — bez wczytywania YouTube/Vimeo przy każdym wejściu.
    YouTube przez youtube-nocookie, Vimeo z dnt=1 (bez ciasteczek śledzących).
    WCAG: przycisk z aria-expanded i aria-controls, ramka z tytułem, fokus wraca na przycisk po zamknięciu.
--}}
@php $pjm = \App\Support\PjmVideo::parse($url ?? null); @endphp
@if ($pjm)
    <div class="mb-6 print:hidden" x-data="{ pjmOpen: false }">
        <button type="button" x-ref="pjmToggle"
            @click="pjmOpen = !pjmOpen; if (pjmOpen) $nextTick(() => $refs.pjmPanel.focus())"
            :aria-expanded="pjmOpen.toString()" aria-controls="pjm-panel"
            class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-hands-asl-interpreting" aria-hidden="true"></i>
            <span x-text="pjmOpen ? 'Ukryj tłumaczenie na PJM' : 'Tłumaczenie na PJM'">Tłumaczenie na PJM</span>
        </button>

        <div id="pjm-panel" x-ref="pjmPanel" x-show="pjmOpen" x-cloak tabindex="-1"
             role="region" aria-label="Tłumaczenie na Polski Język Migowy"
             class="mt-3 max-w-2xl rounded-md bg-gray-100 p-3 focus:outline-none">
            <div class="relative w-full overflow-hidden rounded bg-ink" style="aspect-ratio: 16 / 9">
                <template x-if="pjmOpen">
                    @if ($pjm['type'] === 'file')
                        <video src="{{ $pjm['src'] }}" controls preload="metadata" class="absolute inset-0 h-full w-full"
                               aria-label="Tłumaczenie na PJM: {{ $title }}"></video>
                    @else
                        <iframe src="{{ $pjm['src'] }}" class="absolute inset-0 h-full w-full" loading="lazy"
                                title="Tłumaczenie na Polski Język Migowy: {{ $title }}"
                                allow="fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    @endif
                </template>
            </div>
            <div class="mt-2 flex items-center justify-between gap-3 text-sm text-ink">
                <span>Polski Język Migowy (PJM)</span>
                <button type="button" @click="pjmOpen = false; $refs.pjmToggle.focus()"
                    class="rounded font-bold text-brand-dark underline hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    Zamknij
                </button>
            </div>
        </div>
    </div>
@endif
