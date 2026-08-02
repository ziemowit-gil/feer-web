@extends('admin.layout')

@section('title', 'BIP — dokumenty publiczne')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">
            Dokumenty Biuletynu Informacji Publicznej (strona <a href="{{ route('bip') }}" target="_blank" rel="noopener" class="text-brand underline">/bip</a>).
        </p>
        <a href="{{ route('admin.bip-dokumenty.create') }}"
            class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj dokument
        </a>
    </div>

    @if (session('status'))
        <div role="status" class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Kategoria</th>
                    <th class="px-4 py-3">Widoczność</th>
                    <th class="px-4 py-3">Pliki</th>
                    <th class="px-4 py-3">Dodał/-a</th>
                    <th class="px-4 py-3">Data dodania</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($documents as $document)
                    <tr>
                        <th scope="row" class="px-4 py-3 font-semibold text-ink">
                            <a href="{{ route('admin.bip-dokumenty.edit', $document) }}" class="hover:text-brand hover:underline">
                                {{ $document->title }}
                            </a>
                        </th>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700">
                                {{ $document->categoryLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($document->is_published)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Widoczny</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Ukryty</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $document->getMedia('files')->count() ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $document->creator?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $document->created_at->format('d.m.Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                {{-- Podgląd publiczny --}}
                                @if ($document->is_published)
                                    <a href="{{ route('bip.document', $document->slug) }}" target="_blank" rel="noopener"
                                        class="text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded"
                                        title="Podgląd na stronie">
                                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                        <span class="sr-only">Podgląd na stronie — {{ $document->title }}</span>
                                    </a>
                                @endif

                                {{-- Historia zmian --}}
                                <a href="{{ route('admin.historia.index', ['type' => 'bip_document', 'id' => $document->id]) }}"
                                    class="text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded"
                                    title="Historia zmian">
                                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                                    <span class="sr-only">Historia zmian — {{ $document->title }}</span>
                                </a>

                                {{-- Przełącz widoczność --}}
                                <form method="POST" action="{{ route('admin.bip-dokumenty.widocznosc', $document) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="{{ $document->is_published ? 'text-green-600 hover:text-gray-400' : 'text-gray-400 hover:text-green-600' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded"
                                        title="{{ $document->is_published ? 'Ukryj' : 'Opublikuj' }}">
                                        <i class="fa-solid {{ $document->is_published ? 'fa-eye' : 'fa-eye-slash' }}" aria-hidden="true"></i>
                                        <span class="sr-only">{{ $document->is_published ? 'Ukryj' : 'Opublikuj' }} — {{ $document->title }}</span>
                                    </button>
                                </form>

                                {{-- Edytuj --}}
                                <a href="{{ route('admin.bip-dokumenty.edit', $document) }}"
                                    class="text-brand hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded"
                                    title="Edytuj">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    <span class="sr-only">Edytuj — {{ $document->title }}</span>
                                </a>

                                {{-- Usuń --}}
                                <form method="POST" action="{{ route('admin.bip-dokumenty.destroy', $document) }}"
                                    onsubmit="return confirm('Przenieść dokument „{{ addslashes($document->title) }}" do kosza?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-muted hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 rounded"
                                        title="Usuń">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        <span class="sr-only">Usuń — {{ $document->title }}</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-muted">
                            Brak dokumentów BIP.
                            <a href="{{ route('admin.bip-dokumenty.create') }}" class="text-brand underline">Dodaj pierwszy dokument</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
