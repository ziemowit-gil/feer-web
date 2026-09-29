import Alpine from 'alpinejs';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

window.Alpine = Alpine;
window.Cropper = Cropper;

// Global confirm dialog — zastępuje natywne confirm() alertdialogiem Alpine.
// Formularze z atrybutem data-confirm są przechwytywane automatycznie.
Alpine.store('confirm', {
    open: false,
    message: '',
    extraLabel: '',   // etykieta trzeciego przycisku (np. „Usuń z kopiami (2)")
    _resolve: null,
    ask(message) {
        return new Promise(resolve => {
            this.message = message;
            this.extraLabel = '';
            this.open = true;
            this._resolve = resolve;
            Alpine.nextTick(() => document.getElementById('confirm-cancel-btn')?.focus());
        });
    },
    askWithExtra(message, extraLabel) {
        return new Promise(resolve => {
            this.message = message;
            this.extraLabel = extraLabel;
            this.open = true;
            this._resolve = resolve;
            Alpine.nextTick(() => document.getElementById('confirm-cancel-btn')?.focus());
        });
    },
    confirm()  { this.open = false; this._resolve?.('ok'); },
    extra()    { this.open = false; this._resolve?.('extra'); },
    cancel()   { this.open = false; this._resolve?.(null); },
});

// Komponent edytora układu strony głównej — drag-and-drop dla administratorów.
// Używa CSS `order` wewnątrz flex-col kontenera, więc DOM nie jest przestawiany —
// tylko wizualna kolejność zmienia się reaktywnie. Wywołanie save() zapisuje do API
// i przeładowuje stronę, żeby PHP wyrenderował nową kolejność.
Alpine.data('homepageEditor', (initialOrder, saveUrl) => ({
    editMode: false,
    collapsed: localStorage.getItem('admin-bar-collapsed') === '1',
    sections: [...initialOrder],
    initialSections: [...initialOrder],
    dragging: null,
    dragOver: null,
    saving: false,
    saveSuccess: false,
    error: null,

    toggleBar() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('admin-bar-collapsed', this.collapsed ? '1' : '0');
    },

    // Etykiety zgodne z SiteSetting::HOMEPAGE_SECTIONS w PHP.
    LABELS: {
        hero:     'Slajder (hero)',
        news:     'Aktualności',
        events:   'Szkolenia i wydarzenia',
        ankieta:  'Ankieta i szybkie akcje',
        gallery:  'Galeria',
        substack: 'O tym piszemy (Substack)',
    },

    sectionIndex(key) {
        return this.sections.indexOf(key);
    },

    sectionLabel(key) {
        return this.LABELS[key] ?? key;
    },

    hasChanges() {
        return this.sections.join(',') !== this.initialSections.join(',');
    },

    // --- Drag and Drop (desktop, HTML5 API) ---

    startDrag(key) {
        this.dragging = key;
    },

    enterDrop(key) {
        if (this.dragging && this.dragging !== key) {
            this.dragOver = key;
        }
    },

    leaveDrop(key) {
        if (this.dragOver === key) this.dragOver = null;
    },

    onDrop(key) {
        if (this.dragging && this.dragging !== key) {
            const from = this.sections.indexOf(this.dragging);
            const to   = this.sections.indexOf(key);
            this.sections.splice(from, 1);
            this.sections.splice(to, 0, this.dragging);
        }
        this.dragging = null;
        this.dragOver = null;
    },

    // --- Klawiatura / mobile: przyciski góra/dół ---

    moveUp(key) {
        const idx = this.sections.indexOf(key);
        if (idx > 0) {
            this.sections = this.sections
                .map((k, i) => i === idx - 1 ? key : i === idx ? this.sections[idx - 1] : k);
        }
    },

    moveDown(key) {
        const idx = this.sections.indexOf(key);
        if (idx < this.sections.length - 1) {
            this.sections = this.sections
                .map((k, i) => i === idx ? this.sections[idx + 1] : i === idx + 1 ? key : k);
        }
    },

    // --- Tryb edycji ---

    toggleEdit() {
        this.editMode = !this.editMode;
        this.error = null;
        // Przy wychodzeniu z trybu bez zapisu — odrzuć zmiany.
        if (!this.editMode) this.sections = [...this.initialSections];
    },

    discard() {
        this.sections = [...this.initialSections];
        this.editMode = false;
        this.error = null;
    },

    async save() {
        this.saving = true;
        this.error = null;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ sections: this.sections }),
            });
            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                throw new Error(json.message ?? `HTTP ${res.status}`);
            }
            this.initialSections = [...this.sections];
            this.editMode = false;
            this.saveSuccess = true;
            // Przeładuj po chwili, żeby PHP wyrenderował nową kolejność w DOM.
            setTimeout(() => window.location.reload(), 600);
        } catch (e) {
            this.error = 'Nie udało się zapisać układu. Spróbuj ponownie.';
        } finally {
            this.saving = false;
        }
    },
}));

