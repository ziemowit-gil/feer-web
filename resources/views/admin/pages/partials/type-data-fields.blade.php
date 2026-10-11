{{--
    Pola typów treści trzymanych we wspólnej kolumnie JSON `type_data`
    (oferta/usługa, poradnik, słownik, studium przypadku, rekrutacja…).

    Sekcje pól są w osobnych plikach `admin/pages/partials/type-data/<typ>.blade.php`
    — każdy typ wskazuje swój plik w klasie `App\PageTypes\Types\…` (adminView()),
    a ta pętla dołącza je w kolejności z rejestru typów. Każda sekcja ma
    `data-type-fields="<typ>"` i jest przełączana przez selekt typu strony (JS w form.blade.php).

    Wspólne pomocniki (dostępne w każdej sekcji): $td (dane typu), $tdRows($klucz),
    klasy $inp / $lbl / $box / $addBtn / $rmBtn oraz $recRow() — generator wiersza
    repeatera (klasy siatki z $recCols albo podane wprost szóstym argumentem).

    Listy (korzyści, kroki, hasła…) używają własnego, prostego repeatera
    `data-td-repeater` — bez przeliczania indeksów, bo nazwy pól są zagnieżdżone
    (type_data[lista][i][pole]) i generyczny reindex z form.blade.php by je popsuł.
--}}
@php
    $td = old('type_data', $page->type_data ?? []);
    $tdRows = fn (string $key) => array_values(array_filter(is_array($td[$key] ?? null) ? $td[$key] : [], 'is_array'));
    $inp = 'w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand';
    $lbl = 'mb-1 block text-xs font-bold text-muted';
    $box = 'space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4';
    $addBtn = 'mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
    $rmBtn = 'rounded p-2 text-muted hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400';
    // Klasy siatki jako literały (Tailwind wykrywa tylko pełne nazwy klas w źródle).
    $recCols = [
        'audience' => 'flex gap-2', 'requirements' => 'flex gap-2',
        'bonus' => 'grid gap-2 sm:grid-cols-[3fr_8rem_auto]',
        'benefits' => 'grid gap-2 sm:grid-cols-[10rem_1fr_2fr_auto]',
        'steps' => 'grid gap-2 sm:grid-cols-[1fr_2fr_auto]',
        'docs' => 'grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]',
        'rec_faq' => 'grid gap-2 sm:grid-cols-[1fr_2fr_auto]',
        // Zapytanie ofertowe (ten sam pomocnik wierszy).
        'criteria' => 'grid gap-2 sm:grid-cols-[2fr_7rem_3fr_auto]',
        'notices' => 'grid gap-2 sm:grid-cols-[10rem_1fr_2fr_1fr_auto]',
    ];
    // Wiersz repeatera: $fields = [klucz => ['label' => …, 'ph' => …, 'type' => …, 'textarea' => bool]].
    // Nowy typ może podać własne klasy siatki ($cols), zamiast dopisywać listę do $recCols.
    $recRow = function (string $list, array $fields, string $removeLabel, ?int $i = null, array $row = [], ?string $cols = null) use ($inp, $rmBtn, $recCols) {
        $idx = $i === null ? '__INDEX__' : $i;
        $cols ??= $recCols[$list] ?? 'flex gap-2';
        $html = '<div data-td-row class="'.$cols.'">';
        foreach ($fields as $key => $f) {
            $val = e($row[$key] ?? '');
            $attrs = 'name="type_data['.$list.']['.$idx.']['.$key.']" placeholder="'.e($f['ph'] ?? '').'" aria-label="'.e($f['label']).'" class="'.$inp.'"';
            $html .= ($f['textarea'] ?? false) ? '<textarea rows="2" '.$attrs.'>'.$val.'</textarea>' : '<input type="'.($f['type'] ?? 'text').'" value="'.$val.'" '.$attrs.'>';
        }

        return $html.'<button type="button" data-td-remove class="'.$rmBtn.'" aria-label="'.e($removeLabel).'"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></div>';
    };
@endphp

@foreach (\App\PageTypes\PageTypeRegistry::all() as $pageType)
    @if ($pageType->adminView())
        @include($pageType->adminView())
    @endif
@endforeach

<script>
    // Repeater dla pól type_data: klonuje <template>, podstawia kolejny indeks,
    // usuwa wiersze. Bez przeliczania nazw — kolejność w JSON = kolejność w DOM.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-td-repeater]').forEach(function (rep) {
            var rows = rep.querySelector('[data-td-rows]');
            var template = rep.querySelector('[data-td-template]');
            var addBtn = rep.querySelector('[data-td-add]');
            if (!rows || !template || !addBtn) return;
            var next = rows.querySelectorAll('[data-td-row]').length;
            addBtn.addEventListener('click', function () {
                var wrap = document.createElement('div');
                wrap.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(next++)).trim();
                var row = wrap.firstElementChild;
                rows.appendChild(row);
                row.querySelector('input, textarea')?.focus();
            });
            rep.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-td-remove]');
                if (btn && rep.contains(btn)) { btn.closest('[data-td-row]')?.remove(); addBtn.focus(); }
            });
        });
    });
</script>
