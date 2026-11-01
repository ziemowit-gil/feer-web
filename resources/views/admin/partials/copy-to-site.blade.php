{{-- Kopiuj do witryny: przycisk otwierający okno z wyborem witryny docelowej (tylko administrator, gdy jest inna witryna).
     Parametry: $action (adres POST), $siteId (witryna elementu), $label (nazwa elementu), $note (opcjonalna uwaga w oknie),
     $options (opcjonalnie: [['name' => 'copy_image', 'label' => 'Zdjęcie'], …] — pola wyboru „Co skopiować”, domyślnie zaznaczone;
     niezaznaczone wysyłają 0, brak pola = kopiuj wszystko),
     $variant ('icon' — domyślnie, sama ikona; 'button' — przycisk z napisem w klasach $buttonClass).

     Okno to jedno <dialog> na stronę, dopisywane na koniec <body> (stos „scripts”). Dzięki temu nie jest przycinane przez
     tabelę z overflow-hidden i nie jest formularzem zagnieżdżonym w formularzu zbiorczym listy; dostępność daje sam
     <dialog> (fokus zostaje w oknie, Esc zamyka, po zamknięciu fokus wraca na przycisk). --}}
@php
    $copyAll = $__env->shared('copyToSiteTargets');
    if ($copyAll === null) {
        $copyAll = auth()->user()?->isAdmin()
            ? \App\Models\SiteSetting::query()->notTrashed()->orderBy('site_name')->get(['id', 'site_name', 'slug'])
            : collect();
        $__env->share('copyToSiteTargets', $copyAll);
    }
    $hasCopyTargets = $copyAll->contains(fn ($site) => (int) $site->id !== (int) $siteId);
    $variant = $variant ?? 'icon';
@endphp
@if ($hasCopyTargets)
    <button type="button" data-copy-to-site aria-haspopup="dialog" title="Kopiuj do witryny"
        data-copy-action="{{ $action }}" data-copy-site="{{ $siteId }}" data-copy-label="{{ $label }}"
        @isset($note) data-copy-note="{{ $note }}" @endisset
        data-copy-options="{{ json_encode($options ?? [], JSON_UNESCAPED_UNICODE) }}"
        class="{{ $variant === 'button' ? ($buttonClass ?? '') : 'inline-flex h-11 w-11 items-center justify-center rounded-md text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark' }}">
        <i class="fa-solid {{ $variant === 'button' ? 'fa-copy' : 'fa-share-from-square' }}" aria-hidden="true"></i>
        @if ($variant === 'button') Kopiuj do witryny @else <span class="sr-only">Kopiuj do witryny: {{ $label }}</span> @endif
    </button>

    @once
        @push('scripts')
            <style>
                .cts-dialog { width: min(26rem, calc(100vw - 2rem)); max-height: calc(100vh - 2rem); margin: auto; padding: 0; border: 2px solid #1a1a1a; border-radius: .5rem; background: #fff; color: #1a1a1a; }
                .cts-dialog::backdrop { background: rgba(0, 0, 0, .5); }
            </style>
            <dialog id="copy-to-site-dialog" class="cts-dialog" aria-labelledby="copy-to-site-title">
                <form method="POST" action="" class="space-y-4 p-5">
                    @csrf
                    <h2 id="copy-to-site-title" class="text-lg font-bold text-ink">Kopiuj do innej witryny</h2>
                    <p class="text-sm text-ink">Kopiowana pozycja: <strong data-copy-label></strong></p>
                    <div>
                        <label for="copy-to-site-target" class="mb-1 block text-sm font-bold text-ink">Witryna docelowa</label>
                        <select id="copy-to-site-target" name="target_site_id" required class="min-h-11 w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <option value="" disabled selected>— wybierz witrynę —</option>
                            @foreach ($copyAll as $site)
                                <option value="{{ $site->id }}">{{ $site->site_name ?: ($site->slug ?: 'Witryna '.$site->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset data-copy-options hidden>
                        <legend class="mb-1 text-sm font-bold text-ink">Co skopiować razem z treścią</legend>
                        <div data-copy-options-list class="space-y-1"></div>
                    </fieldset>
                    <p class="text-sm text-ink" data-copy-note hidden></p>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" data-copy-cancel
                            class="inline-flex min-h-11 items-center rounded bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            Anuluj
                        </button>
                        <button type="submit"
                            class="inline-flex min-h-11 items-center gap-2 rounded bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                            <i class="fa-solid fa-copy" aria-hidden="true"></i> Kopiuj
                        </button>
                    </div>
                </form>
            </dialog>
            <script>
                (function () {
                    var dialog = document.getElementById('copy-to-site-dialog');
                    if (! dialog || typeof dialog.showModal !== 'function') { return; }
                    var form = dialog.querySelector('form');
                    var target = dialog.querySelector('#copy-to-site-target');
                    var label = dialog.querySelector('[data-copy-label]');
                    var note = dialog.querySelector('[data-copy-note]');
                    var box = dialog.querySelector('[data-copy-options]');
                    var list = dialog.querySelector('[data-copy-options-list]');

                    document.addEventListener('click', function (event) {
                        var button = event.target.closest('[data-copy-to-site]');
                        if (! button) { return; }
                        event.preventDefault();

                        form.action = button.dataset.copyAction;
                        label.textContent = button.dataset.copyLabel || '';
                        note.textContent = button.dataset.copyNote || '';
                        note.hidden = note.textContent === '';

                        // Witryna, do której pozycja już należy, nie jest celem (serwer też to odrzuca).
                        Array.prototype.forEach.call(target.options, function (option) {
                            if (option.value) { option.disabled = option.hidden = option.value === button.dataset.copySite; }
                        });
                        target.value = '';

                        // „Co skopiować”: zaznaczone pola wysyłają 1, odznaczone 0 (ukryte pole przed polem wyboru).
                        var options = [];
                        try { options = JSON.parse(button.dataset.copyOptions || '[]'); } catch (error) { options = []; }
                        list.textContent = '';
                        options.forEach(function (option, index) {
                            var row = document.createElement('label');
                            row.className = 'flex min-h-11 items-center gap-2 text-sm text-ink';
                            var off = document.createElement('input');
                            off.type = 'hidden'; off.name = option.name; off.value = '0';
                            var on = document.createElement('input');
                            on.type = 'checkbox'; on.name = option.name; on.value = '1'; on.checked = true;
                            on.id = 'copy-to-site-option-' + index;
                            on.className = 'h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark';
                            var text = document.createElement('span');
                            text.textContent = option.label;
                            row.appendChild(off); row.appendChild(on); row.appendChild(text);
                            list.appendChild(row);
                        });
                        box.hidden = options.length === 0;

                        dialog.showModal();
                        target.focus();
                    });

                    dialog.querySelector('[data-copy-cancel]').addEventListener('click', function () { dialog.close(); });
                    // Kliknięcie w tło (cel zdarzenia to samo okno, bo formularz wypełnia całą zawartość) zamyka okno.
                    dialog.addEventListener('click', function (event) { if (event.target === dialog) { dialog.close(); } });
                })();
            </script>
        @endpush
    @endonce
@endif
