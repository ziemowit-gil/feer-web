{{--
    Wybieracz zdjęć z Unsplash: pole wyszukiwania, siatka propozycji i ukryte pola dla App\Support\UnsplashImport.
    Parametry: $initialQuery (opcjonalnie) — fraza startowa; $titleFieldId (opcjonalnie) — id pola tytułu, z którego
    można wziąć frazę. Pokazuje się tylko przy skonfigurowanym kluczu Unsplash.
    Dostępność: wynik wyszukiwania ogłaszany (role=status), zdjęcia to przyciski z aria-pressed i opisem alt.
--}}
@if (\App\Models\SiteSetting::current()->unsplashAccessKey())
    <div class="rounded-lg border border-dashed border-gray-300 p-4"
         x-data="{
            q: @js($initialQuery ?? ''), photos: [], chosen: null, loading: false, error: '', searched: false,
            titleId: @js($titleFieldId ?? null),
            useTitle() { const t = this.titleId ? document.getElementById(this.titleId)?.value : ''; if (t) { this.q = t; this.search(); } },
            async search() {
                const term = this.q.trim();
                if (term === '') { this.error = 'Wpisz, czego szukasz.'; return; }
                this.loading = true; this.error = ''; this.searched = true;
                try {
                    const r = await fetch(@js(route('admin.multimedia.unsplash.search')) + '?q=' + encodeURIComponent(term.slice(0, 100)), { headers: { Accept: 'application/json' } });
                    if (! r.ok) throw new Error();
                    this.photos = (await r.json()).slice(0, 12);
                    if (! this.photos.length) this.error = 'Brak wyników — spróbuj innej frazy albo wgraj własny plik.';
                } catch (e) { this.error = 'Nie udało się pobrać zdjęć z Unsplash.'; }
                this.loading = false;
            },
         }">
        <p class="text-sm font-bold">Albo wybierz zdjęcie z Unsplash</p>
        <p class="mb-3 text-xs text-muted">Wyszukaj zdjęcie i kliknij je, aby wybrać. Zostanie pobrane i zapisane razem z formularzem.</p>

        <div class="flex flex-wrap gap-2">
            <label for="unsplash-q" class="sr-only">Fraza wyszukiwania w Unsplash</label>
            <input id="unsplash-q" type="search" x-model="q" @keydown.enter.prevent="search()" placeholder="np. ludzie, współpraca, edukacja"
                   class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
            <button type="button" @click="search()" class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Szukaj</button>
            <template x-if="titleId"><button type="button" @click="useTitle()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-bold text-ink hover:border-brand hover:text-brand-dark">Użyj tytułu</button></template>
        </div>

        <p x-show="loading" class="mt-3 text-xs text-muted" role="status">Szukam zdjęć…</p>
        <p x-show="error" x-cloak class="mt-3 text-xs text-red-700" x-text="error" role="alert"></p>
        <p x-show="chosen" x-cloak class="mt-3 text-xs font-bold text-brand-dark" role="status">
            Wybrano zdjęcie (autor: <span x-text="chosen?.author_name"></span>).
            <button type="button" class="underline" @click="chosen = null">Cofnij wybór</button>
        </p>

        <ul class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4" role="list" x-show="photos.length" x-cloak>
            <template x-for="p in photos" :key="p.id">
                <li>
                    <button type="button" @click="chosen = p" :aria-pressed="(chosen?.id === p.id).toString()"
                            class="block w-full overflow-hidden rounded-lg border-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                            :class="chosen?.id === p.id ? 'border-brand' : 'border-transparent'">
                        <img :src="p.thumb_url" :alt="p.alt" loading="lazy" class="h-24 w-full object-cover">
                    </button>
                    <p class="mt-1 truncate text-[11px] text-muted" x-text="'Autor: ' + p.author_name"></p>
                </li>
            </template>
        </ul>

        <input type="hidden" name="unsplash_full_url" :value="chosen?.full_url">
        <input type="hidden" name="unsplash_download_location" :value="chosen?.download_location">
        <input type="hidden" name="unsplash_author" :value="chosen?.author_name">
    </div>
@endif
