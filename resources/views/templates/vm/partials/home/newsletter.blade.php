{{-- Newsletter szablonu "vm": szare tło, embed z Ustawień (newsletter_code); bez kodu sekcja się nie pokazuje. --}}
@if ($siteSettings->newsletter_code)
<section class="bg-gray-100 py-14" aria-labelledby="vm-newsletter-heading">
    <div class="mx-auto max-w-xl px-4 text-center">
        <h2 id="vm-newsletter-heading" class="vm-display mb-4 text-2xl text-ink sm:text-3xl lg:text-4xl">Newsletter</h2>
        <p class="mb-8 text-lg font-semibold leading-relaxed text-ink">{{ $siteSettings->vmNewsletterText() }}</p>
        <div class="newsletter-embed mx-auto max-w-sm rounded-md bg-white p-5 text-left shadow-md">{!! $siteSettings->newsletter_code !!}</div>
    </div>
</section>
@endif
