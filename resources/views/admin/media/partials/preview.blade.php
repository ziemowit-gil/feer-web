{{--
    Podgląd pliku na pełnym ekranie z przewijaniem po plikach tej strony (jak podgląd w menedżerze mediów Joomli):
    obraz (z opisem alternatywnym pod spodem), wideo, dźwięk, PDF; inne pliki — informacja i pobranie.
    Strzałki ←/→ przełączają pliki, Esc zamyka i wraca do przycisku, który otworzył podgląd.
    Stan w Alpine.data('mediaLibrary') (previewIdx, items).
--}}
<div x-show="previewItem" x-cloak class="fixed inset-0 bg-ink text-white" style="z-index: 55"
    role="dialog" aria-modal="true" aria-labelledby="media-preview-title"
    @keydown.escape.stop.prevent="closePreview()" @keydown.left="previewKey($event, -1)" @keydown.right="previewKey($event, 1)"
    @keydown.tab="trapIn($event, $el)">
    <template x-if="previewItem">
        <div class="flex h-full flex-col">
            <div class="flex flex-wrap items-center gap-2 px-4 py-3">
                <h2 id="media-preview-title" tabindex="-1" class="min-w-0 flex-1 focus:outline-none">
                    <span class="block truncate font-bold" x-text="previewItem.name"></span>
                    <span class="block text-sm text-gray-300" x-text="previewItem.ext + (previewItem.ext ? ', ' : '') + previewItem.size + ' · plik ' + (previewIdx + 1) + ' z ' + items.length"></span>
                </h2>
                <button type="button" @click="detailsFromPreview()" aria-haspopup="dialog"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-white/10 px-4 text-sm font-bold text-white hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Szczegóły
                </button>
                <button type="button" x-show="previewItem.edit" @click="editFromPreview()" aria-haspopup="dialog"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-white/10 px-4 text-sm font-bold text-white hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <i class="fa-solid fa-crop-simple" aria-hidden="true"></i> Edytuj obraz
                </button>
                <a :href="previewItem.url" download
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-white/10 px-4 text-sm font-bold text-white hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <i class="fa-solid fa-arrow-down" aria-hidden="true"></i> Pobierz
                </a>
                <button type="button" @click="closePreview()" aria-label="Zamknij podgląd (Esc)"
                    class="inline-flex min-h-10 items-center rounded-md bg-white px-3 text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="relative flex min-h-0 flex-1 flex-col items-center justify-center gap-3 px-14 pb-4 sm:px-20">
                <button type="button" x-show="items.length > 1" @click="step(-1)" aria-label="Poprzedni plik (strzałka w lewo)"
                    class="absolute left-2 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-md bg-white/10 text-white hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:left-4">
                    <i class="fa-solid fa-chevron-left text-xl" aria-hidden="true"></i>
                </button>
                <button type="button" x-show="items.length > 1" @click="step(1)" aria-label="Następny plik (strzałka w prawo)"
                    class="absolute right-2 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-md bg-white/10 text-white hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:right-4">
                    <i class="fa-solid fa-chevron-right text-xl" aria-hidden="true"></i>
                </button>

                <template x-if="previewItem.kind === 'image'">
                    <img :src="previewItem.url" :alt="previewItem.alt" class="min-h-0 max-w-full flex-1 object-contain">
                </template>
                <template x-if="previewItem.kind === 'video'">
                    <video :src="previewItem.url" controls preload="metadata" class="min-h-0 max-w-full flex-1"></video>
                </template>
                <template x-if="previewItem.kind === 'audio'">
                    <audio :src="previewItem.url" controls preload="metadata" class="w-full max-w-xl"></audio>
                </template>
                <template x-if="previewItem.kind === 'pdf'">
                    <iframe :src="previewItem.url" :title="'Podgląd dokumentu: ' + previewItem.name" class="min-h-0 w-full max-w-5xl flex-1 rounded bg-white"></iframe>
                </template>
                <template x-if="previewItem.kind === 'other'">
                    <div class="text-center">
                        <i class="fa-solid fa-file-lines mb-3 text-6xl text-white" aria-hidden="true"></i>
                        <p class="font-bold">Podgląd tego rodzaju pliku nie jest dostępny.</p>
                        <p class="mt-1 text-sm text-gray-300">Pobierz plik, aby go otworzyć.</p>
                    </div>
                </template>

                <p x-show="previewItem.altNote" x-text="previewItem.altNote" class="max-w-3xl text-center text-sm text-gray-300"></p>
            </div>
        </div>
    </template>
</div>
