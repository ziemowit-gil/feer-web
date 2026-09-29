{{-- Sekcja formularza strony: typ "faq" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- FAQ — pytania i odpowiedzi --}}
<div data-faq-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'faq' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">FAQ — pytania i odpowiedzi</p>

    <div>
        <label for="faq_intro" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="faq_intro" name="faq_intro" rows="3" placeholder="Krótkie wprowadzenie nad listą pytań."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('faq_intro', $page->faq_intro) }}</textarea>
        <p class="mt-1 text-xs text-muted">Wyświetli się nad listą pytań. Pole „Treść" (edytor) możesz zostawić puste.</p>
        @error('faq_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div data-repeater>
        <p class="mb-1 text-sm font-bold">Pytania i odpowiedzi</p>
        <p class="mb-3 text-xs text-muted">Każda para tworzy zwijany element (akordeon) na stronie. W odpowiedzi możesz dodać <strong>linki</strong> (przycisk łańcucha w edytorze) oraz pogrubienia i listy. Puste wiersze są pomijane; kolejność odpowiada kolejności na liście.</p>

        @php $faqLinkPages = \App\Models\Page::where('is_published', true)->whereNotIn('type', ['internal', 'internal_hub'])->orderBy('title')->get(['slug', 'title']); @endphp
        @if ($faqLinkPages->isNotEmpty())
            <div class="mb-3">
                <label class="sr-only" for="faq-page-link">Wstaw link do podstrony</label>
                <select id="faq-page-link" data-faq-page-link class="rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                    <option value="">Wstaw link do podstrony w serwisie&hellip;</option>
                    @foreach ($faqLinkPages as $p)
                        <option value="/{{ $p->slug }}" data-title="{{ $p->title }}">{{ $p->title }}</option>
                    @endforeach
                </select>
                <span class="ml-2 text-xs text-muted">Najpierw kliknij w treść odpowiedzi, potem wybierz stronę.</span>
            </div>
        @endif
        <div data-repeater-rows class="space-y-3">
            @foreach ($faqItems as $i => $row)
                <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <input type="text" name="faq_items[{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="Pytanie" aria-label="Pytanie {{ $i + 1 }}"
                        class="w-full rounded border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand">
                    <textarea name="faq_items[{{ $i }}][answer]" rows="3" placeholder="Odpowiedź" aria-label="Odpowiedź {{ $i + 1 }}" data-faq-answer
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $row['answer'] ?? '' }}</textarea>
                    <div class="text-right">
                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-2 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń pytanie {{ $i + 1 }}"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie</button>
        <template data-repeater-template>
            <div data-repeater-row class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <input type="text" name="faq_items[__INDEX__][question]" placeholder="Pytanie" aria-label="Pytanie"
                    class="w-full rounded border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand">
                <textarea name="faq_items[__INDEX__][answer]" rows="3" placeholder="Odpowiedź" aria-label="Odpowiedź" data-faq-answer
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></textarea>
                <div class="text-right">
                    <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-2 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń pytanie"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                </div>
            </div>
        </template>
    </div>
</div>
