<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>{{ $news->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Serif', serif;
            font-size: 12pt;
            line-height: 1.7;
            color: #111;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .org {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 18pt;
            padding-bottom: 8pt;
            border-bottom: 2px solid #ddd;
        }

        .meta {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #555;
            margin-bottom: 6pt;
        }

        h1 {
            font-size: 18pt;
            font-weight: bold;
            line-height: 1.3;
            margin: 0 0 16pt 0;
            color: #111;
        }

        .content p    { margin: 0 0 10pt 0; }
        .content h2   { font-size: 13pt; margin: 14pt 0 5pt; }
        .content h3   { font-size: 11pt; margin: 12pt 0 4pt; }
        .content ul,
        .content ol   { padding-left: 18pt; margin: 0 0 10pt 0; }
        .content li   { margin-bottom: 3pt; }
        .content a    { color: #111; text-decoration: underline; }
        .content img  { max-width: 100%; height: auto; }
        .content blockquote {
            border-left: 3px solid #ccc;
            padding-left: 10pt;
            color: #444;
            font-style: italic;
            margin: 10pt 0;
        }

        .footer {
            margin-top: 28pt;
            padding-top: 8pt;
            border-top: 1px solid #ddd;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7pt;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="org">{{ $siteSettings->site_name }}</div>

    <p class="meta">
        {{ $news->published_at->format('d.m.Y') }}
        @if ($news->category) &middot; {{ $news->category->name }}@endif
    </p>

    <h1>{{ $news->title }}</h1>

    <div class="content">
        {!! $news->content !!}
    </div>

    <p class="footer">
        Wydrukowano ze strony {{ $siteSettings->site_name }} dnia {{ $printedAt }}.
        Treść mogła od tego czasu ulec zmianie.
        Aktualna wersja: {{ route('news.show', $news) }}
    </p>
</body>
</html>
