{{--
    Pod polem adresu (slug) w edycji opublikowanej treści: zmiana adresu tworzy przekierowanie 301 ze starego
    (App\Support\SlugRedirects); tu można z niego zrezygnować. Objaśnienie pod „?”. Expects: $model.
--}}
@if ($model->exists && $model->is_published)
    <div class="mt-2 flex flex-wrap items-center gap-x-1 text-xs text-ink">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="skip_slug_redirect" value="1" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
            Nie twórz przekierowania ze starego adresu
        </label>
        @include('admin.partials.help', [
            'topic' => 'przekierowanie ze starego adresu',
            'text' => e('Gdy zmienisz adres, stary (/'.$model->slug.') przekieruje na nowy (301) — linki z wyszukiwarek, maili i mediów społecznościowych dalej zadziałają. Zaznacz, jeśli stary adres ma zacząć pokazywać inną stronę. Przekierowania znajdziesz w Narzędzia SEO → Przekierowania 301.'),
        ])
    </div>
@endif
