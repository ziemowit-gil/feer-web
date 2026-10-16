{{-- Ostrzeżenie o kolejce: $queueHealth = QueueHealth::snapshot(). Widoczne tylko przy problemie. --}}
@if (! empty($queueHealth) && ! $queueHealth['ok'])
    <div role="alert" class="mb-6 rounded-md bg-amber-50 p-4 text-ink">
        <p class="mb-1 font-bold"><i class="fa-solid fa-triangle-exclamation text-amber-800" aria-hidden="true"></i> Wysyłki mogą stać — kolejka zadań wymaga uwagi</p>
        <ul class="list-disc space-y-0.5 pl-6 text-sm">
            @foreach ($queueHealth['problems'] as $problem)<li>{{ $problem }}</li>@endforeach
        </ul>
        <p class="mt-2 text-sm">Administratorzy dostają o tym mail (najwyżej raz na godzinę). Sprawdź, czy cron uruchamia co minutę <code>php84 artisan schedule:run</code>.</p>
    </div>
@endif
