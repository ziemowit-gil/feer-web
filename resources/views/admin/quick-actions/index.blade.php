@extends('admin.layout')

@section('title', 'Szybkie akcje')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.szybkie-akcje.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj szybką akcję
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Ikona</th>
                    <th class="px-4 py-3">Etykieta</th>
                    <th class="px-4 py-3">Link</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($quickActions as $action)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-light text-brand">
                                <i class="bi {{ $action->icon }}"></i>
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $action->label }}</td>
                        <td class="px-4 py-3 text-muted">{{ $action->url }}</td>
                        <td class="px-4 py-3 text-muted">{{ $action->order }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.szybkie-akcje.edit', $action) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.szybkie-akcje.destroy', $action) }}" onsubmit="return confirm('Usunąć szybką akcję &quot;{{ $action->label }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak szybkich akcji. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
