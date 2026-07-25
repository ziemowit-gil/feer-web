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

            <div class="grid gap-6 md:grid-cols-2">
                <div class="flex flex-col rounded-lg border border-gray-200 p-6 shadow-sm">
                    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                        <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                    </span>
                    <h3 class="mb-2 text-lg font-bold text-ink">{{ $siteSettings->supportText('support_method2_title') }}</h3>
                    <p class="mb-4 flex-1 text-sm text-muted">{{ $siteSettings->supportText('support_method2_text') }}</p>

                    @if ($siteSettings->support_quick_transfer_url)
                        <a href="{{ $siteSettings->support_quick_transfer_url }}" target="_blank" rel="noopener"
                            class="mt-auto inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> {{ $siteSettings->supportText('support_method2_cta_label') }}
                        </a>
                    @else
                        <p class="mt-auto text-sm text-muted">Wkrótce dostępne.</p>
                    @endif
                </div>

                <div class="flex flex-col rounded-lg border border-gray-200 p-6 shadow-sm">
                    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                        <i class="fa-solid fa-mug-hot" aria-hidden="true"></i>
                    </span>
                    <h3 class="mb-2 text-lg font-bold text-ink">{{ $siteSettings->supportText('support_method3_title') }}</h3>
                    <p class="mb-4 flex-1 text-sm text-muted">{{ $siteSettings->supportText('support_method3_text') }}</p>

                    @if ($siteSettings->support_buycoffee_url)
                        <a href="{{ $siteSettings->support_buycoffee_url }}" target="_blank" rel="noopener"
                            class="mt-auto inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                            <i class="fa-solid fa-mug-hot" aria-hidden="true"></i> {{ $siteSettings->supportText('support_method3_cta_label') }}
                        </a>
                    @else
                        <p class="mt-auto text-sm text-muted">Wkrótce dostępne.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-10 rounded-lg bg-brand-light p-6 text-center">
            <p class="text-lg font-bold text-ink">{{ $siteSettings->supportText('support_outro_title') }}</p>
            <p class="mt-1 text-sm text-muted">{{ $siteSettings->supportText('support_outro_subtitle') }}</p>
        </div>
    </section>

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
