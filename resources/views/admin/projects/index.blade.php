@extends('admin.layout')

@section('title', 'Działania')

@section('content')
    <form id="bulk-form" method="POST" action="{{ route('admin.projekty.bulk') }}">
        @csrf
        <input type="hidden" name="action" id="bulk-action">

        <div class="mb-4 flex items-center justify-between gap-3">
            <div id="bulk-bar" class="hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
                <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
                <button type="button" onclick="bulkSubmit('publish')" class="rounded border border-green-300 bg-white px-3 py-1 text-xs font-bold text-green-700 hover:bg-green-50">Opublikuj</button>
                <button type="button" onclick="bulkSubmit('unpublish')" class="rounded border border-gray-300 bg-white px-3 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50">Cofnij publikację</button>
                <button type="button" onclick="if(confirm('Przenieść zaznaczone do kosza?')) bulkSubmit('trash')" class="rounded border border-red-300 bg-white px-3 py-1 text-xs font-bold text-red-600 hover:bg-red-50">Do kosza</button>
            </div>
            <div class="ml-auto">
                <a href="{{ route('admin.projekty.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj działanie
                </a>
            </div>
        </div>

        @include('admin.partials.list-filters', [
            'action' => route('admin.projekty.index'),
            'status' => $status,
            'categories' => $categories,
            'categoryId' => $category,
            'sort' => $sort,
            'sortOptions' => ['default' => 'Domyślne (kolejność)', 'title_asc' => 'Tytuł A–Z', 'title_desc' => 'Tytuł Z–A'],
            'total' => $projects->count(),
        ])

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50/80 text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie">
                        </th>
                        <th class="px-4 py-3">Działanie</th>
                        <th class="hidden px-4 py-3 md:table-cell">Kategoria</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="w-28 px-4 py-3 text-right"><span class="sr-only">Akcje</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($projects as $project)
                        <tr class="group transition hover:bg-gray-50/70">
                            <td class="px-4 py-3 align-middle">
                                <input type="checkbox" name="ids[]" value="{{ $project->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $project->title }}">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($project->image_url)
                                        <img src="{{ $project->image_url }}" alt="" loading="lazy" class="h-12 w-16 flex-none rounded-lg object-cover ring-1 ring-gray-200">
                                    @else
                                        <span class="flex h-12 w-16 flex-none items-center justify-center rounded-lg bg-gray-100 text-gray-300" aria-hidden="true"><i class="fa-solid fa-diagram-project text-lg"></i></span>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.projekty.edit', $project) }}" class="block truncate font-bold text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $project->title }}</a>
                                        @if ($project->excerpt)
                                            <p class="mt-0.5 line-clamp-1 text-xs text-muted">{{ $project->excerpt }}</p>
                                        @endif
                                        <p class="mt-0.5 text-xs text-muted md:hidden">{{ $project->category->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-ink">{{ $project->category->name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($project->is_published)
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-green-50 px-2.5 py-0.5 text-xs font-bold text-green-700"><span class="h-1.5 w-1.5 rounded-md bg-green-500" aria-hidden="true"></span>Opublikowany</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-600"><span class="h-1.5 w-1.5 rounded-md bg-gray-400" aria-hidden="true"></span>Szkic</span>
                                    @endif
                                    @if ($project->is_completed)
                                        <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">Zrealizowany</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('projects.show', $project) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-lg text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" title="Podgląd" aria-label="Podgląd: {{ $project->title }} (nowa karta)"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('admin.projekty.edit', $project) }}" class="flex h-9 w-9 items-center justify-center rounded-lg text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" title="Edytuj" aria-label="Edytuj: {{ $project->title }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.projekty.destroy', $project) }}" onsubmit="return confirm('Usunąć działanie &quot;{{ $project->title }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg text-muted hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" title="Usuń" aria-label="Usuń: {{ $project->title }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-muted">Brak projektów. Dodaj pierwszy powyżej (wymaga co najmniej jednej kategorii).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <script>
        const bar = document.getElementById('bulk-bar');
        const countEl = document.getElementById('bulk-count');
        const selectAll = document.getElementById('select-all');
        const checks = () => document.querySelectorAll('.row-check');

        function updateBar() {
            const checked = [...checks()].filter(c => c.checked);
            if (checked.length > 0) {
                countEl.textContent = 'Zaznaczono: ' + checked.length;
                bar.classList.remove('hidden');
                bar.classList.add('flex');
            } else {
                bar.classList.add('hidden');
                bar.classList.remove('flex');
            }
        }

        function bulkSubmit(action) {
            document.getElementById('bulk-action').value = action;
            document.getElementById('bulk-form').submit();
        }

        selectAll.addEventListener('change', function () {
            checks().forEach(c => { c.checked = this.checked; });
            updateBar();
        });

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('row-check')) {
                updateBar();
                selectAll.checked = [...checks()].every(c => c.checked);
            }
        });
    </script>
@endsection
