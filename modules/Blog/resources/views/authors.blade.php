@extends('layouts.site')

{{-- Lista autorów Wiem FEER (z co najmniej jednym opublikowanym wpisem). Dane: BlogController::authors(). --}}

@php
    $blogSite = (bool) request()->attributes->get('blog_site');
    $blogName = $blogSite ? $siteSettings->site_name : 'Wiem FEER';
@endphp

@section('title', 'Autorzy — ' . $blogName)
@section('meta_description', 'Autorki i autorzy bloga Wiem FEER.')

@include('blog::partials.feed-link')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $blogName, 'url' => route('blog.index')],
        ['label' => 'Autorzy', 'url' => null],
    ]])
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0">
                <h1 class="text-3xl font-bold text-ink md:text-4xl">Autorzy</h1>
                <span class="mb-8 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>

                @if ($authors->isEmpty())
                    <p class="rounded-md bg-gray-50 p-4 text-ink">Nie ma jeszcze autorów z opublikowanymi wpisami.</p>
                @else
                    <ul class="space-y-4">
                        @foreach ($authors as $author)
                            <li>@include('blog::partials.author-box', ['author' => $author, 'headingLevel' => 2])</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @include('blog::partials.sidebar', ['q' => ''])
        </div>
    </div>
@endsection