// Komponent szybkiej edycji aktualności wprost na froncie (news/show.blade.php) —
// tytuł, lead i status publikacji, bez wchodzenia do panelu. Analogiczny w
// zachowaniu do homepageEditor (pasek u góry, tryb edycji, zapis przez fetch).
Alpine.data('newsInlineEditor', (initial, saveUrl, options = {}) => {
    // Edytor treści trzymany poza reaktywnością Alpine (proxy psuje TinyMCE).
    let contentEditor = null;
    let contentEl = null;
    let contentInitialHtml = '';
    const engine = options.engine === 'ckeditor' ? 'ckeditor' : 'tinymce';

    return ({
    editMode: false,
    collapsed: localStorage.getItem('admin-bar-collapsed') === '1',
    form: { ...initial },
    initialForm: { ...initial },
    saving: false,
    saveSuccess: false,
    error: null,
    contentDirty: false,
    get hasRichFields() { return !! (options.richContent && document.querySelector('[data-news-content]')); },

    async mountContentEditor() {
        contentEl = options.richContent ? document.querySelector('[data-news-content]') : null;
        if (! contentEl) return;
        contentInitialHtml = contentEl.innerHTML;
        try {
            contentEditor = await createRichEditor(contentEl, { engine, uploadUrl: options.uploadUrl, onDirty: () => { this.contentDirty = true; } });
        } catch (e) {
            console.error(e);
            this.error = 'Nie udało się załadować edytora treści (brak połączenia z CDN?).';
        }
    },

    unmountContentEditor(restore) {
        if (contentEditor) destroyRichEditor(engine, contentEditor);
        contentEditor = null;
        if (restore && contentEl) contentEl.innerHTML = contentInitialHtml;
        this.contentDirty = false;
    },

    toggleBar() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('admin-bar-collapsed', this.collapsed ? '1' : '0');
    },

    hasChanges() {
        return this.contentDirty || JSON.stringify(this.form) !== JSON.stringify(this.initialForm);
    },

    toggleEdit() {
        this.editMode = !this.editMode;
        this.error = null;
        if (this.editMode) {
            this.mountContentEditor();
        } else {
            this.form = { ...this.initialForm };
            this.unmountContentEditor(true);
        }
    },

    discard() {
        this.form = { ...this.initialForm };
        this.unmountContentEditor(true);
        this.editMode = false;
        this.error = null;
    },

    async save() {
        this.saving = true;
        this.error = null;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const payload = { ...this.form };
            if (contentEditor) payload.content = getRichContent(engine, contentEditor);
            const res = await fetch(saveUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                throw new Error(json.message ?? `HTTP ${res.status}`);
            }
            this.initialForm = { ...this.form };
            this.unmountContentEditor(false);
            this.editMode = false;
            this.saveSuccess = true;
            // Przeładuj po chwili, żeby PHP wyrenderował nowy tytuł (breadcrumb, <title> itd.).
            setTimeout(() => window.location.reload(), 600);
        } catch (e) {
            this.error = 'Nie udało się zapisać zmian. Spróbuj ponownie.';
        } finally {
            this.saving = false;
        }
    },
    });
});

// Wizualna edycja "na żywo" — alternatywa dla formularzy admina. Kliknij pole
// (tytuł, treść) na realnej stronie i edytuj bezpośrednio; zapis pojedynczego
// pola przez PUT /admin/edycja-na-zywo (patrz InlineEditController).
// Edycja „na żywo" bezpośrednio na stronie publicznej (alternatywa dla
// formularza admina). Pola oznaczone `data-inline-field="<pole>"` dostają:
//   • data-inline-kind="rich" — pełny edytor WYSIWYG (TinyMCE inline albo
//     CKEditor 5 inline, wg ustawienia „Edytor treści" w panelu) osadzony
//     w istniejącym elemencie, więc widać prawdziwe style strony,
//   • data-inline-kind="text" (domyślnie) — jednoliniowa edycja tekstu.
// Zapis jest jawny (przycisk „Zapisz" / Ctrl+S) z ochroną przed utratą
// niezapisanych zmian. Starsze szablony (federation/wrzos) nadal używają
// `:contenteditable="editMode"` + saveField()/saveArrayField() po blur.
const INLINE_EDITOR_SCRIPTS = {
    tinymce: 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js',
    ckeditor: 'https://cdn.ckeditor.com/ckeditor5/41.4.2/inline/ckeditor.js',
};

function loadInlineEditorScript(src, isReady) {
    if (isReady()) return Promise.resolve();
    window.__inlineEditorLoads = window.__inlineEditorLoads || {};
    if (!window.__inlineEditorLoads[src]) {
        window.__inlineEditorLoads[src] = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.referrerPolicy = 'origin';
            script.onload = resolve;
            script.onerror = () => reject(new Error('Nie udało się pobrać edytora: ' + src));
            document.head.appendChild(script);
        });
    }
    return window.__inlineEditorLoads[src];
}

