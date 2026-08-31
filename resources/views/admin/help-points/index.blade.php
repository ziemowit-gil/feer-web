@extends('admin.layout')

@section('title', 'Mapa pomocy — punkty')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.mapa-pomocy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj punkt pomocy
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa</th>
                    <th class="px-4 py-3">Kategoria</th>
                    <th class="px-4 py-3">Adres</th>
                    <th class="px-4 py-3">Widoczny</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($points as $point)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            <i class="fa-solid {{ $point->categoryIcon() }} mr-2 text-xs text-brand" aria-hidden="true"></i>{{ $point->name }}
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $point->categoryLabel() }}</td>
                        <td class="px-4 py-3 text-muted">{{ $point->address ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($point->is_published)
                                <span class="text-green-700"><i class="fa-solid fa-check" aria-hidden="true"></i> Tak</span>
                            @else
                                <span class="text-muted"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Nie</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $point->order }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.mapa-pomocy.edit', $point) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.mapa-pomocy.destroy', $point) }}" onsubmit="return confirm('Usunąć punkt &quot;{{ $point->name }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-muted">Brak punktów pomocy. Dodaj pierwszy powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
