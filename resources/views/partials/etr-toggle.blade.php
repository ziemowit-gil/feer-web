{{--
    ETR toggle button + content switcher.
    Usage: @include('partials.etr-toggle', ['etr' => $model->etr, 'title' => $model->title])
    Requires x-data="{ etr: false }" on a parent element (injected by the including view).
--}}
@if ($etr && $etr->is_enabled && ($etr->etr_summary || $etr->etr_content))
    <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-xl" aria-hidden="true">📖</span>
            <span class="font-bold text-sky-800">Ta strona ma wersję łatwą do czytania.</span>
        </div>
        <div class="flex items-center gap-3">
            <button type="button"
                @click="etr = !etr"
                :aria-pressed="etr.toString()"
                class="rounded-lg border border-sky-600 px-4 py-1.5 text-sm font-bold text-sky-700 transition hover:bg-sky-600 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600"
                x-text="etr ? 'Wróć do normalnej wersji' : 'Włącz wersję ETR'">
            </button>
            <a href="{{ route('etr.about') }}"
                class="text-sm text-sky-700 underline hover:text-sky-900 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600"
                aria-label="Co to jest ETR? (otwiera stronę informacyjną)">
                Co to jest ETR?
            </a>
        </div>
    </div>

    {{-- ETR view --}}
    <div x-show="etr" x-cloak
        aria-live="polite"
        class="rounded-2xl border-2 border-sky-300 bg-sky-50 p-6 sm:p-8">
        <div class="mb-4 flex items-center gap-3">
            <span class="text-2xl" aria-hidden="true">📖</span>
            <span class="rounded-full bg-sky-200 px-3 py-1 text-xs font-bold uppercase tracking-wide text-sky-900">Wersja łatwa do czytania (ETR)</span>
        </div>

        <h2 class="mb-5 text-2xl font-bold leading-snug text-ink">
            {{ $etr->etr_title ?: $title }}
        </h2>

        @if ($etr->etr_summary)
            <div class="mb-5 rounded-xl bg-white/80 p-4 text-lg leading-relaxed text-ink">
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

        <p class="mt-6 text-sm text-muted">
            <a href="{{ route('etr.about') }}" class="underline hover:text-brand">Dowiedz się więcej o standardzie ETR →</a>
        </p>
    </div>
@endif
