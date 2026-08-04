{{-- Reużywalny render harmonogramu strony typu "schedule".
     Parametry: $page (wymagany), $showHeading (opcjonalnie, domyślnie true). --}}
@php
    $scheduleItems = collect($page->schedule_items ?? [])
        ->filter(fn ($i) => ! empty($i['date']) || ! empty($i['time']) || ! empty($i['location']) || ! empty($i['note']));
    $formatDate = function ($d) {
        if (! $d) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($d)->format('d.m.Y');
        } catch (\Throwable $e) {
            return $d;
        }
    };
    $showHeading = $showHeading ?? true;
    $headingId = 'harmonogram-'.$page->id;
@endphp

@if ($page->schedule_pending)
    <div class="mt-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-5" role="note" aria-label="Status harmonogramu">
        <i class="fa-solid fa-clock mt-0.5 text-amber-700" aria-hidden="true"></i>
        <div>
            <p class="font-bold text-amber-900">Harmonogram jeszcze nie został opublikowany</p>
            <p class="text-sm text-amber-900">Pracujemy nad ustaleniem terminów — zapraszamy wkrótce.</p>
        </div>
    </div>
@else
    @if ($page->schedule_change_notice)
        <div class="mt-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4" role="note" aria-label="Informacja o zmianie w harmonogramie">
            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-700" aria-hidden="true"></i>
            <div>
                <p class="font-bold text-amber-900">Zmiana w harmonogramie</p>
                <p class="text-sm text-amber-900">{!! nl2br(e($page->schedule_change_notice)) !!}</p>
            </div>
        </div>
    @endif

    @if ($scheduleItems->isNotEmpty())
        @if ($showHeading)
            <h2 id="{{ $headingId }}" class="mb-4 mt-8 flex items-center gap-2 text-xl font-bold text-ink">
                <i class="fa-solid fa-calendar-days text-brand" aria-hidden="true"></i> Harmonogram
            </h2>
        @else
            <span id="{{ $headingId }}" class="sr-only">Harmonogram</span>
        @endif
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-sm" aria-labelledby="{{ $headingId }}">
                <caption class="sr-only">Harmonogram — data, godzina, miejsce i uwagi. Terminy oznaczone „zmienione" uległy zmianie.</caption>
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3">Data</th>
                        <th scope="col" class="px-4 py-3">Godzina</th>
                        <th scope="col" class="px-4 py-3">Miejsce</th>
                        <th scope="col" class="px-4 py-3">Uwagi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($scheduleItems as $item)
                        <tr class="{{ ! empty($item['changed']) ? 'bg-amber-50' : '' }}">
                            <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-medium text-ink">
                                {{ $formatDate($item['date'] ?? null) ?? '—' }}
                                @if (! empty($item['changed']))
                                    <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-900">
                                        <i class="fa-solid fa-rotate" aria-hidden="true"></i> Zmienione
                                    </span>
                                @endif
                            </th>
                            <td class="whitespace-nowrap px-4 py-3 text-ink">{{ $item['time'] ?? '' ?: '—' }}</td>
                            <td class="px-4 py-3 text-ink">{{ $item['location'] ?? '' ?: '—' }}</td>
                            <td class="px-4 py-3 text-muted">{{ $item['note'] ?? '' ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
