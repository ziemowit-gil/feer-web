{{--
    Metryczka strony („Pokaż metkę” pod treścią): widoczność na tej stronie, autor (edytowalny),
    osoba publikująca (zapisuje system — wskazać ją może tylko konto wspólne) i podmiot publikujący.

    Konto wspólne (Ustawienia → Treści → Metryczka): przy publikacji okno pyta, kogo wskazać
    jako osobę publikującą i czy pokazać podmiot publikujący.
--}}
@php
    $metkaSettings = \App\Models\SiteSetting::current();
    $metkaReady = \Illuminate\Support\Facades\Schema::hasColumn('pages', 'published_by_id');
    $metkaOwn = old('layout_data.metka', ($page->layout_data ?? [])['metka'] ?? 'inherit');
    $metkaEntityOn = (bool) ($metkaSettings->metka_show_entity ?? true);
    $metkaEntity = (bool) old('layout_data.metka_entity', ($page->layout_data ?? [])['metka_entity'] ?? true);
    $metkaShared = $metkaReady && $metkaSettings->isMetkaSharedAccount(auth()->user());
    $metkaPublisher = $metkaReady ? $page->publishedBy : null;
    $metkaDefault = match ($metkaSettings->metka_mode ?? 'off') {
        'all' => 'włączona w całym serwisie',
        'types' => in_array($page->type ?? 'standard', (array) ($metkaSettings->metka_types ?? []), true) ? 'włączona dla tego typu stron' : 'wyłączona dla tego typu stron',
        default => 'wyłączona',
    };
    $metkaUsers = $metkaShared
        ? \App\Models\User::orderBy('name')->get(['id', 'name', 'email'])
            ->reject(fn ($u) => $metkaSettings->isMetkaSharedAccount($u))->values()
        : collect();
    // Okno pojawia się, dopóki publikującym jest konto wspólne (albo nikt).
    $metkaAsk = $metkaShared && $metkaUsers->isNotEmpty()
        && (! $metkaPublisher || $metkaSettings->isMetkaSharedAccount($metkaPublisher));
