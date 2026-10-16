<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8"><title>Newsletter — zestawienie</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1D1D1A; }
    h1 { font-size: 18px; margin: 0 0 2px; } h2 { font-size: 13px; margin: 16px 0 6px; }
    .muted { color: #4A4A47; } table { width: 100%; border-collapse: collapse; }
    th, td { padding: 4px 6px; border-bottom: 1px solid #D5D9E0; text-align: left; } th { background: #E8F0FF; }
    td.n, th.n { text-align: right; } .kpi td { border: 0; padding: 6px 10px 6px 0; } .kpi strong { font-size: 15px; display: block; }
    .bar { display: inline-block; height: 8px; background: #1752BF; vertical-align: middle; } .bar.c { background: #B86F00; }
    tfoot td { font-weight: bold; border-top: 2px solid #1D1D1A; }
</style></head>
<body>
@php $pct = fn ($v, $dec = 1) => $v === null ? '—' : number_format($v, $dec, ',', ' ') . ' %'; $int = fn ($v) => number_format((int) $v, 0, ',', ' '); @endphp
<h1>Newsletter — zestawienie wyników</h1>
<p class="muted">{{ $site->site_name ?? config('app.name') }} · {{ $label }} · wygenerowano {{ $printedAt }}</p>

<table class="kpi"><tr>
    <td>Kampanie<strong>{{ $int($totals['campaigns']) }}</strong></td>
    <td>Dostarczone wiadomości<strong>{{ $int($totals['delivered']) }}</strong></td>
    <td>Otwarcia<strong>{{ $pct($totals['open_rate']) }}</strong></td>
    <td>Kliknięcia<strong>{{ $pct($totals['click_rate']) }}</strong></td>
    <td>Wypisy<strong>{{ $pct($totals['unsub_rate'], 2) }}</strong></td>
    <td>Aktywni subskrybenci<strong>{{ $int($growth['active_now']) }}</strong></td>
    <td>Nowi / wypisani w okresie<strong>+{{ $int($growth['new']) }} / −{{ $int($growth['unsubscribed']) }}</strong></td>
</tr></table>

@if ($monthly->isNotEmpty())
<h2>Trend miesięczny</h2>
@php $max = max(1, (float) $monthly->max('open_rate')); @endphp
<table>
    <thead><tr><th>Miesiąc</th><th class="n">Kampanie</th><th class="n">Dostarczone</th><th>Otwarcia</th><th>Kliknięcia</th><th class="n">Wypisy</th></tr></thead>
    <tbody>@foreach ($monthly as $m)<tr><td>{{ $m['month']->translatedFormat('F Y') }}</td><td class="n">{{ $m['campaigns'] }}</td><td class="n">{{ $int($m['delivered']) }}</td>
        <td><span class="bar" style="width: {{ round(($m['open_rate'] ?? 0) / $max * 120) }}px"></span> {{ $pct($m['open_rate']) }}</td>
        <td><span class="bar c" style="width: {{ round(($m['click_rate'] ?? 0) / $max * 120) }}px"></span> {{ $pct($m['click_rate']) }}</td>
        <td class="n">{{ $pct($m['unsub_rate'], 2) }}</td></tr>@endforeach</tbody>
</table>
@endif

<h2>Kampanie</h2>
<table>
    <thead><tr><th>Kampania</th><th>Data</th><th class="n">Odbiorcy</th><th class="n">Dostarczone</th><th class="n">Otwarcia</th><th class="n">Kliknięcia</th><th class="n">CTOR</th><th class="n">Wypisy</th><th class="n">Odbicia</th></tr></thead>
    <tbody>@forelse ($rows as $r)<tr><td>{{ $r['title'] }}</td><td>{{ $r['date']?->format('d.m.Y') }}</td><td class="n">{{ $int($r['recipients']) }}</td><td class="n">{{ $int($r['delivered']) }}</td><td class="n">{{ $pct($r['open_rate']) }}</td><td class="n">{{ $pct($r['click_rate']) }}</td><td class="n">{{ $pct($r['ctor']) }}</td><td class="n">{{ $pct($r['unsub_rate'], 2) }}</td><td class="n">{{ $pct($r['bounce_rate'], 2) }}</td></tr>@empty<tr><td colspan="9">Brak wysłanych kampanii w tym okresie.</td></tr>@endforelse</tbody>
    @if ($rows->isNotEmpty())<tfoot><tr><td colspan="2">Razem / średnio</td><td class="n">{{ $int($totals['recipients']) }}</td><td class="n">{{ $int($totals['delivered']) }}</td><td class="n">{{ $pct($totals['open_rate']) }}</td><td class="n">{{ $pct($totals['click_rate']) }}</td><td class="n">{{ $pct($totals['ctor']) }}</td><td class="n">{{ $pct($totals['unsub_rate'], 2) }}</td><td class="n">{{ $pct($totals['bounce_rate'], 2) }}</td></tr></tfoot>@endif
</table>
<p class="muted" style="margin-top:10px">Wskaźniki zbiorcze, bez danych osobowych odbiorców. Otwarcia liczone od wiadomości dostarczonych; mogą być zawyżone przez automatyczne pobieranie obrazków w niektórych programach pocztowych.</p>
</body></html>
