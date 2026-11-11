{{--
    Aktualność w szablonie „bu2”: wygląda jak zwykła podstrona (menu boczne, tytuł z linią, treść, „Drukuj”).
    Data publikacji jest w pasie pod treścią, obok „Drukuj”.
--}}
@extends('templates.bu2.layout')

@section('title', ($news->meta_title ?: $news->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $news->meta_description ?: ($news->excerpt ?: meta_excerpt($news->content)))
@if ($news->image_url)
    @section('og_image', $news->image_url)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => route('news.index')],
        ['label' => $news->title, 'url' => null],
    ]])
@endsection

@section('bu2_main')
    @if (! empty($preview))
        <p class="mb-6 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-ink">Podgląd — ten wpis nie jest jeszcze opublikowany.</p>
    @endif
    <article>
        <h1 class="bu2-title">{{ $news->title }}</h1>
        <div id="article-text" data-news-content class="bu2-body prose">@shortcodes($news->content)</div>
        @include('templates.bu2.partials.tools', ['date' => $news->published_at])
    </article>
@endsection
