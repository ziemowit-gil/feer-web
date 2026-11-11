{{--
    Drzewo menu BU2 (menu boczne podstron i menu na telefonie). Węzły z App\Support\Bu2Menu.
    Napis to link do strony, strzałka obok to osobny przycisk rozwijający gałąź na miejscu (aria-expanded/aria-controls).
    Gałąź bieżącej strony jest rozwinięta, pozostałe zwinięte; bieżąca strona ma aria-current="page".
    $prefix rozróżnia identyfikatory, gdy drzewo jest na stronie dwa razy (menu boczne i menu na telefonie).
--}}
@php $prefix ??= 's'; @endphp
<ul @if ($depth > 1) class="bu2-side-sub" @endif>
    @foreach ($nodes as $node)
        @php
            $hasKids = $node['children'] !== [];
            $listId = 'bu2-'.$prefix.'-'.$node['id'];
            $indent = $depth > 1 ? 'padding-left:'.(1.2 + ($depth - 2) * 1.1).'rem' : '';
        @endphp
        <li @class(['is-open' => $node['open']]) @if ($hasKids) x-data="{ expanded: {{ $node['open'] ? 'true' : 'false' }} }" @endif>
            <div class="bu2-side-row">
                @if ($node['url'])
                    <a href="{{ $node['url'] }}" @if ($indent) style="{{ $indent }}" @endif @if ($node['current']) aria-current="page" @endif>{{ $node['label'] }}</a>
                @else
                    <span @if ($indent) style="{{ $indent }}" @endif>{{ $node['label'] }}</span>
                @endif
                @if ($hasKids)
                    <button type="button" @click="expanded = ! expanded" aria-expanded="{{ $node['open'] ? 'true' : 'false' }}" :aria-expanded="expanded.toString()" aria-controls="{{ $listId }}">
                        <span class="bu2-chev" aria-hidden="true"></span><span class="bu2-sr">Podstrony: {{ $node['label'] }}</span>
                    </button>
                @endif
            </div>
            @if ($hasKids)
                <div id="{{ $listId }}" x-show="expanded" @unless ($node['open']) x-cloak @endunless>
                    @include('templates.bu2.partials.side-tree', ['nodes' => $node['children'], 'depth' => $depth + 1, 'prefix' => $prefix])
                </div>
            @endif
        </li>
    @endforeach
</ul>
