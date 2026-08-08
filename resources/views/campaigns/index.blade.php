@extends('layouts.site')

@section('title', 'Kampanie zbiórkowe — ' . $siteSettings->site_name)
@section('meta_description', 'Wesprzyj nasze kampanie i pomóż nam realizować działania na rzecz osób z niepełnosprawnościami.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Kampanie zbiórkowe', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Kampanie zbiórkowe</h1>
        <p class="mb-10 text-muted">Wesprzyj nasze działania. Każda złotówka pomaga nam pomagać.</p>

        @if ($campaigns->isEmpty())
            <p class="text-muted">Aktualnie nie prowadzimy żadnych zbiórek.</p>
        @else
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($campaigns as $campaign)
                    <article class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        @if ($campaign->imageUrl)
                            <a href="{{ route('kampanie.show', $campaign->slug) }}" tabindex="-1" aria-hidden="true">
                                <img src="{{ $campaign->imageUrl }}" alt="" class="h-48 w-full object-cover">
                            </a>
                        @endif

                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="mb-2 text-lg font-bold text-ink">
                                <a href="{{ route('kampanie.show', $campaign->slug) }}" class="hover:text-brand focus-visible:underline">
                                    {{ $campaign->title }}
                                </a>
                            </h2>

                            @if ($campaign->excerpt)
                                <p class="mb-4 flex-1 text-sm text-muted">{{ $campaign->excerpt }}</p>
                            @endif

                            @if ($campaign->goal_amount > 0)
                                <div class="mt-auto">
                                    <div class="mb-1 h-2.5 w-full overflow-hidden rounded-full bg-gray-200"
                                        role="progressbar"
                                        aria-valuenow="{{ $campaign->progressPercent() }}"
                                        aria-valuemin="0" aria-valuemax="100"
                                        aria-label="Zebrano {{ $campaign->progressPercent() }}% celu">
                                        <div class="h-full rounded-full transition-all {{ $campaign->isGoalReached() ? 'bg-green-500' : 'bg-brand' }}"
                                            style="width: {{ $campaign->progressPercent() }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-muted">
                                        <span>
                                            <strong class="text-ink">{{ number_format($campaign->collected_amount, 0, ',', ' ') }} zł</strong>
                                            z {{ number_format($campaign->goal_amount, 0, ',', ' ') }} zł
                                        </span>
                                        <span class="{{ $campaign->isGoalReached() ? 'font-bold text-green-700' : '' }}">
                                            {{ $campaign->progressPercent() }}%
                                        </span>
                                    </div>
                                </div>
                            @endif

                            @if ($campaign->ends_at)
                                <p class="mt-3 text-xs text-muted">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    Zbiórka do {{ $campaign->ends_at->format('d.m.Y') }}
                                </p>
                            @endif

                            <a href="{{ route('kampanie.show', $campaign->slug) }}"
                                class="mt-4 inline-block rounded bg-brand px-4 py-2 text-center text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                Dowiedz się więcej
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