// Osadza edytor WYSIWYG (TinyMCE inline / CKEditor 5 inline) w istniejącym
// elemencie strony. Zwraca instancję; onDirty wywoływane przy każdej zmianie.
async function createRichEditor(el, { engine = 'tinymce', uploadUrl = null, onDirty = () => {} } = {}) {
const self = { engine, uploadUrl, dirty: false };
Object.defineProperty(self, 'dirty', { set: (v) => { if (v) onDirty(); }, get: () => false });
return (async function () {
    if (self.engine === 'ckeditor') {
        await loadInlineEditorScript(INLINE_EDITOR_SCRIPTS.ckeditor, () => !!window.InlineEditor);
        const editor = await window.InlineEditor.create(el, {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'insertTable', 'blockQuote', '|', 'undo', 'redo'],
            heading: {
                options: [
                    { model: 'paragraph', title: 'Akapit', class: 'ck-heading_paragraph' },
                    { model: 'heading2', view: 'h2', title: 'Nagłówek 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Nagłówek 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Nagłówek 4', class: 'ck-heading_heading4' },
                ],
            },
        });
        editor.model.document.on('change:data', () => { self.dirty = true; });
        return editor;
    }

    await loadInlineEditorScript(INLINE_EDITOR_SCRIPTS.tinymce, () => !!window.tinymce);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const uploadUrl = self.uploadUrl;
    const [editor] = await window.tinymce.init({
        target: el,
        inline: true,
        license_key: 'gpl',
        menubar: false,
        branding: false,
        convert_urls: false,
        plugins: 'lists advlist link image table autolink anchor quickbars code charmap',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough forecolor | bullist numlist outdent indent | alignleft aligncenter alignright | link image table blockquote hr | snippets | charmap removeformat | code',
        toolbar_mode: 'wrap',
        toolbar_persist: true,
        fixed_toolbar_container: '#inline-editor-toolbar',
        block_formats: 'Akapit=p; Nagłówek 2=h2; Nagłówek 3=h3; Nagłówek 4=h4',
        // Paleta zgodna z identyfikacją (kolory o kontraście ≥ 4.5:1 na bieli).
        color_map: ['c31432', 'Kolor marki', '8f0e24', 'Kolor marki (ciemny)', '1a1a1a', 'Tekst', '4b5563', 'Tekst pomocniczy', '0075cf', 'Niebieski', '15803d', 'Zielony'],
        custom_colors: false,
        quickbars_insert_toolbar: false,
        quickbars_selection_toolbar: 'bold italic underline | h2 h3 | link blockquote',
        // Gotowe bloki treści — te same klasy co w edytorze w panelu
        // (style w resources/css/app.css: .cta-button, .content-box, …).
        inline_snippets: [
            { text: 'Przycisk CTA', html: '<p><a href="#" class="cta-button">Sprawdź więcej</a></p><p>&nbsp;</p>' },
            { text: 'Tekst w ramce', html: '<div class="content-box"><p>Wpisz tutaj tekst w ramce…</p></div><p>&nbsp;</p>' },
            { text: 'Notatka / ostrzeżenie', html: '<div class="content-note"><p>Wpisz tutaj treść notatki lub ostrzeżenia…</p></div><p>&nbsp;</p>' },
            { text: 'Ważna informacja', html: '<div class="content-important"><p><strong>Ważne</strong></p><p>Wpisz tutaj treść ważnej informacji…</p></div><p>&nbsp;</p>' },
            { text: 'Dwie kolumny', html: '<div class="content-columns"><div class="content-column"><p>Pierwsza kolumna…</p></div><div class="content-column"><p>Druga kolumna…</p></div></div><p>&nbsp;</p>' },
            { text: 'Tabela dostępna (nagłówki + opis)', html: '<table><caption>Opis tabeli</caption><thead><tr><th scope="col">Kolumna 1</th><th scope="col">Kolumna 2</th></tr></thead><tbody><tr><th scope="row">Wiersz 1</th><td>Dane</td></tr></tbody></table><p>&nbsp;</p>' },
        ],
        paste_data_images: !!uploadUrl,
        automatic_uploads: !!uploadUrl,
        images_upload_handler: uploadUrl ? (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            formData.append('_token', csrf);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.upload.onprogress = (e) => { if (e.lengthComputable) progress(e.loaded / e.total * 100); };
            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) resolve(JSON.parse(xhr.responseText).location);
                else reject({ message: 'Błąd uploadu (' + xhr.status + ')', remove: true });
            };
            xhr.onerror = () => reject({ message: 'Błąd sieci', remove: true });
            xhr.send(formData);
        }) : undefined,
        setup: (ed) => {
            ed.options.register('inline_snippets', { processor: 'array', default: [] });
            ed.ui.registry.addMenuButton('snippets', {
                text: 'Wstaw',
                icon: 'plus',
                tooltip: 'Wstaw gotowy blok treści',
                fetch: (callback) => callback((ed.options.get('inline_snippets') || []).map((snippet) => ({
                    type: 'menuitem',
                    text: snippet.text,
                    onAction: () => ed.insertContent(snippet.html),
                }))),
            });
            ed.on('input change undo redo SetContent', (e) => {
                if (e.type === 'setcontent' && e.initial) return;
                self.dirty = true;
            });
        },
    });
    return editor;
})();
}

function getRichContent(engine, editor) {
return engine === 'ckeditor' ? editor.getData() : editor.getContent();
}

function destroyRichEditor(engine, editor) {
try {
    engine === 'ckeditor' ? editor.destroy() : editor.remove();
} catch (e) { /* edytor mógł już zniknąć */ }
}

