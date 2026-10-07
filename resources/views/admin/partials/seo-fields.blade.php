{{--
    Pola SEO per-treść. Wymaga zmiennej $model z polami meta_title/meta_description (opcjonalnie title, slug, publicUrl()).
    Układ: podgląd wyniku wyszukiwania na żywo, dwa pola z licznikiem, paskiem długości i słowną oceną (nie tylko kolorem).
    Wartość pusta = tytuł i opis generowane automatycznie z treści.
--}}
@php
    $seoTitleFallback = (string) ($model->title ?? '');
    $seoUrl = '';
    try {
        $seoUrl = method_exists($model, 'publicUrl') && $model->exists ? (string) $model->publicUrl() : '';
    } catch (\Throwable $e) {
        $seoUrl = '';
    }
    $seoHost = parse_url($seoUrl ?: config('app.url'), PHP_URL_HOST) ?: request()->getHost();
    $seoPath = $seoUrl ? ltrim((string) parse_url($seoUrl, PHP_URL_PATH), '/') : (string) ($model->slug ?? '');
@endphp
<div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 sm:p-8" x-data="{
    metaTitle: @js((string) old('meta_title', $model->meta_title ?? '')),
    metaDesc:  @js((string) old('meta_description', $model->meta_description ?? '')),
    fallbackTitle: @js($seoTitleFallback),
    rate(n, min, max) { return n === 0 ? 'empty' : (n < min ? 'short' : (n > max ? 'long' : 'ok')); },
    label(r) { return { empty: 'Puste — zostanie wygenerowane', short: 'Za krótki', ok: 'Dobra długość', long: 'Za długi' }[r]; },
    tone(r) { return { empty: 'text-muted', short: 'text-amber-900', ok: 'text-green-800', long: 'text-red-700' }[r]; },
    bar(r) { return { empty: 'bg-gray-300', short: 'bg-amber-600', ok: 'bg-green-700', long: 'bg-red-700' }[r]; },
    icon(r) { return { empty: 'fa-circle', short: 'fa-triangle-exclamation', ok: 'fa-circle-check', long: 'fa-circle-exclamation' }[r]; },
    trunc(t, n) { return t.length > n ? t.slice(0, n - 1).trimEnd() + '…' : t; },
}">
    <div>
        <h2 class="text-lg font-bold text-ink"><i class="fa-solid fa-magnifying-glass mr-1.5 text-muted" aria-hidden="true"></i>SEO <span class="text-sm font-normal text-muted">(opcjonalnie)</span></h2>
        <p class="mt-1 text-sm text-muted">Nadpisuje tytuł i opis w wynikach wyszukiwania oraz w podglądzie linku. Puste pola = tytuł i opis powstają automatycznie z treści.</p>
    </div>

    {{-- Podgląd wyniku wyszukiwania --}}
    <section aria-labelledby="seo-preview-h" class="rounded-lg bg-gray-50 p-5">
        <h3 id="seo-preview-h" class="mb-3 text-xs font-bold uppercase tracking-widest text-muted">Tak może wyglądać w wyszukiwarce</h3>
        <div class="max-w-2xl" aria-live="off">
            <p class="truncate text-sm text-ink">{{ $seoHost }}<span class="text-muted"> › {{ $seoPath }}</span></p>
            <p class="mt-0.5 text-xl leading-snug" style="color:#1a0dab" x-text="trunc(metaTitle.trim() || fallbackTitle || 'Tytuł strony', 60)"></p>
            <p class="mt-1 text-sm leading-snug text-gray-700" x-text="metaDesc.trim() ? trunc(metaDesc.trim(), 160) : 'Opis zostanie wygenerowany automatycznie z początku treści strony.'"></p>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            <div class="mb-1.5 flex items-baseline justify-between gap-3">
                <label for="meta_title" class="text-[15px] font-bold text-ink">Tytuł SEO</label>
                <span class="text-xs font-bold tabular-nums text-ink"><span x-text="metaTitle.length"></span>/60</span>
            </div>
            <input type="text" id="meta_title" name="meta_title" maxlength="255" value="{{ old('meta_title', $model->meta_title ?? '') }}" x-model="metaTitle"
                @if ($seoTitleFallback !== '') placeholder="{{ $seoTitleFallback }}" @endif aria-describedby="meta_title_hint"
                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200" aria-hidden="true">
                <div class="h-full rounded-full transition-all" :class="bar(rate(metaTitle.length, 45, 60))" :style="'width:' + Math.min(100, Math.round(metaTitle.length / 60 * 100)) + '%'"></div>
            </div>
            <p id="meta_title_hint" class="mt-2 flex flex-wrap items-center gap-x-2 text-sm" aria-live="polite">
                <span class="inline-flex items-center gap-1.5 font-bold" :class="tone(rate(metaTitle.length, 45, 60))"><i class="fa-solid" :class="icon(rate(metaTitle.length, 45, 60))" aria-hidden="true"></i><span x-text="label(rate(metaTitle.length, 45, 60))"></span></span>
                <span class="text-muted">· zalecane 45–60 znaków</span>
            </p>
            @error('meta_title') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="mb-1.5 flex items-baseline justify-between gap-3">
                <label for="meta_description" class="text-[15px] font-bold text-ink">Opis SEO</label>
                <span class="text-xs font-bold tabular-nums text-ink"><span x-text="metaDesc.length"></span>/160</span>
            </div>
            <textarea id="meta_description" name="meta_description" rows="3" maxlength="300" x-model="metaDesc" aria-describedby="meta_desc_hint"
                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">{{ old('meta_description', $model->meta_description ?? '') }}</textarea>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200" aria-hidden="true">
                <div class="h-full rounded-full transition-all" :class="bar(rate(metaDesc.length, 120, 160))" :style="'width:' + Math.min(100, Math.round(metaDesc.length / 160 * 100)) + '%'"></div>
            </div>
            <p id="meta_desc_hint" class="mt-2 flex flex-wrap items-center gap-x-2 text-sm" aria-live="polite">
                <span class="inline-flex items-center gap-1.5 font-bold" :class="tone(rate(metaDesc.length, 120, 160))"><i class="fa-solid" :class="icon(rate(metaDesc.length, 120, 160))" aria-hidden="true"></i><span x-text="label(rate(metaDesc.length, 120, 160))"></span></span>
                <span class="text-muted">· zalecane 120–160 znaków</span>
            </p>
            @error('meta_description') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
