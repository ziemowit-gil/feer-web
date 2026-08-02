<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $news->title }} — {{ $siteSettings->site_name }}</title>
    <style>
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
            gap: .75rem;
            margin-bottom: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem 1rem;
            font-family: system-ui, sans-serif;
            font-size: .85rem;
            font-weight: 700;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            border: 1.5px solid #555;
            background: #fff;
            color: #333;
        }
        .btn-primary {
            background: #1a56a5;
            border-color: #1a56a5;
            color: #fff;
        }
        .btn:hover { opacity: .85; }

        .org {
            font-family: system-ui, sans-serif;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 1.8rem;
            padding-bottom: .7rem;
            border-bottom: 2px solid #ddd;
        }

        .meta {
            font-family: system-ui, sans-serif;
            font-size: .8rem;
            color: #666;
            margin-bottom: .6rem;
        }

        h1 {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 1.4rem;
            color: #111;
        }

        .content {
            margin-bottom: 2rem;
        }

        .content p { margin-bottom: 1em; }
        .content h2 { font-size: 1.2rem; margin: 1.4em 0 .5em; }
        .content h3 { font-size: 1rem; margin: 1.2em 0 .4em; }
        .content ul, .content ol { padding-left: 1.5em; margin-bottom: 1em; }
        .content li { margin-bottom: .3em; }
        .content a { color: #1a56a5; }
        .content img { max-width: 100%; height: auto; }
        .content blockquote {
            border-left: 3px solid #ccc;
            padding-left: 1em;
            color: #444;
            font-style: italic;
            margin: 1em 0;
        }

        .footer {
            margin-top: 2.5rem;
            padding-top: .8rem;
            border-top: 1px solid #ddd;
            font-family: system-ui, sans-serif;
            font-size: .75rem;
            color: #888;
        }

        @media print {
            .screen-only { display: none !important; }
            body { padding: 0; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <div class="screen-only" aria-hidden="true">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            &#128424; Zapisz jako PDF / Drukuj
        </button>
        <a href="{{ route('news.show', $news) }}" class="btn">
            &#8592; Wróć do artykułu
        </a>
    </div>

    <div class="org">{{ $siteSettings->site_name }}</div>

    <p class="meta">
        {{ $news->published_at->format('d.m.Y') }}
        @if ($news->category)&nbsp;·&nbsp;{{ $news->category->name }}@endif
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
