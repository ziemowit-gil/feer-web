{{-- Podziękowanie za wpłatę — wszystkie teksty i grafika z panelu (Zbiórki → Teksty i podziękowania lub kampania). --}}
<div style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.5; color: #1d1d1a; max-width: 600px;">
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $texts->get('email_image_alt', $vars) }}" width="600" style="display:block; width:100%; max-width:600px; height:auto; border-radius:8px; margin-bottom:24px;">
    @endif

    <h1 style="font-size: 24px; line-height: 1.25; margin: 0 0 16px;">{{ $texts->get('email_heading', $vars) }}</h1>

    @foreach ($texts->paragraphs('email_body', $vars) as $paragraph)
        <p style="margin: 0 0 16px;">{!! nl2br(e($paragraph)) !!}</p>
    @endforeach

    @if ($donation->isRecurring() && filled($vars['konto']))
        @foreach ($texts->paragraphs('recurring_thanks', $vars) as $paragraph)
            <p style="margin: 0 0 16px;">{!! nl2br(e($paragraph)) !!}</p>
        @endforeach
    @endif

    <table role="presentation" style="border-collapse: collapse; margin: 0 0 24px; font-size: 15px;">
        <tr><th scope="row" style="text-align:left; padding:4px 16px 4px 0;">Kwota</th><td style="padding:4px 0;">{{ $donation->amountLabel() }}</td></tr>
        <tr><th scope="row" style="text-align:left; padding:4px 16px 4px 0;">Cel</th><td style="padding:4px 0;">{{ $vars['kampania'] }}</td></tr>
        <tr><th scope="row" style="text-align:left; padding:4px 16px 4px 0;">Data</th><td style="padding:4px 0;">{{ ($donation->paid_at ?? now())->format('d.m.Y') }}</td></tr>
    </table>

    <p style="margin: 0 0 24px;">
        <a href="{{ $receiptUrl }}" style="display:inline-block; background:#1e6dff; color:#ffffff; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold;">{{ $texts->get('email_cta', $vars) }}</a>
    </p>

    @foreach ($texts->paragraphs('email_signature', $vars) as $paragraph)
        <p style="margin: 0 0 8px;">{!! nl2br(e($paragraph)) !!}</p>
    @endforeach
</div>
