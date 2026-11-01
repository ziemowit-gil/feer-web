{{-- Szary pas skrótów z przewijaniem strzałkami (kolejne „Szybkie akcje” po trzech z kafli). --}}
@php $strip = $quickLinks->skip(3)->values(); @endphp
@if ($strip->isNotEmpty())
<style>
    .z2-strip { background: #efefef; margin-top: 3rem; padding: 1.5rem 0; }
    .z2-strip-in { max-width: 1160px; margin: 0 auto; padding: 0 1rem; display: flex; align-items: center; gap: .5rem; }
    .z2-strip-list { display: flex; gap: 1rem; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; flex: 1; list-style: none; margin: 0; padding: 0; }
    .z2-strip-list::-webkit-scrollbar { display: none; }
    .z2-strip-list li { flex: 0 0 9.5rem; scroll-snap-align: start; }
    .z2-strip-list a { display: flex; flex-direction: column; align-items: center; gap: .6rem; text-align: center; text-decoration: none; color: #0b4f9e; font-weight: 700; font-size: .75rem; line-height: 1.3; padding: .5rem; height: 100%; }
    .z2-strip-list a:hover span { text-decoration: underline; }
    .z2-strip-list i { font-size: 2.2rem; }
    .z2-arrow { flex: none; height: 2.75rem; width: 2.75rem; border: 0; background: transparent; color: #0b4f9e; font-size: 1.3rem; cursor: pointer; }
    .z2-arrow:hover { background: #fff; }
</style>
<section class="z2-strip" aria-label="Na skróty" x-data>
    <div class="z2-strip-in">
        <button type="button" class="z2-arrow" aria-label="Przewiń w lewo" @click="$refs.l.scrollBy({ left: -300, behavior: 'smooth' })"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <ul class="z2-strip-list" x-ref="l">
            @foreach ($strip as $link)
                <li><a href="{{ site_href($link->url) }}">{!! icon_html($link->icon) !!}<span>{{ $link->label }}</span></a></li>
            @endforeach
        </ul>
        <button type="button" class="z2-arrow" aria-label="Przewiń w prawo" @click="$refs.l.scrollBy({ left: 300, behavior: 'smooth' })"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
    </div>
</section>
@endif