Alpine.data('inlineContentEditor', (model, id, saveUrl, options = {}) => {
    // Pola i instancje edytorów trzymamy poza stanem Alpine: reaktywne proxy
    // psuje wewnętrzne `this` TinyMCE/CKEditora (np. remove() nic nie robi).
    const fields = [];

    return ({
    editMode: false,
    saving: false,
    saveSuccess: false,
    error: null,
    dirty: false,
    engine: options.engine === 'ckeditor' ? 'ckeditor' : 'tinymce',
    uploadUrl: options.uploadUrl || null,

    init() {
        window.addEventListener('beforeunload', (e) => {
            if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
        });
        document.addEventListener('keydown', (e) => {
            if (this.editMode && (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                this.saveAll();
            }
        });
        // Tryb edycji zapamiętany między stronami (jak dotąd).
        if (localStorage.getItem('inline-edit-mode') === '1') {
            this.$nextTick(() => this.enterEdit());
        }
    },

    get hasRichFields() {
        return !!this.$root.querySelector('[data-inline-field][data-inline-kind="rich"]');
    },

    toggleEdit() {
        this.editMode ? this.exitEdit() : this.enterEdit();
    },

    async enterEdit() {
        this.error = null;
        this.editMode = true;
        localStorage.setItem('inline-edit-mode', '1');

        const elements = [...this.$root.querySelectorAll('[data-inline-field]')];
        for (const el of elements) {
            const field = { name: el.dataset.inlineField, kind: el.dataset.inlineKind || 'text', el, initial: el.innerHTML, editor: null };
            if (field.kind === 'rich') {
                try {
                    field.editor = await this._createRichEditor(el);
                } catch (e) {
                    console.error(e);
                    this.error = 'Nie udało się załadować edytora (brak połączenia z CDN?). Edytujesz w trybie uproszczonym.';
                    this._attachPlainEditing(field, true);
                }
            } else {
                this._attachPlainEditing(field, el.dataset.inlineMultiline !== undefined);
            }
            fields.push(field);
        }

        this.$nextTick(() => {
            const first = fields.find((f) => f.kind === 'rich') || fields[0];
            if (first?.editor && this.engine === 'tinymce') first.editor.focus();
            else if (first?.editor && this.engine === 'ckeditor') first.editor.editing.view.focus();
            else first?.el.focus();
        });
    },

    exitEdit(force = false) {
        if (this.dirty && !force && !window.confirm('Masz niezapisane zmiany. Odrzucić je?')) return;

        for (const f of fields) {
            if (f.editor) this._destroyRichEditor(f);
            else this._detachPlainEditing(f);
            if (this.dirty) f.el.innerHTML = f.initial;
        }
        fields.length = 0;
        this.dirty = false;
        this.editMode = false;
        localStorage.setItem('inline-edit-mode', '0');
    },

    async saveAll() {
        if (!this.editMode || this.saving) return;
        if (!this.dirty) { this.saveSuccess = true; setTimeout(() => { this.saveSuccess = false; }, 1500); return; }

        this.saving = true;
        this.saveSuccess = false;
        this.error = null;
        try {
            for (const f of fields) {
                const value = f.kind === 'rich' && f.editor ? this._getRichContent(f) : (f.kind === 'rich' ? f.el.innerHTML.trim() : f.el.innerText.trim());
                await this._request({ field: f.name, value });
                f.initial = f.kind === 'rich' && f.editor ? value : f.el.innerHTML;
            }
            this.dirty = false;
            this.saveSuccess = true;
            setTimeout(() => { this.saveSuccess = false; }, 2500);
        } catch (e) {
            this.error = e.message || 'Nie udało się zapisać zmian. Spróbuj ponownie.';
        } finally {
            this.saving = false;
        }
    },

    // ── Edycja tekstu jednoliniowego / awaryjna ────────────────────
    _attachPlainEditing(field, multiline) {
        const el = field.el;
        el.contentEditable = multiline ? 'true' : 'plaintext-only';
        if (!multiline && el.contentEditable !== 'plaintext-only') el.contentEditable = 'true';
        el.setAttribute('role', 'textbox');
        el.setAttribute('aria-label', 'Edytuj: ' + field.name);
        if (multiline) el.setAttribute('aria-multiline', 'true');
        field._onInput = () => { this.dirty = true; };
        field._onKey = (e) => { if (!multiline && e.key === 'Enter') { e.preventDefault(); el.blur(); } };
        el.addEventListener('input', field._onInput);
        el.addEventListener('keydown', field._onKey);
    },

    _detachPlainEditing(field) {
        const el = field.el;
        el.contentEditable = 'false';
        el.removeAttribute('role');
        el.removeAttribute('aria-label');
        el.removeAttribute('aria-multiline');
        if (field._onInput) el.removeEventListener('input', field._onInput);
        if (field._onKey) el.removeEventListener('keydown', field._onKey);
    },

    // ── Edytor WYSIWYG (wspólne funkcje modułu, patrz createRichEditor) ──
    async _createRichEditor(el) {
        return createRichEditor(el, { engine: this.engine, uploadUrl: this.uploadUrl, onDirty: () => { this.dirty = true; } });
    },

    _getRichContent(field) {
        return getRichContent(this.engine, field.editor);
    },

    _destroyRichEditor(field) {
        destroyRichEditor(this.engine, field.editor);
    },

    // ── API zgodne wstecz (szablony federation/wrzos: zapis po blur) ──
    async saveField(field, value) {
        await this._legacySave({ field, value });
    },

    async saveArrayField(field, index, subfield, value) {
        await this._legacySave({ field, index, subfield, value });
    },

    async _legacySave(payload) {
        this.saving = true;
        this.saveSuccess = false;
        this.error = null;
        try {
            await this._request(payload);
            this.saveSuccess = true;
            setTimeout(() => { this.saveSuccess = false; }, 2000);
        } catch (e) {
            this.error = 'Nie udało się zapisać zmiany. Spróbuj ponownie.';
        } finally {
            this.saving = false;
        }
    },

    async _request(payload) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch(saveUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ model, id, ...payload }),
        });
        if (!res.ok) {
            const json = await res.json().catch(() => ({}));
            throw new Error(json.message ?? ('HTTP ' + res.status));
        }
    },
    });
});

