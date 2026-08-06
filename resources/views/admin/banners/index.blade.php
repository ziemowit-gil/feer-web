@extends('admin.layout')

@section('title', 'Bannery')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form id="bulk-form" method="POST" action="{{ route('admin.banery.bulk') }}">
    @csrf
    <input type="hidden" name="action" id="bulk-action">

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <p class="text-sm text-muted">Zarządzaj kreacjami bannerowymi i przypisuj je do stref serwisu.</p>
            <div id="bulk-bar" class="hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
                <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
                <button type="button" onclick="bulkSubmit('activate')" class="rounded border border-green-300 bg-white px-3 py-1 text-xs font-bold text-green-700 hover:bg-green-50">Aktywuj</button>
                <button type="button" onclick="bulkSubmit('deactivate')" class="rounded border border-gray-300 bg-white px-3 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50">Wyłącz</button>
                <button type="button" onclick="if(confirm('Usunąć zaznaczone banery?')) bulkSubmit('delete')" class="rounded border border-red-300 bg-white px-3 py-1 text-xs font-bold text-red-600 hover:bg-red-50">Usuń</button>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.strefy-bannerow.index') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50">
                <i class="fa-solid fa-layer-group fa-sm"></i> Strefy
            </a>
            <a href="{{ route('admin.banery.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus fa-sm"></i> Dodaj baner
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie">
                    </th>
                    <th class="px-4 py-3">Baner</th>
                    <th class="px-4 py-3">Strefy</th>
                    <th class="px-4 py-3">Typ</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Wyśw.</th>
                    <th class="px-4 py-3 text-right">Klik.</th>
                    <th class="px-4 py-3 text-right">CTR</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($banners as $banner)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="ids[]" value="{{ $banner->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $banner->name }}">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($banner->type === 'image' && $banner->image_path)
                                    <img src="{{ Storage::url($banner->image_path) }}" alt=""
                                        class="h-10 w-16 rounded border border-gray-200 object-cover">
                                @else
                                    <div class="flex h-10 w-16 items-center justify-center rounded border border-gray-200 bg-gray-100 text-xs text-muted">
                                        HTML
                                    </div>
                                @endif
                                <span class="font-medium text-ink">{{ $banner->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($banner->zones as $zone)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-muted">{{ $zone->label }}</span>
                                @endforeach
                                @if ($banner->zones->isEmpty())
                                    <span class="text-xs text-muted italic">bez strefy</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $banner->type === 'image' ? 'Obraz' : 'HTML' }}
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $banner->statusColor(); @endphp
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-bold',
                                'bg-green-100 text-green-700' => $color === 'green',
                                'bg-blue-100 text-blue-700'   => $color === 'blue',
                                'bg-gray-100 text-gray-600'   => $color === 'gray',
                                'bg-red-100 text-red-700'     => $color === 'red',
                            ])>{{ $banner->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-muted">{{ number_format($banner->impressions) }}</td>
                        <td class="px-4 py-3 text-right text-muted">{{ number_format($banner->clicks) }}</td>
                        <td class="px-4 py-3 text-right text-muted">{{ $banner->ctr() }}%</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <form method="POST" action="{{ route('admin.banery.toggle', $banner) }}">
                                    @csrf
                                    <button type="submit"
                                        class="text-muted hover:text-brand"
                                        title="{{ $banner->is_active ? 'Wyłącz' : 'Włącz' }}">
                                        <i class="fa-solid {{ $banner->is_active ? 'fa-toggle-on text-brand' : 'fa-toggle-off' }}"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.banery.edit', $banner) }}"
                                    class="text-brand hover:text-brand-dark" title="Edytuj">
                                    <i class="fa-solid fa-pen fa-sm"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.banery.destroy', $banner) }}"
                                    onsubmit="return confirm('Usunąć baner „{{ addslashes($banner->name) }}"?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń">
                                        <i class="fa-solid fa-trash fa-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-muted">
                            Brak banerów. <a href="{{ route('admin.banery.create') }}" class="text-brand underline">Dodaj pierwszy</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($banners->hasPages())
        <div class="mt-4">{{ $banners->links() }}</div>
    @endif
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
