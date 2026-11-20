{{--
    Okruszki szablonu „kreatywni” (włączane z partials/breadcrumbs): w kremowym pasie, który przechodzi w pas z tytułem
    podstrony, mała czcionka, ukośniki między pozycjami, bieżąca strona z aria-current. Style: partials/base-css.
--}}
@php $items ??= []; @endphp
@include('templates.kreatywni.partials.base-css')
<div class="kc kc-crumbs">
    <nav class="kc-wrap" aria-label="Ścieżka nawigacyjna">
        <ol>
            <li><a href="{{ site_home_url() }}">Strona główna</a></li>
            @foreach ($items as $item)
                <li>
                    @if ($loop->last)
                        <span aria-current="page">{{ $item['label'] }}</span>
                    @elseif (! empty($item['url']))
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
</div>
