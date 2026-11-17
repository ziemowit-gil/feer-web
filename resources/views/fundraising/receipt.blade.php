<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>{{ $texts->get('pdf_title', $vars) }} — {{ $siteSettings->site_name }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 15pt; margin: 0 0 2mm; }
        .org { margin: 0 0 8mm; color: #333; font-size: 9pt; }
        .body { margin: 0 0 6mm; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin: 0 0 8mm; }
        th { width: 38%; text-align: left; vertical-align: top; font-size: 9pt; color: #444; padding: 2.5mm 3mm 2.5mm 0; border-bottom: 0.3mm solid #ccc; }
        td { padding: 2.5mm 0; font-size: 10.5pt; border-bottom: 0.3mm solid #ccc; }
        .footer { margin-top: 10mm; font-size: 9pt; color: #333; }
    </style>
</head>
<body>
    <h1>{{ $texts->get('pdf_title', $vars) }}</h1>
    <p class="org">
        {{ $siteSettings->site_name }}
        @if ($siteSettings->contact_address)<br>{{ $siteSettings->contact_address }}, {{ $siteSettings->contact_city }}@endif
        @if ($siteSettings->krs_number)<br>KRS: {{ $siteSettings->krs_number }}@endif
        @if ($siteSettings->nip_number) · NIP: {{ $siteSettings->nip_number }}@endif
        @if ($siteSettings->regon_number) · REGON: {{ $siteSettings->regon_number }}@endif
    </p>

    @foreach ($texts->paragraphs('pdf_body', $vars) as $paragraph)
        <p class="body">{!! nl2br(e($paragraph)) !!}</p>
    @endforeach

    <table>
        <tr><th>Numer potwierdzenia</th><td>{{ strtoupper(substr($donation->session_id, 0, 8)) }}/{{ ($donation->paid_at ?? now())->format('Y') }}</td></tr>
        <tr><th>Darczyńca</th><td>{{ $donation->company_name ?: $donation->fullName() }}@if ($donation->company_name && $donation->fullName() !== '')<br>{{ $donation->fullName() }}@endif</td></tr>
        @if ($donation->nip)
            <tr><th>NIP darczyńcy</th><td>{{ $donation->nip }}</td></tr>
        @endif
        <tr><th>Kwota</th><td>{{ number_format($donation->amount_grosze / 100, 2, ',', ' ') }} {{ $donation->currency }}</td></tr>
        <tr><th>Data wpłaty</th><td>{{ ($donation->paid_at ?? now())->format('d.m.Y') }}</td></tr>
        <tr><th>Cel</th><td>{{ $vars['kampania'] }}</td></tr>
        <tr><th>Sposób wpłaty</th><td>{{ $donation->isManual() ? 'Przelew / wpłata przyjęta przez organizację' : 'Płatność online (Przelewy24)' }}@if ($donation->p24_order_id), nr transakcji {{ $donation->p24_order_id }}@endif</td></tr>
    </table>

    @foreach ($texts->paragraphs('pdf_footer', $vars) as $paragraph)
        <p class="footer">{!! nl2br(e($paragraph)) !!}</p>
    @endforeach
</body>
</html>