// Menu mobilne nagłówka publicznego — wspólne dla wszystkich układów
// (partials/mobile-nav-toggle + partials/mobile-nav-panel). Fokus trafia do
// panelu po otwarciu (po następnej klatce, bo x-show pokazuje element w rAF)
// i wraca na hamburger po zamknięciu; zmiana na szeroki ekran zamyka panel.
Alpine.data('siteMobileNav', (breakpoint = 1024) => ({
    mobileOpen: false,
    init() {
        let timer = null;
        window.addEventListener('resize', () => {
            clearTimeout(timer);
            timer = setTimeout(() => { if (window.innerWidth >= breakpoint) this.closeMenu(false); }, 150);
        });
    },
    openMenu() {
        this.mobileOpen = true;
        setTimeout(() => this.$refs.mobilePanel?.querySelector('a, button, input')?.focus(), 60);
    },
    closeMenu(returnFocus = true) {
        if (! this.mobileOpen) return;
        this.mobileOpen = false;
        if (returnFocus) this.$refs.menuToggle?.focus();
    },
    toggleMenu() {
        this.mobileOpen ? this.closeMenu(false) : this.openMenu();
    },
}));

// Odtwarzacz audio (TTS) — czyta treść artykułu przez SpeechSynthesis.
// Używany w news/show.blade.php i page/show.blade.php.
Alpine.data('audioPlayer', () => ({
    etr: false,
    playing: false,
    supported: typeof window !== 'undefined' && 'speechSynthesis' in window,
    _utterance: null,

    play() {
        if (!this.supported) return;

        const el = document.getElementById('article-text');
        if (!el) return;

        if (this.playing) {
            window.speechSynthesis.cancel();
            this.playing = false;
            return;
        }

        const text = el.innerText?.trim() || '';
        if (!text) return;

        this._utterance = new SpeechSynthesisUtterance(text);
        this._utterance.lang = 'pl-PL';
        this._utterance.rate = 0.95;

        this._utterance.onend = () => { this.playing = false; };
        this._utterance.onerror = () => { this.playing = false; };

        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(this._utterance);
        this.playing = true;
    },

    stop() {
        window.speechSynthesis.cancel();
        this.playing = false;
    },
}));

// Zakładki sekcji (strona kontaktowa, „Dołącz do nas", projekty) — wzorzec
// ARIA Tabs: strzałki lewo/prawo przechodzą po zakładkach razem z fokusem,
// Home/End skaczą na skraje (WCAG 2.1.1).
Alpine.data('sectionTabs', (ids = [], initial = null) => ({
    tabs: ids,
    tab: initial ?? ids[0] ?? null,

    move(step) {
        if (!this.tabs.length) return;
        const index = this.tabs.indexOf(this.tab);
        this.tab = this.tabs[(index + step + this.tabs.length) % this.tabs.length];
        this.focusActive();
    },

    jump(id) {
        this.tab = id;
        this.focusActive();
    },

    focusActive() {
        this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus());
    },
}));

// Selektory ikon/kolorów w panelu — osobny chunk, ładowany tylko w adminie.
if (document.body.dataset.adminPickers !== undefined) {
    import('./admin-pickers.js');
}

Alpine.start();

// Pasek dostępności: kontrast i rozmiar czcionki
const FONT_STEP = 10;
const FONT_MIN = 80;
const FONT_MAX = 150;

function applyFontSize(size) {
    document.documentElement.style.setProperty('--a11y-font-size', `${size}%`);
    localStorage.setItem('a11y-font-size', String(size));
}

document.querySelectorAll('[data-a11y-font]').forEach((button) => {
    button.addEventListener('click', () => {
        const current = parseInt(localStorage.getItem('a11y-font-size') || '100', 10);
        const action = button.dataset.a11yFont;

        if (action === 'up') {
            applyFontSize(Math.min(FONT_MAX, current + FONT_STEP));
        } else if (action === 'down') {
            applyFontSize(Math.max(FONT_MIN, current - FONT_STEP));
        } else {
            applyFontSize(100);
        }
    });
});

const storedFontSize = localStorage.getItem('a11y-font-size');
if (storedFontSize) {
    applyFontSize(parseInt(storedFontSize, 10));
}

// Rozstrzał liter: '' | 'a11y-ls-1' | 'a11y-ls-2'
const LS_MODES = ['', 'a11y-ls-1', 'a11y-ls-2'];
const LS_LABELS = ['Normalny', 'Szeroki', 'Bardzo szeroki'];

