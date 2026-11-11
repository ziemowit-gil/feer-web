{{--
    Okruszki BU2: jasnoszary pas na całą szerokość, niebieskie linki, ukośniki między nimi. Gdy bieżąca strona jest
    w Menu nawigacji, ścieżka idzie za menu (tak jak menu boczne); inaczej ścieżka z widoku ($items, bez strony głównej).
--}}
@php
    $bu2Crumbs = \App\Support\Bu2Menu::trail() ?: ($items ?? []);
@endphp
@include('templates.bu2.partials.base-css')
<div class="bu2 bu2-crumbs">
    <nav class="bu2-wrap" aria-label="Ścieżka nawigacyjna">
        <ol>
            <li><a href="{{ site_home_url() }}">Strona główna</a></li>
            @foreach ($bu2Crumbs as $crumb)
                <li>
                    @if ($loop->last)
                        <span aria-current="page">{{ $crumb['label'] }}</span>
                    @elseif (! empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span>{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
</div>
