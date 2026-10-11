{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ route('blog.index') }}</link>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml"/>
        <description>Artykuły o dostępności cyfrowej, edukacji i empatii.</description>
        <language>pl-PL</language>
        <lastBuildDate>{{ ($items->first()?->published_at ?? now())->toRfc2822String() }}</lastBuildDate>
        <generator>{{ $settings->site_name }}</generator>
        @foreach ($items as $item)
            @php
                $url = route('blog.show', $item);
                $summary = $item->metaDescription();
                // Adresy względne (/storage/…) nie zadziałają w czytniku RSS.
                $body = preg_replace('~\b(src|href)="/(?!/)~i', '$1="'.rtrim(url('/'), '/').'/', (string) $item->body);
                $image = $item->imageUrl();
                $byline = $item->authorLine();
            @endphp
            <item>
                <title>{{ $item->title }}</title>
                <link>{{ $url }}</link>
                <guid isPermaLink="true">{{ $url }}</guid>
                <pubDate>{{ $item->publishedDate()->toRfc2822String() }}</pubDate>
                @if ($byline !== '')
                    <dc:creator>{{ $byline }}</dc:creator>
                @endif
                @if ($item->category)
                    <category>{{ $item->category->name }}</category>
                @endif
                <description>{{ $summary }}</description>
                <content:encoded>{{ $body }}</content:encoded>
                @if ($image)
                    <media:content url="{{ url($image) }}" medium="image"/>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
