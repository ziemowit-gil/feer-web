@extends('admin.layout')

@section('title', 'Dziennik zdarzeń')

@section('content')
    <div class="space-y-6">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-ink">Dziennik zdarzeń</h1>
                <p class="mt-0.5 text-sm text-ink">Kto, co i kiedy zmienił w treściach i kontach — najnowsze na górze.</p>
            </div>
            <span class="text-sm text-muted">{{ $logs->total() }} {{ $logs->total() === 1 ? 'zdarzenie' : ($logs->total() < 5 ? 'zdarzenia' : 'zdarzeń') }}</span>
        </div>

        {{-- Filtry --}}
        <form method="GET" action="{{ route('admin.dziennik.index') }}"
            class="p-4 rounded-md bg-white">
            <fieldset>
                <legend class="sr-only">Filtry dziennika zdarzeń</legend>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

                    <div>
                        <label for="f-event" class="mb-1 block text-sm font-bold text-ink">Zdarzenie</label>
                        <select id="f-event" name="event"
                            class="w-full rounded-lg border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <option value="">Wszystkie</option>
                            @foreach (\App\Models\Activity::EVENTS as $key => $label)
                                <option value="{{ $key }}" @selected($event === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f-subject" class="mb-1 block text-sm font-bold text-ink">Typ treści</label>
                        <select id="f-subject" name="subject"
                            class="w-full rounded-lg border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <option value="">Wszystkie</option>
                            @foreach (\App\Models\Activity::SUBJECTS as $key => $label)
                                <option value="{{ $key }}" @selected($subject === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f-user" class="mb-1 block text-sm font-bold text-ink">Użytkownik</label>
                        <input type="text" id="f-user" name="user" value="{{ $userName }}"
                            list="user-datalist" placeholder="np. Jan Kowalski"
                            class="w-full rounded-lg border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        <datalist id="user-datalist">
                            @foreach ($users as $u)<option value="{{ $u }}">@endforeach
                        </datalist>
                    </div>

                    <div>
                        <label for="f-date-from" class="mb-1 block text-sm font-bold text-ink">Data od</label>
                        <input type="date" id="f-date-from" name="date_from" value="{{ $dateFrom }}"
                            class="w-full rounded-lg border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    </div>

                    <div>
                        <label for="f-date-to" class="mb-1 block text-sm font-bold text-ink">Data do</label>
                        <input type="date" id="f-date-to" name="date_to" value="{{ $dateTo }}"
                            class="w-full rounded-lg border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex min-h-11 items-center rounded-lg bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        Filtruj
                    </button>
                    @if ($event || $subject || $userName || $dateFrom || $dateTo)
                        <a href="{{ route('admin.dziennik.index') }}"
                            class="inline-flex min-h-11 items-center text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline">Wyczyść filtry</a>
                    @endif
                </div>
            </fieldset>
        </form>

        {{-- Oś czasu pogrupowana po dniach: godzina, ikona rodzaju zdarzenia, kto, co i na czym.
             Kontrast: ink/muted na bieli, ikony brand-dark (dekoracyjne — rodzaj zdarzenia jest też słowem). --}}
        @php
            $eventIcons = [
                'created' => 'fa-plus', 'updated' => 'fa-pen', 'deleted' => 'fa-trash', 'force_deleted' => 'fa-trash-can',
                'restored' => 'fa-rotate-left', 'approved' => 'fa-check', 'rejected' => 'fa-xmark', 'imported' => 'fa-file-import',
                'bulk_published' => 'fa-bullhorn', 'bulk_unpublished' => 'fa-eye-slash', 'bulk_deleted' => 'fa-trash',
            ];
            $dayLabel = function ($date) {
                if ($date->isToday()) return 'Dziś';
                if ($date->isYesterday()) return 'Wczoraj';
                return $date->locale('pl')->isoFormat('D MMMM YYYY');
            };
            $days = $logs->getCollection()->groupBy(fn ($log) => $log->created_at->format('Y-m-d'));
        @endphp

        @if ($logs->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-md bg-white py-16 text-center">
                <i class="fa-solid fa-clock-rotate-left text-4xl text-muted" aria-hidden="true"></i>
                <p class="text-sm text-ink">Brak zdarzeń spełniających kryteria.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($days as $day => $entries)
                    <section aria-labelledby="day-{{ $day }}" class="overflow-hidden rounded-md bg-white">
                        <h2 id="day-{{ $day }}" class="flex items-baseline justify-between gap-3 bg-gray-50 px-5 py-3 text-base font-bold text-ink">
                            <span>{{ $dayLabel($entries->first()->created_at) }}</span>
                            <span class="text-sm font-normal text-muted">{{ $entries->count() }} {{ $entries->count() === 1 ? 'zdarzenie' : ($entries->count() % 10 >= 2 && $entries->count() % 10 <= 4 && ($entries->count() % 100 < 12 || $entries->count() % 100 > 14) ? 'zdarzenia' : 'zdarzeń') }}</span>
                        </h2>
                        <ol role="list" class="divide-y divide-gray-100">
                            @foreach ($entries as $log)
                                <li class="flex items-start gap-4 px-5 py-3">
                                    <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d.m.Y H:i:s') }}"
                                          class="w-12 flex-none pt-1 text-sm tabular-nums text-muted">{{ $log->created_at->format('H:i') }}</time>
                                    <span class="mt-0.5 inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-light text-sm text-brand-dark" aria-hidden="true">
                                        <i class="fa-solid {{ $eventIcons[$log->event] ?? 'fa-circle-dot' }}"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-ink">
                                            <strong class="font-bold">{{ $log->user_name ?: 'System' }}</strong>
                                            <span class="text-muted">·</span>
                                            <span class="font-bold">{{ $log->eventLabel() }}</span>
                                            <span class="text-muted">·</span>
                                            {{ $log->subjectLabel() }}
                                        </p>
                                        @if ($log->subject_label)
                                            <p class="mt-0.5 break-words text-sm text-ink">„{{ $log->subject_label }}”</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>
            @if ($logs->hasPages())
                <div class="mt-4">{{ $logs->links() }}</div>
            @endif
        @endif

    </div>
@endsection
