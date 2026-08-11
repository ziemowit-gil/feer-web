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
