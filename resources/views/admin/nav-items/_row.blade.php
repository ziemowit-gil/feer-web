@php $child ??= false; @endphp

<tr>
    <td class="px-4 py-3 font-medium">
        @if ($child)
            <span class="pl-6 text-muted">&#8627;</span>
        @endif
        {{ $item->label }}
    </td>
    <td class="px-4 py-3 text-muted">{{ \App\Models\NavItem::TYPES[$item->type] ?? $item->type }}</td>
    <td class="px-4 py-3 text-muted">
        @if ($item->isDropdown())
            {{ $item->module ? \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module : '—' }}
        @else
            {{ $item->url }}
            @if ($item->module)
                <span class="ml-1 text-xs">({{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }})</span>
            @endif
        @endif
    </td>
    <td class="px-4 py-3">
        @if ($item->is_button)
            <span class="rounded-full bg-brand-light px-2 py-1 text-xs font-bold text-brand">Przycisk (CTA)</span>
        @elseif ($item->isDropdown() && $item->is_transparent_dropdown)
            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-muted">Panel przezroczysty</span>
        @elseif ($item->isDropdown())
            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-muted">Panel pełny</span>
        @else
            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-muted">Link</span>
        @endif
    </td>
    <td class="px-4 py-3">
        @if ($item->is_active)
            <span class="rounded-full bg-green-50 px-2 py-1 text-xs font-bold text-green-700">Aktywna</span>
        @else
            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-muted">Ukryta</span>
        @endif
    </td>
    <td class="px-4 py-3">
        <form method="POST" action="{{ route('admin.pozycje-menu.kolejnosc', $item) }}" class="flex items-center gap-1">
            @csrf
            @method('PATCH')
            <input type="number" name="order" min="0" value="{{ $item->order }}" aria-label="Kolejność pozycji {{ $item->label }}"
                class="w-16 rounded border-gray-300 py-1 text-sm focus:border-brand focus:ring-brand">
            <button type="submit" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" title="Zapisz kolejność"><i class="fa-solid fa-check"></i></button>
        </form>
    </td>
    <td class="px-4 py-3">
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.pozycje-menu.edit', $item) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
            <form method="POST" action="{{ route('admin.pozycje-menu.destroy', $item) }}" onsubmit="return confirm('Usunąć pozycję menu &quot;{{ $item->label }}&quot;?{{ $item->isDropdown() ? ' Usunięte zostaną też jej podpozycje.' : '' }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
