import * as pdfjsLib from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

/**
 * Render the first page of each PDF into its canvas. On any failure the canvas
 * is left untouched (transparent), so the icon behind it stays visible as a
 * graceful fallback.
 */
export async function renderPdfThumbs(canvases) {
    for (const canvas of canvases) {
        const url = canvas.dataset.pdfThumb;
        const wrap = canvas.closest('[data-thumb-wrap]');

        if (!url) {
            continue;
        }

        try {
            const pdf = await pdfjsLib.getDocument(url).promise;
            const page = await pdf.getPage(1);

            const base = page.getViewport({ scale: 1 });
            const targetWidth = (canvas.clientWidth || 400) * (window.devicePixelRatio || 1);
            const viewport = page.getViewport({ scale: targetWidth / base.width });

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

            wrap?.classList.add('pdf-thumb-loaded');
        } catch (error) {
            wrap?.classList.add('pdf-thumb-failed');
        }
    }
}
