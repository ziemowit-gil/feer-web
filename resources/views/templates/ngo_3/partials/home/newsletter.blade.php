{{--
    Sekcja: zapis na newsletter — embed z Ustawień (newsletter_code), ten sam
    mechanizm co dedykowana strona /newsletter (resources/views/newsletter/show.blade.php).
    Sekcja nie pokazuje się wcale, dopóki admin nie skonfiguruje kodu.
--}}
@if ($siteSettings->newsletter_code)
<section class="py-14" aria-labelledby="ngo3-newsletter-heading">
    <div class="mx-auto max-w-2xl px-4 text-center">
        <h2 id="ngo3-newsletter-heading" class="mb-2 text-2xl font-extrabold text-gray-900 md:text-3xl">
            Bądź na bieżąco
        </h2>
        <p class="mb-6 text-muted">Zapisz się na newsletter i nie przegap żadnej nowości.</p>
        <div class="newsletter-embed">{!! $siteSettings->newsletter_code !!}</div>
    </div>
</section>
@endif