function applyLetterSpacing(mode) {
    LS_MODES.forEach((cls) => { if (cls) document.documentElement.classList.remove(cls); });
    if (mode) document.documentElement.classList.add(mode);
    localStorage.setItem('a11y-ls', mode || '');
    document.querySelectorAll('[data-a11y-ls]').forEach((btn) => {
        const idx = LS_MODES.indexOf(mode);
        const next = LS_MODES[(idx + 1) % LS_MODES.length];
        btn.setAttribute('aria-label', 'Rozstrzał liter: ' + (LS_LABELS[idx] || 'Normalny') + ' → kliknij: ' + LS_LABELS[(idx + 1) % LS_MODES.length]);
        btn.setAttribute('aria-pressed', String(!!mode));
    });
}

const storedLs = localStorage.getItem('a11y-ls') || '';
if (storedLs) applyLetterSpacing(storedLs);

document.querySelectorAll('[data-a11y-ls]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const current = localStorage.getItem('a11y-ls') || '';
        const idx = LS_MODES.indexOf(current);
        applyLetterSpacing(LS_MODES[(idx + 1) % LS_MODES.length]);
    });
});

// Czcionka bezszeryfowa (systemowa — czytelność dla dyslektyków)
function applySansFont(active) {
    document.documentElement.classList.toggle('a11y-sans', active);
    localStorage.setItem('a11y-sans', active ? '1' : '0');
    document.querySelectorAll('[data-a11y-sans]').forEach((btn) => {
        btn.setAttribute('aria-pressed', String(active));
    });
}

const storedSans = localStorage.getItem('a11y-sans') === '1';
if (storedSans) applySansFont(true);

document.querySelectorAll('[data-a11y-sans]').forEach((btn) => {
    btn.addEventListener('click', () => {
        applySansFont(!document.documentElement.classList.contains('a11y-sans'));
    });
});

// Odstępy między wierszami: '' | 'a11y-lh-1' | 'a11y-lh-2'
const LH_MODES = ['', 'a11y-lh-1', 'a11y-lh-2'];
const LH_LABELS = ['Normalne', 'Zwiększone', 'Bardzo zwiększone'];

function applyLineHeight(mode) {
    LH_MODES.forEach((cls) => { if (cls) document.documentElement.classList.remove(cls); });
    if (mode) document.documentElement.classList.add(mode);
    localStorage.setItem('a11y-lh', mode || '');
    document.querySelectorAll('[data-a11y-lh]').forEach((btn) => {
        const idx = LH_MODES.indexOf(mode);
        const next = LH_MODES[(idx + 1) % LH_MODES.length];
        btn.setAttribute('aria-label', 'Odstępy między wierszami: ' + (LH_LABELS[idx] || 'Normalne') + ' → kliknij: ' + LH_LABELS[(idx + 1) % LH_MODES.length]);
        btn.setAttribute('aria-pressed', String(!!mode));
    });
}

const storedLh = localStorage.getItem('a11y-lh') || '';
if (storedLh) applyLineHeight(storedLh);

document.querySelectorAll('[data-a11y-lh]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const current = localStorage.getItem('a11y-lh') || '';
        const idx = LH_MODES.indexOf(current);
        applyLineHeight(LH_MODES[(idx + 1) % LH_MODES.length]);
    });
});

// Podkreślenie linków (niezależne od koloru — pomaga przy achromatopsji/dysleksji)
function applyUnderlineLinks(active) {
    document.documentElement.classList.toggle('a11y-underline-links', active);
    localStorage.setItem('a11y-underline-links', active ? '1' : '0');
    document.querySelectorAll('[data-a11y-underline-links]').forEach((btn) => {
        btn.setAttribute('aria-pressed', String(active));
    });
}

const storedUnderline = localStorage.getItem('a11y-underline-links') === '1';
if (storedUnderline) applyUnderlineLinks(true);

document.querySelectorAll('[data-a11y-underline-links]').forEach((btn) => {
    btn.addEventListener('click', () => {
        applyUnderlineLinks(!document.documentElement.classList.contains('a11y-underline-links'));
    });
});

// Wyraźny wskaźnik fokusu (gruby, kontrastowy outline na aktywnym elemencie)
function applyFocusIndicator(active) {
    document.documentElement.classList.toggle('a11y-focus', active);
    localStorage.setItem('a11y-focus', active ? '1' : '0');
    document.querySelectorAll('[data-a11y-focus]').forEach((btn) => {
        btn.setAttribute('aria-pressed', String(active));
    });
}

const storedFocus = localStorage.getItem('a11y-focus') === '1';
if (storedFocus) applyFocusIndicator(true);

document.querySelectorAll('[data-a11y-focus]').forEach((btn) => {
    btn.addEventListener('click', () => {
        applyFocusIndicator(!document.documentElement.classList.contains('a11y-focus'));
    });
});

// Tryby kontrastowe: '' (brak) | 'contrast' | 'contrast-bw' | 'contrast-gray'
const CONTRAST_CLASSES = ['contrast', 'contrast-bw', 'contrast-gray'];

function applyContrastMode(mode) {
    CONTRAST_CLASSES.forEach((cls) => document.documentElement.classList.remove(cls));
    if (mode) document.documentElement.classList.add(mode);
    localStorage.setItem('a11y-contrast-mode', mode || '');

    document.querySelectorAll('[data-a11y-contrast]').forEach((btn) => {
        const btnMode = btn.dataset.a11yContrast || 'contrast';
        btn.setAttribute('aria-pressed', String(btnMode === mode));
    });
}

