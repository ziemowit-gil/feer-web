{{-- Ostatnie odcinki: siatka kart (cała karta jest linkiem), bez najnowszego, który jest w hero. --}}
@if ($episodes->isNotEmpty())
<section class="pc pc-sec" aria-labelledby="pc-eps-h">
    <div class="pc-wrap">
        <h2 class="pc-title" id="pc-eps-h">Odcinki</h2>
        <ul class="pc-eps" role="list">
            @foreach ($episodes as $ep)
                @php $cover = $ep->getFirstMediaUrl('cover'); @endphp
                <li class="pc-ep">
                    @if ($cover)<img class="pc-cover" src="{{ $cover }}" alt="" loading="lazy">@endif
                    <div class="pc-ep-body">
                        <p class="pc-ep-meta">@if ($ep->episode_number)Odcinek {{ $ep->episode_number }} · @endif{{ ($ep->published_at ?? $ep->created_at)?->translatedFormat('j F Y') }}@if ($ep->is_premium) · Premium @endif</p>
                        <h3><a href="{{ route('podcasts.show', $ep) }}">{{ $ep->title }}</a></h3>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="pc-more"><a class="pc-btn" href="{{ route('podcasts.index') }}">Wszystkie odcinki</a></p>
    </div>
</section>
@endif
