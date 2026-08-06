@extends('admin.layout')

@section('title', 'Materiały edukacyjne')

@section('content')
    <form id="bulk-form" method="POST" action="{{ route('admin.materialy-edukacyjne.bulk') }}">
        @csrf
        <input type="hidden" name="action" id="bulk-action">

        <div class="mb-4 flex items-center justify-between gap-3">
            <div id="bulk-bar" class="hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
                <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
                <button type="button" onclick="bulkSubmit('publish')" class="rounded border border-green-300 bg-white px-3 py-1 text-xs font-bold text-green-700 hover:bg-green-50">Opublikuj</button>
                <button type="button" onclick="bulkSubmit('unpublish')" class="rounded border border-gray-300 bg-white px-3 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50">Cofnij publikację</button>
                <button type="button" onclick="if(confirm('Usunąć zaznaczone materiały?')) bulkSubmit('delete')" class="rounded border border-red-300 bg-white px-3 py-1 text-xs font-bold text-red-600 hover:bg-red-50">Usuń</button>
            </div>
            <div class="ml-auto">
                <a href="{{ route('admin.materialy-edukacyjne.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj materiał
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie">
                        </th>
                        <th class="px-4 py-3">Tytuł</th>
                        <th class="px-4 py-3">Typ</th>
                        <th class="px-4 py-3">Dla kogo</th>
                        <th class="px-4 py-3">Kolejność</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($materials as $material)
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $material->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $material->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $material->title }}</td>
                            <td class="px-4 py-3 text-muted">
                                <i class="fa-solid {{ $material->isVideo() ? 'fa-video' : 'fa-file-pdf' }}"></i>
                                {{ \App\Models\EducationalMaterial::TYPES[$material->type] ?? $material->type }}
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $material->target_group }}</td>
                            <td class="px-4 py-3 text-muted">{{ $material->order }}</td>
                            <td class="px-4 py-3">
                                @if ($material->is_published)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.materialy-edukacyjne.edit', $material) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.materialy-edukacyjne.destroy', $material) }}" onsubmit="return confirm('Usunąć materiał &quot;{{ $material->title }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-muted">Brak materiałów. Dodaj pierwszy powyżej.</td>
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
