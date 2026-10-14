{{-- Kanał RSS portalu kategorii. Deklaracja XML sklejana z kawałków (short_open_tag — patrz feed/rss). --}}
{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $siteUrl }}</link>
        <atom:link href="{{ $selfUrl }}" rel="self" type="application/rss+xml"/>
        <description>{{ $description }}</description>
        <language>pl-PL</language>
        <lastBuildDate>{{ ($items->first()?->date ?? now())->toRfc2822String() }}</lastBuildDate>
        <generator>{{ $settings->site_name }}</generator>
        @foreach ($items as $item)
            <item>
                <title>{{ $item->title }}</title>
                <link>{{ $item->url }}</link>
                <guid isPermaLink="true">{{ $item->url }}</guid>
                <pubDate>{{ $item->date->toRfc2822String() }}</pubDate>
                <category>{{ $item->category }}</category>
                <description>{{ $item->summary }}</description>
            </item>
        @endforeach
    </channel>
</rss>
