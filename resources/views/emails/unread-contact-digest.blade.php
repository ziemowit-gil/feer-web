<p>W panelu {{ $siteName }} czeka <strong>{{ $messages->count() }} nieprzeczytanych {{ $messages->count() === 1 ? 'wiadomość' : ($messages->count() < 5 ? 'wiadomości' : 'wiadomości') }}</strong> z formularza kontaktowego.</p>

<table style="border-collapse:collapse;width:100%;font-family:sans-serif;font-size:14px;margin-top:16px">
    <thead>
        <tr style="background:#f3f4f6;text-align:left">
            <th style="padding:8px 12px;border-bottom:2px solid #e5e7eb">Nadawca</th>
            <th style="padding:8px 12px;border-bottom:2px solid #e5e7eb">Temat / treść</th>
            <th style="padding:8px 12px;border-bottom:2px solid #e5e7eb;white-space:nowrap">Data</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($messages as $msg)
            <tr style="border-bottom:1px solid #e5e7eb">
                <td style="padding:8px 12px;vertical-align:top">
                    <strong>{{ $msg->name }}</strong><br>
                    <a href="mailto:{{ $msg->email }}" style="color:#6366f1">{{ $msg->email }}</a>
                    @if ($msg->phone)
                        <br><span style="color:#6b7280">{{ $msg->phone }}</span>
                    @endif
                </td>
                <td style="padding:8px 12px;vertical-align:top;color:#374151">
                    @if ($msg->subject)
                        <strong>{{ $msg->subject }}</strong><br>
                    @endif
                    {{ \Illuminate\Support\Str::limit($msg->message, 120) }}
                </td>
                <td style="padding:8px 12px;vertical-align:top;white-space:nowrap;color:#6b7280;font-size:12px">
                    {{ $msg->created_at->format('d.m.Y') }}<br>
                    {{ $msg->created_at->format('H:i') }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top:20px">
    <a href="{{ route('admin.wiadomosci-kontaktowe.index') }}"
       style="display:inline-block;background:#6366f1;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px">
        Otwórz skrzynkę w panelu →
    </a>
</p>

<p style="margin-top:24px;font-size:12px;color:#9ca3af">
    Ta wiadomość jest wysyłana automatycznie raz dziennie, gdy w skrzynce kontaktowej znajdują się nieprzeczytane wiadomości.
</p>
