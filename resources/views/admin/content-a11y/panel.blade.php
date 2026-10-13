{{--
    Panel „Dostępność treści” w bocznej kolumnie edycji (strona, aktualność).
    Pierwsze sprawdzenie robi serwer przy renderowaniu; potem panel sprawdza bieżącą treść z edytora
    (TinyMCE / CKEditor zapisują ją do textarea) — przyciskiem i samoczynnie po zmianach.

    Expects: $html (zapisana treść), $field (nazwa pola treści, domyślnie content), $formId.
--}}
@php
    // Do czasu odświeżenia cache tras po wdrożeniu trasy może nie być — panel działa wtedy bez sprawdzania na żywo.
    $checkUrl = \Illuminate\Support\Facades\Route::has('admin.content-a11y.check') ? route('admin.content-a11y.check') : null;
    $issues = app(\App\Services\Content\ContentA11yChecker::class)->check($html ?? '');
    $summary = \App\Services\Content\ContentA11yChecker::summary($issues);
    $summaryText = fn ($s) => (! $s['errors'] && ! $s['warnings'])
        ? 'Bez uwag'
        : trim(($s['errors'] ? $s['errors'].' '.trans_choice('błąd|błędy|błędów', $s['errors']) : '').($s['errors'] && $s['warnings'] ? ', ' : '').($s['warnings'] ? $s['warnings'].' '.trans_choice('uwaga|uwagi|uwag', $s['warnings']) : ''));
@endphp
<section class="rounded-md bg-white p-5" aria-labelledby="ca11y-title"
    x-data="{
        loading: false,
        summary: @js($summaryText($summary)),
        failed: false,
        last: null,
        content() {
            if (window.tinymce) window.tinymce.triggerSave();
            const field = document.querySelector('#{{ $formId }} textarea[name={{ $field ?? 'content' }}]');
            return field ? field.value : '';
        },
        async run(force = false) {
            const html = this.content();
            if (! @js($checkUrl) || (! force && html === this.last)) return;
            this.last = html;
            this.loading = true; this.failed = false;
            try {
                const r = await fetch(@js($checkUrl), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ html }),
                });
                if (! r.ok) throw new Error();
                const data = await r.json();
                this.$refs.list.innerHTML = data.html;
                const s = data.summary;
                this.summary = (! s.errors && ! s.warnings) ? 'Bez uwag' : [s.errors ? s.errors + ' ' + (s.errors === 1 ? 'błąd' : (s.errors < 5 ? 'błędy' : 'błędów')) : '', s.warnings ? s.warnings + ' ' + (s.warnings === 1 ? 'uwaga' : (s.warnings < 5 ? 'uwagi' : 'uwag')) : ''].filter(Boolean).join(', ');
            } catch (e) { this.failed = true; }
            this.loading = false;
        }
    }"
    x-init="last = content(); setInterval(() => run(), 15000)">
    <div class="flex items-baseline justify-between gap-2">
        <h2 id="ca11y-title" class="text-sm font-bold uppercase tracking-widest text-muted">Dostępność treści</h2>
        <p class="text-sm font-bold text-ink" aria-live="polite"><span x-text="summary">{{ $summaryText($summary) }}</span></p>
    </div>
    <div class="mt-3 max-h-[24rem] overflow-y-auto" x-ref="list">
        @include('admin.content-a11y.issues', ['issues' => $issues])
    </div>
    <p x-show="failed" x-cloak class="mt-2 text-sm text-red-800" role="alert">Nie udało się sprawdzić treści — spróbuj ponownie.</p>
    @if ($checkUrl)
    <button type="button" @click="run(true)" :disabled="loading"
        class="mt-3 inline-flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light disabled:opacity-60">
        <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-rotate'" aria-hidden="true"></i>
        <span x-text="loading ? 'Sprawdzam…' : 'Sprawdź teraz'">Sprawdź teraz</span>
    </button>
    @endif
    <p class="mt-2 text-xs text-muted">Uwagi nie blokują zapisu.@if ($checkUrl) Lista odświeża się sama po zmianach w treści.@endif</p>
</section>