@endphp
@if ($metkaReady)
<details class="pe-section rounded-md bg-white" data-pe-section="metka">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-4 text-base font-bold text-ink [&::-webkit-details-marker]:hidden">
        <span>Metryczka</span>
        <i class="fa-solid fa-chevron-down text-sm text-muted transition-transform" aria-hidden="true"></i>
    </summary>
    <div class="space-y-5 border-t border-gray-100 px-6 pb-6 pt-5">
        <div>
            <div class="mb-1 flex items-center"><label for="metka-mode" class="text-sm font-bold text-ink">„Pokaż metkę” pod treścią</label>@include('admin.partials.help', ['topic' => 'metryczka', 'text' => 'Rozwijana metryczka pod treścią: autor, osoba publikująca, podmiot, data publikacji i aktualizacji. Domyślne ustawienie dla serwisu i typów stron: Ustawienia → Treści → Metryczka.'])</div>
            <select id="metka-mode" name="layout_data[metka]"
                class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark sm:w-2/3">
                <option value="inherit" @selected($metkaOwn === 'inherit')>Jak w ustawieniach serwisu ({{ $metkaDefault }})</option>
                <option value="show" @selected($metkaOwn === 'show')>Pokazuj na tej stronie</option>
                <option value="hide" @selected($metkaOwn === 'hide')>Nie pokazuj na tej stronie</option>
            </select>
        </div>

        <div>
            <div class="mb-1 flex items-center"><label for="metka-author" class="text-sm font-bold text-ink">Autor <span class="font-normal text-muted">(opcjonalnie)</span></label>@include('admin.partials.help', ['topic' => 'autor', 'text' => 'Osoba lub zespół, który przygotował treść. Puste = osoba, która utworzyła stronę.'])</div>
            <input id="metka-author" name="metka_author" type="text" maxlength="160" value="{{ old('metka_author', $page->metka_author) }}"
                placeholder="{{ $page->creator?->name ?? auth()->user()?->name }}"
                class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark sm:w-2/3">
            @error('metka_author') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        @if ($metkaShared && $metkaUsers->isNotEmpty())
            <div>
                <label for="metka-publisher" class="mb-1 block text-sm font-bold text-ink">Osoba publikująca</label>
                <select id="metka-publisher" name="published_by_id" aria-describedby="metka-publisher-hint"
                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark sm:w-2/3">
                    <option value="">— wybierz przy publikacji —</option>
                    @foreach ($metkaUsers as $u)
                        <option value="{{ $u->id }}" @selected((int) old('published_by_id', $page->published_by_id) === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                <p id="metka-publisher-hint" class="mt-1 text-xs text-muted">Publikujesz z konta wspólnego — wskaż osobę, która odpowiada za publikację.</p>
            </div>
        @else
            <div>
                <p class="flex items-center text-sm font-bold text-ink">Osoba publikująca @include('admin.partials.help', ['topic' => 'osoba publikująca', 'text' => 'Zapisuje ją system przy pierwszej publikacji — nie można jej zmienić.'])</p>
                <p class="text-sm text-ink">{{ $metkaPublisher?->name ?? 'Zapisze się automatycznie przy publikacji' }}</p>
            </div>
        @endif

        @if ($metkaEntityOn)
        <div>
            <input type="hidden" name="layout_data[metka_entity]" value="0">
            <label class="flex cursor-pointer items-start gap-3 text-sm text-ink">
                <input id="metka-entity" type="checkbox" name="layout_data[metka_entity]" value="1" @checked($metkaEntity)
                    class="mt-0.5 h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Pokaż podmiot publikujący</span> <span class="text-muted">— {{ $metkaSettings->metka_entity ?: $metkaSettings->site_name }}</span></span>
            </label>
        </div>
        @endif
    </div>
</details>

@if ($metkaAsk)
    <dialog id="metka-dialog" aria-labelledby="metka-dialog-title" aria-describedby="metka-dialog-desc"
        class="w-[min(32rem,calc(100vw-2rem))] rounded-md bg-white p-0 text-ink backdrop:bg-black/50">
        {{-- Bez <form>: okno leży wewnątrz formularza strony, a zagnieżdżony formularz by go przerwał. --}}
        <div class="space-y-5 p-6">
            <h2 id="metka-dialog-title" class="text-lg font-bold">Kto publikuje tę stronę?</h2>
            <p id="metka-dialog-desc" class="text-sm">Publikujesz z konta wspólnego. W metryczce strony pokażemy osobę, którą wskażesz.</p>
            <div>
                <label for="metka-dialog-user" class="mb-1 block text-sm font-bold">Osoba publikująca <span class="font-normal">(wymagane)</span></label>
                <select id="metka-dialog-user" aria-required="true"
                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    <option value="">— wybierz —</option>
                    @foreach ($metkaUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <p id="metka-dialog-error" class="mt-1 hidden text-sm text-red-700" role="alert">Wybierz osobę publikującą.</p>
            </div>
            @if ($metkaEntityOn)
            <fieldset>
                <legend class="mb-2 text-sm font-bold">Pokazać podmiot publikujący ({{ $metkaSettings->metka_entity ?: $metkaSettings->site_name }})?</legend>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="metka-dialog-entity" value="1" checked class="text-brand-dark focus:ring-brand-dark"> Tak</label>
                <label class="mt-1 flex items-center gap-2 text-sm"><input type="radio" name="metka-dialog-entity" value="0" class="text-brand-dark focus:ring-brand-dark"> Nie</label>
            </fieldset>
            @endif
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" data-metka-cancel class="pe-btn pe-btn-ghost">Anuluj</button>
                <button type="button" data-metka-ok class="pe-btn pe-btn-primary"><i class="fa-solid fa-check" aria-hidden="true"></i>Publikuj</button>
            </div>
        </div>
    </dialog>
    <script>
        (() => {
            const form = document.getElementById('page-edit-form');
            const dlg = document.getElementById('metka-dialog');
            if (!form || !dlg || typeof dlg.showModal !== 'function') return;
            const userSel = document.getElementById('metka-dialog-user');
            const err = document.getElementById('metka-dialog-error');
            let confirmed = false;
            let pending = null;

            form.addEventListener('submit', (e) => {
                const submitter = e.submitter;
                const publishing = form.querySelector('input[name="is_published"]')?.checked;
                if (confirmed || !publishing || submitter?.name === 'draft' || submitter?.form !== form && submitter) return;
                const chosen = document.getElementById('metka-publisher')?.value;
                if (chosen) return; // osoba już wskazana w sekcji Metryczka
                e.preventDefault();
                pending = submitter;
                err.classList.add('hidden');
                userSel.removeAttribute('aria-invalid');
                dlg.showModal();
                userSel.focus();
            });

            dlg.querySelector('[data-metka-cancel]').addEventListener('click', () => dlg.close());
            dlg.querySelector('[data-metka-ok]').addEventListener('click', () => {
                if (!userSel.value) {
                    err.classList.remove('hidden');
                    userSel.setAttribute('aria-invalid', 'true');
                    userSel.setAttribute('aria-describedby', 'metka-dialog-error');
                    userSel.focus();
                    return;
                }
                document.getElementById('metka-publisher').value = userSel.value;
                const entityBox = document.getElementById('metka-entity');
                if (entityBox) entityBox.checked = dlg.querySelector('input[name="metka-dialog-entity"]:checked')?.value === '1';
                confirmed = true;
                dlg.close();
                pending ? form.requestSubmit(pending) : form.requestSubmit();
            });
        })();
    </script>
@endif
@endif
