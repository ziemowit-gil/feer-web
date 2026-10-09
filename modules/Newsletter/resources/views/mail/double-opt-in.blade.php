<!DOCTYPE html>
<html lang="pl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Potwierdź zapis</title></head>
<body style="margin:0;padding:0;background:#F3F6FB;font-family:Montserrat,Arial,Helvetica,sans-serif;color:#1D1D1A">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F6FB"><tr><td align="center" style="padding:24px 12px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:8px;border-top:6px solid #EA8F00">
<tr><td style="padding:32px 32px 8px">
  <p style="margin:0 0 8px;font-size:13px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#1752BF">{{ $site_name }}</p>
  <h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;font-weight:800;color:#1D1D1A">Potwierdź zapis na newsletter</h1>
  <p style="margin:0 0 16px;font-size:16px;line-height:1.55">{{ $first_name ? 'Cześć ' . $first_name . '!' : 'Dzień dobry!' }} Ktoś (mamy nadzieję, że Ty) zapisał ten adres na newsletter{{ $topics ? ' w tematach: ' . $topics : '' }}.</p>
  <p style="margin:0 0 24px;font-size:16px;line-height:1.55">Aby potwierdzić zapis, kliknij przycisk:</p>
  <table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="background:#1752BF;border-radius:6px">
    <a href="{{ $confirm_url }}" style="display:inline-block;padding:14px 28px;font-size:16px;font-weight:800;color:#ffffff;text-decoration:none">Potwierdzam zapis</a>
  </td></tr></table>
  <p style="margin:24px 0 8px;font-size:14px;line-height:1.5;color:#4A4A47">Jeśli przycisk nie działa, skopiuj ten adres do przeglądarki:<br><a href="{{ $confirm_url }}" style="color:#1752BF;word-break:break-all">{{ $confirm_url }}</a></p>
  <p style="margin:0 0 24px;font-size:14px;line-height:1.5;color:#4A4A47">Link jest ważny przez {{ $ttl_days }} dni. Jeśli to nie Ty — zignoruj tę wiadomość, nic się nie stanie.</p>
</td></tr>
<tr><td style="padding:16px 32px 28px;border-top:1px solid #E5E7EB;font-size:12px;line-height:1.5;color:#4A4A47">
  Wiadomość wysłana automatycznie przez {{ $site_name }} · <a href="{{ $site_url }}" style="color:#1752BF">{{ $site_url }}</a>
</td></tr>
</table>
</td></tr></table>
</body>
</html>
