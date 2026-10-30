{{--
    Widoczność listy „Pliki do pobrania” na stronie publicznej. Wyłączenie nie usuwa plików: zostają w sekcji
    „Pliki do pobrania” w panelu i można je włączyć z powrotem. Zapisuje się w layout_data.hide_attachments.
--}}
@php $hideAttachments = (bool) old('layout_data.hide_attachments', ($page->layout_data ?? [])['hide_attachments'] ?? false); @endphp
<details class="pe-section rounded-md bg-white" data-pe-section="lista-plikow">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-4 text-base font-bold text-ink [&::-webkit-details-marker]:hidden">
        <span>Lista plików na stronie</span>
        <i class="fa-solid fa-chevron-down text-sm text-muted transition-transform" aria-hidden="true"></i>
    </summary>
    <div class="border-t border-gray-100 px-6 pb-6 pt-5">
        <input type="hidden" name="layout_data[hide_attachments]" value="0">
        <label class="flex items-start gap-3 text-sm text-ink">
            <input type="checkbox" name="layout_data[hide_attachments]" value="1" class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked($hideAttachments) aria-describedby="hide-attachments-help">
            <span><span class="font-bold">Ukryj listę „Pliki do pobrania” na tej stronie</span>
                <span id="hide-attachments-help" class="mt-1 block text-ink">Pliki zostają w panelu (sekcja „Pliki do pobrania”), tylko nie pokazują się odwiedzającym.</span></span>
        </label>
    </div>
</details>
