{{--
    FAQ warsztatu (KreatywniOfferItem::faq): lista pytań i odpowiedzi z dodawaniem, usuwaniem i kolejnością (Alpine).
    Pola faq[i][q] i faq[i][a]; niepełne pary serwer pomija (KreatywniOfferItem::cleanFaq). Na podstronie warsztatu
    pytania są rozwijane, z danymi strukturalnymi FAQPage. Zmiany ogłaszane w regionie role=status.
--}}
@php
    $kcFaqRows = old('faq', $item->faq ?: []);
    $kcFaqRows = array_values(array_map(fn ($r) => ['q' => (string) ($r['q'] ?? ''), 'a' => (string) ($r['a'] ?? '')], is_array($kcFaqRows) ? $kcFaqRows : []));
    $kcFaqMax = \App\Models\KreatywniOfferItem::FAQ_MAX;
@endphp
<fieldset class="space-y-3" aria-describedby="ki-faq-hint"
    x-data="{
        rows: @js($kcFaqRows),
        max: {{ $kcFaqMax }},
        msg: '',
        add() { if (this.rows.length >= this.max) return; this.rows.push({ q: '', a: '' }); this.msg = 'Dodano pytanie ' + this.rows.length + '.'; this.$nextTick(() => document.getElementById('ki-faq-q-' + (this.rows.length - 1))?.focus()); },
        remove(i) { this.rows.splice(i, 1); this.msg = 'Usunięto pytanie ' + (i + 1) + '.'; this.$nextTick(() => (document.getElementById('ki-faq-q-' + Math.min(i, this.rows.length - 1)) || document.getElementById('ki-faq-add'))?.focus()); },
        move(i, d) { const j = i + d; if (j < 0 || j >= this.rows.length) return; [this.rows[i], this.rows[j]] = [this.rows[j], this.rows[i]]; this.msg = 'Pytanie przeniesione na pozycję ' + (j + 1) + '.'; this.$nextTick(() => document.getElementById('ki-faq-q-' + j)?.focus()); },
    }">
    <legend class="text-base font-bold text-ink">Najczęstsze pytania (FAQ)</legend>
    <p id="ki-faq-hint" class="text-xs text-muted">Pytania i odpowiedzi na podstronie warsztatu, np. „Ile trwają zajęcia?”, „Co trzeba przygotować?”. Pokazują się jako rozwijana lista i trafiają do wyników Google. Do {{ $kcFaqMax }} pytań; pytanie bez odpowiedzi nie jest zapisywane.</p>
    <p class="sr-only" role="status" aria-live="polite" x-text="msg"></p>

    <ol class="space-y-3">
        <template x-for="(row, i) in rows" :key="i">
            <li class="rounded-md bg-gray-50 p-4">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-bold text-ink" x-text="'Pytanie ' + (i + 1)"></span>
                    <span class="flex flex-wrap gap-1">
                        <button type="button" @click="move(i, -1)" :disabled="i === 0" :aria-label="'Przenieś pytanie ' + (i + 1) + ' wyżej'"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded bg-white text-ink hover:bg-brand-light disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                        <button type="button" @click="move(i, 1)" :disabled="i === rows.length - 1" :aria-label="'Przenieś pytanie ' + (i + 1) + ' niżej'"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded bg-white text-ink hover:bg-brand-light disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                        <button type="button" @click="remove(i)" :aria-label="'Usuń pytanie ' + (i + 1)"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded bg-white px-3 text-sm font-bold text-red-800 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-800"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                    </span>
                </div>
                <label :for="'ki-faq-q-' + i" class="mb-1 block text-sm font-bold text-ink">Pytanie</label>
                <input type="text" :id="'ki-faq-q-' + i" :name="'faq[' + i + '][q]'" x-model="row.q" maxlength="300"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark">
                <label :for="'ki-faq-a-' + i" class="mb-1 mt-3 block text-sm font-bold text-ink">Odpowiedź</label>
                <textarea :id="'ki-faq-a-' + i" :name="'faq[' + i + '][a]'" x-model="row.a" rows="3" maxlength="3000"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark"></textarea>
            </li>
        </template>
    </ol>
    <p x-show="rows.length === 0" class="text-sm text-muted">Brak pytań.</p>

    <button type="button" id="ki-faq-add" @click="add()" :disabled="rows.length >= max"
        class="inline-flex min-h-11 items-center gap-2 rounded bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie
    </button>
    @error('faq') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
</fieldset>
