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

@section('content')
    <section class="relative mb-14 flex min-h-[18rem] items-end overflow-hidden bg-brand bg-linear-to-br from-brand to-brand-dark text-white md:min-h-[22rem]"
        @if ($siteSettings->supportImageUrl())
            style="background-image: linear-gradient(0deg, rgba(0,0,0,.7), rgba(0,0,0,.2)), url('{{ $siteSettings->supportImageUrl() }}'); background-size: cover; background-position: center;"
        @endif>
        <div class="relative mx-auto w-full max-w-5xl px-4 py-12 md:py-16">
            <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-sm font-bold backdrop-blur">
                <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i> {{ $siteSettings->supportText('support_hero_badge') }}
            </span>
            <h1 class="max-w-2xl text-3xl font-bold leading-tight md:text-4xl">{{ $siteSettings->supportText('support_hero_title') }}</h1>
            <p class="mt-3 max-w-xl text-white/90">{{ $siteSettings->supportText('support_hero_subtitle') }}</p>
            @if ($siteSettings->support_quick_transfer_url)
                <a href="{{ $siteSettings->support_quick_transfer_url }}" target="_blank" rel="noopener"
                    class="mt-6 inline-flex items-center gap-2 rounded bg-white px-5 py-2.5 text-sm font-bold text-brand transition hover:bg-white/90">
                    <i class="fa-solid fa-bolt" aria-hidden="true"></i> {{ $siteSettings->supportText('support_hero_cta_label') }}
                </a>
            @endif
        </div>
    </section>

    @if ($siteSettings->hasFundraiser())
        @php $progress = $siteSettings->fundraiserProgress(); @endphp
        <section class="mx-auto -mt-8 mb-14 max-w-3xl px-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-xl font-bold text-ink">{{ $siteSettings->support_fundraiser_title }}</h2>
                @if ($siteSettings->support_fundraiser_text)
                    <p class="mt-2 text-muted">{{ $siteSettings->support_fundraiser_text }}</p>
                @endif

                <div class="mt-5">
                    <div class="mb-1 flex items-end justify-between gap-2 text-sm">
                        <span class="font-bold text-ink">{{ number_format((int) $siteSettings->support_fundraiser_raised, 0, ',', ' ') }} zł</span>
                        <span class="text-muted">z {{ number_format((int) $siteSettings->support_fundraiser_goal, 0, ',', ' ') }} zł</span>
                    </div>
                    <div class="h-4 w-full overflow-hidden rounded-full bg-gray-100"
                        role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"
                        aria-label="Postęp zbiórki: {{ $progress }}%">
                        <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="mt-1 text-right text-xs font-bold text-brand">{{ $progress }}%</p>
                </div>

                @if ($siteSettings->support_fundraiser_url)
                    <a href="{{ $siteSettings->support_fundraiser_url }}" target="_blank" rel="noopener"
                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-brand px-6 py-3 font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <i class="fa-solid fa-heart" aria-hidden="true"></i> {{ $siteSettings->support_fundraiser_cta_label ?: 'Wesprzyj zbiórkę' }}
                    </a>
                @endif
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-5xl px-4">
        <div class="mx-auto mb-10 max-w-2xl text-center">
            <h2 class="text-2xl font-bold text-ink">{{ $siteSettings->supportText('support_benefits_title') }}</h2>
            <p class="mt-2 text-muted">{{ $siteSettings->supportText('support_benefits_subtitle') }}</p>
        </div>
        <div class="grid gap-8 sm:grid-cols-3">
            @foreach (['1', '2', '3'] as $i)
                <div class="text-center">
                    <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-2xl text-brand" aria-hidden="true"><i class="{{ $siteSettings->supportText('support_benefit'.$i.'_icon') }}"></i></span>
                    <h3 class="mb-1 font-bold text-ink">{{ $siteSettings->supportText('support_benefit'.$i.'_title') }}</h3>
                    <p class="text-sm text-muted">{{ $siteSettings->supportText('support_benefit'.$i.'_text') }}</p>
                </div>
            @endforeach
        </div>
    </section>

    @if ($stats->isNotEmpty())
        <section class="mx-auto mt-14 max-w-5xl px-4">
            <div class="rounded-2xl bg-brand-light p-8">
                <h2 class="mb-6 text-center text-2xl font-bold text-ink">Twoje wsparcie napędza konkretne działania</h2>
                @php
                    // Literalne klasy — Tailwind JIT musi je zobaczyć w źródle
                    // (lg:grid-cols-2 lg:grid-cols-3 lg:grid-cols-4).
                    $statCols = [
                        2 => 'lg:grid-cols-2',
                        3 => 'lg:grid-cols-3',
                        4 => 'lg:grid-cols-4',
                        5 => 'lg:grid-cols-5',
                        6 => 'lg:grid-cols-3',
                    ][min(6, max(2, $stats->count()))];
                @endphp
                <dl class="grid gap-6 text-center sm:grid-cols-2 md:grid-cols-3 {{ $statCols }}">
                    @foreach ($stats as $stat)
                        <div>
                            <dt class="sr-only">{{ $stat['label'] }}</dt>
                            <dd class="text-3xl font-bold text-brand md:text-4xl">{{ $stat['value'] }}</dd>
                            <p class="mt-1 text-sm text-muted">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    @if ($photos->isNotEmpty())
        <section class="mx-auto max-w-5xl px-4 pt-14">
            <h2 class="mb-6 text-2xl font-bold text-ink">Zobacz nas w działaniu</h2>
            <div class="grid auto-rows-[8rem] grid-cols-2 gap-3 sm:auto-rows-[10rem] sm:grid-cols-4">
                @foreach ($photos as $i => $photo)
                    <div class="overflow-hidden rounded-lg {{ $i === 0 ? 'col-span-2 row-span-2' : '' }}">
                        <img src="{{ $photo->getUrl() }}" alt="{{ $siteSettings->site_name }} w działaniu"
                            class="h-full w-full object-cover transition duration-300 hover:scale-105" loading="lazy">
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (filled($siteSettings->support_testimonial_quote))
        <section class="mx-auto max-w-3xl px-4 pt-14">
            <figure class="rounded-2xl bg-brand-light p-8 text-center">
                <i class="fa-solid fa-quote-left text-2xl text-brand" aria-hidden="true"></i>
                <blockquote class="mt-3 text-lg font-medium text-ink">{{ $siteSettings->support_testimonial_quote }}</blockquote>
                @if ($siteSettings->support_testimonial_author)
                    <figcaption class="mt-4 text-sm text-muted">
                        <span class="font-bold text-ink">{{ $siteSettings->support_testimonial_author }}</span>@if ($siteSettings->support_testimonial_role), {{ $siteSettings->support_testimonial_role }}@endif
                    </figcaption>
                @endif
            </figure>
        </section>
    @endif

    <section class="mx-auto max-w-5xl px-4 py-14">
        <h2 class="mb-6 text-2xl font-bold text-ink">{{ $siteSettings->supportText('support_methods_title') }}</h2>

        @if ($siteSettings->support_intro)
            <div class="prose mb-8 max-w-none text-ink">{!! $siteSettings->support_intro !!}</div>
        @endif

        <div class="space-y-6">
            {{-- Przelew tradycyjny — pełna szerokość, aby długie numery kont nigdy nie wychodziły poza kartę. --}}
            <div class="rounded-lg border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                        <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                    </span>
                    <h3 class="text-lg font-bold text-ink">{{ $siteSettings->supportText('support_method1_title') }}</h3>
                </div>

                @if ($siteSettings->bank_account_number)
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="rounded-lg bg-gray-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ $siteSettings->supportText('support_method1_account_label') }}</p>
                            <p class="mt-1 break-words font-mono text-base font-bold text-ink">{{ $siteSettings->bank_account_number }}</p>
                            <button type="button" data-copy-button data-copy-value="{{ $siteSettings->bank_account_number }}"
                                class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                            </button>
                        </div>

                        @if ($siteSettings->bank_account_tax_number)
                            <div class="rounded-lg bg-gray-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ $siteSettings->supportText('support_method1_tax_label') }}</p>
                                <p class="mt-1 break-words font-mono text-base font-bold text-ink">{{ $siteSettings->bank_account_tax_number }}</p>
                                <button type="button" data-copy-button data-copy-value="{{ $siteSettings->bank_account_tax_number }}"
                                    class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                                </button>
                            </div>
                        @endif

                        @if ($siteSettings->supportText('support_transfer_title') !== '')
                            <div class="rounded-lg bg-gray-50 p-4 sm:col-span-2">
                                <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ $siteSettings->supportText('support_method1_transfer_label') }}</p>
                                <p class="mt-1 break-words text-base font-bold text-ink">{{ $siteSettings->supportText('support_transfer_title') }}</p>
                                <button type="button" data-copy-button data-copy-value="{{ $siteSettings->supportText('support_transfer_title') }}"
                                    class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj tytuł
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-sm text-muted">Numer konta zostanie wkrótce podany.</p>
                @endif
            </div>

            @php
                // Pokazujemy tylko metody z podanym linkiem i dopasowujemy liczbę
                // kolumn do ich liczby (literalne klasy — dla skanera Tailwind).
                $onlineMethods = collect([
                    $siteSettings->support_quick_transfer_url,
                    $siteSettings->support_wplacam_url,
                    $siteSettings->support_buycoffee_url,
                ])->filter()->count();
                $methodCols = [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 lg:grid-cols-3'][$onlineMethods] ?? 'sm:grid-cols-2 lg:grid-cols-3';
            @endphp

            @if ($onlineMethods > 0)
                <div class="grid gap-6 {{ $methodCols }}">
                    @if ($siteSettings->support_quick_transfer_url)
                        <div class="flex flex-col rounded-lg border border-gray-200 p-6 shadow-sm">
                            <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                                <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                            </span>
                            <h3 class="mb-2 text-lg font-bold text-ink">{{ $siteSettings->supportText('support_method2_title') }}</h3>
                            <p class="mb-4 flex-1 text-sm text-muted">{{ $siteSettings->supportText('support_method2_text') }}</p>
                            <a href="{{ $siteSettings->support_quick_transfer_url }}" target="_blank" rel="noopener"
                                class="mt-auto inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ $siteSettings->supportText('support_method2_cta_label') }}
                            </a>
                        </div>
                    @endif

                    @if ($siteSettings->support_wplacam_url)
                        <div class="flex flex-col rounded-lg border border-gray-200 p-6 shadow-sm">
                            <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                                <i class="fa-solid fa-heart" aria-hidden="true"></i>
                            </span>
                            <h3 class="mb-2 text-lg font-bold text-ink">{{ $siteSettings->supportText('support_method4_title') }}</h3>
                            <p class="mb-4 flex-1 text-sm text-muted">{{ $siteSettings->supportText('support_method4_text') }}</p>
                            <a href="{{ $siteSettings->support_wplacam_url }}" target="_blank" rel="noopener"
                                class="mt-auto inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ $siteSettings->supportText('support_method4_cta_label') }}
                            </a>
                        </div>
                    @endif

                    @if ($siteSettings->support_buycoffee_url)
                        <div class="flex flex-col rounded-lg border border-gray-200 p-6 shadow-sm">
                            <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                                <i class="fa-solid fa-mug-hot" aria-hidden="true"></i>
                            </span>
                            <h3 class="mb-2 text-lg font-bold text-ink">{{ $siteSettings->supportText('support_method3_title') }}</h3>
                            <p class="mb-4 flex-1 text-sm text-muted">{{ $siteSettings->supportText('support_method3_text') }}</p>
                            <a href="{{ $siteSettings->support_buycoffee_url }}" target="_blank" rel="noopener"
                                class="mt-auto inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                                <i class="fa-solid fa-mug-hot" aria-hidden="true"></i> {{ $siteSettings->supportText('support_method3_cta_label') }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-10 rounded-lg bg-brand-light p-6 text-center">
            <p class="text-lg font-bold text-ink">{{ $siteSettings->supportText('support_outro_title') }}</p>
            <p class="mt-1 text-sm text-muted">{{ $siteSettings->supportText('support_outro_subtitle') }}</p>
        </div>
    </section>

    @if ($partners->isNotEmpty())
        <section class="bg-gray-50 px-4 py-14" aria-label="Zaufali nam">
            <div class="mx-auto max-w-5xl text-center">
                <h2 class="mb-2 text-2xl font-bold text-ink">Zaufali nam</h2>
                <p class="mb-8 text-muted">Działamy dzięki partnerom i instytucjom, które nas wspierają.</p>
                <ul class="flex flex-wrap items-center justify-center gap-x-12 gap-y-8">
                    @foreach ($partners as $partner)
                        @php
                            $logo = $partner->logo_url
                                ? '<img src="'.e($partner->logo_url).'" alt="'.e($partner->name).'" loading="lazy" class="h-16 w-auto max-w-[180px] object-contain">'
                                : '<span class="text-lg font-bold text-ink">'.e($partner->name).'</span>';
                        @endphp
                        <li>
                            @if ($partner->url)
                                <a href="{{ $partner->url }}" target="_blank" rel="noopener"
                                    class="block transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                    title="{{ $partner->name }}">{!! $logo !!}</a>
                            @else
                                {!! $logo !!}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($latestNews->isNotEmpty())
        <section class="mx-auto max-w-5xl px-4 pb-14">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <h2 class="text-2xl font-bold text-ink">Co ostatnio u nas słychać</h2>
                <a href="{{ route('news.index') }}" class="text-sm font-bold text-brand hover:text-brand-dark">Wszystkie aktualności →</a>
            </div>
            <div class="grid gap-6 sm:grid-cols-3">
                @foreach ($latestNews as $item)
                    <a href="{{ route('news.show', $item) }}" class="group flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        @if ($item->imageUrlOrDefault())
                            <img src="{{ $item->imageUrlOrDefault() }}" alt="{{ $item->image_alt ?: '' }}" class="h-40 w-full object-cover" loading="lazy">
                        @endif
                        <div class="flex flex-1 flex-col p-4">
                            @if ($item->published_at)
                                <p class="text-xs text-muted">{{ $item->published_at->locale('pl')->isoFormat('D MMMM YYYY') }}</p>
                            @endif
                            <h3 class="mt-1 font-bold text-ink group-hover:text-brand">{{ $item->title }}</h3>
                            @if ($item->excerpt)
                                <p class="mt-2 line-clamp-3 flex-1 text-sm text-muted">{{ $item->excerpt }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <script>
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
