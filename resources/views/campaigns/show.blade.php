@extends('layouts.site')

{{--
    Strona zbiórki. Wszystkie napisy z FundraisingTexts (kampania → ustawienia globalne → domyślne),
    zdjęcie i jego ALT z formularza kampanii. Wpłata: Przelewy24 (formularz), wplacam.ngo.pl
    (przycisk albo osadzony formularz) albo zewnętrzny link.
--}}
@section('title', ($campaign->meta_title ?: $campaign->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', (string) ($campaign->meta_description ?: $campaign->excerpt))
@if ($campaign->imageUrl)
    @section('og_image', $campaign->imageUrl)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => \App\Support\FundraisingTexts::for(null)->get('list_title'), 'url' => route('kampanie.index')],
        ['label' => $campaign->title, 'url' => null],
    ]])
@endsection

@php
    $display = 'vm-display';
    $wplacamUrl = $campaign->wplacamUrl();
    $isOpen = $campaign->isActive();
@endphp

@section('content')
<div class="{{ ($siteSettings->site_template ?? 'default') === 'feer' ? 'feer-flat' : '' }}">
<div class="mx-auto max-w-[1200px] px-4 py-10 lg:py-14">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">
        <article aria-labelledby="campaign-title">
            <h1 id="campaign-title" class="{{ $display }} mb-4 text-3xl leading-tight text-ink sm:text-4xl">{{ $campaign->title }}</h1>

            @if ($campaign->excerpt)
                <p class="mb-6 text-lg leading-relaxed text-ink">{{ $campaign->excerpt }}</p>
            @endif

            @if ($campaign->imageUrl)
                <img src="{{ $campaign->imageUrl }}" alt="{{ $campaign->image_alt ?? '' }}" class="mb-8 w-full rounded-2xl object-cover" style="max-height: 440px;">
            @endif

            @if ($campaign->content)
                <div class="prose max-w-none text-ink">
                    {!! \App\Support\SafeHtml::clean($campaign->content) !!}
                </div>
            @endif
        </article>

        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start" aria-labelledby="campaign-support-heading">
            <div class="rounded-2xl bg-gray-50 p-5 sm:p-6">
                <h2 id="campaign-support-heading" class="{{ $display }} mb-4 text-xl text-ink sm:text-2xl">{{ $texts->get('support_heading', $vars) }}</h2>

                @include('campaigns.partials.progress', ['campaign' => $campaign, 'texts' => $texts, 'compact' => false])

                @if ($campaign->ends_at)
                    <p class="mt-4 text-sm text-ink">
                        <i class="fa-regular fa-calendar mr-1" aria-hidden="true"></i>{{ $texts->get('ends_label', $vars) }}
                    </p>
                @endif

                @if (! $isOpen)
                    @foreach ($texts->paragraphs('closed_message', $vars) as $paragraph)
                        <p class="mt-4 font-bold text-ink" role="status">{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                @elseif ($campaign->payment_mode === 'wplacam' && $wplacamUrl)
                    @foreach ($texts->paragraphs('wplacam_note', $vars) as $paragraph)
                        <p class="mt-4 text-sm leading-relaxed text-ink">{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                    @if ($campaign->wplacam_display === 'embed')
                        <iframe src="{{ $wplacamUrl }}" title="{{ $texts->get('wplacam_embed_title', $vars) }}"
                                class="mt-4 h-[720px] w-full rounded-xl bg-white" loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    @endif
                    <a href="{{ $wplacamUrl }}" target="_blank" rel="noopener"
                       class="{{ $display }} mt-4 inline-flex min-h-12 items-center gap-2 rounded-2xl bg-brand-dark px-6 text-sm text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-solid fa-heart" aria-hidden="true"></i> {{ $texts->get('wplacam_cta', $vars) }}
                        <span class="sr-only">(otwiera się w nowej karcie)</span>
                    </a>
                @elseif ($campaign->payment_mode === 'external' && $campaign->donation_url)
                    <a href="{{ $campaign->donation_url }}" target="_blank" rel="noopener"
                       class="{{ $display }} mt-4 inline-flex min-h-12 items-center gap-2 rounded-2xl bg-brand-dark px-6 text-sm text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-solid fa-heart" aria-hidden="true"></i> {{ $texts->get('external_cta', $vars) }}
                        <span class="sr-only">(otwiera się w nowej karcie)</span>
                    </a>
                @endif
            </div>

            @if ($isOpen && $campaign->payment_mode === 'online')
                @include('campaigns.partials.donation-form', [
                    'campaign' => $campaign, 'texts' => $texts, 'vars' => $vars,
                    'amounts' => $amounts, 'onlineEnabled' => $onlineEnabled,
                ])
            @endif
        </aside>
    </div>

    @if ($campaign->show_recent_donations)
        <section class="mt-16" aria-labelledby="campaign-recent-heading">
            <h2 id="campaign-recent-heading" class="{{ $display }} mb-6 text-2xl text-ink sm:text-3xl">{{ $texts->get('recent_heading', $vars) }}</h2>
            @if ($recent->isEmpty())
                <p class="text-ink">{{ $texts->get('recent_empty', $vars) }}</p>
            @else
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                    @foreach ($recent as $donation)
                        <li class="flex items-start gap-4 rounded-2xl bg-gray-50 p-4">
                            <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-white text-xl text-brand-dark" aria-hidden="true">
                                <i class="fa-solid fa-heart"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="{{ $display }} block text-sm text-ink">{{ $donation->publicLabel() }}</span>
                                <span class="{{ $display }} block text-base text-brand-dark">{{ $donation->amountLabel() }}</span>
                                @if ($donation->paid_at)
                                    <span class="block text-xs text-ink"><time datetime="{{ $donation->paid_at->toDateString() }}">{{ $donation->paid_at->translatedFormat('j F Y') }}</time></span>
                                @endif
                                @if ($dedication = $donation->publicDedication())
                                    <span class="mt-1 block text-sm italic text-ink">„{{ $dedication }}”</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    <p class="mt-12">
        <a href="{{ route('kampanie.index') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">
            <i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i>{{ $texts->get('back_link', $vars) }}
        </a>
    </p>
</div>
</div>
@endsection
