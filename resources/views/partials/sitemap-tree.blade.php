{{-- Mapa strony: zagnieżdżona lista podstron (rekurencyjnie). $nodes = [['page' => Page, 'children' => Collection], …] --}}
<ul class="mt-2 space-y-1.5 border-l-2 border-solid border-gray-200 pl-4" role="list">
    @foreach ($nodes as $node)
        <li data-sm-item>
            <a href="{{ $node['page']->publicUrl() }}" class="text-ink underline decoration-gray-300 underline-offset-4 transition hover:text-brand-dark hover:decoration-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">{{ $node['page']->title }}</a>
            @if ($node['children']->isNotEmpty())
                @include('partials.sitemap-tree', ['nodes' => $node['children']])
            @endif
        </li>
    @endforeach
</ul>
