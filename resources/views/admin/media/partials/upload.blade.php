{{--
    Wgrywanie plików do bieżącego folderu: przeciągnij i upuść albo wybór z dysku,
    autor i opis alternatywny dla każdego pliku, kadrowanie obrazów przed wysłaniem (Cropper.js).
    Expects: $folder (MediaFolder|null).
--}}
<form method="POST" action="{{ route('admin.multimedia.store') }}" enctype="multipart/form-data"
    x-data="{
        files: [],
        meta: [],
        croppedFiles: {},
        cropIdx: null,
        cropperInst: null,
        drag: false,
        setFiles(list) {
            this.files = Array.from(list);
            this.croppedFiles = {};
            this.meta = this.files.map(f => ({
                author: '',
                alt: f.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ')
            }));
        },
        onDrop(event) {
            this.drag = false;
            this.$refs.input.files = event.dataTransfer.files;
            this.setFiles(event.dataTransfer.files);
        },
        clear() { this.$refs.input.value = ''; this.files = []; this.meta = []; this.croppedFiles = {}; },
        isImage(f) { return f.type.startsWith('image/'); },
        openCrop(idx) {
            this.cropIdx = idx;
            const src = this.croppedFiles[idx]
                ? URL.createObjectURL(this.croppedFiles[idx])
                : URL.createObjectURL(this.files[idx]);
            this.$nextTick(() => {
                const img = this.$refs.cropImg;
                img.src = src;
                if (this.cropperInst) this.cropperInst.destroy();
                this.cropperInst = new Cropper(img, { viewMode: 1, autoCropArea: 0.9 });
            });
        },
        confirmCrop() {
            const idx = this.cropIdx;
            const mime = this.files[idx]?.type || 'image/jpeg';
            this.cropperInst.getCroppedCanvas({ maxWidth: 4096 }).toBlob(blob => {
                this.croppedFiles[idx] = blob;
                this.cancelCrop();
            }, mime, 0.92);
        },
        cancelCrop() {
            this.cropIdx = null;
            if (this.cropperInst) { this.cropperInst.destroy(); this.cropperInst = null; }
        },
        prepareAndSubmit(form) {
            if (Object.keys(this.croppedFiles).length === 0) { form.submit(); return; }
            const dt = new DataTransfer();
            this.files.forEach((f, i) => {
                dt.items.add(this.croppedFiles[i]
                    ? new File([this.croppedFiles[i]], f.name, { type: f.type })
                    : f);
            });
            this.$refs.input.files = dt.files;
            form.submit();
        }
    }"
    @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="onDrop($event)"
    @submit.prevent="prepareAndSubmit($el)"
    class="rounded-md bg-white p-4">
    @csrf
    <input type="hidden" name="folder_id" value="{{ $folder?->id }}">

    <input id="media-upload" x-ref="input" type="file" name="files[]" multiple required
        class="peer sr-only" @change="setFiles($event.target.files)">
    <label for="media-upload"
        class="flex cursor-pointer items-center gap-4 rounded-md px-6 py-8 transition peer-focus-visible:ring-2 peer-focus-visible:ring-brand-dark"
        :class="drag ? 'bg-brand-light' : 'bg-gray-50 hover:bg-brand-light'">
        <i class="fa-solid fa-cloud-arrow-up flex-none text-3xl text-brand-dark" aria-hidden="true"></i>
        <div>
            <p class="text-sm font-bold text-ink">Przeciągnij pliki tutaj lub kliknij, aby wybrać</p>
            <p class="text-xs text-muted">Wiele plików naraz &middot; maks. 10 MB każdy</p>
        </div>
    </label>

    <template x-if="files.length">
        <div class="mt-3 overflow-hidden rounded-md bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-widest text-muted">
                    Wybrane pliki (<span x-text="files.length"></span>)
                </p>
                <p class="text-[10px] text-muted">Wypełnij autora i opis — pomagają przy wyszukiwaniu i dostępności</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-100 bg-gray-50 text-[10px] font-bold uppercase tracking-widest text-muted">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left">Plik</th>
                            <th scope="col" class="px-4 py-2 text-left">
                                Autor / twórca
                                <span class="font-normal normal-case tracking-normal">(opcjonalnie)</span>
                            </th>
                            <th scope="col" class="px-4 py-2 text-left">
                                Opis alternatywny
                                <span class="font-normal normal-case tracking-normal">(zalecany dla obrazów)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(file, i) in files" :key="i">
                            <tr>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-file flex-none text-muted" aria-hidden="true"></i>
                                        <span class="max-w-[10rem] truncate text-sm" x-text="file.name"></span>
                                        <template x-if="isImage(file)">
                                            <button type="button" @click="openCrop(i)"
                                                class="flex-none rounded-md px-1.5 py-0.5 text-[10px] font-bold text-muted hover:text-brand-dark bg-gray-100 hover:bg-brand-light"
                                                :aria-label="'Kadruj plik ' + file.name">
                                                <i class="fa-solid fa-crop-simple" aria-hidden="true"></i>
                                                <span x-text="croppedFiles[i] ? 'Kadrowano' : 'Kadruj'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <label :for="'upl-author-' + i" class="sr-only"
                                        x-text="'Autor pliku ' + file.name"></label>
                                    <input :id="'upl-author-' + i" type="text"
                                        :name="'authors[' + i + ']'"
                                        x-model="meta[i].author"
                                        placeholder="np. Jan Kowalski"
                                        class="w-full min-w-[10rem] rounded-lg border-gray-500 py-1.5 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                </td>
                                <td class="px-4 py-2">
                                    <label :for="'upl-alt-' + i" class="sr-only"
                                        x-text="'Opis alternatywny pliku ' + file.name"></label>
                                    <input :id="'upl-alt-' + i" type="text"
                                        :name="'alts[' + i + ']'"
                                        x-model="meta[i].alt"
                                        placeholder="Co przedstawia ten plik?"
                                        class="w-full min-w-[14rem] rounded-lg border-gray-500 py-1.5 text-xs focus:border-brand-dark focus:ring-brand-dark">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-3 border-t border-gray-100 px-4 py-3">
                <button type="submit"
                    class="rounded-lg bg-brand-dark px-4 py-2 text-xs font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-upload" aria-hidden="true"></i>
                    Prześlij (<span x-text="files.length"></span>)
                </button>
                <button type="button" @click="clear()"
                    class="text-xs font-bold text-muted hover:text-ink">Wyczyść</button>
            </div>
        </div>
    </template>

    {{-- Modal kadrowania --}}
    <div x-show="cropIdx !== null" x-cloak
        @keydown.escape.window="cancelCrop()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        role="dialog" aria-modal="true" aria-label="Kadruj obraz"
        x-transition.opacity>
        <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-bold text-ink">Kadrowanie obrazu</h2>
                <button type="button" @click="cancelCrop()"
                    class="rounded-lg p-1.5 text-muted hover:bg-gray-100 hover:text-ink"
                    aria-label="Zamknij">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden bg-gray-100 p-2">
                <img x-ref="cropImg" src="" alt="Podgląd do kadrowania"
                    class="block max-h-[60vh] max-w-full mx-auto">
            </div>
            <div class="flex items-center gap-3 border-t border-gray-100 px-5 py-4">
                <button type="button" @click="confirmCrop()"
                    class="rounded-lg bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Zastosuj kadrowanie
                </button>
                <button type="button" @click="cancelCrop()"
                    class="text-sm font-bold text-muted hover:text-ink">Anuluj</button>
                <p class="ml-auto text-xs text-muted">Przeciągnij zaznaczenie &middot; Kółko myszy = zoom</p>
            </div>
        </div>
    </div>
</form>
