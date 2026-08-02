<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $news->title }} — {{ $siteSettings->site_name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap">
    <style>
        :root {
            --brand: {{ $brandPalette['color'] }};
            --brand-dark: {{ $brandPalette['dark'] }};
            --brand-light: {{ $brandPalette['light'] }};
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 13pt;
            line-height: 1.7;
            color: #111;
            background: #fff;
            padding: 2cm 2.5cm;
            max-width: 900px;
            margin: 0 auto;
        }

        .screen-only {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-bottom: 1.8rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem 1rem;
            font-family: 'Ubuntu', system-ui, sans-serif;
            font-size: .8rem;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: 1.5px solid #d1d5db;
            background: #fff;
            color: #374151;
            transition: background .15s, border-color .15s;
        }
        .btn:hover { background: #f9fafb; border-color: #9ca3af; }

        .btn-primary {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }
        .btn-primary:hover { background: var(--brand-dark); border-color: var(--brand-dark); }

        .org {
            font-family: 'Ubuntu', system-ui, sans-serif;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 1.6rem;
            padding-bottom: .65rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .meta {
            font-family: 'Ubuntu', system-ui, sans-serif;
            font-size: .78rem;
            color: #6b7280;
            margin-bottom: .55rem;
        }

        h1 {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 1.4rem;
            color: #111827;
        }

        .content { margin-bottom: 2rem; }
        .content p { margin-bottom: 1em; }
        .content h2 { font-size: 1.2rem; margin: 1.4em 0 .5em; }
        .content h3 { font-size: 1rem; margin: 1.2em 0 .4em; }
        .content ul, .content ol { padding-left: 1.5em; margin-bottom: 1em; }
        .content li { margin-bottom: .3em; }
        .content a { color: var(--brand); }
        .content img { max-width: 100%; height: auto; }
        .content blockquote {
            border-left: 3px solid #d1d5db;
            padding-left: 1em;
            color: #4b5563;
            font-style: italic;
            margin: 1em 0;
        }

        .footer {
            margin-top: 2.5rem;
            padding-top: .75rem;
            border-top: 1px solid #e5e7eb;
            font-family: 'Ubuntu', system-ui, sans-serif;
            font-size: .72rem;
            color: #9ca3af;
        }

        @media print {
            .screen-only { display: none !important; }
            body { padding: 0; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <div class="screen-only">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print" aria-hidden="true"></i> Zapisz jako PDF / Drukuj
        </button>
        <a href="{{ route('news.show', $news) }}" class="btn">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do artykułu
        </a>
    </div>

    <div class="org">{{ $siteSettings->site_name }}</div>

    <p class="meta">
        {{ $news->published_at->format('d.m.Y') }}
        @if ($news->category)&nbsp;&middot;&nbsp;{{ $news->category->name }}@endif
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

    <script>
        if (window.location.search.indexOf('auto=1') !== -1) {
            window.addEventListener('load', function () { window.print(); });
        }
    </script>
</body>
</html>
