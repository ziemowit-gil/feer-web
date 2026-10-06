<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>{{ $forSubmitter ? 'Potwierdzenie zgłoszenia' : 'Nowe zgłoszenie' }}: {{ $form->title }}</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e5e7eb;">
        <tr>
            <td style="padding:24px 28px 8px;">
                <p style="margin:0 0 4px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">{{ $siteSettings->site_name ?? config('app.name') }}</p>
                <h1 style="margin:0;font-size:20px;line-height:1.3;">
                    {{ $forSubmitter ? 'Potwierdzenie zgłoszenia' : 'Nowe zgłoszenie' }}: {{ $form->title }}
                </h1>
                @if ($introText)
                    <p style="margin:12px 0 0;font-size:15px;line-height:1.5;">{{ $introText }}</p>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:16px 28px 8px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:15px;">
                    @foreach ($rows as $row)
                        <tr>
                            <th scope="row" align="left" style="width:38%;padding:10px 12px;border-top:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;vertical-align:top;">{{ $row['label'] }}</th>
                            <td style="padding:10px 12px;border-top:1px solid #e5e7eb;vertical-align:top;white-space:pre-wrap;">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        @unless ($forSubmitter)
            <tr>
                <td style="padding:8px 28px 24px;font-size:13px;color:#6b7280;line-height:1.5;">
                    Zgłoszenie nr {{ $submission->id }}, {{ $submission->created_at?->format('d.m.Y H:i') }}.
                    @if ($submission->ip_address) IP: {{ $submission->ip_address }}. @endif
                    <br>
                    <a href="{{ route('admin.formularze.zgloszenia', $form) }}" style="color:#1d4ed8;">Zobacz zgłoszenia tego formularza w panelu</a>
                </td>
            </tr>
        @else
            <tr>
                <td style="padding:8px 28px 24px;font-size:13px;color:#6b7280;line-height:1.5;">
                    Ta wiadomość została wysłana automatycznie — prosimy na nią nie odpowiadać, chyba że w treści podano inaczej.
                </td>
            </tr>
        @endunless
    </table>
</body>
</html>
