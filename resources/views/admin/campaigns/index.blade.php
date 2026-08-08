@extends('admin.layout')

@section('title', 'Kampanie zbiórkowe')

@section('content')
    <form id="bulk-form" method="POST" action="{{ route('admin.kampanie.bulk') }}">
        @csrf
        <input type="hidden" name="action" id="bulk-action">

        <div class="mb-4 flex items-center justify-between gap-3">
            <div id="bulk-bar" class="hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
                <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
                <button type="button" onclick="bulkSubmit('publish')" class="rounded border border-green-300 bg-white px-3 py-1 text-xs font-bold text-green-700 hover:bg-green-50">Opublikuj</button>
                <button type="button" onclick="bulkSubmit('unpublish')" class="rounded border border-gray-300 bg-white px-3 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50">Cofnij publikację</button>
                @if ($status === 'trashed')
                    <button type="button" onclick="bulkSubmit('restore')" class="rounded border border-blue-300 bg-white px-3 py-1 text-xs font-bold text-blue-700 hover:bg-blue-50">Przywróć</button>
                @else
                    <button type="button" onclick="if(confirm('Przenieść zaznaczone do kosza?')) bulkSubmit('trash')" class="rounded border border-red-300 bg-white px-3 py-1 text-xs font-bold text-red-600 hover:bg-red-50">Do kosza</button>
                @endif
            </div>

            <div class="ml-auto flex items-center gap-3">
                <div class="flex gap-1">
                    @foreach ([''=>'Wszystkie','published'=>'Opublikowane','draft'=>'Szkice','trashed'=>'Kosz'] as $val => $lbl)
                        <a href="{{ route('admin.kampanie.index', $val ? ['status'=>$val] : []) }}"
                            class="rounded px-3 py-1.5 text-xs font-bold {{ $status === $val ? 'bg-brand text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            {{ $lbl }}
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('admin.kampanie.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kampanię
                </a>
            </div>
        </div>

        @if (session('status'))
            <div role="alert" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie">
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
                                <input type="checkbox" name="ids[]" value="{{ $campaign->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $campaign->title }}">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $campaign->title }}</td>
                            <td class="px-4 py-3 text-muted">{{ number_format($campaign->goal_amount, 0, ',', ' ') }} zł</td>
                            <td class="px-4 py-3 font-bold text-brand">{{ number_format($campaign->collected_amount, 0, ',', ' ') }} zł</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200" role="progressbar"
                                        aria-valuenow="{{ $campaign->progressPercent() }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="h-full rounded-full {{ $campaign->isGoalReached() ? 'bg-green-500' : 'bg-brand' }}"
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
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Kosz</span>
                                @elseif ($campaign->is_published)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowana</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                                @endif
                                @if ($campaign->isGoalReached() && ! $campaign->trashed())
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Cel osiągnięty</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    @if (! $campaign->trashed())
                                        <a href="{{ route('kampanie.show', $campaign->slug) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                        <a href="{{ route('admin.kampanie.edit', $campaign) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
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
