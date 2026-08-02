@props(['colspan' => 5, 'icon' => 'fa-folder-open', 'message' => 'Brak elementów', 'createRoute' => null, 'createLabel' => 'Dodaj pierwszy'])
<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-14 text-center">
        <div class="mx-auto flex max-w-xs flex-col items-center gap-3">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400">
                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
            </span>
            <p class="text-sm text-muted">{{ $message }}</p>
            @if ($createRoute)
                <a href="{{ $createRoute }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    {{ $createLabel }}
                </a>
            @endif
        </div>
    </td>
</tr>
