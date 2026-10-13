{{--
    Asystent AI (Claude) pod edytorem treści. Działa na zaznaczeniu (TinyMCE) albo na całej treści.
    Wynik zawsze trafia najpierw do podglądu — wstawienie, zastąpienie albo skopiowanie wybiera użytkownik.
    Propozycje tytułu, zajawki i opisu SEO można wstawić jednym kliknięciem do pól formularza (title, excerpt, meta_description).
    Parametry: $editorId. WCAG: przyciski z nazwami, status w aria-live, fokus przenoszony na wynik.
--}}
@php $aiActions = \App\Http\Controllers\Admin\AiAssistantController::ACTIONS; @endphp
<div class="mt-2" x-data="aiAssistant(@js($editorId), @js(route('admin.asystent-ai')))">
    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="{{ $editorId }}-ai"
        class="inline-flex min-h-9 items-center gap-2 rounded-md bg-brand-light px-3 text-sm font-bold text-brand-dark hover:bg-brand-dark hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Asystent AI
    </button>

    <div id="{{ $editorId }}-ai" x-show="open" x-cloak class="mt-2 space-y-3 rounded-md bg-gray-50 p-4" role="region" aria-label="Asystent AI">
        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            <div>
                <label for="{{ $editorId }}-ai-action" class="mb-1 block text-sm font-bold text-ink">Co zrobić?</label>
                <select id="{{ $editorId }}-ai-action" x-model="action" class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    @foreach ($aiActions as $key => [$label])
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" @click="run()" :disabled="loading"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'" aria-hidden="true"></i>
                <span x-text="loading ? 'Asystent pisze…' : 'Zaproponuj'">Zaproponuj</span>
            </button>
        </div>
        <div x-show="action === 'custom'" x-cloak>
            <label for="{{ $editorId }}-ai-instr" class="mb-1 block text-sm font-bold text-ink">Polecenie</label>
            <textarea id="{{ $editorId }}-ai-instr" x-model="instruction" rows="2" maxlength="1000" data-no-dirty placeholder="np. Dodaj na końcu krótkie wezwanie do zapisu na szkolenie."
                class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark"></textarea>
        </div>
        <p class="text-xs text-ink" x-text="scopeHint"></p>

        <p class="text-sm font-bold" role="status" aria-live="polite" :class="error ? 'text-red-700' : 'text-ink'" x-text="status"></p>

        <div x-show="result !== null" x-cloak class="space-y-3">
            <p class="text-sm font-bold text-ink">Propozycja</p>
            {{-- Podgląd: HTML oczyszczony po stronie serwera; propozycje tekstowe jako lista. --}}
            <div x-ref="preview" tabindex="-1" class="prose max-w-none rounded-md bg-white p-4 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" x-show="isHtml"></div>
            <ul x-show="! isHtml" class="space-y-2" role="list">
                <template x-for="(line, i) in lines" :key="i">
                    <li class="flex flex-wrap items-center gap-2 rounded-md bg-white p-3">
                        <span class="min-w-0 flex-1 text-sm text-ink" x-text="line"></span>
                        <template x-if="targetField">
                            <button type="button" @click="fillField(line)" class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-brand-dark px-3 text-xs font-bold text-white hover:bg-ink">
                                <i class="fa-solid fa-arrow-turn-down" aria-hidden="true"></i> <span x-text="'Wstaw do pola: ' + targetLabel"></span>
                            </button>
                        </template>
                        <button type="button" @click="copy(line)" class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-gray-100 px-3 text-xs font-bold text-ink hover:bg-gray-200"><i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj</button>
                    </li>
                </template>
            </ul>
            <div x-show="isHtml" class="flex flex-wrap gap-2">
                <button type="button" @click="apply('replace')" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> <span x-text="usedSelection ? 'Zastąp zaznaczenie' : 'Zastąp całą treść'"></span>
                </button>
                <button type="button" @click="apply('append')" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-gray-200">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Wstaw na końcu
                </button>
                <button type="button" @click="copy(result)" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-gray-200">
                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj HTML
                </button>
                <button type="button" @click="result = null; status = ''" class="inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold text-ink underline underline-offset-4 hover:no-underline">Odrzuć</button>
            </div>
        </div>
        <p class="text-xs text-muted">Tekst trafia do asystenta Claude (Anthropic). Sprawdź propozycję przed zapisem — asystent może się mylić.</p>
    </div>
