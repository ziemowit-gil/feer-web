@extends('admin.layout')

@section('title', 'Kampanie zbiórkowe')

@section('content')
    <form id="bulk-form" method="POST" action="{{ route('admin.kampanie.bulk') }}">
        @csrf
        <input type="hidden" name="action" id="bulk-action">

        <div class="mb-4 flex items-center justify-between gap-3">
            <div id="bulk-bar" class="hidden items-center gap-3 rounded-md bg-brand-light px-4 py-2">
                <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
                <button type="button" onclick="bulkSubmit('publish')" class="rounded-md px-3 py-1 text-xs font-bold text-ink bg-white hover:bg-brand-light">Opublikuj</button>
                <button type="button" onclick="bulkSubmit('unpublish')" class="rounded-md px-3 py-1 text-xs font-bold text-gray-700 bg-white hover:bg-brand-light">Cofnij publikację</button>
                @if ($status === 'trashed')
                    <button type="button" onclick="bulkSubmit('restore')" class="rounded-md px-3 py-1 text-xs font-bold text-ink bg-white hover:bg-brand-light">Przywróć</button>
                @else
                    <button type="button" onclick="if(confirm('Przenieść zaznaczone do kosza?')) bulkSubmit('trash')" class="rounded-md px-3 py-1 text-xs font-bold text-red-800 bg-red-50 hover:bg-red-100">Do kosza</button>
                @endif
            </div>

            <div class="ml-auto flex items-center gap-3">
                <div class="flex gap-1">
                    @foreach ([''=>'Wszystkie','published'=>'Opublikowane','draft'=>'Szkice','trashed'=>'Kosz'] as $val => $lbl)
                        <a href="{{ route('admin.kampanie.index', $val ? ['status'=>$val] : []) }}"
                            class="rounded px-3 py-1.5 text-xs font-bold {{ $status === $val ? 'bg-brand-dark text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            {{ $lbl }}
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('admin.kampanie.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kampanię
                </a>
            </div>
        </div>

        @if (session('status'))
            <div role="alert" class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-md bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-500" aria-label="Zaznacz wszystkie">
                        </th>
                        <th class="px-4 py-3">Tytuł</th>
                        <th class="px-4 py-3">Cel</th>
                        <th class="px-4 py-3">Zebrano</th>
                        <th class="px-4 py-3">Postęp</th>
                        <th class="px-4 py-3">Czas trwania</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($campaigns as $campaign)
                        <tr class="{{ $campaign->trashed() ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $campaign->id }}" class="row-check rounded border-gray-500" aria-label="Zaznacz {{ $campaign->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $campaign->title }}</td>
                            <td class="px-4 py-3 text-muted">{{ number_format($campaign->goal_amount, 0, ',', ' ') }} zł</td>
                            <td class="px-4 py-3 font-bold text-brand-dark">{{ number_format($campaign->collected_amount, 0, ',', ' ') }} zł</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200" role="progressbar"
                                        aria-valuenow="{{ $campaign->progressPercent() }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="h-full rounded-full {{ $campaign->isGoalReached() ? 'bg-green-500' : 'bg-brand-dark' }}"
                                            style="width: {{ $campaign->progressPercent() }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold {{ $campaign->isGoalReached() ? 'text-green-700' : 'text-muted' }}">{{ $campaign->progressPercent() }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted">
                                @if ($campaign->starts_at || $campaign->ends_at)
                                    {{ $campaign->starts_at?->format('d.m.Y') ?? '∞' }} – {{ $campaign->ends_at?->format('d.m.Y') ?? '∞' }}
                                @else
                                    bezterminowo
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($campaign->trashed())
                                    <span class="text-xs font-bold text-ink"><i class="fa-solid fa-trash-can mr-1 text-red-700" aria-hidden="true"></i>Kosz</span>
                                @elseif ($campaign->is_published)
                                    <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Opublikowana</span>
                                @else
                                    <span class="text-xs font-bold text-muted">Szkic</span>
                                @endif
                                @if ($campaign->isGoalReached() && ! $campaign->trashed())
                                    <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Cel osiągnięty</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    @if (! $campaign->trashed())
                                        <a href="{{ route('kampanie.show', $campaign->slug) }}" target="_blank" class="text-muted hover:text-brand-dark" title="Podgląd"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                        <a href="{{ route('admin.kampanie.edit', $campaign) }}" class="text-muted hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                        <form method="POST" action="{{ route('admin.kampanie.destroy', $campaign) }}" onsubmit="return confirm('Przenieść kampanię do kosza?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń {{ $campaign->title }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-muted">Brak kampanii.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <script>
        document.getElementById('select-all')?.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
            updateBulkBar();
        });
        document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', updateBulkBar));
        function updateBulkBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            const bar = document.getElementById('bulk-bar');
            document.getElementById('bulk-count').textContent = checked.length + ' zaznaczonych';
            bar.classList.toggle('hidden', checked.length === 0);
            bar.classList.toggle('flex', checked.length > 0);
        }
        function bulkSubmit(action) {
            document.getElementById('bulk-action').value = action;
            document.getElementById('bulk-form').submit();
        }
    </script>
@endsection
