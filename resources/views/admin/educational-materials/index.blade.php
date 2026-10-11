@extends('admin.layout')

@section('title', 'Materiały edukacyjne')

@section('content')
    <form id="bulk-form" method="POST" action="{{ route('admin.materialy-edukacyjne.bulk') }}">
        @csrf
        <input type="hidden" name="action" id="bulk-action">

        <div class="mb-4 flex items-center justify-between gap-3">
            <div id="bulk-bar" class="hidden items-center gap-3 rounded-md bg-brand-light px-4 py-2">
                <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
                <button type="button" onclick="bulkSubmit('publish')" class="rounded-md px-3 py-1 text-xs font-bold text-ink bg-white hover:bg-brand-light">Opublikuj</button>
                <button type="button" onclick="bulkSubmit('unpublish')" class="rounded-md px-3 py-1 text-xs font-bold text-gray-700 bg-white hover:bg-brand-light">Cofnij publikację</button>
                <button type="button" onclick="if(confirm('Usunąć zaznaczone materiały?')) bulkSubmit('delete')" class="rounded-md px-3 py-1 text-xs font-bold text-red-800 bg-red-50 hover:bg-red-100">Usuń</button>
            </div>
            <div class="ml-auto">
                <a href="{{ route('admin.materialy-edukacyjne.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj materiał
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-md bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-500" aria-label="Zaznacz wszystkie">
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
                                <input type="checkbox" name="ids[]" value="{{ $material->id }}" class="row-check rounded border-gray-500" aria-label="Zaznacz {{ $material->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $material->title }}</td>
                            <td class="px-4 py-3 text-muted">
                                <i class="fa-solid {{ $material->isVideo() ? 'fa-video' : 'fa-file-pdf' }}"></i>
                                {{ \App\Models\EducationalMaterial::TYPES[$material->type] ?? $material->type }}
                            </td>
                            <td class="px-4 py-3 text-muted">{{ \App\Models\EducationalMaterial::TARGET_GROUPS[$material->target_group] ?? $material->target_group }}</td>
                            <td class="px-4 py-3 text-muted">{{ $material->order }}</td>
                            <td class="px-4 py-3">
                                @if ($material->is_published)
                                    <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Opublikowany</span>
                                @else
                                    <span class="text-xs font-bold text-muted">Szkic</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.materialy-edukacyjne.edit', $material) }}" class="text-muted hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
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
