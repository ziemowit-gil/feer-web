@extends('admin.layout')

@section('title', 'Szkolenia i wydarzenia')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Szkolenia i wydarzenia (strona <a href="{{ route('events.index') }}" target="_blank" rel="noopener" class="text-brand-dark underline">/wydarzenia</a>).</p>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.wydarzenia.statystyki') }}"
                class="rounded-md px-3 py-2 text-sm font-bold text-muted bg-white hover:bg-brand-light">
                <i class="fa-solid fa-chart-bar" aria-hidden="true"></i> Statystyki
            </a>
            @if ($showArchived || $archivedCount > 0)
                <a href="{{ route('admin.wydarzenia.index', ['archived' => $showArchived ? null : 1]) }}"
                    class="rounded border px-3 py-2 text-sm font-bold {{ $showArchived ? 'border-brand-dark bg-brand-light text-brand-dark' : 'border-gray-300 text-muted hover:bg-gray-100' }}">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                    {{ $showArchived ? 'Pokaż aktywne' : 'Archiwum ('.$archivedCount.')' }}
                </a>
            @endif
            <a href="{{ route('admin.wydarzenia.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wydarzenie
            </a>
        </div>
    </div>

    @if ($showArchived)
        <p class="mb-4 rounded-md bg-amber-50 px-4 py-2 text-sm text-amber-800">
            <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Widok archiwum — zakończone wydarzenia schowane z domyślnej listy.
        </p>
    @endif

    <form id="bulk-form" method="POST" action="{{ route('admin.wydarzenia.bulk') }}">
        @csrf

        {{-- Pasek akcji zbiorczych (widoczny po zaznaczeniu przynajmniej jednej pozycji) --}}
        <div id="bulk-bar" class="mb-3 hidden items-center gap-3 rounded-md bg-brand-light px-4 py-2">
            <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
            <select name="action" class="rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                @if ($showArchived)
                    <option value="restore">Przywróć z archiwum</option>
                @else
                    <option value="archive">Zarchiwizuj</option>
                @endif
                <option value="delete">Usuń</option>
            </select>
            <button type="submit" onclick="return confirm('Wykonać tę operację na zaznaczonych pozycjach?')"
                class="rounded bg-brand-dark px-3 py-1.5 text-sm font-bold text-white hover:bg-ink">
                Wykonaj
            </button>
        </div>

        <div class="overflow-x-auto rounded-md bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-8 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-500" aria-label="Zaznacz wszystkie">
                        </th>
                        <th class="w-full px-4 py-3">Tytuł</th>
                        <th class="min-w-[6rem] whitespace-nowrap px-4 py-3">Rodzaj</th>
                        <th class="min-w-[9rem] whitespace-nowrap px-4 py-3">Termin</th>
                        <th class="min-w-[6rem] whitespace-nowrap px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $event->id }}" class="row-check rounded border-gray-500" aria-label="Zaznacz {{ $event->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ $event->title }}
                                @if ($event->isSeries())
                                    <span class="ml-1 text-xs font-bold text-ink" title="Seria powtarzająca się">
                                        <i class="fa-solid fa-rotate" aria-hidden="true"></i> Seria &middot; {{ $event->instances()->count() }}
                                    </span>
                                @elseif ($event->isInstance())
                                    <span class="ml-1 text-xs text-muted font-bold" title="Instancja serii">instancja</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted">
                                <i class="fa-solid {{ $event->typeIcon() }} mr-1 text-gray-400" aria-hidden="true"></i>{{ $event->typeLabel() }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $event->shortDateLabel() }}</td>
                            <td class="px-4 py-3">
                                @if (! $event->is_published)
                                    <span class="text-xs font-bold text-muted">Szkic</span>
                                @elseif ($event->isPast())
                                    <span class="text-xs font-bold text-ink"><i class="fa-solid fa-flag-checkered mr-1 text-muted" aria-hidden="true"></i>Zakończone</span>
                                @else
                                    <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Nadchodzące</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    @if ($siteSettings->isModuleEnabled('news'))
                                        <form method="POST" action="{{ route('admin.wydarzenia.na-aktualnosc', $event) }}" onsubmit="return confirm('Utworzyć aktualność na podstawie „{{ $event->title }}”? Powstanie szkic do przejrzenia.');">
                                            @csrf
                                            <button type="submit"
                                                class="group/btn relative inline-flex items-center rounded px-2 py-1 text-xs font-medium text-muted hover:bg-gray-100 hover:text-brand-dark"
                                                aria-label="Stwórz aktualność z tego wydarzenia">
                                                <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                                                <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-700 px-2 py-0.5 text-xs font-normal text-white opacity-0 transition-opacity group-hover/btn:opacity-100" role="tooltip">Aktualność</span>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($siteSettings->isModuleEnabled('landing'))
                                        <form method="POST" action="{{ route('admin.wydarzenia.na-landing', $event) }}" onsubmit="return confirm('Utworzyć landing page na podstawie „{{ $event->title }}”? Powstanie szkic do przejrzenia.');">
                                            @csrf
                                            <button type="submit"
                                                class="group/btn relative inline-flex items-center rounded px-2 py-1 text-xs font-medium text-muted hover:bg-gray-100 hover:text-brand-dark"
                                                aria-label="Stwórz landing page z tego wydarzenia">
                                                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                                                <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-700 px-2 py-0.5 text-xs font-normal text-white opacity-0 transition-opacity group-hover/btn:opacity-100" role="tooltip">Landing</span>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.wydarzenia.edit', $event) }}"
                                        class="group/btn relative inline-flex items-center rounded px-2 py-1 text-xs font-medium text-brand-dark hover:bg-brand-light"
                                        aria-label="Edytuj wydarzenie">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-700 px-2 py-0.5 text-xs font-normal text-white opacity-0 transition-opacity group-hover/btn:opacity-100" role="tooltip">Edytuj</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.wydarzenia.klonuj', $event) }}">
                                        @csrf
                                        <button type="submit"
                                            class="group/btn relative inline-flex items-center rounded px-2 py-1 text-xs font-medium text-muted hover:bg-gray-100 hover:text-brand-dark"
                                            aria-label="Klonuj wydarzenie">
                                            <i class="fa-solid fa-copy" aria-hidden="true"></i>
                                            <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-700 px-2 py-0.5 text-xs font-normal text-white opacity-0 transition-opacity group-hover/btn:opacity-100" role="tooltip">Klonuj</span>
                                        </button>
                                    </form>
                                    @if ($event->archived_at)
                                        <form method="POST" action="{{ route('admin.wydarzenia.restore', $event) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit"
                                                class="group/btn relative inline-flex items-center rounded px-2 py-1 text-xs font-medium text-muted hover:bg-gray-100 hover:text-brand-dark"
                                                aria-label="Przywróć wydarzenie z archiwum">
                                                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                                <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-700 px-2 py-0.5 text-xs font-normal text-white opacity-0 transition-opacity group-hover/btn:opacity-100" role="tooltip">Przywróć</span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.wydarzenia.archive', $event) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit"
                                                class="group/btn relative inline-flex items-center rounded px-2 py-1 text-xs font-medium text-muted hover:bg-gray-100 hover:text-brand-dark"
                                                aria-label="Archiwizuj wydarzenie">
                                                <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                                                <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-700 px-2 py-0.5 text-xs font-normal text-white opacity-0 transition-opacity group-hover/btn:opacity-100" role="tooltip">Archiwizuj</span>
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.wydarzenia.destroy', $event) }}" onsubmit="return confirm('Usunąć wydarzenie „{{ $event->title }}”? Tej operacji nie można cofnąć.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="group/btn relative inline-flex items-center rounded px-2 py-1 text-xs font-medium text-muted hover:bg-red-50 hover:text-red-600"
                                            aria-label="Usuń wydarzenie">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-700 px-2 py-0.5 text-xs font-normal text-white opacity-0 transition-opacity group-hover/btn:opacity-100" role="tooltip">Usuń</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                            @include('admin.partials.empty-state', ['icon' => 'fa-calendar-days', 'title' => 'Nie ma jeszcze wydarzeń', 'text' => 'Szkolenia i wydarzenia pokażą się w kalendarzu na stronie, z zapisami.', 'route' => 'admin.wydarzenia.create', 'action' => 'Dodaj wydarzenie', 'colspan' => 6])
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
