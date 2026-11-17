@extends('layouts.site')

{{-- Lista zbiórek. Teksty: Zbiórki → Teksty i podziękowania (grupa „Lista zbiórek”). --}}
@section('title', $texts->get('list_title') . ' — ' . $siteSettings->site_name)
@section('meta_description', \Illuminate\Support\Str::limit($texts->get('list_intro'), 160))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $texts->get('list_title'), 'url' => null],
    ]])
@endsection

@php $display = 'vm-display'; @endphp

@section('content')
<div class="{{ ($siteSettings->site_template ?? 'default') === 'feer' ? 'feer-flat' : '' }}">
    <section class="mx-auto max-w-[1200px] px-4 py-10 lg:py-14" aria-labelledby="campaigns-title">
        <h1 id="campaigns-title" class="{{ $display }} mb-4 text-3xl text-ink sm:text-4xl">{{ $texts->get('list_title') }}</h1>
        @foreach ($texts->paragraphs('list_intro') as $paragraph)
            <p class="mb-3 max-w-3xl text-lg leading-relaxed text-ink">{!! nl2br(e($paragraph)) !!}</p>
        @endforeach

        @if ($open->isEmpty() && $finished->isEmpty())
            <p class="mt-8 text-ink">{{ $texts->get('list_empty') }}</p>
        @else
            @if ($open->isEmpty())
                <p class="mt-8 text-ink">{{ $texts->get('list_empty') }}</p>
            @else
                <ul class="mt-10 grid gap-8 md:grid-cols-2 lg:grid-cols-3" role="list">
                    @foreach ($open as $campaign)
                        @include('campaigns.partials.card', ['campaign' => $campaign, 'texts' => $texts, 'headingLevel' => 2])
                    @endforeach
                </ul>
            @endif

            @if ($finished->isNotEmpty())
                <h2 class="{{ $display }} mb-6 mt-16 text-2xl text-ink">{{ $texts->get('list_finished_heading') }}</h2>
                <ul class="grid gap-8 md:grid-cols-2 lg:grid-cols-3" role="list">
                    @foreach ($finished as $campaign)
                        @include('campaigns.partials.card', ['campaign' => $campaign, 'texts' => $texts, 'headingLevel' => 3])
                    @endforeach
                </ul>
            @endif
        @endif
    </section>
</div>
@endsection
