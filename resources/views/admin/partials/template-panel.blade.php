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
    $panelId        = 'tmpl-panel-' . $templateType;
@endphp

<div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4" x-data="templatePanel('{{ $templateType }}', @js($templateFields))" x-cloak>
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm font-bold text-gray-600">
            <i class="fa-solid fa-layer-group mr-1" aria-hidden="true"></i> Szablon
        </span>

        <select x-model="selected" @change="selected && loadPreview()"
            class="flex-1 rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            <option value="">— wybierz szablon —</option>
            <template x-for="t in templates" :key="t.id">
                <option :value="t.id" x-text="t.name"></option>
            </template>
        </select>

        <button type="button" @click="applyTemplate()"
            :disabled="!selected"
            class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-40 disabled:cursor-not-allowed">
            Załaduj
        </button>

        <button type="button" @click="saveModalOpen = true"
            class="rounded border border-gray-300 px-3 py-1.5 text-sm font-bold text-gray-700 hover:bg-gray-100">
            <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Zapisz jako szablon
        </button>

        <a href="{{ route('admin.szablony.manage') }}" target="_blank" rel="noopener"
            class="text-sm text-muted hover:text-brand" title="Zarządzaj szablonami">
            <i class="fa-solid fa-ellipsis"></i>
        </a>
    </div>

    <p x-show="applied" x-transition class="mt-2 text-xs text-green-700">
        <i class="fa-solid fa-circle-check"></i> Szablon załadowany — sprawdź wypełnione pola i uzupełnij brakujące.
    </p>

    {{-- Modal: Zapisz jako szablon --}}
    <div x-show="saveModalOpen" x-transition
        class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <label class="mb-1 block text-sm font-bold text-blue-900">Nazwa szablonu</label>
        <div class="flex items-center gap-2">
            <input type="text" x-model="saveName" placeholder="np. Szkolenie FEER — standardowy"
                @keydown.enter.prevent="saveTemplate()"
                class="flex-1 rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            <button type="button" @click="saveTemplate()"
                :disabled="!saveName.trim()"
                class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-40 disabled:cursor-not-allowed">
                Zapisz
            </button>
            <button type="button" @click="saveModalOpen = false"
                class="text-sm text-muted hover:text-gray-700">Anuluj</button>
        </div>
        <p x-show="savedMsg" x-transition class="mt-1 text-xs text-green-700">
            <i class="fa-solid fa-circle-check"></i> <span x-text="savedMsg"></span>
        </p>
    </div>
</div>

@push('scripts')
<script>
function templatePanel(type, fields) {
    return {
        type,
        fields,
        templates: [],
        selected: '',
        applied: false,
        saveModalOpen: false,
        saveName: '',
        savedMsg: '',

        async init() {
            try {
                const res = await fetch(`{{ url('admin/szablony') }}?type=${type}`);
                this.templates = await res.json();
            } catch {}
        },

        async applyTemplate() {
            if (!this.selected) return;
            try {
                const res = await fetch(`{{ url('admin/szablony') }}/${this.selected}/dane`);
                const data = await res.json();
                this.fillFields(data);
                this.applied = true;
                setTimeout(() => { this.applied = false; }, 4000);
            } catch {
                alert('Nie udało się załadować szablonu.');
            }
        },

        fillFields(data) {
            for (const [key, value] of Object.entries(data)) {
                if (!this.fields.includes(key)) continue;

                // Zwykłe inputy i selecty
                const el = document.querySelector(`[name="${key}"]`);
                if (el && el.tagName !== 'TEXTAREA') {
                    el.value = value ?? '';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Textarea bez edytora
                if (el && el.tagName === 'TEXTAREA') {
                    el.value = value ?? '';
                }

                // TinyMCE
                if (window.tinymce) {
                    const ed = tinymce.get(key);
                    if (ed) { ed.setContent(value ?? ''); }
                }

                // CKEditor 5
                if (window.ckeditorInstances && window.ckeditorInstances[key]) {
                    window.ckeditorInstances[key].setData(value ?? '');
                }

                // Kolor: synchronizuj picker z polem tekstowym
                if (key === 'accent_color') {
                    const picker = document.getElementById('accent_color_picker');
                    if (picker && /^#[0-9a-fA-F]{6}$/.test(value)) picker.value = value;
                }
            }
        },

        async saveTemplate() {
            if (!this.saveName.trim()) return;

            const formEl = document.querySelector('form');
            const formData = new FormData(formEl);
            const data = {};
            this.fields.forEach(f => {
                const val = formData.get(f);
                if (val !== null) data[f] = val;
            });

            // Pobierz treść z edytora WYSIWYG jeśli pole 'content' jest w fields
            if (this.fields.includes('content') || this.fields.includes('body')) {
                const field = this.fields.includes('content') ? 'content' : 'body';
                if (window.tinymce) {
                    const ed = tinymce.get(field) || tinymce.editors[0];
                    if (ed) data[field] = ed.getContent();
                }
                if (window.ckeditorInstances && window.ckeditorInstances[field]) {
                    data[field] = window.ckeditorInstances[field].getData();
                }
            }

            const payload = {
                type: this.type,
                name: this.saveName.trim(),
                data,
                _token: '{{ csrf_token() }}',
            };

            try {
                const res = await fetch('{{ route('admin.szablony.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                this.savedMsg = json.status ?? 'Zapisano.';
                this.saveName = '';
                // Odśwież listę szablonów
                const listRes = await fetch(`{{ url('admin/szablony') }}?type=${this.type}`);
                this.templates = await listRes.json();
                setTimeout(() => { this.savedMsg = ''; this.saveModalOpen = false; }, 2500);
            } catch {
                alert('Nie udało się zapisać szablonu.');
            }
        },
    };
}
</script>
@endpush
