<p>Cześć,</p>

<p>informujemy, że zmienił się nasz harmonogram spotkań stacjonarnych ({{ $scheduleTitle }}). Aktualne terminy:</p>

@if (count($items) > 0)
    <ul>
        @foreach ($items as $item)
            <li>
                <strong>{{ $item['when_label'] }}</strong>{{ $item['is_next'] ? ' (najbliższy termin)' : '' }}
                @if (!empty($item['where']))<br>Gdzie: {{ $item['where'] }}@endif
                @if (!empty($item['note']))<br>{{ $item['note'] }}@endif
            </li>
        @endforeach
    </ul>
@else
    <p>Na ten moment nie mamy zaplanowanych terminów stacjonarnych.</p>
@endif

<p>Do zobaczenia!<br>Zespół {{ $siteName }}</p>

<p style="color:#888;font-size:12px;">Otrzymujesz tę wiadomość, bo zgłosiłeś(-aś) chęć udziału w spotkaniu przez formularz „Daj znać, że przyjdziesz".</p>
