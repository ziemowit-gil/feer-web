@extends('layouts.site')

@section('title', 'Newsletter — ' . $siteSettings->site_name)
@section('meta_description', 'Zapisz się do newslettera ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Newsletter', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-6 text-3xl font-bold text-ink">Newsletter</h1>

        @if ($siteSettings->newsletter_code)
            <div class="newsletter-embed">{!! $siteSettings->newsletter_code !!}</div>
        @else
            <p class="text-muted">Formularz zapisu na newsletter nie został jeszcze skonfigurowany.</p>
        @endif
    </section>
@endsection
