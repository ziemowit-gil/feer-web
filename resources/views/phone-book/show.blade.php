{{-- Publiczna strona modułu „Książka telefoniczna”: tytuł, wstęp i lista osób (partials/phone-book/list). --}}
@extends('layouts.site')

@php $pbTitle = filled($settings->phonebook_title) ? $settings->phonebook_title : 'Książka telefoniczna'; @endphp

@section('title', $pbTitle.' — '.$siteSettings->site_name)
@section('meta_description', 'Kto, numer telefonu i w jakiej sprawie się kontaktować — '.$siteSettings->site_name.'.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [['label' => $pbTitle]]])
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12" data-page-type="phone_book">
        <header class="mb-8 max-w-3xl">
            <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $pbTitle }}</h1>
            @if (filled($settings->phonebook_lead))
                <p class="text-lg leading-relaxed text-ink">{{ $settings->phonebook_lead }}</p>
            @endif
        </header>

        @include('partials.phone-book.list', [
            'contacts' => $contacts,
            'layout' => $settings->phonebook_layout ?: 'table',
            'sort' => $settings->phonebook_sort ?: 'manual',
            'hide' => (bool) $settings->phonebook_hide_numbers,
        ])
    </section>
@endsection
