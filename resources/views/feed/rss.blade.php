{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $siteUrl }}</link>
        <atom:link href="{{ $selfUrl }}" rel="self" type="application/rss+xml"/>
        <description>{{ $description }}</description>
        <language>pl-PL</language>
        <lastBuildDate>{{ ($items->first()?->published_at ?? now())->toRfc2822String() }}</lastBuildDate>
        <generator>{{ $settings->site_name }}</generator>
        @if ($logo = $settings->logoUrl())
            <image>
                <url>{{ $logo }}</url>
                <title>{{ $title }}</title>
                <link>{{ $siteUrl }}</link>
            </image>
        @endif
        @foreach ($items as $item)
            @php
                $url = route('news.show', $item);
                $summary = trim((string) $item->excerpt) !== ''
                    ? trim(strip_tags($item->excerpt))
                    : \Illuminate\Support\Str::limit(trim(strip_tags((string) $item->content)), 300);
                // Adresy względne (/storage/…, /aktualnosci/…) nie zadziałają w czytniku RSS.
                $body = preg_replace('~\b(src|href)="/(?!/)~i', '$1="'.rtrim(url('/'), '/').'/', (string) $item->content);
                $image = $item->imageUrlOrDefault();
            @endphp
            <item>
                <title>{{ $item->title }}</title>
                <link>{{ $url }}</link>
                <guid isPermaLink="true">{{ $url }}</guid>
                <pubDate>{{ $item->published_at->toRfc2822String() }}</pubDate>
                @if ($item->category)
                    <category>{{ $item->category->name }}</category>
                @endif
                <description>{{ $summary }}</description>
                <content:encoded>{{ $body }}</content:encoded>
                @if ($image)
                    <media:content url="{{ $image }}" medium="image"/>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
