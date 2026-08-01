import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global confirm dialog — zastępuje natywne confirm() alertdialogiem Alpine.
// Formularze z atrybutem data-confirm są przechwytywane automatycznie.
Alpine.store('confirm', {
    open: false,
    message: '',
    _resolve: null,
    ask(message) {
        return new Promise(resolve => {
            this.message = message;
            this.open = true;
            this._resolve = resolve;
            Alpine.nextTick(() => document.getElementById('confirm-cancel-btn')?.focus());
        });
    },
    confirm() { this.open = false; this._resolve?.(true); },
    cancel() { this.open = false; this._resolve?.(false); },
});

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

const contrastButton = document.querySelector('[data-a11y-contrast]');
if (contrastButton) {
    if (localStorage.getItem('a11y-contrast') === '1') {
        document.documentElement.classList.add('contrast');
        contrastButton.setAttribute('aria-pressed', 'true');
    }

    contrastButton.addEventListener('click', () => {
        const isActive = document.documentElement.classList.toggle('contrast');
        contrastButton.setAttribute('aria-pressed', String(isActive));
        localStorage.setItem('a11y-contrast', isActive ? '1' : '0');
    });
}

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

    // WCAG 2.2.2 (Pause, Stop, Hide): auto-advance can always be stopped, and
    // never starts at all for users who asked for reduced motion.
    function restartTimer() {
        clearInterval(timer);
        if (!userPaused) {
            timer = setInterval(() => showSlide(activeIndex + 1), 6000);
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
    heroSlider.addEventListener('mouseenter', () => clearInterval(timer));
    heroSlider.addEventListener('mouseleave', () => restartTimer());
    heroSlider.addEventListener('focusin', () => clearInterval(timer));
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
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.dataset.confirm) return;
    e.preventDefault();
    const ok = await Alpine.store('confirm').ask(form.dataset.confirm);
    if (ok) form.submit();
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
