<p>Dzień dobry,</p>

<p>pojawił się nowy termin szkolenia <strong>{{ $course->title }}</strong>, o który prosiłeś(-aś) w {{ $siteName }}.</p>

<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:16px 0">
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;width:140px;font-weight:bold;vertical-align:top">Termin</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $edition->datesLabel() }}</td>
    </tr>
    @if ($edition->schedule)
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">Harmonogram</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $edition->schedule }}</td>
    </tr>
    @endif
    @if ($edition->location)
    <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:bold;vertical-align:top">Miejsce</td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb">{{ $edition->location }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding:8px 0;font-weight:bold;vertical-align:top">Wolne miejsca</td>
        <td style="padding:8px 0">{{ $edition->free_seats }} z {{ $edition->capacity }}</td>
    </tr>
</table>

<p><a href="{{ route('courses.show', [$course, 'termin' => $edition->id]) }}#zapis">Zobacz szkolenie i zapisz się na ten termin</a></p>

<p style="color:#4b5563;font-size:14px">To jednorazowa wiadomość: nie wyślemy kolejnych. Dostałeś(-aś) ją, bo zostawiłeś(-aś) adres e-mail przy tym szkoleniu.</p>
