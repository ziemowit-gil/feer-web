<!DOCTYPE html>
<html lang="pl" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light only">
<title>Potwierdź zapis na newsletter</title>
<style>
  body, table, td, p, a, h1 { font-family: Arial, Helvetica, sans-serif !important; font-style: normal !important; }
  @media (max-width: 600px) { .wrap { width: 100% !important; } .pad { padding-left: 22px !important; padding-right: 22px !important; } .btn, .btn a { display: block !important; width: 100% !important; text-align: center !important; box-sizing: border-box; } }
</style>
</head>
@php $host = preg_replace('#^https?://(www\.)?#', '', rtrim((string) $site_url, '/')); @endphp
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,Helvetica,sans-serif;font-style:normal;color:#1D1D1A;-webkit-text-size-adjust:100%">
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#F3F4F6">Jedno kliknięcie i gotowe: potwierdź zapis na newsletter.&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F4F6">
<tr><td align="center" style="padding:32px 12px">
<table role="presentation" class="wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:#FFFFFF;border-radius:12px;border-top:6px solid #1E6DFF">

  {{-- Logo na białym tle (logo FEER jest ciemne — na ciemnym tle znikało) --}}
  <tr><td class="pad" style="padding:30px 48px 6px">
    @if ($logo_url)
      <a href="{{ $site_url }}" style="text-decoration:none"><img src="{{ $logo_url }}" alt="{{ $site_name }}" height="48" style="display:block;height:48px;width:auto;max-width:240px;border:0"></a>
    @else
      <a href="{{ $site_url }}" style="font-size:20px;font-weight:bold;color:#1D1D1A;text-decoration:none">{{ $site_name }}</a>
    @endif
  </td></tr>

  {{-- Treść --}}
  <tr><td class="pad" style="padding:26px 48px 8px">
    <p style="margin:0 0 6px;font-size:13px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;color:#1752BF">Newsletter</p>
    <h1 style="margin:0 0 20px;font-size:28px;line-height:1.25;font-weight:bold;color:#1D1D1A">Potwierdź zapis jednym kliknięciem</h1>
    <p style="margin:0 0 14px;font-size:17px;line-height:1.6">{{ $first_name ? 'Cześć ' . $first_name . ',' : 'Dzień dobry,' }}</p>
    <p style="margin:0 0 26px;font-size:17px;line-height:1.6">dziękujemy za zapis na nasz newsletter. Kliknij przycisk poniżej, żebyśmy wiedzieli, że ten adres należy do Ciebie. Bez potwierdzenia nie wyślemy niczego więcej.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn" style="margin:0 0 28px"><tr>
      <td style="background:#1752BF;border-radius:8px;mso-padding-alt:16px 36px">
        <a href="{{ $confirm_url }}" style="display:inline-block;padding:16px 36px;font-size:18px;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:8px">Potwierdzam zapis</a>
      </td>
    </tr></table>

    @if (count($topics))
    <p style="margin:0 0 6px;font-size:15px;line-height:1.6"><strong>Wybrane tematy:</strong> {{ implode(', ', $topics) }}</p>
    @endif
    <p style="margin:0 0 6px;font-size:15px;line-height:1.6;color:#1D1D1A">Link jest ważny przez {{ $ttl_days }} {{ $ttl_days === 1 ? 'dzień' : 'dni' }}. Tematy zmienisz, a wypiszesz się jednym kliknięciem w stopce każdej wiadomości.</p>
  </td></tr>

  {{-- Podpis --}}
  <tr><td class="pad" style="padding:22px 48px 30px">
    <p style="margin:0 0 4px;font-size:16px;line-height:1.6">Pozdrawiamy serdecznie</p>
    <p style="margin:0 0 12px;font-size:16px;line-height:1.5;font-weight:bold">{{ $signature['team'] ?? 'Zespół ' . $site_name }}</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
      <td style="width:4px;background:#1E6DFF;border-radius:2px;font-size:0;line-height:0">&nbsp;</td>
      <td style="padding:2px 0 2px 14px;font-size:14px;line-height:1.65;color:#1D1D1A">
        @if (! empty($signature['address'])){{ $signature['address'] }}<br>@endif
        @if (! empty($signature['phone']))tel. <a href="tel:{{ preg_replace('/[^0-9+]/', '', $signature['phone']) }}" style="color:#1D1D1A;text-decoration:none">{{ $signature['phone'] }}</a><br>@endif
        @if (! empty($signature['email']))<a href="mailto:{{ $signature['email'] }}" style="color:#1752BF;text-decoration:underline">{{ $signature['email'] }}</a> · @endif<a href="{{ $site_url }}" style="color:#1752BF;text-decoration:underline">{{ $host }}</a>
      </td>
    </tr></table>
  </td></tr>

  {{-- Stopka --}}
  <tr><td class="pad" style="padding:18px 48px 26px;border-top:1px solid #E5E7EB;font-size:13px;line-height:1.6;color:#4A4A47">
    <p style="margin:0 0 8px">Jeśli przycisk nie działa, skopiuj ten adres do przeglądarki:<br><a href="{{ $confirm_url }}" style="color:#1752BF;word-break:break-all">{{ $confirm_url }}</a></p>
    <p style="margin:0">To nie Ty? Zignoruj tę wiadomość, bez potwierdzenia adres nie zostanie dodany do listy.</p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