// Migracja ze starego klucza binarnego
const legacyContrast = localStorage.getItem('a11y-contrast');
if (legacyContrast === '1') {
    localStorage.setItem('a11y-contrast-mode', 'contrast');
    localStorage.removeItem('a11y-contrast');
} else if (legacyContrast === '0') {
    localStorage.removeItem('a11y-contrast');
}

const savedContrastMode = localStorage.getItem('a11y-contrast-mode') || '';
if (savedContrastMode) applyContrastMode(savedContrastMode);

document.querySelectorAll('[data-a11y-contrast]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const btnMode = btn.dataset.a11yContrast || 'contrast';
        const current = localStorage.getItem('a11y-contrast-mode') || '';
        applyContrastMode(current === btnMode ? '' : btnMode);
    });
});

// Wyłączanie animacji: klasa `no-animations` na <html> zeruje animacje i przejścia
// (CSS), a zdarzenie `a11y-animations-changed` pozwala też wstrzymać karuzelę hero.
const animationsButton = document.querySelector('[data-a11y-animations]');
if (animationsButton) {
    if (localStorage.getItem('a11y-animations') === '1') {
        document.documentElement.classList.add('no-animations');
        animationsButton.setAttribute('aria-pressed', 'true');
    }

    animationsButton.addEventListener('click', () => {
        const isDisabled = document.documentElement.classList.toggle('no-animations');
        animationsButton.setAttribute('aria-pressed', String(isDisabled));
        localStorage.setItem('a11y-animations', isDisabled ? '1' : '0');
        window.dispatchEvent(new CustomEvent('a11y-animations-changed', { detail: { disabled: isDisabled } }));
    });
}

// Reset — przywraca wszystkie ustawienia dostępności do wartości domyślnych
document.querySelectorAll('[data-a11y-reset]').forEach((btn) => {
    btn.addEventListener('click', () => {
        applyFontSize(100);
        applyLetterSpacing('');
        applyLineHeight('');
        applySansFont(false);
        applyUnderlineLinks(false);
        applyContrastMode('');
        if (document.documentElement.classList.contains('no-animations')) {
            animationsButton?.click();
        }
    });
});

// Karuzela hero
const heroSlider = document.querySelector('[data-hero-slider]');
if (heroSlider) {
    const slides = Array.from(heroSlider.querySelectorAll('[data-hero-slide]'));
    const counter = heroSlider.querySelector('[data-hero-counter]');
    const toggleButton = heroSlider.querySelector('[data-hero-toggle]');
    const toggleIcon = heroSlider.querySelector('[data-hero-toggle-icon]');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Auto-advance stays off when the OS asks for reduced motion OR the user
    // turned animations off in the accessibility bar.
    const motionDisabled = prefersReducedMotion || localStorage.getItem('a11y-animations') === '1';

    let activeIndex = 0;
    let timer;
    let userPaused = motionDisabled;

    function showSlide(index) {
        slides[activeIndex].classList.add('opacity-0', 'pointer-events-none');
        slides[activeIndex].classList.remove('opacity-100');
        slides[activeIndex].setAttribute('aria-hidden', 'true');
        slides[activeIndex].querySelector('[data-hero-cta]')?.setAttribute('tabindex', '-1');

        activeIndex = (index + slides.length) % slides.length;

        slides[activeIndex].classList.remove('opacity-0', 'pointer-events-none');
        slides[activeIndex].classList.add('opacity-100');
        slides[activeIndex].removeAttribute('aria-hidden');
        slides[activeIndex].querySelector('[data-hero-cta]')?.removeAttribute('tabindex');

        if (counter) {
            counter.textContent = String(activeIndex + 1);
        }
    }

    function getDelay() {
        const dur = parseInt(slides[activeIndex]?.dataset.heroDuration, 10);
        return (!isNaN(dur) && dur > 0) ? dur * 1000 : 6000;
    }

    // WCAG 2.2.2 (Pause, Stop, Hide): auto-advance can always be stopped, and
    // never starts at all for users who asked for reduced motion.
    function restartTimer() {
        clearTimeout(timer);
        if (!userPaused) {
            timer = setTimeout(() => { showSlide(activeIndex + 1); restartTimer(); }, getDelay());
        }
    }

    function setPaused(paused) {
        userPaused = paused;
        toggleButton?.setAttribute('aria-pressed', String(paused));
        toggleButton?.setAttribute('aria-label', paused ? 'Wznów automatyczną zmianę slajdów' : 'Wstrzymaj automatyczną zmianę slajdów');
        toggleIcon?.classList.toggle('fa-play', paused);
        toggleIcon?.classList.toggle('fa-pause', !paused);
        restartTimer();
    }

    heroSlider.querySelector('[data-hero-prev]')?.addEventListener('click', () => {
        showSlide(activeIndex - 1);
        restartTimer();
    });

    heroSlider.querySelector('[data-hero-next]')?.addEventListener('click', () => {
        showSlide(activeIndex + 1);
        restartTimer();
    });

    toggleButton?.addEventListener('click', () => setPaused(!userPaused));

    // Pause on hover/keyboard focus so a slide doesn't change under a reading user;
    // resume only if they hadn't explicitly paused it themselves.
    heroSlider.addEventListener('mouseenter', () => clearTimeout(timer));
    heroSlider.addEventListener('mouseleave', () => restartTimer());
    heroSlider.addEventListener('focusin', () => clearTimeout(timer));
    heroSlider.addEventListener('focusout', () => restartTimer());

    setPaused(userPaused);

    // React to the accessibility "disable animations" toggle in real time:
    // pause auto-advance when animations go off, resume when turned back on.
    window.addEventListener('a11y-animations-changed', (event) => setPaused(event.detail.disabled));
}

