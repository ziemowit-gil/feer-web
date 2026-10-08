@extends('layouts.site')

@section('title', 'Wesprzyj nas — ' . $siteSettings->site_name)
@section('meta_description', 'Wesprzyj ' . $siteSettings->site_name . '.')
@if ($siteSettings->supportImageUrl())
    @section('og_image', $siteSettings->supportImageUrl())
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wesprzyj nas', 'url' => null],
    ]])
@endsection

{{--
    Strona „Wesprzyj nas" w układzie FEER (Brand book): jasny nagłówek ze zbiórką/zdjęciem obok, kwoty jako duże liczby,
    dwie kolumny „Jak wesprzeć" (przelew z kopiowaniem po lewej, metody online po prawej), korzyści, liczby, zdjęcia,
    cytat, inne formy pomocy, FAQ, końcowy pasek wezwania, partnerzy i aktualności. Płasko: bez ramek, cieni i kółek.
    Kontrast: ink/muted na bieli i #F3F4F6, biały na #1456CC (6,5:1), linki brand-dark.
--}}
@section('content')
    @php
        $btnPrimary = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
        $btnGhost = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-200 px-5 text-sm font-bold text-ink transition hover:bg-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
        $copyBtn = 'mt-2 inline-flex min-h-9 items-center gap-1.5 rounded-md bg-white px-3 text-xs font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
        $hasFundraiser = $siteSettings->hasFundraiser();
        $progress = $hasFundraiser ? $siteSettings->fundraiserProgress() : 0;
        $onlineMethods = array_values(array_filter([
            $siteSettings->support_quick_transfer_url ? ['key' => 'method2', 'url' => $siteSettings->support_quick_transfer_url, 'icon' => 'fa-solid fa-bolt'] : null,
            $siteSettings->support_wplacam_url ? ['key' => 'method4', 'url' => $siteSettings->support_wplacam_url, 'icon' => 'fa-solid fa-heart'] : null,
            $siteSettings->support_buycoffee_url ? ['key' => 'method3', 'url' => $siteSettings->support_buycoffee_url, 'icon' => 'fa-solid fa-mug-hot'] : null,
        ]));
    @endphp

    {{-- ══ Nagłówek: zachęta + zbiórka lub zdjęcie ══ --}}
    <section id="wesprzyj-hero" class="bg-gray-50">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 md:py-16 {{ ($hasFundraiser || $siteSettings->supportImageUrl()) ? 'lg:grid-cols-[minmax(0,1fr)_26rem]' : '' }}">
            <div class="min-w-0">
                <p class="mb-3 text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $siteSettings->supportText('support_hero_badge') }}</p>
                <h1 class="max-w-2xl text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">{{ $siteSettings->supportText('support_hero_title') }}</h1>
                <p class="mt-4 max-w-xl text-lg leading-relaxed text-ink">{{ $siteSettings->supportText('support_hero_subtitle') }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('donation.show') }}" class="{{ $btnPrimary }}">Wpłać darowiznę online</a>
                    <a href="#sposoby" class="{{ $btnGhost }}">Zobacz inne sposoby pomocy</a>
                </div>
                <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink" role="list" aria-label="Dlaczego możesz nam zaufać">
                    <li>Bezpieczna płatność (Przelewy24)</li>
                    @if ($siteSettings->krs_number)<li>KRS {{ $siteSettings->krs_number }}</li>@endif
                    <li>Możesz wpłacić anonimowo</li>
                </ul>
            </div>

            @if ($hasFundraiser)
                <div class="rounded-lg bg-white p-6">
                    <h2 class="text-xl font-bold text-ink">{{ $siteSettings->support_fundraiser_title }}</h2>
                    @if ($siteSettings->support_fundraiser_text)<p class="mt-2 text-sm leading-relaxed text-muted">{{ $siteSettings->support_fundraiser_text }}</p>@endif
                    <div class="mt-5">
                        <div class="mb-1 flex items-end justify-between gap-2 text-sm">
                            <span class="text-lg font-extrabold text-ink">{{ number_format((int) $siteSettings->support_fundraiser_raised, 0, ',', ' ') }} zł</span>
                            <span class="text-muted">z {{ number_format((int) $siteSettings->support_fundraiser_goal, 0, ',', ' ') }} zł</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-sm bg-gray-200" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Postęp zbiórki: {{ $progress }}%">
                            <div class="h-full bg-brand-dark" style="width: {{ $progress }}%"></div>
                        </div>
                        <p class="mt-1 text-right text-xs font-bold text-ink">{{ $progress }}%</p>
                    </div>
                    @if ($siteSettings->support_fundraiser_url)
                        <a href="{{ $siteSettings->support_fundraiser_url }}" target="_blank" rel="noopener" class="{{ $btnPrimary }} mt-5">
                            {{ $siteSettings->support_fundraiser_cta_label ?: 'Wesprzyj zbiórkę' }}<span class="sr-only"> (otwiera się w nowej karcie)</span>
                        </a>
                    @endif
                </div>
            @elseif ($siteSettings->supportImageUrl())
                <img src="{{ $siteSettings->supportImageUrl() }}" alt="" class="aspect-[4/3] w-full rounded-lg object-cover">
            @endif
        </div>
    </section>

    {{-- ══ Co daje Twoja wpłata: duże liczby ══ --}}
    @if (! empty($impacts))
        <section class="mx-auto max-w-6xl px-4 py-14" aria-labelledby="impact-heading">
            <h2 id="impact-heading" class="text-2xl font-bold text-ink md:text-3xl">Co daje Twoja wpłata</h2>
            <p class="mt-2 max-w-2xl text-muted">Wybierz kwotę — przejdziesz do formularza z gotową wartością i dokończysz w minutę.</p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 {{ match (min(4, count($impacts))) { 1, 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', default => 'lg:grid-cols-4' } }}" role="list">
                @foreach ($impacts as $amount => $text)
                    <li>
                        <a href="{{ route('donation.show', ['kwota' => $amount]) }}"
                           class="group flex h-full flex-col rounded-lg bg-gray-50 p-6 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <span class="text-4xl font-extrabold tracking-tight text-ink">{{ number_format($amount, 0, ',', ' ') }} zł</span>
                            <span class="mt-3 flex-1 text-sm leading-relaxed text-ink">{{ $text }}</span>
                            <span class="mt-5 text-sm font-bold text-brand-dark group-hover:underline">Wpłacam {{ number_format($amount, 0, ',', ' ') }} zł →</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ══ Jak wesprzeć: przelew (lewa) + metody online (prawa) ══ --}}
    <section id="sposoby" class="scroll-mt-24 bg-gray-50" aria-labelledby="methods-heading">
        <div class="mx-auto max-w-6xl px-4 py-14">
            <h2 id="methods-heading" class="text-2xl font-bold text-ink md:text-3xl">{{ $siteSettings->supportText('support_methods_title') }}</h2>
            @if ($siteSettings->support_intro)
                <div class="prose mt-3 max-w-2xl text-ink">{!! $siteSettings->support_intro !!}</div>
            @endif

            <div class="mt-8 grid gap-10 lg:grid-cols-2 lg:gap-14">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-ink">{{ $siteSettings->supportText('support_method1_title') }}</h3>
                    @if ($siteSettings->bank_account_number)
                        <dl class="mt-4 space-y-3">
                            <div class="rounded-md bg-white p-4">
                                <dt class="text-xs font-bold uppercase tracking-widest text-muted">{{ $siteSettings->supportText('support_method1_account_label') }}</dt>
                                <dd class="mt-1 break-words font-mono text-base font-bold text-ink">{{ $siteSettings->bank_account_number }}</dd>
                                <button type="button" data-copy-button data-copy-value="{{ $siteSettings->bank_account_number }}" class="{{ $copyBtn }}"><i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer</button>
                            </div>
                            @if ($siteSettings->bank_account_tax_number)
                                <div class="rounded-md bg-white p-4">
                                    <dt class="text-xs font-bold uppercase tracking-widest text-muted">{{ $siteSettings->supportText('support_method1_tax_label') }}</dt>
                                    <dd class="mt-1 break-words font-mono text-base font-bold text-ink">{{ $siteSettings->bank_account_tax_number }}</dd>
                                    <button type="button" data-copy-button data-copy-value="{{ $siteSettings->bank_account_tax_number }}" class="{{ $copyBtn }}"><i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer</button>
                                </div>
                            @endif
                            @if ($siteSettings->supportText('support_transfer_title') !== '')
                                <div class="rounded-md bg-white p-4">
                                    <dt class="text-xs font-bold uppercase tracking-widest text-muted">{{ $siteSettings->supportText('support_method1_transfer_label') }}</dt>
                                    <dd class="mt-1 break-words text-base font-bold text-ink">{{ $siteSettings->supportText('support_transfer_title') }}</dd>
                                    <button type="button" data-copy-button data-copy-value="{{ $siteSettings->supportText('support_transfer_title') }}" class="{{ $copyBtn }}"><i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj tytuł</button>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="mt-4 text-sm text-muted">Numer konta zostanie wkrótce podany.</p>
                    @endif
                </div>

                @if (count($onlineMethods) > 0)
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-ink">Szybko, online</h3>
                        <ul class="mt-4 space-y-3" role="list">
                            @foreach ($onlineMethods as $m)
                                <li class="rounded-md bg-white p-5 sm:flex sm:items-center sm:justify-between sm:gap-5">
                                    <div class="min-w-0">
                                        <p class="font-bold text-ink"><i class="{{ $m['icon'] }} mr-2 text-brand-dark" aria-hidden="true"></i>{{ $siteSettings->supportText('support_'.$m['key'].'_title') }}</p>
                                        <p class="mt-1 text-sm leading-relaxed text-muted">{{ $siteSettings->supportText('support_'.$m['key'].'_text') }}</p>
                                    </div>
                                    <a href="{{ $m['url'] }}" target="_blank" rel="noopener" class="{{ $btnPrimary }} mt-3 flex-none sm:mt-0">
                                        {{ $siteSettings->supportText('support_'.$m['key'].'_cta_label') }}<span class="sr-only"> (otwiera się w nowej karcie)</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ══ Dlaczego warto (korzyści) ══ --}}
    <section class="mx-auto max-w-6xl px-4 py-14" aria-labelledby="benefits-heading">
        <h2 id="benefits-heading" class="text-2xl font-bold text-ink md:text-3xl">{{ $siteSettings->supportText('support_benefits_title') }}</h2>
        <p class="mt-2 max-w-2xl text-muted">{{ $siteSettings->supportText('support_benefits_subtitle') }}</p>
        <div class="mt-8 grid gap-8 sm:grid-cols-3">
            @foreach (['1', '2', '3'] as $i)
                <div>
                    <i class="{{ $siteSettings->supportText('support_benefit'.$i.'_icon') }} text-2xl text-brand-dark" aria-hidden="true"></i>
                    <h3 class="mt-3 text-lg font-bold text-ink">{{ $siteSettings->supportText('support_benefit'.$i.'_title') }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $siteSettings->supportText('support_benefit'.$i.'_text') }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ══ Działania, które wspierasz (kategorie projektów) ══ --}}
    @if ($programs->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-14" aria-labelledby="programs-heading">
            <h2 id="programs-heading" class="text-2xl font-bold text-ink md:text-3xl">Działania, które wspierasz</h2>
            <p class="mt-2 max-w-2xl text-muted">Wybierz obszar, który jest Ci najbliższy — zobacz, co w nim robimy.</p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 {{ $programs->count() >= 3 ? 'lg:grid-cols-3' : '' }}" role="list">
                @foreach ($programs as $program)
                    <li class="flex flex-col rounded-lg bg-gray-50 p-6">
                        <h3 class="text-lg font-bold text-ink">{{ $program->name }}</h3>
                        <ul class="mt-3 flex-1 space-y-1.5 text-sm" role="list">
                            @foreach ($program->publishedProjects->take(4) as $project)
                                <li><a href="{{ route('projects.show', $project) }}" class="font-semibold text-brand-dark underline underline-offset-2 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $project->title }}</a></li>
                            @endforeach
                        </ul>
                        <a href="{{ site_route('categories.show', $program) }}" class="mt-4 text-sm font-bold text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Zobacz wszystkie w tym obszarze →</a>
                    </li>
                @endforeach
            </ul>
            @if ($paidInfoPage)
                <p class="mt-6 max-w-3xl text-sm leading-relaxed text-ink">Część zajęć jest bezpłatna, a przy części prosimy o dołożenie się do kosztów. <a href="{{ $paidInfoPage->publicUrl() }}" class="font-bold text-brand-dark underline underline-offset-2 hover:text-ink">Przeczytaj, jak to działa →</a></p>
            @endif
        </section>
    @endif

    {{-- ══ Liczby ══ --}}
    @if ($stats->isNotEmpty())
        <section class="bg-gray-50" aria-labelledby="stats-heading">
            <div class="mx-auto max-w-6xl px-4 py-14">
                <h2 id="stats-heading" class="text-2xl font-bold text-ink md:text-3xl">Twoje wsparcie napędza konkretne działania</h2>
                <dl class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($stats as $stat)
                        <div>
                            <dd class="order-first text-4xl font-extrabold tracking-tight text-ink md:text-5xl">{{ $stat['value'] }}</dd>
                            <dt class="mt-1 text-sm text-muted">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    {{-- ══ Zdjęcia ══ --}}
    @if ($photos->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-14" aria-labelledby="photos-heading">
            <h2 id="photos-heading" class="mb-6 text-2xl font-bold text-ink md:text-3xl">Zobacz nas w działaniu</h2>
            <div class="grid auto-rows-[8rem] grid-cols-2 gap-3 sm:auto-rows-[10rem] sm:grid-cols-4" data-lightbox>
                @foreach ($photos as $i => $photo)
                    <div class="overflow-hidden rounded-lg {{ $i === 0 ? 'col-span-2 row-span-2' : '' }}">
                        <img src="{{ $photo->getUrl() }}" alt="{{ $siteSettings->site_name }} w działaniu" class="h-full w-full object-cover transition duration-500 hover:scale-[1.03]" loading="lazy">
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══ Cytat ══ --}}
    @if (filled($siteSettings->support_testimonial_quote))
        <section class="mx-auto max-w-4xl px-4 pb-14">
            <figure class="border-l-4 border-brand pl-6">
                <blockquote class="text-xl font-medium leading-relaxed text-ink md:text-2xl">„{{ $siteSettings->support_testimonial_quote }}"</blockquote>
                @if ($siteSettings->support_testimonial_author)
                    <figcaption class="mt-4 text-sm text-muted">
                        <span class="font-bold text-ink">{{ $siteSettings->support_testimonial_author }}</span>@if ($siteSettings->support_testimonial_role), {{ $siteSettings->support_testimonial_role }}@endif
                    </figcaption>
                @endif
            </figure>
        </section>
    @endif

    {{-- ══ Pomóż inaczej + FAQ ══ --}}
    <section class="bg-gray-50" aria-labelledby="other-ways-heading">
        <div class="mx-auto grid max-w-6xl gap-12 px-4 py-14 lg:grid-cols-2 lg:gap-16">
            <div>
                <h2 id="other-ways-heading" class="text-2xl font-bold text-ink">Nie możesz wpłacić? Pomóż inaczej</h2>
                <ul class="mt-6 space-y-3" role="list">
                    @if ($volunteeringEnabled)
                        <li><a href="{{ route('volunteer.index') }}" class="block rounded-md bg-white p-5 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><span class="font-bold text-ink">Zostań wolontariuszem →</span><span class="mt-1 block text-sm text-muted">Podaruj swój czas i umiejętności.</span></a></li>
                    @endif
                    @if ($cooperationPage)
                        <li><a href="{{ $cooperationPage->publicUrl() }}" class="block rounded-md bg-white p-5 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><span class="font-bold text-ink">Nawiąż współpracę →</span><span class="mt-1 block text-sm text-muted">Zostań partnerem organizacji lub projektu.</span></a></li>
                    @endif
                    <li><a href="{{ route('newsletter.show') }}" class="block rounded-md bg-white p-5 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><span class="font-bold text-ink">Bądź na bieżąco →</span><span class="mt-1 block text-sm text-muted">Zapisz się na newsletter i opowiedz o nas znajomym.</span></a></li>
                </ul>
            </div>

            <div aria-labelledby="faq-heading" role="group">
                <h2 id="faq-heading" class="text-2xl font-bold text-ink">Najczęstsze pytania</h2>
                <div class="mt-6 space-y-2">
                    @foreach ($faq as $item)
                        <details class="group rounded-md bg-white">
                            <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 px-5 py-3 font-bold text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand">
                                <span>{{ $item['q'] }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-muted transition-transform group-open:rotate-180" aria-hidden="true"></i>
                            </summary>
                            <p class="px-5 pb-4 text-sm leading-relaxed text-muted">{{ $item['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ══ Końcowe wezwanie ══ --}}
    <section class="bg-brand-dark text-white" aria-labelledby="outro-heading">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-12 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 id="outro-heading" class="text-2xl font-extrabold md:text-3xl">{{ $siteSettings->supportText('support_outro_title') }}</h2>
                <p class="mt-2 max-w-xl">{{ $siteSettings->supportText('support_outro_subtitle') }}</p>
            </div>
            <a href="{{ route('donation.show') }}" class="inline-flex min-h-12 flex-none items-center gap-2 rounded-md bg-white px-6 text-sm font-bold text-ink transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-dark">Wpłać darowiznę online</a>
        </div>
    </section>

    {{-- ══ Partnerzy ══ --}}
    @if ($partners->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-14" aria-labelledby="partners-heading">
            <h2 id="partners-heading" class="text-2xl font-bold text-ink md:text-3xl">Zaufali nam</h2>
            <p class="mt-2 text-muted">Działamy dzięki partnerom i instytucjom, które nas wspierają.</p>
            <ul class="mt-8 flex flex-wrap items-center gap-x-12 gap-y-8" role="list">
                @foreach ($partners as $partner)
                    @php
                        $logo = $partner->logo_url
                            ? '<img src="'.e($partner->logo_url).'" alt="'.e($partner->name).'" loading="lazy" class="h-14 w-auto max-w-[180px] object-contain grayscale transition hover:grayscale-0">'
                            : '<span class="text-lg font-bold text-ink">'.e($partner->name).'</span>';
                    @endphp
                    <li>
                        @if ($partner->url)
                            <a href="{{ $partner->url }}" target="_blank" rel="noopener" class="block rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2" title="{{ $partner->name }}">{!! $logo !!}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                        @else
                            {!! $logo !!}
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ══ Aktualności ══ --}}
    @if ($latestNews->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pb-16" aria-labelledby="latest-news-heading">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <h2 id="latest-news-heading" class="text-2xl font-bold text-ink md:text-3xl">Co ostatnio u nas słychać</h2>
                <a href="{{ site_route('news.index') }}" class="text-sm font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Wszystkie aktualności →</a>
            </div>
            <ul class="grid gap-6 sm:grid-cols-3" role="list">
                @foreach ($latestNews as $item)
                    <li>
                        <a href="{{ site_route('news.show', $item) }}" class="feer-card group flex h-full flex-col overflow-hidden rounded-lg bg-gray-50 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            @if ($item->imageUrlOrDefault())
                                <img src="{{ $item->imageUrlOrDefault() }}" alt="{{ $item->image_alt ?: '' }}" class="aspect-[16/10] w-full object-cover" loading="lazy">
                            @endif
                            <span class="flex flex-1 flex-col p-5">
                                @if ($item->published_at)<span class="text-xs font-bold uppercase tracking-widest text-muted">{{ $item->published_at->locale('pl')->isoFormat('D MMMM YYYY') }}</span>@endif
                                <span class="mt-2 text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $item->title }}</span>
                                @if ($item->excerpt)<span class="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-muted">{{ $item->excerpt }}</span>@endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Przypięty pasek na telefonie po przewinięciu nagłówka (nie zasłania treści — dolny odstęp strony). --}}
    <div id="support-sticky" hidden class="fixed inset-x-0 bottom-0 z-40 bg-ink px-4 py-3 md:hidden">
        <a href="{{ route('donation.show') }}" class="flex min-h-11 items-center justify-center rounded-md bg-white px-5 text-sm font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">Wpłać darowiznę online</a>
    </div>

    <script>
        (function () {
            const hero = document.getElementById('wesprzyj-hero');
            const bar = document.getElementById('support-sticky');
            if (! hero || ! bar || ! ('IntersectionObserver' in window)) return;
            new IntersectionObserver(function (entries) {
                bar.hidden = entries[0].isIntersecting;
                document.body.style.paddingBottom = entries[0].isIntersecting ? '' : '4.5rem';
            }).observe(hero);
        })();

        document.querySelectorAll('[data-copy-button]').forEach(function (button) {
            button.addEventListener('click', function () {
                navigator.clipboard.writeText(button.dataset.copyValue).then(function () {
                    const original = button.innerHTML;
                    button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Skopiowano';
                    setTimeout(function () { button.innerHTML = original; }, 2000);
                });
            });
        });
    </script>
@endsection
