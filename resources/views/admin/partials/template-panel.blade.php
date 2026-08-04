{{--
    Panel szablonów treści — do wbudowania w formularze (news, wydarzenie itp.)

    Parametry:
      $templateType  — 'news' | 'event' | 'volunteer_ad'
      $templateFields — tablica pól, które szablon może wypełnić, np.:
                         ['excerpt', 'content', 'audience', 'meta_description']
--}}
@php
    $templateType   = $templateType ?? 'news';
    $templateFields = $templateFields ?? [];
@endphp

<div x-data="templateModal('{{ $templateType }}', @js($templateFields))" x-cloak>

    {{-- Przycisk wyzwalający modal --}}
    <button type="button"
        @click="trigger = $event.currentTarget; open('load')"
        class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-3 py-1.5 text-sm font-bold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
        <i class="fa-solid fa-layer-group text-brand" aria-hidden="true"></i>
        Dodaj z szablonu / Zarządzaj szablonami
        <template x-if="templates.length > 0">
            <span class="rounded-full bg-brand/10 px-1.5 py-0.5 text-xs text-brand" x-text="templates.length" aria-hidden="true"></span>
        </template>
    </button>

    {{-- Komunikat o załadowaniu (poza modalem) --}}
    <p x-show="applied" x-transition role="status"
        class="mt-1 text-xs text-green-700">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        Załadowano szablon „<span x-text="appliedName"></span>" — sprawdź wypełnione pola i uzupełnij brakujące.
    </p>

    {{-- ===== MODAL ===== --}}
    <div x-show="isOpen" x-transition
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6"
        role="dialog" aria-modal="true" aria-labelledby="tmpl-modal-title"
        @keydown.escape.window="isOpen && close()"
        style="display: none">

        {{-- Tło przyciemniające --}}
        <div class="fixed inset-0 bg-ink/60" @click="close()" aria-hidden="true"></div>

        {{-- Panel modala --}}
        <div x-ref="panel"
            @keydown.tab="trapTab($event)"
            class="relative z-10 my-4 w-full max-w-xl rounded-lg border border-gray-200 bg-white shadow-xl">

            {{-- Nagłówek --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 id="tmpl-modal-title" class="text-base font-bold">
                    <i class="fa-solid fa-layer-group mr-1.5 text-brand" aria-hidden="true"></i>
                    Szablony treści
                </h2>
                <button type="button" @click="close()"
                    class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    <span class="sr-only">Zamknij okno</span>
                </button>
            </div>

            {{-- Zakładki --}}
            <div class="flex border-b border-gray-100" role="tablist" aria-label="Zakładki szablonów">
                <button type="button" role="tab" id="tmpl-tab-load"
                    :aria-selected="activeTab === 'load'"
                    :tabindex="activeTab === 'load' ? 0 : -1"
                    aria-controls="tmpl-panel-load"
                    @click="activeTab = 'load'"
                    :class="activeTab === 'load'
                        ? '-mb-px border-b-2 border-brand text-brand'
                        : 'text-muted hover:text-ink'"
                    class="px-5 py-3 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand">
                    <i class="fa-solid fa-file-import mr-1" aria-hidden="true"></i> Dodaj z szablonu
                </button>
                <button type="button" role="tab" id="tmpl-tab-manage"
                    :aria-selected="activeTab === 'manage'"
                    :tabindex="activeTab === 'manage' ? 0 : -1"
                    aria-controls="tmpl-panel-manage"
                    @click="activeTab = 'manage'"
                    :class="activeTab === 'manage'
                        ? '-mb-px border-b-2 border-brand text-brand'
                        : 'text-muted hover:text-ink'"
                    class="px-5 py-3 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand">
                    <i class="fa-solid fa-list-check mr-1" aria-hidden="true"></i> Zarządzaj szablonami
                </button>
            </div>

            {{-- ---- Tab: Dodaj z szablonu ---- --}}
            <div id="tmpl-panel-load" role="tabpanel" aria-labelledby="tmpl-tab-load"
                x-show="activeTab === 'load'" class="p-5">

                <p class="mb-3 text-sm text-muted">
                    Kliknij „Załaduj", aby wstawić dane z szablonu do bieżącego formularza.
                    Niezapisane zmiany zostaną nadpisane.
                </p>

                <template x-if="templates.length === 0">
                    <p class="rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-muted">
                        Brak szablonów tego typu. Wypełnij formularz i zapisz jako szablon
                        w zakładce „Zarządzaj szablonami".
                    </p>
                </template>

                <template x-if="templates.length > 0">
                    <ul class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200">
                        <template x-for="t in templates" :key="t.id">
                            <li class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-gray-50">
                                <span class="flex-1 text-sm font-medium text-ink" x-text="t.name"></span>
                                <button type="button"
                                    @click="apply(t.id)"
                                    :aria-label="'Załaduj szablon: ' + t.name"
                                    class="rounded bg-brand px-3 py-1 text-xs font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1">
                                    Załaduj
                                </button>
                            </li>
                        </template>
                    </ul>
                </template>

                <div class="mt-5 flex justify-end">
                    <button type="button" @click="close()"
                        class="rounded px-3 py-1.5 text-sm text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        Anuluj
                    </button>
                </div>
            </div>

            {{-- ---- Tab: Zarządzaj szablonami ---- --}}
            <div id="tmpl-panel-manage" role="tabpanel" aria-labelledby="tmpl-tab-manage"
                x-show="activeTab === 'manage'" class="space-y-5 p-5">

                {{-- Zapisz bieżącą treść jako szablon --}}
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                    <p class="mb-2 text-sm font-bold text-blue-900">
                        <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i>
                        Zapisz bieżącą treść jako szablon
                    </p>
                    <div class="flex items-center gap-2">
                        <label for="tmpl-save-name" class="sr-only">Nazwa szablonu</label>
                        <input type="text" id="tmpl-save-name" x-model="saveName"
                            placeholder="np. Szkolenie FEER — standardowy"
                            @keydown.enter.prevent="saveTemplate()"
                            class="flex-1 rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <button type="button" @click="saveTemplate()"
                            :disabled="!saveName.trim()"
                            class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark
                                   disabled:cursor-not-allowed disabled:opacity-40
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            Zapisz
                        </button>
                    </div>
                    <p x-show="savedMsg" x-transition role="status" class="mt-1.5 text-xs text-green-700">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span x-text="savedMsg"></span>
                    </p>
                </div>

                {{-- Lista istniejących szablonów --}}
                <div>
                    <p class="mb-2 text-sm font-bold text-gray-700">Zapisane szablony</p>

                    <p x-show="deleteMsg" x-transition role="status" class="mb-2 text-xs text-green-700">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span x-text="deleteMsg"></span>
                    </p>

                    <template x-if="templates.length === 0">
                        <p class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-muted">
                            Brak zapisanych szablonów.
                        </p>
                    </template>

                    <template x-if="templates.length > 0">
                        <ul class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200">
                            <template x-for="t in templates" :key="t.id">
                                <li class="flex items-center justify-between gap-3 px-4 py-3">
                                    <span class="flex-1 text-sm text-ink" x-text="t.name"></span>
                                    <button type="button"
                                        @click="deleteTemplate(t)"
                                        :aria-label="'Usuń szablon: ' + t.name"
                                        title="Usuń"
                                        class="rounded p-1.5 text-muted hover:text-red-600
                                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                                        <i class="fa-solid fa-trash text-sm" aria-hidden="true"></i>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>

                <div class="flex justify-end border-t border-gray-100 pt-4">
                    <button type="button" @click="close()"
                        class="rounded px-3 py-1.5 text-sm text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        Zamknij
                    </button>
                </div>
            </div>

        </div>{{-- /panel --}}
    </div>{{-- /modal --}}
</div>{{-- /x-data --}}

@push('scripts')
<script>
function templateModal(type, fields) {
    return {
        type,
        fields,
        isOpen: false,
        activeTab: 'load',
        trigger: null,
        templates: [],
        applied: false,
        appliedName: '',
        saveName: '',
        savedMsg: '',
        deleteMsg: '',

        async init() {
            await this.fetchTemplates();
        },

        async fetchTemplates() {
            try {
                const r = await fetch('{{ url('admin/szablony') }}?type=' + this.type);
                this.templates = await r.json();
            } catch {}
        },

        open(tab) {
            this.activeTab = tab || 'load';
            this.isOpen = true;
            this.$nextTick(() => {
                const items = this.focusables();
                if (items.length) items[0].focus();
            });
        },

        close() {
            this.isOpen = false;
            this.$nextTick(() => { if (this.trigger) this.trigger.focus(); });
        },

        async apply(id) {
            try {
                const r = await fetch('{{ url('admin/szablony') }}/' + id + '/dane');
                if (!r.ok) throw new Error();
                const data = await r.json();
                this.fillFields(data);
                const tmpl = this.templates.find(t => t.id === id);
                this.appliedName = tmpl ? tmpl.name : '';
                this.applied = true;
                setTimeout(() => { this.applied = false; }, 5000);
                this.close();
            } catch {
                alert('Nie udało się załadować szablonu.');
            }
        },

        fillFields(data) {
            for (const [key, value] of Object.entries(data)) {
                if (!this.fields.includes(key)) continue;

                const el = document.querySelector('[name="' + key + '"]');
                if (el && el.tagName !== 'TEXTAREA') {
                    el.value = value ?? '';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (el && el.tagName === 'TEXTAREA') {
                    el.value = value ?? '';
                }

                if (window.tinymce) {
                    const ed = tinymce.get(key);
                    if (ed) ed.setContent(value ?? '');
                }
                if (window.ckeditorInstances && window.ckeditorInstances[key]) {
                    window.ckeditorInstances[key].setData(value ?? '');
                }

                if (key === 'accent_color') {
                    const picker = document.getElementById('accent_color_picker');
                    if (picker && /^#[0-9a-fA-F]{6}$/.test(value)) picker.value = value;
                }
            }
        },

        async saveTemplate() {
            if (!this.saveName.trim()) return;

            const formEl = document.querySelector('form[enctype="multipart/form-data"]') || document.querySelector('form');
            const formData = new FormData(formEl);
            const data = {};
            this.fields.forEach(f => {
                const val = formData.get(f);
                if (val !== null) data[f] = val;
            });

            const contentField = this.fields.includes('content') ? 'content'
                               : this.fields.includes('body') ? 'body' : null;
            if (contentField) {
                if (window.tinymce) {
                    const ed = tinymce.get(contentField) || tinymce.editors[0];
                    if (ed) data[contentField] = ed.getContent();
                }
                if (window.ckeditorInstances && window.ckeditorInstances[contentField]) {
                    data[contentField] = window.ckeditorInstances[contentField].getData();
                }
            }

            try {
                const r = await fetch('{{ route('admin.szablony.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ type: this.type, name: this.saveName.trim(), data }),
                });
                const json = await r.json();
                if (!r.ok) {
                    const msg = json.message || Object.values(json.errors || {}).flat().join(' ') || 'Błąd zapisu szablonu.';
                    this.savedMsg = '❌ ' + msg;
                    setTimeout(() => { this.savedMsg = ''; }, 5000);
                    return;
                }
                this.savedMsg = json.status ?? 'Zapisano.';
                this.saveName = '';
                await this.fetchTemplates();
                setTimeout(() => { this.savedMsg = ''; }, 3000);
            } catch {
                this.savedMsg = '❌ Błąd połączenia — szablon niezapisany.';
                setTimeout(() => { this.savedMsg = ''; }, 5000);
            }
        },

        async deleteTemplate(t) {
            if (!confirm('Usunąć szablon „' + t.name + '"?')) return;
            try {
                const r = await fetch('{{ url('admin/szablony') }}/' + t.id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                if (r.ok) {
                    this.templates = this.templates.filter(tmpl => tmpl.id !== t.id);
                    this.deleteMsg = 'Szablon „' + t.name + '" usunięty.';
                    setTimeout(() => { this.deleteMsg = ''; }, 3000);
                } else {
                    alert('Nie udało się usunąć szablonu.');
                }
            } catch {
                alert('Nie udało się usunąć szablonu.');
            }
        },

        focusables() {
            const sel = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
            return Array.from(this.$refs.panel.querySelectorAll(sel))
                .filter(el => el.offsetParent !== null || el === document.activeElement);
        },

        trapTab(event) {
            const items = this.focusables();
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}
</script>
@endpush
