@extends('admin.layout')

@section('title', 'Dziennik zdarzeń')

@section('content')
    <p class="mb-4 text-sm text-muted">Kto, co i kiedy zmienił w treściach i kontach.</p>

    <form method="GET" action="{{ route('admin.dziennik.index') }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-3">
        <div>
            <label for="f-event" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Zdarzenie</label>
            <select id="f-event" name="event" onchange="this.form.submit()" class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                <option value="">Wszystkie</option>
                @foreach (\App\Models\ActivityLog::EVENTS as $key => $label)
                    <option value="{{ $key }}" @selected($event === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-subject" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Typ</label>
            <select id="f-subject" name="subject" onchange="this.form.submit()" class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                <option value="">Wszystkie</option>
                @foreach (\App\Models\ActivityLog::SUBJECTS as $key => $label)
                    <option value="{{ $key }}" @selected($subject === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if ($event || $subject)
            <a href="{{ route('admin.dziennik.index') }}" class="rounded px-2 py-1.5 text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Wyczyść</a>
        @endif
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Kiedy</th>
                    <th class="px-4 py-3">Kto</th>
                    <th class="px-4 py-3">Zdarzenie</th>
                    <th class="px-4 py-3">Typ</th>
                    <th class="px-4 py-3">Element</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 font-medium text-ink">{{ $log->user_name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @php $badge = match ($log->event) {
                                'created' => 'bg-green-100 text-green-700',
                                'updated' => 'bg-amber-100 text-amber-800',
                                'deleted' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700',
                            }; @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $badge }}">{{ $log->eventLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $log->subjectLabel() }}</td>
                        <td class="px-4 py-3 text-ink">{{ $log->subject_label ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted">Brak zapisanych zdarzeń.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
