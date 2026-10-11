@extends('admin.layout')

@section('title', 'Wiem FEER — kategorie')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.wiem-feer.index') }}" class="text-sm font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do artykułów
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-labelledby="cat-list-heading">
            <h2 id="cat-list-heading" class="mb-3 text-lg font-bold text-ink">Kategorie</h2>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                        <tr>
                            <th class="px-4 py-3">Nazwa</th>
                            <th class="px-4 py-3">Adres</th>
                            <th class="px-4 py-3">Wpisy</th>
                            <th class="px-4 py-3">Kolejność</th>
                            <th class="px-4 py-3 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $c)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $c->name }}</td>
                                <td class="px-4 py-3 text-muted">?kategoria={{ $c->slug }}</td>
                                <td class="px-4 py-3 text-muted">{{ $c->articles_count }}</td>
                                <td class="px-4 py-3 text-muted">{{ $c->sort_order }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.kategorie-bloga.edit', $c) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj {{ $c->name }}</span></a>
                                        <form method="POST" action="{{ route('admin.kategorie-bloga.destroy', $c) }}" onsubmit="return confirm('Usunąć kategorię? Jej wpisy zostaną bez kategorii.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń {{ $c->name }}</span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-muted">Brak kategorii. Dodaj pierwszą obok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-labelledby="cat-new-heading">
            <h2 id="cat-new-heading" class="mb-3 text-lg font-bold text-ink">Nowa kategoria</h2>
            @include('blog::admin.categories.fields', ['action' => route('admin.kategorie-bloga.store'), 'method' => null])
        </section>
    </div>
@endsection
