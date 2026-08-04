@extends('layouts.site')

@section('title', $page->title . ' — ' . $siteSettings->site_name)
@section('meta_description', 'Strona chroniona — wymagane dane logowania.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $page->title, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-md px-4 py-16">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-2xl text-brand" aria-hidden="true">
                <i class="fa-solid fa-shield-halved"></i>
            </span>
            <h1 class="text-center text-2xl font-bold text-ink">{{ $page->title }}</h1>
            <p class="mt-2 text-center text-sm text-muted">Podaj swój indywidualny login i hasło, aby uzyskać dostęp do plików.</p>

            @if ($errors->any())
                <div role="alert" class="mt-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    <i class="fa-solid fa-circle-exclamation mr-1" aria-hidden="true"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('page.brand-login.post', $page) }}" class="mt-6 space-y-4" novalidate>
                @csrf

                <div>
                    <label for="brand_login" class="mb-1 block text-sm font-bold">Login</label>
                    <input
                        type="text"
                        id="brand_login"
                        name="login"
                        value="{{ old('login') }}"
                        required
                        autofocus
                        autocomplete="username"
                        aria-describedby="{{ $errors->has('login') ? 'login-error' : null }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand @error('login') border-red-400 @enderror"
                    >
                    @error('login')
                        <p id="login-error" class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="brand_password" class="mb-1 block text-sm font-bold">Hasło</label>
                    <input
                        type="password"
                        id="brand_password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-brand px-5 py-2.5 font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                >
                    Zaloguj się
                </button>
            </form>
        </div>
    </section>
@endsection
