@extends('admin.layout')

@section('title', 'Wolontariat — ogłoszenia')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Ogłoszenia o wolontariacie (strona <a href="{{ route('volunteer.index') }}" target="_blank" rel="noopener" class="text-brand underline">/wolontariat</a>).</p>
        <div class="flex items-center gap-3">
            @if ($showArchived || $archivedCount > 0)
                <a href="{{ route('admin.wolontariat.index', ['archived' => $showArchived ? null : 1]) }}"
                    class="rounded border px-3 py-2 text-sm font-bold {{ $showArchived ? 'border-brand bg-brand-light text-brand' : 'border-gray-300 text-muted hover:bg-gray-100' }}">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                    {{ $showArchived ? 'Pokaż aktywne' : 'Archiwum ('.$archivedCount.')' }}
                </a>
            @endif
            <a href="{{ route('admin.wolontariat.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj ogłoszenie
            </a>
        </div>
    </div>

    @if ($showArchived)
        <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Widok archiwum — ogłoszenia po terminie zgłoszeń schowane z domyślnej listy.
        </p>
    @endif

    <form id="bulk-form" method="POST" action="{{ route('admin.wolontariat.bulk') }}">
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
                        <th class="w-8 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie">
                        </th>
                        <th class="px-4 py-3">Tytuł</th>
                        <th class="px-4 py-3">Tryb</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Termin zgłoszeń</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ads as $ad)
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $ad->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $ad->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $ad->title }}</td>
                            <td class="px-4 py-3 text-muted">{{ $ad->modeLabel() }}</td>
                            <td class="px-4 py-3">
                                @if (! $ad->is_published)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Szkic</span>
                                @elseif ($ad->isClosed())
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Zakończone</span>
                                @else
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Aktywne</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $ad->closes_at ? $ad->closes_at->format('d.m.Y') : '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.wolontariat.edit', $ad) }}" class="text-brand hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.wolontariat.klonuj', $ad) }}">
                                        @csrf
                                        <button type="submit" class="text-muted hover:text-brand" title="Klonuj jako szkic" aria-label="Klonuj jako szkic"><i class="fa-solid fa-copy"></i></button>
                                    </form>
                                    @if ($ad->archived_at)
                                        <form method="POST" action="{{ route('admin.wolontariat.restore', $ad) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-muted hover:text-brand" title="Przywróć z archiwum" aria-label="Przywróć z archiwum"><i class="fa-solid fa-box-open"></i></button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.wolontariat.archive', $ad) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-muted hover:text-brand" title="Zarchiwizuj (schowaj z listy)" aria-label="Zarchiwizuj (schowaj z listy)"><i class="fa-solid fa-box-archive"></i></button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.wolontariat.destroy', $ad) }}" onsubmit="return confirm('Usunąć ogłoszenie &quot;{{ $ad->title }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted">Brak ogłoszeń. <a href="{{ route('admin.wolontariat.create') }}" class="text-brand underline">Dodaj pierwsze</a>.</td>
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

        document.querySelectorAll('.row-check').forEach(cb => {
            cb.addEventListener('change', updateBar);
        });
    </script>
@endsection
