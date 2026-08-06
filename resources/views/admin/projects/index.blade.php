@extends('admin.layout')

@section('title', 'Projekty')

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
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj projekt
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

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie">
                        </th>
                        <th class="px-4 py-3">Tytuł</th>
                        <th class="px-4 py-3">Kategoria</th>
                        <th class="px-4 py-3">Kolejność</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($projects as $project)
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $project->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $project->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $project->title }}</td>
                            <td class="px-4 py-3 text-muted">{{ $project->category->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $project->order }}</td>
                            <td class="px-4 py-3">
                                @if ($project->is_published)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                                @endif
                                @if ($project->is_completed)
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">Zrealizowany</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('projects.show', $project) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('admin.projekty.edit', $project) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.projekty.destroy', $project) }}" onsubmit="return confirm('Usunąć projekt &quot;{{ $project->title }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-muted">Brak projektów. Dodaj pierwszy powyżej (wymaga co najmniej jednej kategorii).</td>
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
