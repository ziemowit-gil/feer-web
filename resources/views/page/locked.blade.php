@extends('layouts.site')

@section('title', $page->title . ' — ' . $siteSettings->site_name)
@section('meta_description', 'Strona wewnętrzna — dostęp ograniczony.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $page->title, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-md px-4 py-16">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-2xl text-brand" aria-hidden="true">
                <i class="fa-solid fa-lock"></i>
            </span>
            <h1 class="text-2xl font-bold text-ink">{{ $page->title }}</h1>
            <p class="mt-2 text-sm text-muted">To strona wewnętrzna. Podaj hasło dostępu, aby zobaczyć jej treść.</p>

            <form method="POST" action="{{ route('page.unlock', $page) }}" class="mt-6 space-y-3 text-left">
                @csrf
                <div>
                    <label for="access_password" class="mb-1 block text-sm font-bold">Hasło</label>
                    <input type="password" id="access_password" name="access_password" required autofocus autocomplete="off"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('access_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full rounded-lg bg-brand px-5 py-2.5 font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Odblokuj
                </button>
            </form>
        </div>
    </section>
@endsection
