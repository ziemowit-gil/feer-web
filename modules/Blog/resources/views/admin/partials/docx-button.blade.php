{{--
    Przycisk „Import z Worda” w edytorze wpisu (tryb wizualny: target=html, Markdown: target=markdown). Wersja darmowa
    CMS: przycisk zostaje, ale po kliknięciu wyjaśnia, że to funkcja wersji płatnej. Obsługa: docx-script.
--}}
<button type="button" data-docx-import="{{ $target }}" @unless ($docxAllowed) data-docx-locked="1" @endunless
    aria-describedby="docx-import-hint"
    class="inline-flex min-h-10 items-center gap-2 rounded border border-gray-400 bg-white px-4 text-sm font-bold text-ink hover:bg-gray-50 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
    <i class="fa-solid fa-file-word text-blue-700" aria-hidden="true"></i> Import z Worda
</button>
@once
    <span id="docx-import-hint" class="sr-only">Wstawia treść pliku .docx (nagłówki, listy, pogrubienia, tabele, zdjęcia) na końcu wpisu.</span>
    <input type="file" id="blog-docx-input" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="sr-only" tabindex="-1" aria-hidden="true">
    <p id="blog-docx-status" class="sr-only" role="status" aria-live="polite"></p>
@endonce
