@extends('admin.layout')

@section('title', 'Organizacje członkowskie')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.organizacje.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj organizację
        </a>
    </div>

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
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
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-exclamation mr-1 text-ink" aria-hidden="true"></i>Testowa</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('federation.organizations.show', $organization) }}" target="_blank" rel="noopener" class="text-muted hover:text-brand-dark" title="Podgląd wizytówki">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('admin.organizacje.edit', $organization) }}" class="text-muted hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.organizacje.destroy', $organization) }}" onsubmit="return confirm('Usunąć organizację &quot;{{ $organization->name }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                        @include('admin.partials.empty-state', ['icon' => 'fa-people-roof', 'title' => 'Nie ma jeszcze organizacji członkowskich', 'route' => 'admin.organizacje.create', 'action' => 'Dodaj organizację', 'colspan' => 5])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
