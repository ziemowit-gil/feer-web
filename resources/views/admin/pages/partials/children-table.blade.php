{{--
    Tabela podstron w prawym panelu widoku drzewa. Kolejność: pozycja wśród rodzeństwa i przyciski
    „Wyżej / Niżej” (zamiast surowego numeru `order`, który dla nowych stron jest zawsze 0).
    Zmienne: $children (w zapisanej kolejności), $selected (null = poziom główny), $byParent, $inheritedDisabled.
--}}
<div class="overflow-x-auto rounded-md bg-white">
    <table class="w-full text-left text-sm">
        <caption class="sr-only">Podstrony {{ $selected ? 'strony „'.$selected->title.'”' : 'najwyższego poziomu' }}</caption>
        <thead class="border-b border-gray-100 bg-gray-50/80">
            <tr>
                <th scope="col" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-muted">Strona</th>
                <th scope="col" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                <th scope="col" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-muted">Pozycja</th>
                <th scope="col" class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-muted">Akcje</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach ($children as $child)
                @php $subCount = $byParent->get($child->id, collect())->count(); @endphp
                <tr class="hover:bg-gray-50/60">
                    <th scope="row" class="px-4 py-2.5 font-normal">
                        <a href="{{ route('admin.podstrony.index', ['wybrana' => $child->id]) }}"
                            class="font-semibold text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $child->title }}</a>
                        <span class="block font-mono text-xs text-muted">/{{ $child->slug }}</span>
                        @if ($subCount)<span class="text-xs text-muted">{{ $subCount }} {{ trans_choice('podstrona|podstrony|podstron', $subCount) }}</span>@endif
                    </th>
                    <td class="px-4 py-2.5">
                        @include('admin.pages.partials.status-chip', ['page' => $child, 'inherited' => isset($inheritedDisabled[$child->id])])
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <span class="whitespace-nowrap text-sm" style="min-width:3rem"><span class="font-bold">{{ $loop->iteration }}</span> <span class="text-muted">z {{ $loop->count }}</span></span>
                            @if ($loop->count > 1 && (! $child->is_locked || auth()->user()->isAdmin()))
                                @include('admin.pages.partials.position-buttons', ['page' => $child, 'parentId' => $selected?->id, 'index' => $loop->index, 'count' => $loop->count, 'selectedId' => $selected?->id])
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('admin.podstrony.edit', $child) }}"
                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-gray-100 hover:bg-brand-light">
                                <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i> Edytuj<span class="sr-only"> {{ $child->title }}</span>
                            </a>
                            <a href="{{ $child->is_published && ($child->publish_at === null || $child->publish_at->isPast()) ? $child->publicUrl() : $child->previewUrl() }}" target="_blank" rel="noopener"
                                class="inline-flex h-[30px] w-[30px] items-center justify-center rounded-md text-muted hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-gray-100 hover:bg-brand-light"
                                title="Podgląd" aria-label="Podgląd {{ $child->title }} (nowa karta)">
                                <i class="fa-solid fa-eye text-xs" aria-hidden="true"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
