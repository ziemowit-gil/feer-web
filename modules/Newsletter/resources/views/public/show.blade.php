@extends('layouts.site')

@section('title', ($form?->heading ?? 'Newsletter') . ' — ' . $siteSettings->site_name)

@section('content')
    @if ($form)
        <div class="mx-auto max-w-5xl px-4 py-10 md:py-16">
            <nav aria-label="Okruszki" class="mb-6 text-sm text-muted"><a href="{{ route('home') }}" class="underline hover:text-ink">Strona główna</a> › <span aria-current="page">Newsletter</span></nav>
            <h1 class="sr-only">Newsletter</h1>
            <x-newsletter-widget :form="$form" style="band" source="page_newsletter" />
            <p class="mt-8 text-sm text-muted">Masz już subskrypcję? Link do zmiany preferencji i wypisu znajdziesz w stopce każdej wiadomości.</p>
        </div>
    @elseif ($embedCode)
        <section class="mx-auto max-w-2xl px-4 py-16 text-center" aria-labelledby="newsletter-heading">
            <h1 id="newsletter-heading" class="mb-2 text-2xl font-extrabold text-ink md:text-3xl">Newsletter</h1>
            <div class="newsletter-embed">{!! $embedCode !!}</div>
        </section>
    @else
        <section class="mx-auto max-w-2xl px-4 py-16 text-center">
            <h1 class="mb-2 text-2xl font-extrabold text-ink">Newsletter</h1>
            <p class="text-muted">Zapisy na newsletter nie są obecnie dostępne.</p>
        </section>
    @endif
@endsection