// Przewijanie galerii
const galleryTrack = document.querySelector('[data-gallery-track]');
if (galleryTrack) {
    document.querySelector('[data-gallery-prev]')?.addEventListener('click', () => {
        galleryTrack.scrollBy({ left: -240, behavior: 'smooth' });
    });

    document.querySelector('[data-gallery-next]')?.addEventListener('click', () => {
        galleryTrack.scrollBy({ left: 240, behavior: 'smooth' });
    });
}

// Miniatury PDF (materiały edukacyjne) — ładowane leniwie tylko tam, gdzie są.
const pdfThumbs = document.querySelectorAll('canvas[data-pdf-thumb]');
if (pdfThumbs.length) {
    import('./pdf-thumbs.js').then((module) => module.renderPdfThumbs(pdfThumbs));
}

// Mapa pomocy (Leaflet) — ładowana leniwie tylko na stronie /mapa-pomocy.
const helpMapEl = document.getElementById('help-map');
if (helpMapEl) {
    import('./help-map.js').then((module) => module.initHelpMap(helpMapEl));
}

// Przechwytuje submit formularzy z data-confirm → Alpine modal zamiast confirm().
// Jeśli formularz ma data-clone-count > 0, modal pokazuje trzeci przycisk
// „Usuń z kopiami". Wybranie go dodaje hidden input with_clones=1 przed submit.
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.dataset.confirm) return;
    e.preventDefault();
    const cloneCount = parseInt(form.dataset.cloneCount ?? '0', 10);
    let result;
    if (cloneCount > 0) {
        const label = `Usuń oryginał i ${cloneCount} ${cloneCount === 1 ? 'kopię' : 'kopie'}`;
        result = await Alpine.store('confirm').askWithExtra(form.dataset.confirm, label);
    } else {
        result = await Alpine.store('confirm').ask(form.dataset.confirm);
    }
    if (!result) return;
    if (result === 'extra') {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'with_clones'; inp.value = '1';
        form.appendChild(inp);
    }
    form.submit();
}, { capture: true });

// Live preview sluga: auto-generuje slug z tytułu dla nowych rekordów.
// Na istniejących slug jest już ustawiony — pojawia się przycisk ↺ reset.
(function () {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    if (!titleInput || !slugInput) return;

    const pl = { ą:'a',ć:'c',ę:'e',ł:'l',ń:'n',ó:'o',ś:'s',ź:'z',ż:'z',Ą:'a',Ć:'c',Ę:'e',Ł:'l',Ń:'n',Ó:'o',Ś:'s',Ź:'z',Ż:'z' };

    function slugify(str) {
        return str.split('').map(c => pl[c] ?? c).join('')
            .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }

    let auto = slugInput.value === '';

    const resetBtn = document.createElement('button');
    resetBtn.type = 'button';
    resetBtn.title = 'Wygeneruj slug ponownie z tytułu';
    resetBtn.setAttribute('aria-label', 'Wygeneruj slug ponownie z tytułu');
    resetBtn.innerHTML = '<i class="fa-solid fa-rotate-left text-xs" aria-hidden="true"></i>';
    resetBtn.className = 'flex-none rounded p-1 text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
    slugInput.parentNode.appendChild(resetBtn);

    function syncBtn() {
        resetBtn.style.display = (!auto && titleInput.value) ? '' : 'none';
    }

    resetBtn.addEventListener('click', () => {
        slugInput.value = slugify(titleInput.value);
        auto = true;
        syncBtn();
        slugInput.focus();
    });

    titleInput.addEventListener('input', () => {
        if (auto) slugInput.value = slugify(titleInput.value);
        syncBtn();
    });

    slugInput.addEventListener('input', () => {
        auto = slugInput.value === '';
        syncBtn();
    });

    syncBtn();
})();

// WCAG 3.2.5 (G201): każdemu linkowi otwieranemu w nowej karcie dodaj ukryty
// dla wzroku dopisek „(link otwiera się w nowej karcie)" — czytniki ekranu
// odczytają go razem z tekstem linku. Wizualny sygnał (ikonę) zapewnia CSS.
// Przy okazji domykamy bezpieczeństwo: rel=noopener.
document.querySelectorAll('a[target="_blank"]').forEach((link) => {
    if (link.dataset.newtabNoted) return;
    link.dataset.newtabNoted = '1';

    const rel = (link.getAttribute('rel') || '').split(/\s+/).filter(Boolean);
    if (!rel.includes('noopener')) rel.push('noopener');
    link.setAttribute('rel', rel.join(' '));

    const note = document.createElement('span');
    note.className = 'sr-only';
    note.textContent = ' (link otwiera się w nowej karcie)';
    link.appendChild(note);
});

import './push';
