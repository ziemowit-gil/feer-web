{{--
    Sekcja: zapis na newsletter — dedykowany formularz modułu Newsletter
    (panel: Newsletter → Formularze zapisu). Gdy moduł nie ma aktywnego formularza,
    pokazujemy kod osadzenia z Ustawień (newsletter_code); bez obu — nic.
--}}
@php
    $nlForm = class_exists(\Modules\Newsletter\Models\NewsletterForm::class) && \Illuminate\Support\Facades\Schema::hasTable('newsletter_forms')
        ? \Modules\Newsletter\Models\NewsletterForm::defaultFor($siteSettings->id) : null;
@endphp
@if ($nlForm)
    <x-newsletter-widget :form="$nlForm" source="widget_home" />
@elseif ($siteSettings->newsletter_code)
<section class="py-14" aria-labelledby="home-newsletter-heading">
    <div class="mx-auto max-w-2xl px-4 text-center">
        <h2 id="home-newsletter-heading" class="mb-2 text-2xl font-extrabold text-gray-900 md:text-3xl">Bądź na bieżąco</h2>
        <p class="mb-6 text-muted">Zapisz się na newsletter i nie przegap żadnej nowości.</p>
        <div class="newsletter-embed">{!! $siteSettings->newsletter_code !!}</div>
    </div>
</section>
@endif
