<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>{{ $course->title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1d1d1a; line-height: 1.55; }
        .page { padding: 24px 30px; }
        .org { font-size: 11px; font-weight: 700; color: #4b5563; }
        .kicker { margin-top: 10px; font-size: 10px; font-weight: 700; color: #1752bf; }
        h1 { font-size: 22px; line-height: 1.25; margin-top: 4px; }
        .lead { margin-top: 8px; font-size: 13px; }
        h2 { font-size: 14px; margin: 18px 0 6px; padding-top: 10px; border-top: 1px solid #d1d5db; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 5px 6px 5px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { font-size: 10px; color: #4b5563; border-bottom: 2px solid #1d1d1a; }
        ul, ol { padding-left: 18px; }
        li { margin-bottom: 3px; }
        .facts td { width: 33%; border: 0; }
        .facts strong { display: block; font-size: 9px; color: #4b5563; }
        .foot { margin-top: 22px; padding-top: 8px; border-top: 1px solid #d1d5db; font-size: 9px; color: #4b5563; }
    </style>
</head>
<body>
<div class="page">
    <p class="org">{{ $siteSettings->site_name }}</p>
    <p class="kicker">{{ $course->categoryLabel() }} · {{ $course->formatLabel() }}</p>
    <h1>{{ $course->title }}</h1>
    @if ($course->lead)<p class="lead">{{ $course->lead }}</p>@endif

    @php
        $facts = array_values(array_filter([
            ['Cena', $course->priceLabel()], ['Czas trwania', $course->duration], ['Forma', $course->formatLabel()],
            ['Poziom', $course->level], ['Certyfikat', $course->certificate],
        ], fn ($f) => filled($f[1])));
    @endphp
    @if ($facts)
        <table class="facts" style="margin-top:12px"><tr>
            @foreach ($facts as $i => [$label, $value])
                <td><strong>{{ $label }}</strong>{{ $value }}</td>
                @if (($i + 1) % 3 === 0 && ! $loop->last)</tr><tr>@endif
            @endforeach
        </tr></table>
    @endif

    @if (filled($course->description))
        <h2>O szkoleniu</h2>
        <div>{!! \App\Support\SafeHtml::clean($course->description) !!}</div>
    @endif
    @if ($course->listOf('audience'))
        <h2>Dla kogo</h2>
        <ul>@foreach ($course->listOf('audience') as $row)<li>{{ $row }}</li>@endforeach</ul>
    @endif
    @if ($course->listOf('outcomes'))
        <h2>Czego się nauczysz</h2>
        <ul>@foreach ($course->listOf('outcomes') as $row)<li>{{ $row }}</li>@endforeach</ul>
    @endif
    @if ($course->programModules())
        <h2>Program</h2>
        <ol>@foreach ($course->programModules() as $row)<li><strong>{{ $row['title'] }}</strong>@if ($row['hours'] !== '') ({{ $row['hours'] }} godz.)@endif @if ($row['text'] !== '')<br>{{ $row['text'] }}@endif</li>@endforeach</ol>
    @endif
    @if (filled($course->prerequisites))
        <h2>Wymagania wstępne</h2>
        <p>{!! nl2br(e($course->prerequisites)) !!}</p>
    @endif

    <h2>Terminy</h2>
    @if ($editions->isEmpty())
        <p>Aktualnie nie ma zaplanowanych terminów.</p>
    @else
        <table>
            <tr><th>Termin</th><th>Miejsce</th><th>Harmonogram</th></tr>
            @foreach ($editions as $e)
                <tr><td>{{ $e->datesLabel() }}</td><td>{{ $e->location ?: '—' }}</td><td>{{ $e->schedule ?: '—' }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($course->price !== null || filled($course->price_note) || filled($course->funding))
        <h2>Cennik</h2>
        <p><strong>{{ $course->priceLabel() }}</strong> za osobę.@if (filled($course->price_note)) {{ $course->price_note }}@endif</p>
        @if (filled($course->funding))<p>Dofinansowanie: {{ $course->funding }}</p>@endif
    @endif

    @if (filled($course->terms))
        <h2>Warunki udziału</h2>
        <div>{!! \App\Support\SafeHtml::clean($course->terms) !!}</div>
    @endif

    <p class="foot">
        {{ route('courses.show', $course) }}
        @if ($siteSettings->contact_phone) · tel. {{ $siteSettings->contact_phone }}@endif
        @if ($siteSettings->contact_email) · {{ $siteSettings->contact_email }}@endif
        · wydruk z dnia {{ $printedAt }}
    </p>
</div>
</body>
</html>
