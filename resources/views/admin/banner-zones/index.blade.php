@extends('admin.layout')

@section('title', 'Strefy bannerów')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-muted">Strefy to miejsca w serwisie, w których wyświetlają się bannery.</p>
            <p class="mt-1 text-xs text-muted">Użyj <code class="rounded bg-gray-100 px-1 font-mono">&lt;x-banner-zone name="slug" /&gt;</code> w szablonie Blade.</p>
        </div>
        <a href="{{ route('admin.strefy-bannerow.create') }}"
            class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus fa-sm"></i> Dodaj strefę
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa / slug</th>
                    <th class="px-4 py-3">Opis</th>
                    <th class="px-4 py-3 text-center">Maks. równocześnie</th>
                    <th class="px-4 py-3 text-center">Bannerów</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($zones as $zone)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-bold text-ink">{{ $zone->label }}</span>
                            <code class="ml-2 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-muted">{{ $zone->slug }}</code>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $zone->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-muted">{{ $zone->max_concurrent }}</td>
                        <td class="px-4 py-3 text-center text-muted">{{ $zone->banners_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.strefy-bannerow.edit', $zone) }}"
                                    class="text-brand hover:text-brand-dark" title="Edytuj">
                                    <i class="fa-solid fa-pen fa-sm"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.strefy-bannerow.destroy', $zone) }}"
                                    onsubmit="return confirm('Usunąć strefę „{{ addslashes($zone->label) }}"? Bannery pozostaną, ale stracą to przypisanie.');">
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
                        <td colspan="5" class="px-4 py-8 text-center text-muted">
                            Brak stref. <a href="{{ route('admin.strefy-bannerow.create') }}" class="text-brand underline">Dodaj pierwszą</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
