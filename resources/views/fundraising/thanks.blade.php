@extends('layouts.site')

{{--
    Strona po powrocie z płatności — wspólna dla zbiórek i darowizny ogólnej.
    Trzy warianty: sukces (zdjęcie/film, podsumowanie, udostępnianie, PDF, ulga podatkowa),
    w toku (odśwież) i odrzucona (ponów płatność). Teksty i grafiki: FundraisingTexts.
    Parametry: $donation, $campaign (lub null), $texts, $vars, $retryUrl, $refreshUrl, $shareUrl, $canRetry.
--}}
@php
    $display = 'vm-display';
    $state = $donation->isPaid() ? 'success' : ($donation->status === 'pending' ? 'pending' : 'failed');
    $title = match ($state) {
        'success' => $texts->get('success_title', $vars),
        'pending' => $texts->get('pending_title', $vars),
        default => $texts->get('failed_title', $vars),
    };
    $imageUrl = $state === 'success' ? $texts->imageUrl('page') : null;
    $videoUrl = $state === 'success' ? $texts->videoEmbedUrl() : null;
    $shareText = $texts->get('share_text', $vars);
    $shareLinks = [
        ['Facebook', 'fa-brands fa-facebook-f', 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl)],
        ['LinkedIn', 'fa-brands fa-linkedin-in', 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($shareUrl)],
        ['X', 'fa-brands fa-x-twitter', 'https://twitter.com/intent/tweet?text=' . urlencode($shareText) . '&url=' . urlencode($shareUrl)],
        ['E-mail', 'fa-solid fa-envelope', 'mailto:?subject=' . rawurlencode($vars['kampania']) . '&body=' . rawurlencode($shareText . ' ' . $shareUrl)],
    ];
@endphp

@section('title', $title . ' — ' . $siteSettings->site_name)

