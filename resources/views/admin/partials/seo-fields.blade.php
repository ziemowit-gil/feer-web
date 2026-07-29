{{-- Pola SEO per-treść. Wymaga zmiennej $model z polami meta_title/meta_description. --}}
<div class="rounded-lg border border-gray-200 bg-white p-6">
    <p class="mb-1 text-sm font-bold text-ink"><i class="fa-solid fa-magnifying-glass text-muted" aria-hidden="true"></i> SEO <span class="font-normal text-muted">(opcjonalnie)</span></p>
    <p class="mb-3 text-xs text-muted">Nadpisuje tytuł i opis w wynikach wyszukiwania oraz podglądzie linku. Puste = generowane automatycznie z treści.</p>
    <div class="mb-3">
        <label for="meta_title" class="mb-1 block text-sm font-bold">Tytuł SEO</label>
        <input type="text" id="meta_title" name="meta_title" maxlength="255" value="{{ old('meta_title', $model->meta_title) }}"
            class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
    </div>
    <div>
        <label for="meta_description" class="mb-1 block text-sm font-bold">Opis SEO</label>
        <textarea id="meta_description" name="meta_description" rows="2" maxlength="300"
            class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">{{ old('meta_description', $model->meta_description) }}</textarea>
        <p class="mt-1 text-xs text-muted">Zalecana długość: do ~160 znaków.</p>
    </div>
</div>
