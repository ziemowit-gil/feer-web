@extends('admin.layout')

@section('title', 'Grupy użytkowników')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.grupy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj grupę
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa</th>
                    <th class="px-4 py-3">Dostępne moduły</th>
                    <th class="px-4 py-3">Użytkownicy</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($groups as $group)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $group->name }}</td>
                        <td class="px-4 py-3 text-muted">
                            @forelse ($group->modules ?? [] as $module)
                                <span class="mr-1 inline-block rounded-full bg-brand-light px-2 py-0.5 text-xs font-bold text-brand">{{ \App\Models\SiteSetting::MODULES[$module] ?? $module }}</span>
                            @empty
                                <span class="text-xs">brak</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $group->users_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.grupy.edit', $group) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.grupy.destroy', $group) }}" onsubmit="return confirm('Usunąć grupę &quot;{{ $group->name }}&quot;? Przypisani użytkownicy stracą dostęp do jej modułów.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-muted">Brak grup. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
