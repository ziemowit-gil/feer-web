{{--
    Moduł „Jak wesprzeć w trzech krokach” na stronie /wsparcie. Teksty edytuje się w panelu (Ustawienia → Wsparcie → Treść),
    puste pola dają wartości domyślne z SiteSetting::SUPPORT_DEFAULTS. Sekcję można ukryć przełącznikiem w panelu.
    Płasko: numer w jasnym polu, bez ramek; siatka dopasowuje się do szerokości, więc trzy kroki układają się w rząd
    albo jeden pod drugim. Lista numerowana (<ol>), więc czytnik ekranu zapowiada kolejność kroków.
--}}
@unless ($siteSettings->support_steps_hidden)
    <section class="mx-auto max-w-6xl px-4 py-14" aria-labelledby="steps-heading">
        <h2 id="steps-heading" class="text-2xl font-bold text-ink md:text-3xl">{{ $siteSettings->supportText('support_steps_title') }}</h2>
        @if (filled($siteSettings->supportText('support_steps_subtitle')))
            <p class="mt-2 max-w-2xl text-ink">{{ $siteSettings->supportText('support_steps_subtitle') }}</p>
        @endif
        <ol class="mt-8 grid gap-6" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))" role="list">
            @foreach ([1, 2, 3] as $i)
                <li class="flex items-start gap-4">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-md bg-brand-light text-lg font-extrabold text-brand-dark" aria-hidden="true">{{ $i }}</span>
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold leading-snug text-ink"><span class="sr-only">Krok {{ $i }}: </span>{{ $siteSettings->supportText('support_step'.$i.'_title') }}</h3>
                        <p class="mt-1 leading-relaxed text-ink">{{ $siteSettings->supportText('support_step'.$i.'_text') }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endunless
