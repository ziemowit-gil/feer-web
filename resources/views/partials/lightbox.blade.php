<div data-lightbox-overlay class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label="Podgląd grafiki">
    <button type="button" data-lightbox-close class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20" aria-label="Zamknij podgląd grafiki">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>

    <button type="button" data-lightbox-prev class="absolute left-2 top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20" aria-label="Poprzednia grafika">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
    <button type="button" data-lightbox-next class="absolute right-2 top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20" aria-label="Następna grafika">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>

    <figure class="flex max-h-full w-full max-w-5xl flex-col items-center">
        <img data-lightbox-image src="" alt="" class="max-h-[85vh] max-w-full rounded object-contain">
        <figcaption data-lightbox-caption class="mt-3 hidden text-center text-sm text-white/80"></figcaption>
    </figure>
</div>

<script>
    (function () {
        const overlay = document.querySelector('[data-lightbox-overlay]');
        if (!overlay) return;

        const imgEl = overlay.querySelector('[data-lightbox-image]');
        const captionEl = overlay.querySelector('[data-lightbox-caption]');
        const prevBtn = overlay.querySelector('[data-lightbox-prev]');
        const nextBtn = overlay.querySelector('[data-lightbox-next]');
        const closeBtn = overlay.querySelector('[data-lightbox-close]');

        // Jawnie oznaczone: treść (.prose), galerie i wszystko z data-lightbox.
        const EXPLICIT = '.prose img, [data-gallery-track] img, img[data-lightbox], [data-lightbox] img';
        // Ogólnie: każde zdjęcie w treści strony (<main>) — poza linkami/przyciskami, klikalnymi kartami
        // (stretched-link), logotypami (object-contain), miniaturami i tym, co oznaczono data-no-lightbox.
        const CARD = 'article, li, .feer-card, .group';
        const MIN_NATURAL = 240;  // px — mniejsze pliki to ikony/miniatury
        const MIN_RENDERED = 96;  // px — tak małe na stronie nie wymagają powiększenia

        const triggers = [];

        function inClickableCard(img) {
            const card = img.closest(CARD);
            if (!card) return false;
            return Array.from(card.querySelectorAll('a[href].stretched-link, a[href][class*="after:absolute"], a[href][class*="before:absolute"]'))
                .some(function (a) { return a.closest(CARD) === card; });
        }

        function structurallyEligible(img) {
            if (img.matches(EXPLICIT)) return true;
            if (!img.closest('main') || img.closest('[data-lightbox-overlay]')) return false;
            if (img.closest('a, button, label, summary, [role="button"], [data-no-lightbox], [aria-hidden="true"], [aria-roledescription]')) return false;
            if (img.classList.contains('object-contain') || img.getAttribute('role') === 'presentation') return false;
            return !inClickableCard(img);
        }

        function bigEnough(img) {
            if (img.matches(EXPLICIT)) return true;
            if (img.complete && img.naturalWidth && img.naturalWidth < MIN_NATURAL && img.naturalHeight < MIN_NATURAL) return false;
            const r = img.getBoundingClientRect();
            // Ukryte (np. „hidden sm:block") mają 0 — wtedy decyduje sam plik.
            return !(r.width > 0 && r.width < MIN_RENDERED && r.height < MIN_RENDERED);
        }

        function arm(img) {
            if (triggers.indexOf(img) !== -1) return;
            triggers.push(img);
            img.classList.add('cursor-zoom-in');
            img.setAttribute('tabindex', '0');
            img.setAttribute('role', 'button');
            img.setAttribute('aria-label', 'Powiększ grafikę' + (img.alt ? ': ' + img.alt : ''));
            img.addEventListener('click', function () { open(img); });
            img.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    open(img);
                }
            });
        }

        function consider(img) {
            if (!structurallyEligible(img)) return;
            if (img.complete || img.matches(EXPLICIT)) {
                if (bigEnough(img)) arm(img);
            } else {
                // Leniwie ładowane — rozmiar pliku znany dopiero po wczytaniu.
                img.addEventListener('load', function () { if (bigEnough(img)) arm(img); }, { once: true });
            }
        }

        document.querySelectorAll('main img, ' + EXPLICIT).forEach(consider);

        let group = [];
        let index = 0;
        let lastFocused = null;

        function groupFor(img) {
            // Prev/next grouping scope: a gallery track, a container opted-in with
            // data-lightbox (but not the image itself — a bare img[data-lightbox]
            // stays a singleton), or the surrounding prose.
            const scope = img.closest('[data-gallery-track]')
                || img.closest('[data-lightbox]:not(img)')
                || img.closest('.prose')
                || img.closest('section');
            if (!scope) return [img];
            return Array.from(scope.querySelectorAll('img')).filter(function (i) { return triggers.indexOf(i) !== -1; });
        }

        function render() {
            const img = group[index];
            imgEl.src = img.dataset.full || img.currentSrc || img.src;
            imgEl.alt = img.alt || '';
            const fig = img.closest('figure');
            const figCap = fig && fig.querySelector('figcaption');
            const caption = (figCap && figCap.textContent.trim()) || img.alt || '';
            captionEl.textContent = caption;
            captionEl.classList.toggle('hidden', !caption);

            const many = group.length > 1;
            [prevBtn, nextBtn].forEach(function (btn) {
                btn.classList.toggle('flex', many);
                btn.classList.toggle('hidden', !many);
            });
        }

        function step(delta) {
            index = (index + delta + group.length) % group.length;
            render();
        }

        function open(img) {
            lastFocused = document.activeElement;
            group = groupFor(img);
            index = Math.max(0, group.indexOf(img));
            render();
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            document.body.style.overflow = 'hidden';
            closeBtn.focus();
        }

        function close() {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            document.body.style.overflow = '';
            imgEl.src = '';
            if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
        }

        closeBtn.addEventListener('click', close);
        prevBtn.addEventListener('click', function () { step(-1); });
        nextBtn.addEventListener('click', function () { step(1); });
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) close();
        });
        document.addEventListener('keydown', function (event) {
            if (overlay.classList.contains('hidden')) return;
            if (event.key === 'Escape') close();
            else if (event.key === 'ArrowLeft') step(-1);
            else if (event.key === 'ArrowRight') step(1);
        });
    })();
</script>
