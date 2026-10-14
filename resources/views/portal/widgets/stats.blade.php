{{-- Widżet portalu: liczby (płasko, duże cyfry w kolorze tekstu); same zera się nie pokazują. --}}
@if (array_sum($stats) > 0)
<section aria-labelledby="portal-w-stats">
    <h2 id="portal-w-stats" class="sr-only">{{ $category->portalWidgetTitle('stats') }}</h2>
    <dl class="grid grid-cols-3 gap-4 rounded-md bg-gray-50 p-6 text-center">
        <div class="flex flex-col-reverse"><dt class="text-sm text-ink">Działania</dt><dd class="text-3xl font-bold text-ink">{{ $stats['projects'] }}</dd></div>
        <div class="flex flex-col-reverse"><dt class="text-sm text-ink">Aktualności</dt><dd class="text-3xl font-bold text-ink">{{ $stats['news'] }}</dd></div>
        <div class="flex flex-col-reverse"><dt class="text-sm text-ink">Nadchodzące wydarzenia</dt><dd class="text-3xl font-bold text-ink">{{ $stats['events'] }}</dd></div>
    </dl>
</section>
@endif
