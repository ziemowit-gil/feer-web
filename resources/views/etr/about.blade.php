@extends('layouts.site')

@section('title', 'Łatwe do czytania (ETR) — ' . $siteSettings->site_name)
@section('meta_description', 'ETR to sposób pisania tekstów prostym językiem. Pomagamy, żeby ważne informacje były zrozumiałe dla każdej osoby.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'O ETR', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12">

        <div class="mb-6 flex items-center gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-3xl" aria-hidden="true">📖</span>
            <h1 class="text-3xl font-bold text-ink">Łatwe do czytania</h1>
        </div>

        <div class="mb-8 rounded-xl border border-sky-200 bg-sky-50 p-6 text-lg leading-relaxed text-ink">
            <p class="mb-3">
                <strong>ETR</strong> to skrót od angielskiego <em>Easy to Read</em>.
            </p>
            <p>
                Po polsku mówimy: <strong>łatwe do czytania</strong>.
            </p>
        </div>

        <div class="space-y-6 text-lg leading-relaxed text-ink">

            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-3 text-xl font-bold">Co to jest ETR?</h2>
                <p class="mb-3">ETR to sposób pisania tekstów.</p>
                <p class="mb-3">Tekst ETR jest napisany prostym językiem.</p>
                <p>Krótkie zdania. Ważne słowa są wyjaśnione.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-3 text-xl font-bold">Dla kogo jest ETR?</h2>
                <ul class="space-y-2">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">✓</span>
                        <span>Dla osób z niepełnosprawnością intelektualną.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">✓</span>
                        <span>Dla osób, które mają trudności z czytaniem.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">✓</span>
                        <span>Dla osób uczących się języka polskiego.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">✓</span>
                        <span>Dla każdej osoby, która woli proste teksty.</span>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-3 text-xl font-bold">Jak wygląda wersja ETR?</h2>
                <p class="mb-3">Na naszej stronie możesz włączyć wersję ETR.</p>
                <p class="mb-3">Kliknij przycisk <strong>„Wersja łatwa do czytania"</strong>.</p>
                <p>Ten przycisk jest widoczny przy wybranych artykułach i stronach.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-3 text-xl font-bold">Zasady ETR</h2>
                <p class="mb-3">Tekst ETR musi być sprawdzony przez osoby z niepełnosprawnością intelektualną.</p>
                <p class="mb-3">Ważne zasady ETR:</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">•</span>
                        <span>Krótkie zdania — najlepiej jedno zdanie, jedna myśl.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">•</span>
                        <span>Proste słowa — trudne słowa są wyjaśnione.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">•</span>
                        <span>Duża czcionka i dużo odstępów między wierszami.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-sky-600" aria-hidden="true">•</span>
                        <span>Obrazki pomagają zrozumieć tekst.</span>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border border-sky-100 bg-sky-50 p-6">
                <p class="mb-2 font-bold">Więcej o standardzie ETR:</p>
                <a href="https://www.inclusion-europe.eu/easy-to-read/"
                    target="_blank" rel="noopener"
                    class="font-bold text-brand underline hover:text-brand-dark">
                    Inclusion Europe — Easy to Read <span class="text-sm font-normal">(po angielsku)</span>
                </a>
            </div>
        </div>

    </section>
@endsection
