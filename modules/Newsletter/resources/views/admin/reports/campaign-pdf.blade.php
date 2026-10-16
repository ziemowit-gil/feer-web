<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8"><title>Raport kampanii</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1D1D1A; }
    h1 { font-size: 18px; margin: 0 0 2px; } h2 { font-size: 13px; margin: 16px 0 6px; }
    .muted { color: #4A4A47; } table { width: 100%; border-collapse: collapse; }
    th, td { padding: 5px 6px; border-bottom: 1px solid #D5D9E0; text-align: left; } th { background: #E8F0FF; } td.n, th.n { text-align: right; }
    .kpi td { border: 0; padding: 6px 12px 6px 0; } .kpi strong { font-size: 16px; display: block; }
</style></head>
<body>
@php $pct = fn ($v, $dec = 1) => $v === null ? '—' : number_format($v, $dec, ',', ' ') . ' %'; $int = fn ($v) => number_format((int) $v, 0, ',', ' '); @endphp
<h1>{{ $campaign->title }}</h1>
<p class="muted">Temat: {{ $campaign->subject }} · wysłano {{ $row['date']?->format('d.m.Y H:i') ?? '—' }} · {{ $site->site_name ?? config('app.name') }} · wygenerowano {{ $printedAt }}</p>

<table class="kpi"><tr>
    <td>Odbiorcy<strong>{{ $int($row['recipients']) }}</strong></td>
    <td>Dostarczone<strong>{{ $int($row['delivered']) }}</strong></td>
    <td>Otwarcia<strong>{{ $pct($row['open_rate']) }}</strong>{{ $int($row['opened']) }} unikalnych</td>
    <td>Kliknięcia<strong>{{ $pct($row['click_rate']) }}</strong>{{ $int($row['clicked']) }} unikalnych</td>
    <td>CTOR<strong>{{ $pct($row['ctor']) }}</strong></td>
    <td>Wypisy<strong>{{ $pct($row['unsub_rate'], 2) }}</strong>{{ $int($row['unsubscribed']) }} osób</td>
    <td>Odbicia<strong>{{ $pct($row['bounce_rate'], 2) }}</strong></td>
</tr></table>

<h2>Najczęściej klikane linki</h2>
<table>
    <thead><tr><th>#</th><th>Link</th><th class="n">Klikający</th><th class="n">Kliknięcia</th><th class="n">% klikających</th></tr></thead>
    <tbody>@forelse ($links as $i => $l)<tr><td>{{ $i + 1 }}</td><td>{{ $l->label ?: '(obraz)' }}<br><span class="muted">{{ \Illuminate\Support\Str::limit($l->url, 90) }}</span></td><td class="n">{{ $l->clicks_unique }}</td><td class="n">{{ $l->clicks_total }}</td><td class="n">{{ $row['clicked'] ? round($l->clicks_unique / $row['clicked'] * 100) . ' %' : '—' }}</td></tr>@empty<tr><td colspan="5">Brak kliknięć.</td></tr>@endforelse</tbody>
</table>
<p class="muted" style="margin-top:10px">Raport zawiera wyłącznie wskaźniki zbiorcze, bez danych osobowych odbiorców.</p>
</body></html>
