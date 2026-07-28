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

        // Content images, gallery images, and anything opted in with data-lightbox.
        const triggers = Array.from(document.querySelectorAll(
            '.prose img, [data-gallery-track] img, img[data-lightbox], [data-lightbox] img'
        ));
        if (!triggers.length) return;

        let group = [];
        let index = 0;
        let lastFocused = null;

        function groupFor(img) {
            // Prev/next grouping scope: a gallery track, a container opted-in with
            // data-lightbox (but not the image itself — a bare img[data-lightbox]
            // stays a singleton), or the surrounding prose.
            const scope = img.closest('[data-gallery-track]')
                || img.closest('[data-lightbox]:not(img)')
                || img.closest('.prose');
            if (!scope) return [img];
            return Array.from(scope.querySelectorAll('img')).filter(function (i) { return triggers.indexOf(i) !== -1; });
        }

        function render() {
            const img = group[index];
            imgEl.src = img.currentSrc || img.src;
            imgEl.alt = img.alt || '';
            captionEl.textContent = img.alt || '';
            captionEl.classList.toggle('hidden', !img.alt);

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

        triggers.forEach(function (img) {
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
        });

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
