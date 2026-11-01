{{--
    Partnerzy: pasek logotypów z przewijaniem strzałkami (scroll-snap), logo z nazwą w opisie alternatywnym, link w nowej
    karcie, gdy partner ma adres. Pusty pasek się nie pokazuje.
--}}
@php $logos = ($partners ?? collect())->filter(fn ($p) => $p->logo_url)->values(); @endphp
@include('templates.test3.partials.home.css')
@if ($logos->isNotEmpty())
<section class="z3 z3-sec" aria-label="Partnerzy" style="padding-top:0" x-data>
    <div class="z3-wrap z3-partners">
        <button type="button" class="z3-partners-btn" aria-label="Przewiń partnerów w lewo" @click="$refs.list.scrollBy({ left: -320, behavior: 'smooth' })"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
        <ul x-ref="list" role="list">
            @foreach ($logos as $partner)
                <li>
                    @if ($partner->url)
                        <a href="{{ $partner->url }}" target="_blank" rel="noopener"><img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy"><span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                    @else
                        <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy">
                    @endif
                </li>
            @endforeach
        </ul>
        <button type="button" class="z3-partners-btn" aria-label="Przewiń partnerów w prawo" @click="$refs.list.scrollBy({ left: 320, behavior: 'smooth' })"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
    </div>
</section>
@endif
