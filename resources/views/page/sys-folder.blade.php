@extends('layouts.site')

{{-- Typ „Folder systemowy” — widok tylko w podglądzie z panelu (na żywo adres daje 404, PageController::show). --}}

@section('title', $page->title . ' — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-16 text-center" aria-labelledby="sysfolder-h" data-page-type="sys_folder">
        <i class="fa-solid fa-box-archive mb-5 block text-4xl text-brand-dark" aria-hidden="true"></i>
        <h1 id="sysfolder-h" class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
        <p class="text-lg text-ink">To folder systemowy: porządkuje strony w panelu i nie ma strony publicznej. Odwiedzający pod tym adresem zobaczą „Nie znaleziono”, a folderu nie ma w menu, na mapie strony ani w listach podstron.</p>
    </section>
@endsection
