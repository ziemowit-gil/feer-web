<p>Ktoś dał znać, że wybiera się na spotkanie (formularz „Daj znać, że przyjdziesz” na stronie).</p>

<p>
    <strong>Imię i nazwisko:</strong> {{ $senderName }}<br>
    <strong>E-mail:</strong> {{ $senderEmail }}
    @if (filled($term))
        <br><strong>Termin:</strong> {{ $term }}
    @endif
</p>

@if (filled($messageBody))
    <p><strong>Wiadomość:</strong></p>
    <p>{{ $messageBody }}</p>
@endif
