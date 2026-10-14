{{--
    ETR toggle button + content switcher.
    Usage: @include('partials.etr-toggle', ['etr' => $model->etr, 'title' => $model->title])
    Requires x-data="{ etr: false }" on a parent element (injected by the including view).
--}}
@if ($etr && $etr->is_enabled && ($etr->etr_summary || $etr->etr_content))
    {{-- Płasko: tło marki bez ramki, ikona zamiast emoji, ciemny przycisk; na telefonie przyciski zawijają się pod tekst. --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-x-4 gap-y-3 rounded-lg bg-brand-light px-4 py-3">
        <p class="flex items-center gap-2.5 text-sm font-bold text-ink">
            <i class="fa-solid fa-book-open text-base text-brand-dark" aria-hidden="true"></i>
            Ta strona ma wersję łatwą do czytania.
        </p>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <button type="button"
                @click="etr = !etr"
                :aria-pressed="etr.toString()"
                class="inline-flex min-h-11 items-center rounded-md bg-ink px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink"
                x-text="etr ? 'Wróć do normalnej wersji' : 'Włącz wersję ETR'">Włącz wersję ETR</button>
            <a href="{{ route('etr.about') }}"
                class="inline-flex min-h-11 items-center text-sm font-bold text-brand-dark underline underline-offset-2 hover:text-ink focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-dark">
                Co to jest ETR?
            </a>
        </div>
    </div>

    {{-- ETR view --}}
    <div x-show="etr" x-cloak
        aria-live="polite"
        class="rounded-lg bg-brand-light p-6 sm:p-8">
        <p class="mb-4 flex items-center gap-2.5 text-sm font-bold text-brand-dark">
            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            Wersja łatwa do czytania (ETR)
        </p>

        <h2 class="mb-5 text-2xl font-bold leading-snug text-ink">
            {{ $etr->etr_title ?: $title }}
        </h2>

        @if ($etr->etr_summary)
            <div class="mb-5 rounded-md bg-white p-4 text-lg leading-relaxed text-ink">
                {{ $etr->etr_summary }}
            </div>
        @endif

        @if ($etr->etr_content)
            <div class="space-y-4 text-lg leading-relaxed text-ink">
                @foreach (preg_split('/\n{2,}/', trim($etr->etr_content)) as $paragraph)
                    @if (trim($paragraph) !== '')
                        <p>{{ $paragraph }}</p>
                    @endif
                @endforeach
            </div>
        @endif

        <p class="mt-6 text-sm">
            <a href="{{ route('etr.about') }}" class="font-bold text-brand-dark underline underline-offset-2 hover:text-ink">Dowiedz się więcej o standardzie ETR <span aria-hidden="true">→</span></a>
        </p>
    </div>
@endif
