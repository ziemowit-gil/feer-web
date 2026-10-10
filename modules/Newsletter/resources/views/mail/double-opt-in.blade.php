<!DOCTYPE html>
<html lang="pl" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title>Potwierdź zapis na newsletter</title>
<style>
  body, table, td, p, a, h1, h2 { font-family: Arial, Helvetica, sans-serif !important; font-style: normal !important; }
  @media (max-width: 600px) { .wrap { width: 100% !important; } .pad { padding: 24px 20px !important; } .btn a { display: block !important; text-align: center !important; } }
</style>
</head>
<body style="margin:0;padding:0;background:#EEF2F8;font-family:Arial,Helvetica,sans-serif;font-style:normal;color:#1D1D1A;-webkit-text-size-adjust:100%">
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#EEF2F8">Jedno kliknięcie i gotowe — potwierdź zapis na newsletter {{ $site_name }}.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF2F8">
<tr><td align="center" style="padding:32px 12px">
<table role="presentation" class="wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px">

  {{-- Nagłówek --}}
  <tr><td style="background:#1D1D1A;border-radius:12px 12px 0 0;padding:22px 32px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      <td style="vertical-align:middle">
        @if ($logo_url)
          <img src="{{ $logo_url }}" alt="{{ $site_name }}" height="40" style="display:block;height:40px;width:auto;border:0">
        @else
          <span style="font-size:20px;font-weight:bold;color:#FFFFFF;letter-spacing:.02em">{{ $site_name }}</span>
        @endif
      </td>
      <td align="right" style="vertical-align:middle;font-size:12px;font-weight:bold;letter-spacing:.12em;text-transform:uppercase;color:#8FB4FF">Newsletter</td>
    </tr></table>
  </td></tr>
  <tr><td style="height:6px;background:#EA8F00;font-size:0;line-height:0">&nbsp;</td></tr>

  {{-- Treść --}}
  <tr><td class="pad" style="background:#FFFFFF;padding:40px 44px 28px">
    <h1 style="margin:0 0 18px;font-size:28px;line-height:1.2;font-weight:bold;color:#1D1D1A">Jeszcze jeden krok</h1>
    <p style="margin:0 0 14px;font-size:17px;line-height:1.6">{{ $first_name ? 'Cześć ' . $first_name . ',' : 'Dzień dobry,' }}</p>
    <p style="margin:0 0 24px;font-size:17px;line-height:1.6">ten adres został zapisany na newsletter <strong>{{ $site_name }}</strong>. Żeby mieć pewność, że to Ty, prosimy o potwierdzenie — bez tego nie wyślemy niczego więcej.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn" style="margin:0 0 28px"><tr>
      <td style="background:#1752BF;border-radius:8px;mso-padding-alt:16px 32px">
        <a href="{{ $confirm_url }}" style="display:inline-block;padding:16px 32px;font-size:17px;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:8px">Potwierdzam zapis &rarr;</a>
      </td>
    </tr></table>

    @if (count($topics))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F6FB;border-radius:10px;margin:0 0 28px"><tr><td style="padding:18px 20px">
      <p style="margin:0 0 8px;font-size:12px;font-weight:bold;letter-spacing:.1em;text-transform:uppercase;color:#4A4A47">Wybrane tematy</p>
      <p style="margin:0;font-size:15px;line-height:1.7">
        @foreach ($topics as $t)<span style="display:inline-block;margin:0 6px 6px 0;padding:5px 12px;border-radius:999px;background:#FFFFFF;border:1px solid #D6DEEA;font-weight:bold;color:#1752BF">{{ $t }}</span>@endforeach
      </p>
    </td></tr></table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px">
      @foreach ([['1', 'Kliknij przycisk powyżej — link działa przez ' . $ttl_days . ' dni.'], ['2', 'Pierwszy newsletter dotrze przy najbliższej wysyłce.'], ['3', 'Tematy zmienisz, a wypiszesz się jednym kliknięciem w stopce każdej wiadomości.']] as [$n, $txt])
      <tr>
        <td width="34" style="vertical-align:top;padding:0 12px 12px 0"><span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:50%;background:#EAF1FF;color:#1752BF;font-weight:bold;font-size:14px;text-align:center">{{ $n }}</span></td>
        <td style="vertical-align:top;padding:4px 0 12px;font-size:15px;line-height:1.55;color:#1D1D1A">{{ $txt }}</td>
      </tr>
      @endforeach
    </table>

    <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#4A4A47">Jeśli przycisk nie działa, skopiuj ten adres do przeglądarki:<br><a href="{{ $confirm_url }}" style="color:#1752BF;word-break:break-all">{{ $confirm_url }}</a></p>
  </td></tr>

  {{-- Stopka --}}
  <tr><td class="pad" style="background:#FFFFFF;border-top:1px solid #E5E7EB;border-radius:0 0 12px 12px;padding:20px 44px 28px;font-size:12px;line-height:1.6;color:#4A4A47">
    <p style="margin:0 0 6px">To nie Ty? Zignoruj tę wiadomość — bez potwierdzenia adres nie zostanie aktywowany i nic więcej nie wyślemy.</p>
    <p style="margin:0">{{ $site_name }}{{ $tagline ? ' · ' . $tagline : '' }} · <a href="{{ $site_url }}" style="color:#1752BF">{{ preg_replace('#^https?://#', '', $site_url) }}</a></p>
  </td></tr>

</table>
<p style="margin:18px 0 0;font-size:11px;color:#6B7280">Wiadomość wygenerowana automatycznie po zapisie przez formularz na stronie.</p>
</td></tr>
</table>
</body>
</html>
