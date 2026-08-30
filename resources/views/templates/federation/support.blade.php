@extends('layouts.site')

@section('title', 'Wesprzyj nas — ' . $siteSettings->site_name)
@section('meta_description', 'Wesprzyj ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wesprzyj nas', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Wesprzyj nas</p>
        <h1 class="mb-4 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
            Twoje wsparcie pomaga nam działać na rzecz organizacji pozarządowych i mieszkańców Krakowa
        </h1>
        <p class="mb-10 max-w-2xl text-base leading-relaxed text-muted">
            {{ $siteSettings->site_name }} działa dzięki wsparciu ludzi i instytucji, którym zależy na silnym
            i skutecznym sektorze pozarządowym. Możesz nas wesprzeć finansowo lub skontaktować się z nami,
            aby porozmawiać o innych formach współpracy.
        </p>

        <div class="grid gap-6 sm:grid-cols-2">
            @if ($siteSettings->bank_account_number)
                <div class="rounded-lg border border-gray-200 p-6"
                    x-data="{ copied: false }"
                    x-init="$watch('copied', value => { if (value) setTimeout(() => copied = false, 2000) })">
                    <i class="fa-solid fa-building-columns mb-3 text-2xl text-brand" aria-hidden="true"></i>
                    <h2 class="mb-2 text-lg font-bold text-ink">Przelew tradycyjny</h2>
                    <p class="mb-3 text-sm text-muted">Wpłać dowolną kwotę bezpośrednio na nasze konto.</p>
                    <div class="flex items-center gap-2">
                        <p class="select-all overflow-x-auto rounded bg-gray-50 px-3 py-2 font-mono text-sm text-ink">{{ $siteSettings->bank_account_number }}</p>
                        <button type="button"
                            @click="navigator.clipboard.writeText('{{ $siteSettings->bank_account_number }}'); copied = true"
                            class="flex-none rounded border border-gray-200 p-2 text-muted transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                            :aria-label="copied ? 'Skopiowano numer konta' : 'Kopiuj numer konta'">
                            <i class="fa-regular" :class="copied ? 'fa-circle-check' : 'fa-copy'" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="sr-only" role="status" x-show="copied" x-cloak>Numer konta skopiowany do schowka</p>
                </div>
            @endif

            <div class="rounded-lg border border-gray-200 p-6">
                <i class="fa-solid fa-handshake mb-3 text-2xl text-brand" aria-hidden="true"></i>
                <h2 class="mb-2 text-lg font-bold text-ink">Współpraca i inne formy wsparcia</h2>
                <p class="mb-4 text-sm text-muted">Napisz do nas — chętnie porozmawiamy o współpracy, wolontariacie lub wsparciu rzeczowym.</p>
                <a href="{{ route('contact.show') }}"
                    class="inline-flex items-center gap-1.5 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Skontaktuj się z nami
                </a>
            </div>
        </div>
    </section>
@endsection
