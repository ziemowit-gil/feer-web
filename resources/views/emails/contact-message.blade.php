<p>Nowa wiadomość z formularza kontaktowego.</p>

<table style="border-collapse:collapse;width:100%;font-family:sans-serif;font-size:14px">
    <tr>
        <td style="padding:6px 12px 6px 0;font-weight:bold;white-space:nowrap;vertical-align:top">Imię i nazwisko</td>
        <td style="padding:6px 0">{{ $contactMessage->name }}</td>
    </tr>
    <tr>
        <td style="padding:6px 12px 6px 0;font-weight:bold;white-space:nowrap;vertical-align:top">E-mail</td>
        <td style="padding:6px 0"><a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></td>
    </tr>
    @if ($contactMessage->phone)
    <tr>
        <td style="padding:6px 12px 6px 0;font-weight:bold;white-space:nowrap;vertical-align:top">Telefon</td>
        <td style="padding:6px 0"><a href="tel:{{ $contactMessage->phone }}">{{ $contactMessage->phone }}</a></td>
    </tr>
    @endif
    @if ($contactMessage->subject)
    <tr>
        <td style="padding:6px 12px 6px 0;font-weight:bold;white-space:nowrap;vertical-align:top">Temat</td>
        <td style="padding:6px 0">{{ $contactMessage->subject }}</td>
    </tr>
    @endif
</table>

<hr style="margin:16px 0;border:none;border-top:1px solid #e5e7eb">

<p style="white-space:pre-wrap">{{ $contactMessage->message }}</p>
