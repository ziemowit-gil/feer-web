@extends('admin.layout')

@section('title', 'Strony')

@section('content')
    @include('admin.partials.content-nav-tabs')

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.podstrony.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj stronę
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($pages as $page)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            @if ($page->parent_id)
                                <span class="pl-4 text-muted">↳</span>
                            @endif
                            {{ $page->title }}
                            @if ($page->parent)
                                <span class="ml-1 text-xs font-normal text-muted">(w: {{ $page->parent->title }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">/{{ $page->slug }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.podstrony.kolejnosc', $page) }}" class="flex items-center gap-1">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="order" min="0" value="{{ $page->order }}" aria-label="Kolejność strony {{ $page->title }}"
                                    class="w-16 rounded border-gray-300 py-1 text-sm focus:border-brand focus:ring-brand">
                                <button type="submit" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" title="Zapisz kolejność"><i class="fa-solid fa-check"></i></button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            @if ($page->is_published)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowana</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                            @endif
                            @if ($page->is_disabled)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700"><i class="fa-solid fa-ban"></i> Wyłączona</span>
                            @endif
                            @if ($page->isWip())
                                <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-700"><i class="fa-solid fa-person-digging"></i> W przygotowaniu</span>
                            @endif
                            @if (! $page->parent_id && $page->show_in_menu)
                                <span class="rounded-full bg-brand-light px-2 py-0.5 text-xs font-bold text-brand">W menu</span>
                            @endif
                            @if ($page->is_system)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700"><i class="fa-solid fa-lock"></i> Systemowa</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('page.show', $page) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye"></i></a>
                                <a href="{{ route('admin.podstrony.edit', $page) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.podstrony.clone', $page) }}">
                                    @csrf
                                    <button type="submit" class="text-muted hover:text-brand" title="Klonuj stronę"><i class="fa-solid fa-clone"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.podstrony.widocznosc', $page) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($page->is_published)
                                        <button type="submit" class="text-muted hover:text-amber-600" title="Ukryj (cofnij publikację)"><i class="fa-solid fa-eye-slash"></i></button>
                                    @else
                                        <button type="submit" class="text-muted hover:text-green-600" title="Opublikuj (pokaż)"><i class="fa-solid fa-eye"></i></button>
                                    @endif
                                </form>
                                <form method="POST" action="{{ route('admin.podstrony.wylacz', $page) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($page->is_disabled)
                                        <button type="submit" class="text-red-600 hover:text-green-600" title="Włącz stronę (jest wyłączona)"><i class="fa-solid fa-ban"></i></button>
                                    @else
                                        <button type="submit" class="text-muted hover:text-red-600" title="Wyłącz stronę"><i class="fa-solid fa-power-off"></i></button>
                                    @endif
                                </form>
                                @if ($page->is_system)
                                    <span class="cursor-not-allowed text-gray-300" title="Strony systemowej nie można usunąć"><i class="fa-solid fa-trash"></i></span>
                                @else
                                    <form method="POST" action="{{ route('admin.podstrony.destroy', $page) }}" onsubmit="return confirm('Usunąć stronę &quot;{{ $page->title }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak stron. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
