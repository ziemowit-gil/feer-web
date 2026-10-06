{{--
    Tabela podstron w prawym panelu widoku drzewa.
    Zmienne: $children, $selected (null = poziom główny), $byParent.
--}}
<div class="overflow-x-auto rounded-lg border border-gray-200">
    <table class="w-full text-left text-sm">
        <caption class="sr-only">Podstrony {{ $selected ? 'strony „'.$selected->title.'”' : 'najwyższego poziomu' }}</caption>
        <thead class="border-b border-gray-100 bg-gray-50/80">
            <tr>
                <th scope="col" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-muted">Strona</th>
                <th scope="col" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                <th scope="col" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-muted">Kolejność</th>
                <th scope="col" class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wide text-muted">Akcje</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach ($children as $child)
                @php $subCount = $byParent->get($child->id, collect())->count(); @endphp
                <tr class="hover:bg-gray-50/60">
                    <th scope="row" class="px-4 py-2.5 font-normal">
                        <a href="{{ route('admin.podstrony.index', ['wybrana' => $child->id]) }}"
                            class="font-semibold text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $child->title }}</a>
                        <span class="block font-mono text-xs text-muted">/{{ $child->slug }}</span>
                        @if ($subCount)<span class="text-xs text-muted">{{ $subCount }} {{ trans_choice('podstrona|podstrony|podstron', $subCount) }}</span>@endif
                    </th>
                    <td class="px-4 py-2.5">
                        @include('admin.pages.partials.status-chip', ['page' => $child])
                    </td>
                    <td class="px-4 py-2.5">
                        <form method="POST" action="{{ route('admin.podstrony.kolejnosc', $child) }}" class="flex items-center gap-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="wybrana" value="{{ $selected?->id }}">
                            <input type="number" name="order" min="0" value="{{ $child->order }}" aria-label="Kolejność strony {{ $child->title }}"
                                class="w-16 rounded border-gray-300 py-1 text-sm focus:border-brand focus:ring-brand">
                            <button type="submit" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Zapisz kolejność strony {{ $child->title }}" title="Zapisz kolejność">
                                <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('admin.podstrony.edit', $child) }}"
                                class="inline-flex items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i> Edytuj<span class="sr-only"> {{ $child->title }}</span>
                            </a>
                            <a href="{{ $child->is_published && ($child->publish_at === null || $child->publish_at->isPast()) ? $child->publicUrl() : $child->previewUrl() }}" target="_blank" rel="noopener"
                                class="inline-flex h-[30px] w-[30px] items-center justify-center rounded border border-gray-200 bg-white text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
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
