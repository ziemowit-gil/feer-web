@extends('admin.layout')

@section('title', 'Menu nawigacyjne')

@section('content')
    @include('admin.partials.content-nav-tabs')

    <p class="mb-4 text-sm text-muted">
        Pozycje menu wyświetlanego w nagłówku strony. Pozycja typu „Rozwijane menu” może mieć własne podpozycje (submenu),
        a „Menu projektów” pobiera zawartość automatycznie z kategorii projektów (<a href="{{ route('admin.kategorie.index') }}" class="text-brand hover:text-brand-dark">zarządzaj kategoriami</a>).
    </p>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.pozycje-menu.create', ['location' => $location]) }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj pozycję menu
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Etykieta</th>
                    <th class="px-4 py-3">Typ</th>
                    <th class="px-4 py-3">Link / Moduł</th>
                    <th class="px-4 py-3">Styl</th>
                    <th class="px-4 py-3">Widoczność</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($navItems as $item)
                    @include('admin.nav-items._row', ['item' => $item, 'child' => false])
                    @foreach ($item->allChildren as $child)
                        @include('admin.nav-items._row', ['item' => $child, 'child' => true])
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-muted">Brak pozycji menu. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
