{{--
    Edytor obrazu w bibliotece (jak „Edytuj” w menedżerze mediów Joomli): kadrowanie z proporcjami
    i polami X/Y/szerokość/wysokość (obsługa z klawiatury), obrót o 90°, odbicie, rozmiar wyniku i jakość.
    Liczy się w przeglądarce (Cropper.js), zapis przez admin.multimedia.edit-image jako nowa wersja
    (ten sam adres, poprzednia w historii wersji) albo jako kopia w tym samym folderze.

    Otwierany zdarzeniem `media-edit` z danymi z MediaLibraryController::editorPayload()
    (szuflada szczegółów, menu akcji kafla, podgląd). Logika: Alpine.data('mediaImageEditor') niżej.
--}}
<div x-data="mediaImageEditor" @media-edit.window="start($event.detail)">
    <div x-show="open" x-cloak class="fixed inset-0 flex items-center justify-center bg-ink/70 p-2 sm:p-4" style="z-index: 60"
        role="dialog" aria-modal="true" aria-labelledby="media-editor-title"
        @keydown.escape.stop.prevent="close()" @keydown.tab="trap($event)">
        <div x-ref="dialog" class="flex w-full max-w-6xl flex-col overflow-hidden rounded-md bg-white shadow-2xl" style="max-height: 96vh">
            <div class="flex items-center justify-between gap-3 bg-gray-50 px-5 py-3">
                <h2 id="media-editor-title" x-ref="heading" tabindex="-1" class="min-w-0 truncate text-base font-bold text-ink focus:outline-none">
                    Edycja obrazu<span x-show="item" x-text="item ? ': ' + item.name : ''"></span>
                </h2>
                <button type="button" @click="close()" class="flex-none rounded-md p-2 text-ink hover:bg-gray-200" aria-label="Zamknij edytor bez zapisu (Esc)">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
                {{-- Obszar obrazu --}}
                <div class="relative min-h-0 flex-1 bg-gray-100 p-3">
                    <div class="h-full w-full" style="height: min(62vh, 44rem); min-height: 16rem">
                        <img x-ref="img" src="" alt="" class="block max-w-full">
                    </div>
                    <p x-show="open && ! ready && ! error" class="absolute inset-0 flex items-center justify-center text-sm text-ink" role="status">
                        <i class="fa-solid fa-spinner fa-spin mr-2" aria-hidden="true"></i> Wczytywanie obrazu…
                    </p>
                </div>

                {{-- Ustawienia --}}
                <div class="w-full flex-none space-y-5 overflow-y-auto p-5 lg:w-80">
                    <fieldset :disabled="! ready">
                        <legend class="mb-2 text-sm font-bold text-ink">Kadrowanie</legend>
                        <label for="me-aspect" class="mb-1 block text-xs font-bold text-ink">Proporcje</label>
                        <select id="me-aspect" x-model="aspect" @change="applyAspect()"
                            class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <template x-for="option in aspects" :key="option.key">
                                <option :value="option.key" x-text="option.label"></option>
                            </template>
                        </select>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div>
                                <label for="me-x" class="mb-1 block text-xs font-bold text-ink">Od lewej (px)</label>
                                <input id="me-x" type="number" min="0" step="1" x-model.number="x" @change="applyCrop()"
                                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                            <div>
                                <label for="me-y" class="mb-1 block text-xs font-bold text-ink">Od góry (px)</label>
                                <input id="me-y" type="number" min="0" step="1" x-model.number="y" @change="applyCrop()"
                                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                            <div>
                                <label for="me-w" class="mb-1 block text-xs font-bold text-ink">Szerokość kadru</label>
                                <input id="me-w" type="number" min="1" step="1" x-model.number="w" @change="applyCrop()"
                                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                            <div>
                                <label for="me-h" class="mb-1 block text-xs font-bold text-ink">Wysokość kadru</label>
                                <input id="me-h" type="number" min="1" step="1" x-model.number="h" @change="applyCrop()"
                                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-muted">Kadr możesz też przeciągnąć myszą na obrazie.</p>
                    </fieldset>

                    <fieldset :disabled="! ready">
                        <legend class="mb-2 text-sm font-bold text-ink">Obrót i odbicie</legend>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="rotateBy(-90)" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-3 text-sm font-bold text-ink hover:bg-brand-light">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> W lewo
                            </button>
                            <button type="button" @click="rotateBy(90)" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-3 text-sm font-bold text-ink hover:bg-brand-light">
                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> W prawo
                            </button>
                            <button type="button" @click="flip('x')" :aria-pressed="flipX < 0"
                                :class="flipX < 0 ? 'bg-brand-dark text-white' : 'bg-gray-100 text-ink hover:bg-brand-light'"
                                class="inline-flex min-h-10 items-center gap-2 rounded-md px-3 text-sm font-bold">
                                <i class="fa-solid fa-left-right" aria-hidden="true"></i> Odbij w poziomie
                            </button>
                            <button type="button" @click="flip('y')" :aria-pressed="flipY < 0"
                                :class="flipY < 0 ? 'bg-brand-dark text-white' : 'bg-gray-100 text-ink hover:bg-brand-light'"
                                class="inline-flex min-h-10 items-center gap-2 rounded-md px-3 text-sm font-bold">
                                <i class="fa-solid fa-up-down" aria-hidden="true"></i> Odbij w pionie
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-muted" aria-live="polite" x-text="'Obrót: ' + rotate + '°'"></p>
                    </fieldset>

                    <fieldset :disabled="! ready">
                        <legend class="mb-2 text-sm font-bold text-ink">Rozmiar wyniku</legend>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="me-out-w" class="mb-1 block text-xs font-bold text-ink">Szerokość (px)</label>
                                <input id="me-out-w" type="number" min="1" max="8000" step="1" x-model.number="outW" @input="linkOut('w')" :placeholder="w"
                                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                            <div>
                                <label for="me-out-h" class="mb-1 block text-xs font-bold text-ink">Wysokość (px)</label>
                                <input id="me-out-h" type="number" min="1" max="8000" step="1" x-model.number="outH" @input="linkOut('h')" :placeholder="h"
                                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-muted">Wymiary trzymają proporcje kadru — wpisz jeden, drugi policzy się sam. Puste pola = rozmiar kadru.</p>
                        <p class="mt-2 text-sm text-ink" aria-live="polite">Wynik: <strong x-text="resultW + ' × ' + resultH + ' px'"></strong></p>
                        <template x-if="lossy">
                            <div class="mt-3">
                                <label for="me-quality" class="mb-1 flex justify-between text-xs font-bold text-ink">
                                    <span>Jakość</span><span x-text="quality + '%'"></span>
                                </label>
                                <input id="me-quality" type="range" min="60" max="100" step="1" x-model.number="quality" class="w-full accent-brand-dark"
                                    :aria-valuetext="quality + ' procent'">
                            </div>
                        </template>
                    </fieldset>

                    <div>
                        <label for="me-note" class="mb-1 block text-xs font-bold text-ink">Opis zmiany <span class="font-normal text-muted">(opcjonalnie, do historii wersji)</span></label>
                        <input id="me-note" type="text" maxlength="500" x-model="note" placeholder="np. przycięte do formatu 16:9"
                            class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    </div>

                    <p x-show="error" x-text="error" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-800" role="alert"></p>

                    <div class="space-y-2">
                        <button type="button" @click="save('version')" :disabled="! ready || busy"
                            class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink disabled:cursor-not-allowed disabled:bg-gray-500">
                            <i class="fa-solid" :class="busy ? 'fa-spinner fa-spin' : 'fa-floppy-disk'" aria-hidden="true"></i> Zapisz jako nową wersję
                        </button>
                        <button type="button" @click="save('copy')" :disabled="! ready || busy"
                            class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light disabled:cursor-not-allowed disabled:text-muted">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i> Zapisz jako kopię
                        </button>
                        <div class="flex gap-2">
                            <button type="button" @click="reset()" :disabled="! ready || busy"
                                class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-md px-3 text-sm font-bold text-ink hover:bg-gray-100">
                                <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Cofnij zmiany
                            </button>
                            <button type="button" @click="close()" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-md px-3 text-sm font-bold text-ink hover:bg-gray-100">
                                Anuluj
                            </button>
                        </div>
                        <p class="text-xs text-muted">Nowa wersja zostaje pod tym samym adresem, więc obraz zmieni się wszędzie, gdzie go użyto — poprzednią przywrócisz z historii wersji. Kopia to nowy plik w tym samym folderze, oryginał zostaje bez zmian.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('mediaImageEditor', () => {
                    // Instancja Croppera poza danymi Alpine (bez reaktywnego proxy).
                    let cropper = null;

                    return {
                        open: false, ready: false, busy: false, error: '', item: null, returnFocus: null,
                        aspect: 'free', x: 0, y: 0, w: 0, h: 0, rotate: 0, flipX: 1, flipY: 1,
                        outW: '', outH: '', quality: 90, note: '',
                        aspects: [
                            { key: 'free', label: 'Dowolne', ratio: NaN },
                            { key: '1:1', label: 'Kwadrat 1:1', ratio: 1 },
                            { key: '4:3', label: '4:3 (miniatury 800×600)', ratio: 4 / 3, out: [800, 600] },
                            { key: '3:2', label: '3:2', ratio: 3 / 2 },
                            { key: '16:9', label: '16:9', ratio: 16 / 9 },
                            { key: 'hero', label: 'Slajder hero 1600×600', ratio: 1600 / 600, out: [1600, 600] },
                            { key: 'og', label: 'Udostępnianie (OG) 1200×630', ratio: 1200 / 630, out: [1200, 630] },
                            { key: 'project', label: 'Zdjęcie działania 1200×500', ratio: 1200 / 500, out: [1200, 500] },
                            { key: '3:4', label: 'Pionowe 3:4', ratio: 3 / 4 },
                        ],
                        get lossy() { return this.item && this.item.mime !== 'image/png'; },
                        get resultW() { return parseInt(this.outW, 10) || this.w || 0; },
                        get resultH() { return parseInt(this.outH, 10) || this.h || 0; },

                        start(detail) {
                            if (! detail || ! detail.src) return;
                            this.returnFocus = document.activeElement;
                            this.item = detail;
                            Object.assign(this, { open: true, ready: false, busy: false, error: '', aspect: 'free', rotate: 0, flipX: 1, flipY: 1, outW: '', outH: '', quality: 90, note: '' });
                            this.$nextTick(() => {
                                this.$refs.heading.focus();
                                const img = this.$refs.img;
                                img.onload = () => this.initCropper();
                                img.onerror = () => { this.error = 'Nie udało się wczytać obrazu — odśwież stronę i spróbuj ponownie.'; };
                                img.alt = 'Edytowany obraz: ' + detail.name;
                                img.src = detail.src;
                            });
                        },
                        initCropper() {
                            if (! this.open) return;
                            if (typeof window.Cropper === 'undefined') { this.error = 'Edytor obrazu nie wczytał się — odśwież stronę.'; return; }
                            if (cropper) cropper.destroy();
                            cropper = new window.Cropper(this.$refs.img, {
                                viewMode: 1, autoCropArea: 1, background: false, checkOrientation: false, toggleDragModeOnDblclick: false,
                                ready: () => { this.ready = true; },
                                crop: (e) => {
                                    this.x = Math.round(e.detail.x); this.y = Math.round(e.detail.y);
                                    this.w = Math.round(e.detail.width); this.h = Math.round(e.detail.height);
                                    this.rotate = ((Math.round(e.detail.rotate) % 360) + 360) % 360;
                                    if (this.outW && this.w) this.outH = Math.max(1, Math.round(this.outW * this.h / this.w));
                                },
                            });
                        },
                        applyAspect() {
                            const option = this.aspects.find(a => a.key === this.aspect);
                            if (! cropper || ! option) return;
                            cropper.setAspectRatio(option.ratio);
                            if (option.out) { this.outW = option.out[0]; this.outH = option.out[1]; }
                        },
                        applyCrop() {
                            if (! cropper) return;
                            cropper.setData({ x: +this.x || 0, y: +this.y || 0, width: Math.max(1, +this.w || 1), height: Math.max(1, +this.h || 1) });
                        },
                        rotateBy(deg) { if (cropper) cropper.rotate(deg); },
                        flip(axis) {
                            if (! cropper) return;
                            if (axis === 'x') { this.flipX = -this.flipX; cropper.scaleX(this.flipX); }
                            else { this.flipY = -this.flipY; cropper.scaleY(this.flipY); }
                        },
                        linkOut(changed) {
                            if (! this.w || ! this.h) return;
                            if (changed === 'w') this.outH = this.outW ? Math.max(1, Math.round(this.outW * this.h / this.w)) : '';
                            else this.outW = this.outH ? Math.max(1, Math.round(this.outH * this.w / this.h)) : '';
                        },
                        reset() {
                            if (! cropper) return;
                            cropper.reset(); cropper.setAspectRatio(NaN); cropper.scale(1, 1);
                            Object.assign(this, { aspect: 'free', flipX: 1, flipY: 1, outW: '', outH: '', quality: 90 });
                        },
                        close() {
                            if (this.busy) return;
                            if (cropper) { cropper.destroy(); cropper = null; }
                            this.open = false; this.ready = false; this.item = null;
                            this.$refs.img.removeAttribute('src');
                            this.$nextTick(() => this.returnFocus?.focus?.());
                        },
                        trap(event) {
                            const f = [...this.$refs.dialog.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea,[tabindex]:not([tabindex="-1"])')].filter(e => e.offsetParent !== null);
                            if (! f.length) return;
                            if (event.shiftKey && document.activeElement === f[0]) { event.preventDefault(); f[f.length - 1].focus(); }
                            else if (! event.shiftKey && document.activeElement === f[f.length - 1]) { event.preventDefault(); f[0].focus(); }
                        },
                        async save(mode) {
                            if (! cropper || this.busy) return;
                            this.busy = true; this.error = '';
                            const mime = this.item.mime;
                            const options = { imageSmoothingEnabled: true, imageSmoothingQuality: 'high', width: this.resultW, height: this.resultH };
                            if (mime === 'image/jpeg') options.fillColor = '#fff';
                            const canvas = cropper.getCroppedCanvas(options);
                            const blob = canvas ? await new Promise(resolve => canvas.toBlob(resolve, mime, this.quality / 100)) : null;
                            if (! blob) { this.error = 'Nie udało się przygotować obrazu — spróbuj mniejszego rozmiaru.'; this.busy = false; return; }
                            if (blob.type !== mime) { this.error = 'Ta przeglądarka nie zapisuje obrazów w tym formacie — użyj Chrome, Edge albo Firefoksa.'; this.busy = false; return; }

                            const data = new FormData();
                            data.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                            data.append('mode', mode);
                            data.append('note', this.note);
                            data.append('file', new File([blob], this.item.fileName, { type: mime }));
                            try {
                                const response = await fetch(this.item.saveUrl, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                                const json = await response.json().catch(() => ({}));
                                if (response.ok && json.redirect) { window.location.assign(json.redirect); return; }
                                const byStatus = { 403: 'Nie masz dostępu do tego pliku.', 413: 'Obraz po edycji jest za duży — zmniejsz rozmiar wyniku.', 419: 'Sesja wygasła — odśwież stronę i spróbuj ponownie.' };
                                this.error = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message || byStatus[response.status] || 'Nie udało się zapisać obrazu.');
                            } catch (e) {
                                this.error = 'Brak połączenia — spróbuj ponownie.';
                            }
                            this.busy = false;
                        },
                    };
                });
            });
        </script>
    @endpush
@endonce
