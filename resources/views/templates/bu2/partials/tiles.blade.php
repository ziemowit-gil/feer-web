{{--
    Kafle podstron BU2 (strona działu): niebieski kwadrat z białą ikoną i niebieski tytuł obok, w dwóch kolumnach.
    $nodes: węzły z App\Support\Bu2Menu (label, url, icon). Ikona: pole „Ikona strony” (layout_data.icon) albo ikona
    pozycji menu; bez ikony — dokument. Ikona jest ozdobą (aria-hidden), nazwą linku jest tytuł.
--}}
@php $nodes = collect($nodes ?? [])->filter(fn ($n) => ! empty($n['url']))->values(); @endphp
@if ($nodes->isNotEmpty())
    <div class="bu2-tiles-wrap">
        <ul class="bu2-tiles" role="list">
            @foreach ($nodes as $tile)
                <li>
                    <a href="{{ $tile['url'] }}">
                        <span class="bu2-tile-icon" aria-hidden="true">{{ icon_html($tile['icon'] ?: 'mi-description') }}</span>
                        <span class="bu2-tile-label">{{ $tile['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
