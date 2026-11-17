{{--
    Tabela podstron w prawym panelu widoku drzewa — zwarta, w stylu drzewa stron (jak lista stron w TYPO3):
    niskie wiersze, ikona typu przed tytułem, adres obok tytułu, lekkie przyciski bez obrysów.
    Kolejność: pozycja wśród rodzeństwa i przyciski „Wyżej / Niżej” (zamiast surowego numeru `order`).
    Zmienne: $children (w zapisanej kolejności), $selected (null = poziom główny), $byParent, $inheritedDisabled.
--}}
@php
    $typeIcons = \App\Models\Page::typeIcons();
    $rowBtn = 'inline-flex min-h-7 items-center gap-1.5 rounded px-2 py-1 text-xs font-bold text-ink hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <caption class="sr-only">Podstrony {{ $selected ? 'strony „'.$selected->title.'”' : 'najwyższego poziomu' }}</caption>
        <thead class="border-b border-gray-200">
            <tr>
                <th scope="col" class="px-3 py-1.5 text-xs font-semibold text-muted">Strona</th>
                <th scope="col" class="px-3 py-1.5 text-xs font-semibold text-muted">Status</th>
                <th scope="col" class="px-3 py-1.5 text-xs font-semibold text-muted">Pozycja</th>
                <th scope="col" class="px-3 py-1.5 text-right text-xs font-semibold text-muted"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($children as $child)
                @php
                    $subCount = $byParent->get($child->id, collect())->count();
                    $childIcon = $subCount ? 'fa-folder' : ($typeIcons[$child->type] ?? 'fa-file-lines');
                    $childOff = ! $child->is_published || $child->is_disabled || isset($inheritedDisabled[$child->id]);
                @endphp
                <tr class="group hover:bg-gray-50">
                    <th scope="row" class="px-3 py-1 font-normal">
                        <div class="flex min-w-0 items-center gap-2">
                            <i class="fa-solid {{ $childIcon }} w-4 flex-none text-center text-xs text-gray-400" aria-hidden="true"></i>
                            <a href="{{ route('admin.podstrony.index', ['wybrana' => $child->id]) }}"
                                class="min-w-0 truncate font-semibold {{ $childOff ? 'text-muted' : 'text-ink' }} hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $child->title }}</a>
                            <span class="hidden min-w-0 truncate font-mono text-xs text-muted sm:inline">/{{ $child->slug }}</span>
                            @if ($subCount)<span class="flex-none rounded-full bg-gray-100 px-1.5 text-[10px] font-bold text-muted"><span class="sr-only">podstron: </span>{{ $subCount }}</span>@endif
                        </div>
                    </th>
                    <td class="whitespace-nowrap px-3 py-1">
                        @include('admin.pages.partials.status-chip', ['page' => $child, 'inherited' => isset($inheritedDisabled[$child->id])])
                    </td>
                    <td class="whitespace-nowrap px-3 py-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs" style="min-width:2.75rem"><span class="font-bold">{{ $loop->iteration }}</span> <span class="text-muted">z {{ $loop->count }}</span></span>
                            @if ($loop->count > 1 && (! $child->is_locked || auth()->user()->isAdmin()))
                                @include('admin.pages.partials.position-buttons', ['page' => $child, 'parentId' => $selected?->id, 'index' => $loop->index, 'count' => $loop->count, 'selectedId' => $selected?->id, 'compact' => true])
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-2 py-1">
                        <div class="flex items-center justify-end gap-0.5">
                            <a href="{{ route('admin.podstrony.edit', $child) }}" class="{{ $rowBtn }}">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj<span class="sr-only"> {{ $child->title }}</span>
                            </a>
                            <a href="{{ $child->is_published && ($child->publish_at === null || $child->publish_at->isPast()) ? $child->publicUrl() : $child->previewUrl() }}" target="_blank" rel="noopener"
                                class="{{ $rowBtn }}" title="Podgląd" aria-label="Podgląd {{ $child->title }} (nowa karta)">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
