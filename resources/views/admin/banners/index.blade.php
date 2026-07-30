@extends('admin.layout')

@section('title', 'Bannery')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Zarządzaj kreacjami bannerowymi i przypisuj je do stref serwisu.</p>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.strefy-bannerow.index') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50">
                <i class="fa-solid fa-layer-group fa-sm"></i> Strefy
            </a>
            <a href="{{ route('admin.banery.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                <i class="fa-solid fa-plus fa-sm"></i> Dodaj baner
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
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
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń">
                                        <i class="fa-solid fa-trash fa-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-muted">
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
@endsection
