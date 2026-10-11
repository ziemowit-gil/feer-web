{{-- Przypomnienie o terminie naboru / zapytania ofertowego (PageDeadlineReminderMail). Prosty, dostępny HTML. --}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:24px;background:#ffffff;color:#1D1D1A;font-family:Montserrat,Arial,sans-serif;font-size:16px;line-height:1.6">
    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#1D1D1A">{{ $headline }}</h1>

    <p style="margin:0 0 16px">Strona: <strong>{{ $page->title }}</strong>@if ($siteName) (witryna: {{ $siteName }})@endif</p>

    <p style="margin:0 0 24px">{{ $body }}</p>

    <p style="margin:0 0 12px">
        <a href="{{ $editUrl }}" style="display:inline-block;padding:12px 20px;background:#1752bf;color:#ffffff;font-weight:bold;text-decoration:underline;border-radius:6px">Edytuj stronę w panelu</a>
    </p>
    <p style="margin:0 0 24px">
        Strona publiczna: <a href="{{ $publicUrl }}" style="color:#1752bf;font-weight:bold">{{ $publicUrl }}</a>
    </p>

    <p style="margin:0;font-size:14px;color:#4B4B47">To automatyczne przypomnienie wysyłamy jeden raz. Otrzymujesz je jako autor strony albo osoba kontaktowa podana na stronie.</p>
</body>
</html>
