<section class="py-4" aria-labelledby="federation-cta-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <div class="flex flex-col items-center justify-between gap-6 rounded-lg px-8 py-10 sm:flex-row"
            style="background:{{ $siteSettings->brandColorN(3) }}">
            <div class="text-center text-white sm:text-left">
                <p class="text-sm font-bold text-white/80">Chcesz dowiedzieć się więcej o naszej działalności?</p>
                <h2 id="federation-cta-heading" class="mt-1 text-2xl font-extrabold sm:text-3xl">Skontaktuj się z nami!</h2>
            </div>
            <a href="{{ route('contact.show') }}"
                class="flex-none rounded-md bg-white px-8 py-3 text-sm font-extrabold text-ink transition hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                Kontakt
            </a>
        </div>
    </div>
</section>
