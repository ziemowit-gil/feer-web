{{--
    Podgląd PDF materiału w wyskakującym okienku (natywny <dialog>: pułapka fokusu, Esc zamyka,
    fokus wraca na przycisk „Podgląd"). Przyciski z data-material-preview otwierają okienko;
    bez JS albo bez wbudowanego podglądu PDF w przeglądarce zostają zwykłymi linkami do pliku. Płasko: biel, ink, kolor marki.
--}}
<dialog data-material-preview-dialog aria-labelledby="material-preview-title"
    class="m-auto h-[min(92vh,60rem)] w-[min(96vw,64rem)] max-w-none overflow-hidden rounded-lg bg-white p-0 text-ink backdrop:bg-ink/80">
    <div class="flex h-full flex-col">
        <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-5">
            <h2 id="material-preview-title" data-material-preview-title class="min-w-0 flex-1 truncate text-base font-bold sm:text-lg"></h2>
            <a data-material-preview-download href="#" download
               class="hidden min-h-11 shrink-0 items-center gap-2 rounded-md bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 sm:inline-flex">
                <i class="fa-solid fa-download" aria-hidden="true"></i> Pobierz PDF
            </a>
            <a data-material-preview-tab href="#" target="_blank" rel="noopener"
               class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-md px-3 text-sm font-bold text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span class="hidden md:inline">Nowa karta</span><span class="sr-only md:hidden">Otwórz w nowej karcie</span>
            </a>
            <button type="button" data-material-preview-close
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-xl text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-label="Zamknij podgląd">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <iframe data-material-preview-frame title="" class="min-h-0 w-full flex-1 bg-gray-100"></iframe>
    </div>
</dialog>

<script>
    (function () {
        const dialog = document.querySelector('[data-material-preview-dialog]');
        // Bez wbudowanego podglądu PDF (większość telefonów) ramka zostałaby pusta — wtedy link po prostu otwiera plik.
        if (!dialog || typeof dialog.showModal !== 'function' || navigator.pdfViewerEnabled === false) return;

        const frame = dialog.querySelector('[data-material-preview-frame]');
        const titleEl = dialog.querySelector('[data-material-preview-title]');
        const download = dialog.querySelector('[data-material-preview-download]');
        const tab = dialog.querySelector('[data-material-preview-tab]');
        let trigger = null;
        let viaKeyboard = false;

        document.addEventListener('click', function (event) {
            const link = event.target.closest('a[data-material-preview]');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
            event.preventDefault();

            trigger = link;
            // detail === 0: „klik" z klawiatury lub czytnika ekranu — wtedy po zamknięciu obramowanie fokusu zostaje (WCAG 2.4.7).
            viaKeyboard = event.detail === 0;

            const title = link.dataset.materialPreview || 'Podgląd materiału';
            titleEl.textContent = title;
            frame.title = 'Podgląd PDF: ' + title;
            frame.src = link.href;
            download.href = link.href;
            tab.href = link.href;

            dialog.showModal();
            document.body.style.overflow = 'hidden';
            dialog.querySelector('[data-material-preview-close]').focus();
        });

        dialog.querySelector('[data-material-preview-close]').addEventListener('click', function () { dialog.close(); });

        // Klik w przyciemnione tło (poza okienkiem) zamyka podgląd.
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close();
        });

        dialog.addEventListener('close', function () {
            document.body.style.overflow = '';
            frame.removeAttribute('src');
            if (trigger) {
                // Po otwarciu myszą fokus wraca bez niebieskiej ramki; z klawiatury ramka zostaje.
                trigger.focus({ focusVisible: viaKeyboard, preventScroll: true });
                trigger = null;
            }
        });
    })();
</script>
