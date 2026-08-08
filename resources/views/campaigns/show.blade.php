@extends('layouts.site')

@section('title', ($campaign->meta_title ?: $campaign->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $campaign->meta_description ?: $campaign->excerpt)
@if ($campaign->imageUrl)
    @section('og_image', $campaign->imageUrl)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Kampanie zbiórkowe', 'url' => route('kampanie.index')],
        ['label' => $campaign->title, 'url' => null],
    ]])
@endsection

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-4 text-3xl font-bold text-ink">{{ $campaign->title }}</h1>

        @if ($campaign->excerpt)
            <p class="mb-6 text-lg text-muted">{{ $campaign->excerpt }}</p>
        @endif

        @if ($campaign->imageUrl)
            <img src="{{ $campaign->imageUrl }}" alt="" class="mb-8 w-full rounded-xl object-cover" style="max-height: 400px;">
        @endif

        {{-- Progress bar --}}
        @if ($campaign->goal_amount > 0)
            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-3 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-3xl font-bold text-ink">{{ number_format($campaign->collected_amount, 0, ',', ' ') }} zł</p>
                        <p class="text-sm text-muted">zebrano z celu {{ number_format($campaign->goal_amount, 0, ',', ' ') }} zł</p>
                    </div>
                    <p class="text-2xl font-bold {{ $campaign->isGoalReached() ? 'text-green-600' : 'text-brand' }}">
                        {{ $campaign->progressPercent() }}%
                    </p>
                </div>

                <div class="mb-4 h-4 w-full overflow-hidden rounded-full bg-gray-200"
                    role="progressbar"
                    aria-valuenow="{{ $campaign->progressPercent() }}"
                    aria-valuemin="0" aria-valuemax="100"
                    aria-label="Zebrano {{ $campaign->progressPercent() }}% celu zbiórki">
                    <div class="h-full rounded-full transition-all {{ $campaign->isGoalReached() ? 'bg-green-500' : 'bg-brand' }}"
                        style="width: {{ $campaign->progressPercent() }}%"></div>
                </div>

                @if ($campaign->isGoalReached())
                    <p class="mb-4 font-bold text-green-700">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Cel zbiórki został osiągnięty. Dziękujemy!
                    </p>
                @endif

                @if ($campaign->ends_at)
                    <p class="mb-4 text-sm text-muted">
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                        Zbiórka trwa do {{ $campaign->ends_at->format('d.m.Y') }}
                        @if ($campaign->ends_at->isPast())
                            <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">zakończona</span>
                        @elseif ($campaign->ends_at->diffInDays(now()) <= 7)
                            <span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-700">
                                zostało {{ $campaign->ends_at->diffForHumans() }}
                            </span>
                        @endif
                    </p>
                @endif

                @if ($campaign->donation_url && $campaign->isActive())
                    <a href="{{ $campaign->donation_url }}" target="_blank" rel="noopener"
                        class="inline-block rounded-lg bg-brand px-8 py-3 text-base font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-solid fa-heart" aria-hidden="true"></i> Wesprzyj kampanię
                    </a>
                @endif
            </div>
        @elseif ($campaign->donation_url && $campaign->isActive())
            <div class="mb-8">
                <a href="{{ $campaign->donation_url }}" target="_blank" rel="noopener"
                    class="inline-block rounded-lg bg-brand px-8 py-3 text-base font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-heart" aria-hidden="true"></i> Wesprzyj kampanię
                </a>
            </div>
        @endif

        @if ($campaign->content)
            <div class="prose max-w-none text-ink">
                {!! $campaign->content !!}
            </div>
        @endif

        <div class="mt-10 border-t border-gray-200 pt-6">
            <a href="{{ route('kampanie.index') }}" class="text-sm font-bold text-brand hover:text-brand-dark">
                ← Wróć do kampanii
            </a>
        </div>
    </article>
@endsection
