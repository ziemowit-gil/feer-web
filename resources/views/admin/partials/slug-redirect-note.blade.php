{{--
    Pod polem adresu (slug) w edycji opublikowanej treści: zmiana adresu tworzy przekierowanie 301 ze starego
    (App\Support\SlugRedirects); tu można z niego zrezygnować. Expects: $model.
--}}
@if ($model->exists && $model->is_published)
    <details class="mt-2 text-xs text-muted">
        <summary class="cursor-pointer font-bold text-ink">Zmiana adresu tworzy przekierowanie ze starego</summary>
        <p class="mt-1">Gdy zmienisz adres, stary ({{ '/'.$model->slug }}) przekieruje na nowy (301) — linki z wyszukiwarek, maili i mediów społecznościowych dalej zadziałają. Przekierowania znajdziesz w Narzędzia SEO → Przekierowania 301.</p>
        <label class="mt-2 flex items-center gap-2 text-ink">
            <input type="checkbox" name="skip_slug_redirect" value="1" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
            Nie twórz przekierowania (np. stary adres ma zacząć pokazywać inną stronę)
        </label>
    </details>
@endif
