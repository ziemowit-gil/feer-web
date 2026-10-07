@extends('layouts.site')

@section('title', 'Newsletter — ' . $siteSettings->site_name)
@section('meta_description', 'Zapisz się do newslettera ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Newsletter', 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $feer = ($siteSettings->site_template ?? 'default') === 'feer';
        $inp = $feer ? 'w-full rounded-t-md rounded-b-none border-0 border-b-2 border-gray-500 bg-gray-100 text-base text-ink focus:border-brand focus:bg-white focus:outline-none focus:ring-0' : 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand';
        $btn = $feer ? 'rounded-md' : 'rounded';
        $linkCls = $feer ? 'text-brand-dark underline underline-offset-4 hover:text-ink' : 'text-brand hover:text-brand-dark';
    @endphp
    <section class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-6 text-3xl font-bold text-ink">Newsletter</h1>
        @if ($feer)<span class="mb-5 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif

        @if ($siteSettings->newsletter_code)
            <div class="newsletter-embed">{!! $siteSettings->newsletter_code !!}</div>
        @else
            <p class="text-muted">Formularz zapisu na newsletter nie został jeszcze skonfigurowany.</p>
        @endif
    </section>
@endsection
