<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Druk przelewu — {{ $siteSettings->site_name }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 14pt; margin: 0 0 4mm; }
        .intro { margin: 0 0 8mm; color: #444; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        th { width: 38%; text-align: left; vertical-align: top; font-size: 8pt; text-transform: uppercase; color: #555; padding: 3mm 3mm 3mm 0; }
        td { border: 0.4mm solid #333; padding: 3mm; font-size: 11pt; letter-spacing: 0.3mm; }
        .blank { height: 8mm; }
        .note { margin-top: 10mm; font-size: 8pt; color: #555; }
    </style>
</head>
<body>
    <h1>Polecenie przelewu — darowizna</h1>
    <p class="intro">Wydrukuj i złóż w banku lub na poczcie albo przepisz dane do bankowości internetowej.</p>
    <table>
        <tr><th>Nazwa odbiorcy</th><td>{{ $siteSettings->site_name }}</td></tr>
        @if ($siteSettings->contact_address)
            <tr><th>Adres odbiorcy</th><td>{{ $siteSettings->contact_address }}, {{ $siteSettings->contact_city }}</td></tr>
        @endif
        <tr><th>Numer rachunku odbiorcy</th><td>{{ $siteSettings->bank_account_number }}</td></tr>
        <tr><th>Waluta</th><td>PLN</td></tr>
        <tr><th>Kwota</th><td class="{{ $amount ? '' : 'blank' }}">{{ $amount ? number_format($amount, 2, ',', ' ') : '' }}</td></tr>
        <tr><th>Tytuł</th><td>{{ $title }}</td></tr>
        <tr><th>Nazwa zleceniodawcy</th><td class="blank"></td></tr>
        <tr><th>Adres zleceniodawcy</th><td class="blank"></td></tr>
    </table>
    <p class="note">
        Zachowaj potwierdzenie przelewu.
        @if ($siteSettings->krs_number) KRS odbiorcy: {{ $siteSettings->krs_number }}. @endif
    </p>
</body>
</html>
