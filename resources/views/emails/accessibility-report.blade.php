<p>Ktoś zgłosił problem z dostępnością przez formularz na stronie deklaracji dostępności.</p>

<p>
    @if (filled($senderName))
        <strong>Imię i nazwisko:</strong> {{ $senderName }}<br>
    @endif
    <strong>E-mail (do odpowiedzi):</strong> {{ $senderEmail }}
    @if (filled($pageUrl))
        <br><strong>Której strony/elementu dotyczy:</strong> {{ $pageUrl }}
    @endif
</p>

<p><strong>Opis bariery:</strong></p>
<p>{{ $messageBody }}</p>

<p style="color:#666;font-size:12px">Zgodnie z ustawą o dostępności cyfrowej na zgłoszenie należy odpowiedzieć bez zbędnej zwłoki, najpóźniej w ciągu 7 dni.</p>
