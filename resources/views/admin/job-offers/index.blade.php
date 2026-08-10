@extends('admin.layout')

@section('title', 'Oferty pracy')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Ogłoszenia o pracę (strona <a href="{{ route('praca.index') }}" target="_blank" rel="noopener" class="text-brand underline">/praca</a>).</p>
        <div class="flex items-center gap-3">
            @if ($showArchived || $archivedCount > 0)
                <a href="{{ route('admin.praca.index', ['archived' => $showArchived ? null : 1]) }}"
                    class="rounded border px-3 py-2 text-sm font-bold {{ $showArchived ? 'border-brand bg-brand-light text-brand' : 'border-gray-300 text-muted hover:bg-gray-100' }}">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                    {{ $showArchived ? 'Pokaż aktywne' : 'Archiwum ('.$archivedCount.')' }}
                </a>
            @endif
            <a href="{{ route('admin.praca.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj ogłoszenie
            </a>
        </div>
    </div>

    @if ($showArchived)
        <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Widok archiwum — ogłoszenia po terminie naboru schowane z domyślnej listy.
        </p>
    @endif

    <form id="bulk-form" method="POST" action="{{ route('admin.praca.bulk') }}">
        @csrf

        <div id="bulk-bar" class="mb-3 hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
            <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
            <select name="action" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                @if ($showArchived)
                    <option value="restore">Przywróć z archiwum</option>
                @else
                    <option value="archive">Zarchiwizuj</option>
                @endif
                <option value="delete">Usuń</option>
            </select>
            <button type="submit" onclick="return confirm('Wykonać tę operację na zaznaczonych pozycjach?')"
                class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Wykonaj
            </button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-8 px-4 py-3"><input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie"></th>
                        <th class="px-4 py-3">Tytuł</th>
                        <th class="px-4 py-3">Umowa / tryb</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Termin aplikacji</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($offers as $offer)
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $offer->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $offer->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $offer->title }}</td>
                            <td class="px-4 py-3 text-muted">{{ $offer->jobTypeLabel() }} · {{ $offer->modeLabel() }}</td>
                            <td class="px-4 py-3">
                                @if (! $offer->is_published)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Szkic</span>
                                @elseif ($offer->isClosed())
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Zakończone</span>
                                @else
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Aktywne</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $offer->closes_at ? $offer->closes_at->format('d.m.Y') : '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('praca.show', $offer) }}" target="_blank" rel="noopener" class="text-muted hover:text-brand" title="Podgląd" aria-label="Podgląd"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('praca.pdf', $offer) }}" target="_blank" rel="noopener" class="text-muted hover:text-brand" title="PDF" aria-label="Pobierz PDF"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i></a>
                                    <a href="{{ route('admin.praca.edit', $offer) }}" class="text-brand hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.praca.klonuj', $offer) }}">
                                        @csrf
                                        <button type="submit" class="text-muted hover:text-brand" title="Klonuj jako szkic" aria-label="Klonuj"><i class="fa-solid fa-copy"></i></button>
                                    </form>
                                    @if ($offer->archived_at)
                                        <form method="POST" action="{{ route('admin.praca.restore', $offer) }}">
                                            @csrf @method('PUT')
                                            <button type="submit" class="text-muted hover:text-brand" title="Przywróć z archiwum" aria-label="Przywróć"><i class="fa-solid fa-box-open"></i></button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.praca.archive', $offer) }}">
                                            @csrf @method('PUT')
                                            <button type="submit" class="text-muted hover:text-amber-600" title="Zarchiwizuj" aria-label="Zarchiwizuj"><i class="fa-solid fa-box-archive"></i></button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.praca.destroy', $offer) }}" onsubmit="return confirm('Usunąć ogłoszenie &quot;{{ $offer->title }}&quot;?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted">Brak ogłoszeń. <a href="{{ route('admin.praca.create') }}" class="text-brand underline">Dodaj pierwsze</a>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <script>
        const selectAll = document.getElementById('select-all');
        const bar = document.getElementById('bulk-bar');
        const countEl = document.getElementById('bulk-count');
        function updateBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            bar.classList.toggle('hidden', checked.length === 0);
            bar.classList.toggle('flex', checked.length > 0);
            countEl.textContent = `Zaznaczono: ${checked.length}`;
        }
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.row-check').forEach(cb => { cb.checked = selectAll.checked; });
            updateBar();
        });
        document.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateBar));
    </script>
@endsection