</div>

@once
<script>
function aiAssistant(editorId, url) {
    const fields = { title: ['title', 'Tytuł'], lead: ['excerpt', 'Zajawka'], meta: ['meta_description', 'Opis SEO'] };
    return {
        open: false, action: 'improve', instruction: '', loading: false, status: '', error: false,
        result: null, isHtml: true, lines: [], usedSelection: false,
        get scopeHint() {
            return this.selectionHtml() ? 'Asystent zajmie się zaznaczonym fragmentem.' : 'Asystent zajmie się całą treścią (zaznacz fragment w edytorze, aby pracować tylko na nim).';
        },
        get targetField() { const f = fields[this.action]; return f ? this.form()?.querySelector('[name="' + f[0] + '"]') : null; },
        get targetLabel() { return (fields[this.action] || [, ''])[1]; },
        form() { return document.getElementById(editorId)?.closest('form'); },
        tiny() { return window.tinymce ? window.tinymce.get(editorId) : null; },
        ck() { return (window.__ckEditors || {})[editorId] || null; },
        selectionHtml() {
            const t = this.tiny();
            if (t && ! t.selection.isCollapsed()) return t.selection.getContent();
            return '';
        },
        allHtml() {
            const t = this.tiny(); if (t) return t.getContent();
            const c = this.ck(); if (c) return c.getData();
            return document.getElementById(editorId)?.value || '';
        },
        async run() {
            const sel = this.selectionHtml();
            const text = sel || this.allHtml();
            if (! text.replace(/<[^>]*>/g, '').trim()) { this.error = true; this.status = 'Najpierw wpisz tekst w edytorze.'; return; }
            if (this.action === 'custom' && ! this.instruction.trim()) { this.error = true; this.status = 'Wpisz polecenie dla asystenta.'; return; }
            this.loading = true; this.error = false; this.status = 'Asystent pisze propozycję…'; this.result = null; this.usedSelection = !! sel;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ action: this.action, text, instruction: this.instruction }),
                });
                const data = await res.json().catch(() => ({}));
                if (! res.ok) { throw new Error(data.message || (res.status === 429 ? 'Za dużo zapytań — odczekaj minutę.' : 'Asystent nie odpowiedział.')); }
                this.result = data.result || '';
                this.isHtml = !! data.html;
                this.lines = this.isHtml ? [] : this.result.split(/\n+/).map((l) => l.trim()).filter(Boolean);
                this.status = 'Gotowe — sprawdź propozycję poniżej.';
                this.$nextTick(() => { if (this.isHtml && this.$refs.preview) { this.$refs.preview.innerHTML = this.result; this.$refs.preview.focus(); } });
            } catch (e) {
                this.error = true; this.status = e.message;
            }
            this.loading = false;
        },
        apply(mode) {
            const html = this.result || '';
            const t = this.tiny(), c = this.ck();
            if (t) {
                if (mode === 'replace' && this.usedSelection) t.selection.setContent(html);
                else if (mode === 'replace') t.setContent(html);
                else { t.selection.select(t.getBody(), true); t.selection.collapse(false); t.insertContent(html); }
                t.undoManager?.add(); t.fire?.('change');
            } else if (c) {
                c.setData(mode === 'replace' ? html : c.getData() + html);
            } else {
                const ta = document.getElementById(editorId);
                if (ta) ta.value = mode === 'replace' ? html : ta.value + html;
            }
            document.getElementById(editorId)?.dispatchEvent(new Event('input', { bubbles: true }));
            this.status = mode === 'replace' ? 'Wstawiono propozycję w miejsce tekstu. Pamiętaj o zapisaniu.' : 'Dodano propozycję na końcu treści. Pamiętaj o zapisaniu.';
            this.result = null;
        },
        fillField(line) {
            const f = this.targetField; if (! f) return;
            f.value = line; f.dispatchEvent(new Event('input', { bubbles: true })); f.focus();
            this.status = 'Wstawiono do pola „' + this.targetLabel + '”.';
        },
        async copy(text) {
            try { await navigator.clipboard.writeText(text); this.status = 'Skopiowano do schowka.'; } catch (e) { this.status = 'Nie udało się skopiować.'; }
        },
    };
}
</script>
@endonce