@section('content')
<div class="{{ ($siteSettings->site_template ?? 'default') === 'feer' ? 'feer-flat' : '' }}">
<div class="mx-auto max-w-3xl px-4 py-12 lg:py-16">
    @if ($state === 'success')
        <h1 class="{{ $display }} mb-3 text-3xl leading-tight text-ink sm:text-4xl">{{ $title }}</h1>
        <p class="mb-6 text-lg font-semibold text-ink">{{ $texts->get('success_lead', $vars) }}</p>

        @if ($videoUrl)
            <div class="mb-8 aspect-video overflow-hidden rounded-2xl bg-gray-100">
                <iframe src="{{ $videoUrl }}" title="{{ $texts->get('success_video_title', $vars) }}" class="h-full w-full"
                        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
            </div>
        @elseif ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $texts->get('success_image_alt', $vars) }}" class="mb-8 w-full rounded-2xl object-cover" style="max-height: 420px;">
        @endif

        <div class="space-y-4 text-lg leading-relaxed text-ink">
            @foreach ($texts->paragraphs('success_body', $vars) as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @endforeach
        </div>

        <section class="mt-10 rounded-2xl bg-gray-50 p-5 sm:p-6" aria-labelledby="thanks-summary-heading">
            <h2 id="thanks-summary-heading" class="{{ $display }} mb-4 text-xl text-ink">{{ $texts->get('summary_heading', $vars) }}</h2>
            <dl class="grid gap-x-6 gap-y-3 text-ink sm:grid-cols-[auto_1fr]">
                <dt class="font-bold">Kwota</dt><dd>{{ $donation->amountLabel() }}</dd>
                <dt class="font-bold">Cel</dt><dd>{{ $vars['kampania'] }}</dd>
                <dt class="font-bold">Rodzaj</dt><dd>{{ \App\Models\Donation::TYPES[$donation->type] ?? \App\Models\Donation::TYPES['one_time'] }}</dd>
                <dt class="font-bold">Data</dt><dd>{{ ($donation->paid_at ?? now())->format('d.m.Y') }}</dd>
                @if ($donation->company_name)
                    <dt class="font-bold">Firma</dt><dd>{{ $donation->company_name }}@if ($donation->nip), NIP {{ $donation->nip }}@endif</dd>
                @endif
            </dl>
            @if ($donation->isRecurring() && filled($vars['konto']))
                @foreach ($texts->paragraphs('recurring_thanks', $vars) as $paragraph)
                    <p class="mt-4 text-ink">{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            @endif
            <a href="{{ route('donation.receipt', $donation) }}"
               class="{{ $display }} mt-5 inline-flex min-h-12 items-center gap-2 rounded-2xl bg-brand-dark px-6 text-sm text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> {{ $texts->get('pdf_cta', $vars) }}
            </a>
        </section>

        <section class="mt-8" aria-labelledby="thanks-tax-heading">
            <h2 id="thanks-tax-heading" class="{{ $display }} mb-3 text-xl text-ink">{{ $texts->get('tax_heading', $vars) }}</h2>
            @foreach ($texts->paragraphs('tax_body', $vars) as $paragraph)
                <p class="mb-3 leading-relaxed text-ink">{!! nl2br(e($paragraph)) !!}</p>
            @endforeach
        </section>

        <section class="mt-8" aria-labelledby="thanks-share-heading">
            <h2 id="thanks-share-heading" class="{{ $display }} mb-4 text-xl text-ink">{{ $texts->get('share_heading', $vars) }}</h2>
            <ul class="flex flex-wrap gap-3" role="list">
                @foreach ($shareLinks as [$name, $icon, $href])
                    <li>
                        <a href="{{ $href }}" @if (! str_starts_with($href, 'mailto:')) target="_blank" rel="noopener" @endif
                           class="inline-flex min-h-11 items-center gap-2 rounded-2xl bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            <i class="{{ $icon }}" aria-hidden="true"></i> {{ $name }}
                            @unless (str_starts_with($href, 'mailto:'))<span class="sr-only">(udostępnij, otwiera się w nowej karcie)</span>@endunless
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @elseif ($state === 'pending')
        <h1 class="{{ $display }} mb-4 text-3xl leading-tight text-ink">{{ $title }}</h1>
        <div class="space-y-4 text-lg leading-relaxed text-ink" role="status">
            @foreach ($texts->paragraphs('pending_body', $vars) as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @endforeach
        </div>
        <a href="{{ $refreshUrl }}"
           class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-2xl bg-gray-100 px-6 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-rotate" aria-hidden="true"></i> {{ $texts->get('pending_refresh', $vars) }}
        </a>
    @else
        <h1 class="{{ $display }} mb-4 text-3xl leading-tight text-ink">{{ $title }}</h1>
        <div class="space-y-4 text-lg leading-relaxed text-ink" role="alert">
            @foreach ($texts->paragraphs('failed_body', $vars) as $paragraph)
                <p>{!! nl2br(e($paragraph)) !!}</p>
            @endforeach
        </div>
        @if (session('donation_error'))
            <p class="mt-4 rounded-xl bg-red-50 p-4 text-sm font-semibold text-red-800" role="alert">{{ session('donation_error') }}</p>
        @endif
        @if ($canRetry)
            <form method="POST" action="{{ $retryUrl }}" class="mt-6">
                @csrf
                <button type="submit"
                        class="{{ $display }} inline-flex min-h-12 items-center gap-2 rounded-2xl bg-brand-dark px-6 text-sm text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> {{ $texts->get('retry_cta', $vars) }}
                </button>
            </form>
        @endif
    @endif

    <p class="mt-10">
        @if ($campaign)
            <a href="{{ route('kampanie.show', $campaign->slug) }}" class="mr-6 font-bold text-brand-dark underline underline-offset-4 hover:no-underline">{{ $campaign->title }}</a>
        @endif
        <a href="{{ site_route('home') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">{{ $texts->get('home_cta', $vars) }}</a>
    </p>
</div>
</div>
@endsection
