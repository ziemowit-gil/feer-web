<x-guest-layout>
    @section('title', 'Strefa wewnętrzna')
    @section('brand_heading', 'Strefa współpracownika')
    @section('brand_lead', 'Zaloguj się kontem Microsoft 365 organizacji, aby uzyskać dostęp do stron wewnętrznych.')

    {{-- Płasko, kolory firmowe — jak logowanie do panelu (auth/login). --}}
    @php
        $notice = 'mb-5 flex items-start gap-2 rounded-md bg-brand-light px-4 py-3 text-sm text-ink';
    @endphp

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-ink">Strefa wewnętrzna</h1>
        <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
        <p class="mt-3 text-ink">Dostęp dla współpracowników {{ $siteSettings->siteNameGenitive() }}.</p>
    </div>

    @if (session('error'))
        <div class="{{ $notice }}" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-0.5 text-brand-dark" aria-hidden="true"></i>
            <span><strong>Nie udało się zalogować.</strong> {{ session('error') }}</span>
        </div>
    @endif

    @if ($siteSettings->memberLoginEnabled())
        <a href="{{ route('member.microsoft.redirect') }}"
            class="flex w-full items-center justify-center gap-2.5 rounded-md bg-brand-dark px-5 py-3 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            @include('auth.partials.microsoft-logo')
            Zaloguj się przez Microsoft 365
        </a>

        <p class="mt-4 text-sm text-ink">
            Logowanie do strefy wewnętrznej jest niezależne od logowania do panelu administracyjnego.
        </p>
    @else
        <p class="{{ $notice }} font-bold">
            <i class="fa-solid fa-circle-info mt-0.5 text-brand-dark" aria-hidden="true"></i>
            <span>Logowanie do strefy wewnętrznej jest teraz wyłączone. Skontaktuj się z administratorem.</span>
        </p>
    @endif

    @if ($siteSettings->szoTozsamoscUrl())
        <section class="mt-8 rounded-md bg-gray-50 p-5" aria-labelledby="tozsamosc-heading">
            <h2 id="tozsamosc-heading" class="mb-3 font-bold text-ink">Konto współpracownika</h2>
            @include('partials.strefa-tozsamosc', ['margin' => false])
            <p class="text-sm text-ink">Zarządzaj swoim kontem i danymi logowania współpracownika.</p>
        </section>
    @endif
</x-guest-layout>
