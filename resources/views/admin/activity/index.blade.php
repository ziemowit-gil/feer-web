@extends('admin.layout')

@section('title', 'Dziennik zdarzeń')

@section('content')
    <div class="space-y-6">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-ink">Dziennik zdarzeń</h1>
                <p class="mt-0.5 text-sm text-muted">Kto, co i kiedy zmienił w treściach i kontach.</p>
            </div>
            <span class="text-sm text-muted">{{ $logs->total() }} {{ $logs->total() === 1 ? 'zdarzenie' : ($logs->total() < 5 ? 'zdarzenia' : 'zdarzeń') }}</span>
        </div>

        {{-- Filtry --}}
        <form method="GET" action="{{ route('admin.dziennik.index') }}"
            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <fieldset>
                <legend class="sr-only">Filtry dziennika zdarzeń</legend>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

                    <div>
                        <label for="f-event" class="mb-1 block text-xs font-bold text-muted">Zdarzenie</label>
                        <select id="f-event" name="event"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <option value="">Wszystkie</option>
                            @foreach (\App\Models\Activity::EVENTS as $key => $label)
                                <option value="{{ $key }}" @selected($event === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f-subject" class="mb-1 block text-xs font-bold text-muted">Typ treści</label>
                        <select id="f-subject" name="subject"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <option value="">Wszystkie</option>
                            @foreach (\App\Models\Activity::SUBJECTS as $key => $label)
                                <option value="{{ $key }}" @selected($subject === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f-user" class="mb-1 block text-xs font-bold text-muted">Użytkownik</label>
                        <input type="text" id="f-user" name="user" value="{{ $userName }}"
                            list="user-datalist" placeholder="np. Jan Kowalski"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <datalist id="user-datalist">
                            @foreach ($users as $u)<option value="{{ $u }}">@endforeach
                        </datalist>
                    </div>

                    <div>
                        <label for="f-date-from" class="mb-1 block text-xs font-bold text-muted">Data od</label>
                        <input type="date" id="f-date-from" name="date_from" value="{{ $dateFrom }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>

                    <div>
                        <label for="f-date-to" class="mb-1 block text-xs font-bold text-muted">Data do</label>
                        <input type="date" id="f-date-to" name="date_to" value="{{ $dateTo }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <button type="submit"
                        class="rounded-lg bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        Filtruj
                    </button>
                    @if ($event || $subject || $userName || $dateFrom || $dateTo)
                        <a href="{{ route('admin.dziennik.index') }}"
                            class="text-xs font-bold text-muted hover:text-ink">Wyczyść filtry</a>
                    @endif
                </div>
            </fieldset>
        </form>

        {{-- Tabela --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @if ($logs->isEmpty())
                <div class="flex flex-col items-center gap-3 py-16 text-center">
                    <i class="fa-solid fa-clock-rotate-left text-4xl text-gray-300" aria-hidden="true"></i>
                    <p class="text-sm text-muted">Brak zdarzeń spełniających kryteria.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-[10px] font-bold uppercase tracking-widest text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left">Kiedy</th>
                                <th scope="col" class="px-4 py-3 text-left">Zdarzenie</th>
                                <th scope="col" class="px-4 py-3 text-left">Treść</th>
                                <th scope="col" class="px-4 py-3 text-left">Kto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($logs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-muted">
                                        <time datetime="{{ $log->created_at->toIso8601String() }}"
                                            title="{{ $log->created_at->format('d.m.Y H:i:s') }}">
                                            {{ $log->created_at->format('d.m.Y') }}
                                            <span class="block text-xs">{{ $log->created_at->format('H:i') }}</span>
                                        </time>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @php $badge = match ($log->event) {
                                            'created' => 'bg-green-100 text-green-700',
                                            'updated' => 'bg-blue-100 text-blue-700',
                                            'deleted' => 'bg-red-100 text-red-700',
                                            default   => 'bg-gray-100 text-gray-700',
                                        }; @endphp
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge }}">
                                            {{ $log->eventLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-bold text-muted">{{ $log->subjectLabel() }}</span>
                                        @if ($log->subject_label)
                                            <p class="max-w-xs truncate text-ink" title="{{ $log->subject_label }}">{{ $log->subject_label }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($log->user_name)
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex h-7 w-7 flex-none items-center justify-center rounded-full bg-brand-light text-xs font-bold text-brand"
                                                    aria-hidden="true">
                                                    {{ mb_strtoupper(mb_substr($log->user_name, 0, 1)) }}
                                                </span>
                                                <span>{{ $log->user_name }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
