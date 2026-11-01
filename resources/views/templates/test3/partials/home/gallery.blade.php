{{--
    Galeria: do ośmiu zdjęć w siatce 4×2 (2 kolumny na telefonie) z podpisem na zdjęciu; klik otwiera powiększenie
    (data-lightbox). Podpis jest też opisem alternatywnym zdjęcia, więc sam podpis jest ukryty przed czytnikami ekranu.
    Pokazuje się, gdy moduł galerii ma co najmniej trzy zdjęcia i sekcja „Galeria” nie jest ukryta.
--}}
@php $shots = ($gallery ?? collect())->filter(fn ($p) => filled($p->image_url))->values()->take(8); @endphp
@include('templates.test3.partials.home.css')
@if ($shots->count() >= 3 && $siteSettings->isHomepageSectionEnabled('gallery'))
<section id="galeria" class="z3 z3-sec" aria-labelledby="z3-gallery-h">
    <div class="z3-wrap">
        <h2 id="z3-gallery-h">Galeria</h2>
        <ul class="z3-gal" role="list">
            @foreach ($shots as $photo)
                <li>
                    <img src="{{ $photo->image_url }}" alt="{{ $photo->caption }}" data-lightbox loading="lazy" decoding="async">
                    @if (filled($photo->caption))<figcaption aria-hidden="true">{{ $photo->caption }}</figcaption>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
