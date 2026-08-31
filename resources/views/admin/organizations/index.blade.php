@extends('admin.layout')

@section('title', 'Organizacje członkowskie')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.organizacje.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj organizację
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa</th>
                    <th class="px-4 py-3">Forma prawna</th>
                    <th class="px-4 py-3">Miejscowość</th>
                    <th class="px-4 py-3">Testowa</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($organizations as $organization)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $organization->name }}</td>
                        <td class="px-4 py-3 text-muted">{{ $organization->type }}</td>
                        <td class="px-4 py-3 text-muted">{{ $organization->town }}</td>
                        <td class="px-4 py-3">
                            @if ($organization->is_test)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">Testowa</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('federation.organizations.show', $organization) }}" target="_blank" rel="noopener" class="text-muted hover:text-brand" title="Podgląd wizytówki">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('admin.organizacje.edit', $organization) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.organizacje.destroy', $organization) }}" onsubmit="return confirm('Usunąć organizację &quot;{{ $organization->name }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak organizacji. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
