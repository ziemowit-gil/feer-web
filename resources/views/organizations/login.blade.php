@extends('layouts.site')

@section('title', 'Logowanie organizacji — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Organizacje członkowskie', 'url' => route('federation.organizations')],
        ['label' => 'Logowanie organizacji', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-md px-4 py-12 lg:py-16">
        <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-ink sm:text-3xl">Logowanie organizacji</h1>
        <p class="mb-8 text-sm leading-relaxed text-muted">
            Zaloguj się danymi swojej organizacji, aby edytować jej opis i dane kontaktowe widoczne w katalogu
            organizacji członkowskich.
        </p>

        <form method="POST" action="{{ route('organization.login.submit') }}" class="space-y-5 rounded-lg border border-gray-100 bg-white p-6 shadow-sm">
            @csrf

            @if ($errors->any())
                <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label for="login" class="mb-1 block text-sm font-bold text-ink">Login organizacji</label>
                <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-bold text-ink">Hasło</label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>

            <button type="submit" class="w-full rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Zaloguj się
            </button>
        </form>
    </section>
@endsection
