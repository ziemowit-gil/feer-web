@extends('layouts.site')

@section('title', $page->title . ' — ' . $siteSettings->site_name)
@section('meta_description', 'Strona o ograniczonym dostępie.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $page->title, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-md px-4 py-16">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-2xl text-brand" aria-hidden="true">
                <i class="fa-solid fa-user-lock"></i>
            </span>
            <h1 class="text-2xl font-bold text-ink">Brak dostępu</h1>
            <p class="mt-2 text-sm text-ink">Strona „{{ $page->title }}” jest dostępna tylko dla wybranych grup użytkowników. Twoje konto nie ma do niej uprawnień.</p>
            <a href="{{ route('home') }}" class="mt-6 inline-block rounded-lg bg-brand px-5 py-2.5 font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Wróć na stronę główną</a>
        </div>
    </section>
@endsection
