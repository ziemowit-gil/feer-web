{{--
    Ikona strony jako „usługi” w siatce motywu FEER IS (strona główna, zakładki odbiorców). Zapisuje się w layout_data.icon.
    Pusta = domyślna ikona dokumentu. Przyjmuje nazwy Material Symbols (mi-…), Font Awesome (fa-…) i Bootstrap Icons (bi-…).
--}}
@php
    $serviceIcon = (string) old('layout_data.icon', ($page->layout_data ?? [])['icon'] ?? '');
    $suggested = ['description', 'groups', 'school', 'work', 'health_and_safety', 'gavel', 'accessibility_new', 'volunteer_activism', 'support_agent', 'home', 'family_restroom', 'payments', 'mail', 'call', 'event', 'menu_book', 'computer', 'translate', 'hearing', 'visibility', 'psychology', 'directions_bus'];
@endphp
<details class="pe-section rounded-md bg-white" data-pe-section="ikona-uslugi">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-4 text-base font-bold text-ink [&::-webkit-details-marker]:hidden">
        <span>Ikona w siatce usług (FEER IS)</span>
        <i class="fa-solid fa-chevron-down text-sm text-muted transition-transform" aria-hidden="true"></i>
    </summary>
    <div class="border-t border-gray-100 px-6 pb-6 pt-5">
        <label for="layout-data-icon" class="mb-1 block text-sm font-bold text-ink">Nazwa ikony</label>
        <div class="flex items-center gap-3">
            <input type="text" id="layout-data-icon" name="layout_data[icon]" value="{{ $serviceIcon }}" list="service-icon-suggestions" maxlength="60" placeholder="mi-groups"
                aria-describedby="service-icon-help" class="w-full max-w-xs rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-light text-brand-dark" aria-hidden="true">{{ icon_html($serviceIcon !== '' ? $serviceIcon : 'mi-description', 'text-2xl') }}</span>
        </div>
        <datalist id="service-icon-suggestions">
            @foreach ($suggested as $name)<option value="mi-{{ $name }}">@endforeach
        </datalist>
        <p id="service-icon-help" class="mt-2 text-sm text-ink">Ikona pokazuje się obok tytułu tej strony, gdy jest ona usługą w zakładce portalu. Wpisz nazwę z Material Symbols z prefiksem <code>mi-</code> (np. <code>mi-groups</code>) albo z Font Awesome (<code>fa-solid fa-users</code>). Puste pole to ikona dokumentu.</p>
    </div>
</details>
