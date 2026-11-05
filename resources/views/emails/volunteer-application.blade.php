<p>Nowe zgłoszenie na ogłoszenie wolontariackie: <strong>{{ $application->ad->title }}</strong>.</p>

<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:16px 0">
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;width:160px;font-weight:bold;vertical-align:top">Imię i nazwisko</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $application->name }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">E-mail</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></td>
    </tr>
    @if ($application->phone)
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">Telefon</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $application->phone }}</td>
    </tr>
    @endif
    @if ($application->availability)
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">Dostępność</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $application->availability }}</td>
    </tr>
    @endif
    @if ($application->motivation)
    <tr>
        <td style="padding:8px 0;font-weight:bold;vertical-align:top">Motywacja</td>
        <td style="padding:8px 0;white-space:pre-line">{{ $application->motivation }}</td>
    </tr>
    @endif
</table>

<p>Odpowiedz bezpośrednio na tę wiadomość, a trafi do kandydata.</p>
