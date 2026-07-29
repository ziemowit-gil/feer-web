<x-guest-layout>
    @section('title', 'Strefa wewnętrzna')
    @section('brand_heading', 'Strefa współpracownika')
    @section('brand_lead', 'Zaloguj się kontem Microsoft 365 organizacji, aby uzyskać dostęp do stron wewnętrznych.')

    <div class="mb-6 flex flex-col items-center text-center">
        <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-brand">
            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
        </span>
        <h1 class="text-xl font-bold text-ink">Strefa wewnętrzna</h1>
        <p class="mt-1 text-sm text-muted">Dostęp dla współpracowników {{ $siteSettings->site_name }}</p>
    </div>

    @if (session('error'))
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
            <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($siteSettings->memberLoginEnabled())
        <a href="{{ route('member.microsoft.redirect') }}"
            class="flex w-full items-center justify-center gap-2.5 rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-bold text-ink shadow-sm transition hover:bg-gray-50 hover:shadow-md active:scale-[0.99]">
            <svg class="h-5 w-5" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            Zaloguj się przez Microsoft 365
        </a>

        <p class="mt-4 text-center text-xs text-muted">
            Logowanie do strefy wewnętrznej jest niezależne od logowania do panelu administracyjnego.
        </p>
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>
            Logowanie do strefy wewnętrznej jest obecnie wyłączone. Skontaktuj się z administratorem.
        </div>
    @endif
</x-guest-layout>
