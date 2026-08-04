{{-- Pola SEO per-treść. Wymaga zmiennej $model z polami meta_title/meta_description. --}}
<div class="rounded-lg border border-gray-200 bg-white p-6" x-data="{
    metaTitle: '{{ addslashes(old('meta_title', $model->meta_title ?? '')) }}',
    metaDesc:  '{{ addslashes(old('meta_description', $model->meta_description ?? '')) }}',
    titleColor(n)  { return n === 0 ? 'text-muted' : (n >= 45 && n <= 60 ? 'text-green-600' : (n < 45 ? 'text-amber-600' : 'text-red-600')); },
    descColor(n)   { return n === 0 ? 'text-muted' : (n >= 120 && n <= 160 ? 'text-green-600' : (n < 120 ? 'text-amber-600' : 'text-red-600')); },
}">
    <p class="mb-1 text-sm font-bold text-ink"><i class="fa-solid fa-magnifying-glass text-muted" aria-hidden="true"></i> SEO <span class="font-normal text-muted">(opcjonalnie)</span></p>
    <p class="mb-3 text-xs text-muted">Nadpisuje tytuł i opis w wynikach wyszukiwania oraz podglądzie linku. Puste = generowane automatycznie z treści.</p>

    <div class="mb-3">
        <div class="mb-1 flex items-baseline justify-between">
            <label for="meta_title" class="text-sm font-bold">Tytuł SEO</label>
            <span class="text-xs font-bold tabular-nums" :class="titleColor(metaTitle.length)">
                <span x-text="metaTitle.length"></span>/60
            </span>
        </div>
        <input type="text" id="meta_title" name="meta_title" maxlength="255"
            value="{{ old('meta_title', $model->meta_title ?? '') }}"
            x-model="metaTitle"
            class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
        <p class="mt-1 text-xs text-muted">Zalecana długość: 45–60 znaków.
            <span x-show="metaTitle.length > 60" x-cloak class="font-bold text-red-600">Za długi — wyszukiwarki obetną tytuł.</span>
        </p>
    </div>

    <div>
        <div class="mb-1 flex items-baseline justify-between">
            <label for="meta_description" class="text-sm font-bold">Opis SEO</label>
            <span class="text-xs font-bold tabular-nums" :class="descColor(metaDesc.length)">
                <span x-text="metaDesc.length"></span>/160
            </span>
        </div>
        <textarea id="meta_description" name="meta_description" rows="2" maxlength="300"
            x-model="metaDesc"
            class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">{{ old('meta_description', $model->meta_description ?? '') }}</textarea>
        <div class="mt-1 flex items-center gap-2">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-200">
                <div class="h-full rounded-full transition-all"
                    :style="'width:' + Math.min(100, Math.round(metaDesc.length / 160 * 100)) + '%'"
                    :class="descColor(metaDesc.length).replace('text-', 'bg-')"></div>
            </div>
            <span class="text-xs text-muted">zalecane 120–160 znaków</span>
        </div>
    </div>
</div>
