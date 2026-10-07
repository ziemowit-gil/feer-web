@extends('admin.layout')

@section('title', 'FEER Paski')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-ink">FEER Paski</h1>
            <p class="text-sm text-muted">Paski z tytułem, tekstem i przyciskami (np. „Twoje wsparcie tworzy zmianę"). Pokazują się na stronie głównej szablonu FEER w wybranym miejscu albo w treści strony przez skrót <code class="rounded bg-gray-100 px-1">[pasek:ID]</code>.</p>
        </div>
        <a href="{{ route('admin.feer-paski.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Dodaj pasek</a>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">{{ session('status') }}</div>
    @endif

    @if ($bands->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-10 text-center text-muted">Brak pasków. Dodaj pierwszy powyżej.</div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-3">Pasek</th>
                        <th class="hidden px-4 py-3 md:table-cell">Miejsce</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Styl</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right"><span class="sr-only">Akcje</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($bands as $band)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.feer-paski.edit', $band) }}" class="font-bold text-ink hover:text-brand">{{ $band->title }}</a>
                                <p class="mt-0.5 text-xs text-muted">Skrót: <code class="rounded bg-gray-100 px-1">{{ $band->shortcode() }}</code></p>
                            </td>
                            <td class="hidden px-4 py-3 text-muted md:table-cell">{{ \App\Models\FeerBand::PLACEMENTS[$band->placement] ?? $band->placement }}</td>
                            <td class="hidden px-4 py-3 text-muted sm:table-cell">{{ \App\Models\FeerBand::STYLES[$band->style] ?? $band->style }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $band->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">{{ $band->is_active ? 'Aktywny' : 'Wyłączony' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.feer-paski.edit', $band) }}" class="font-bold text-brand hover:underline">Edytuj</a>
                                    <form method="POST" action="{{ route('admin.feer-paski.destroy', $band) }}" onsubmit="return confirm('Usunąć pasek &quot;{{ $band->title }}&quot;?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="font-bold text-red-700 hover:underline">Usuń</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
