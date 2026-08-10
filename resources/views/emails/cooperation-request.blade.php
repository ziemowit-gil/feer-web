<p>Nowe zgłoszenie współpracy z formularza na stronie.</p>

<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:16px 0">
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;width:140px;font-weight:bold;vertical-align:top">Imię i nazwisko</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $cooperationRequest->name }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">E-mail</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">
            <a href="mailto:{{ $cooperationRequest->email }}">{{ $cooperationRequest->email }}</a>
        </td>
    </tr>
    @if ($cooperationRequest->organization)
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">Organizacja / firma</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $cooperationRequest->organization }}</td>
    </tr>
    @endif
    @if ($cooperationRequest->sector)
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">Sektor</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $cooperationRequest->sector }}</td>
    </tr>
    @endif
    @if (!empty($cooperationRequest->cooperation_types))
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">Formy współpracy</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ implode(', ', $cooperationRequest->cooperation_types) }}</td>
    </tr>
    @endif
    @if ($cooperationRequest->message)
    <tr>
        <td style="padding:8px 0;font-weight:bold;vertical-align:top">Wiadomość</td>
        <td style="padding:8px 0">{{ $cooperationRequest->message }}</td>
    </tr>
    @endif
</table>
