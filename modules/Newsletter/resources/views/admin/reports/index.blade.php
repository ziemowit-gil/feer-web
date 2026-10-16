@extends('admin.layout')
@section('title', 'Newsletter — porównanie kampanii')
@section('content')
    @php
        $pct = fn ($v, $dec = 1) => $v === null ? '—' : number_format($v, $dec, ',', ' ') . ' %';
        $int = fn ($v) => number_format((int) $v, 0, ',', ' ');
        $maxRate = max(1, (float) $monthly->max('open_rate'));
    @endphp
    <p class="mb-3 text-sm text-muted"><a href="{{ route('admin.newsletter.dashboard') }}" class="hover:underline">← Pulpit newslettera</a></p>

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3 rounded-md bg-white p-4">
        <div><label for="okres" class="mb-1 block text-sm font-bold">Okres</label>
            <select id="okres" name="okres" class="rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">@foreach ($periods as $k => $l)<option value="{{ $k }}" @selected($period === (string) $k)>{{ $l }}</option>@endforeach</select></div>
        <button class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink">Pokaż</button>
        <div class="ml-auto flex flex-wrap gap-2">
            <a href="{{ route('admin.newsletter.raporty.csv', ['okres' => $period]) }}" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-brand-light"><i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksport CSV</a>
            <a href="{{ route('admin.newsletter.raporty.pdf', ['okres' => $period]) }}" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-brand-light"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Eksport PDF</a>
        </div>
        <p class="w-full text-xs text-muted">Eksport zawiera wyłącznie wskaźniki zbiorcze, bez adresów odbiorców — nadaje się do sprawozdań dla grantodawców.</p>
    </form>

    <h2 class="mb-3 text-lg font-bold">{{ $label }}</h2>
    <dl class="mb-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-6" role="group" aria-label="Wskaźniki zbiorcze">
        @foreach ([
            ['Kampanie', $int($totals['campaigns']), 'dostarczone: ' . $int($totals['delivered'])],
            ['Otwarcia', $pct($totals['open_rate']), $int($totals['opened']) . ' unikalnych'],
            ['Kliknięcia', $pct($totals['click_rate']), $int($totals['clicked']) . ' unikalnych'],
            ['CTOR', $pct($totals['ctor']), 'klikający / otwierający'],
            ['Wypisy', $pct($totals['unsub_rate'], 2), $int($totals['unsubscribed']) . ' osób'],
            ['Subskrybenci', $int($growth['active_now']), '+' . $int($growth['new']) . ' / −' . $int($growth['unsubscribed']) . ' w okresie'],
        ] as [$l, $v, $sub])
            <div class="rounded-md bg-white p-3"><dt class="text-xs font-bold uppercase text-muted">{{ $l }}</dt><dd class="text-2xl font-extrabold text-ink">{{ $v }}</dd><dd class="text-xs text-muted">{{ $sub }}</dd></div>
        @endforeach
    </dl>

    @if ($monthly->isNotEmpty())
    <section class="mb-6 rounded-md bg-white p-4" aria-labelledby="trend-h">
        <h2 id="trend-h" class="mb-1 text-sm font-bold uppercase text-muted">Trend miesięczny</h2>
        <p class="mb-3 flex flex-wrap gap-4 text-xs text-ink" aria-hidden="true">
            <span><span class="mr-1 inline-block h-3 w-3 rounded-sm align-middle" style="background:#1752BF"></span>otwarcia</span>
            <span><span class="mr-1 inline-block h-3 w-3 rounded-sm align-middle" style="background:#B86F00"></span>kliknięcia</span>
        </p>
        <div class="flex items-end gap-3 overflow-x-auto pb-2" aria-hidden="true">
            @foreach ($monthly as $m)
                <div class="flex min-w-[4.5rem] flex-col items-center">
                    <div class="flex h-40 items-end gap-1">
                        <div class="flex flex-col items-center justify-end"><span class="text-[11px] font-bold text-ink">{{ $m['open_rate'] !== null ? round($m['open_rate']) . '%' : '' }}</span><div class="w-5 rounded-t" style="height: {{ max(2, ($m['open_rate'] ?? 0) / $maxRate * 128) }}px; background:#1752BF"></div></div>
                        <div class="flex flex-col items-center justify-end"><span class="text-[11px] font-bold text-ink">{{ $m['click_rate'] !== null ? round($m['click_rate']) . '%' : '' }}</span><div class="w-5 rounded-t" style="height: {{ max(2, ($m['click_rate'] ?? 0) / $maxRate * 128) }}px; background:#B86F00"></div></div>
                    </div>
                    <span class="mt-1 text-xs text-ink">{{ $m['month']->translatedFormat('M Y') }}</span>
                </div>
            @endforeach
        </div>
        <table class="mt-4 w-full text-left text-sm">
            <caption class="sr-only">Wskaźniki newslettera w kolejnych miesiącach</caption>
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted"><tr><th scope="col" class="px-3 py-2">Miesiąc</th><th scope="col" class="px-3 py-2 text-right">Kampanie</th><th scope="col" class="px-3 py-2 text-right">Dostarczone</th><th scope="col" class="px-3 py-2 text-right">Otwarcia</th><th scope="col" class="px-3 py-2 text-right">Kliknięcia</th><th scope="col" class="px-3 py-2 text-right">Wypisy</th></tr></thead>
            <tbody>
            @foreach ($monthly as $m)
                <tr class="border-t border-gray-100"><th scope="row" class="px-3 py-2 font-normal">{{ $m['month']->translatedFormat('F Y') }}</th><td class="px-3 py-2 text-right">{{ $m['campaigns'] }}</td><td class="px-3 py-2 text-right">{{ $int($m['delivered']) }}</td><td class="px-3 py-2 text-right">{{ $pct($m['open_rate']) }}</td><td class="px-3 py-2 text-right">{{ $pct($m['click_rate']) }}</td><td class="px-3 py-2 text-right">{{ $pct($m['unsub_rate'], 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </section>
    @endif

    <section class="overflow-x-auto rounded-md bg-white" aria-labelledby="camp-h">
        <h2 id="camp-h" class="px-4 pt-4 text-sm font-bold uppercase text-muted">Kampanie w okresie</h2>
        <table class="mt-2 w-full text-left text-sm">
            <caption class="sr-only">Porównanie kampanii: otwarcia, kliknięcia, wypisy i odbicia</caption>
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted"><tr><th scope="col" class="px-3 py-2">Kampania</th><th scope="col" class="px-3 py-2">Wysłano</th><th scope="col" class="px-3 py-2 text-right">Dostarczone</th><th scope="col" class="px-3 py-2 text-right">Otwarcia</th><th scope="col" class="px-3 py-2 text-right">Kliknięcia</th><th scope="col" class="px-3 py-2 text-right">CTOR</th><th scope="col" class="px-3 py-2 text-right">Wypisy</th><th scope="col" class="px-3 py-2 text-right">Odbicia</th></tr></thead>
            <tbody>
            @forelse ($rows->sortByDesc('date') as $r)
                <tr class="border-t border-gray-100">
                    <th scope="row" class="px-3 py-2 font-normal"><a href="{{ route('admin.newsletter.kampanie.report', $r['id']) }}" class="font-bold text-brand-dark underline">{{ $r['title'] }}</a><br><span class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($r['subject'], 80) }}</span></th>
                    <td class="whitespace-nowrap px-3 py-2">{{ $r['date']?->format('d.m.Y') }}</td>
                    <td class="px-3 py-2 text-right">{{ $int($r['delivered']) }}</td>
                    <td class="px-3 py-2 text-right">{{ $pct($r['open_rate']) }}</td>
                    <td class="px-3 py-2 text-right">{{ $pct($r['click_rate']) }}</td>
                    <td class="px-3 py-2 text-right">{{ $pct($r['ctor']) }}</td>
                    <td class="px-3 py-2 text-right">{{ $pct($r['unsub_rate'], 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ $pct($r['bounce_rate'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-3 py-6 text-center text-muted">Brak wysłanych kampanii w tym okresie.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="px-4 py-3 text-xs text-muted">Otwarcia liczone od dostarczonych; część programów pocztowych (np. Apple Mail) otwiera wiadomości automatycznie, więc ten wskaźnik bywa zawyżony — kliknięcia są pewniejsze.</p>
    </section>
@endsection
