{{-- Oferta na stronie głównej: nagłówek i wstęp, pod nimi blok sprzedażowy (karta cenowa + kolekcje warsztatów). --}}
@if ($texts->rows('offer_items') !== [] || $texts->get('offer_price') !== '')
<section id="oferta" class="kc-sec kc-sec-mint" aria-labelledby="kc-offer-title">
    <div class="kc-wrap">
        <div class="kc-head">
            <h2 id="kc-offer-title" class="kc-h2">{{ $texts->get('offer_title') }}</h2>
            @foreach ($texts->paragraphs('offer_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
        </div>
        @include('templates.kreatywni.partials.offer-sales', ['kcHeadingLevel' => 3])
    </div>
</section>
@endif
