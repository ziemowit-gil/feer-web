@php
    $feerSide = (($siteSettings ?? \App\Models\SiteSetting::current())->site_template ?? 'default') === 'feer';
    $bipSettings   = $siteSettings ?? \App\Models\SiteSetting::current();
    $isExternalMode = ($bipSettings->bip_mode ?? 'internal') === 'external';
    $onBip          = request()->routeIs('bip') && ! request()->routeIs('bip.*');
    $onChangelog    = request()->routeIs('bip.changelog');

    $bipNavItems = \App\Models\NavItem::where('location', 'bip')
        ->where('is_active', true)
        ->orderBy('order')
        ->get();
@endphp

<nav aria-label="Nawigacja BIP">
    <ul class="space-y-0.5 text-sm">
        {{-- Strona główna BIP — stała pierwsza pozycja --}}
        <li>
            <a href="{{ route('bip') }}"
                @if ($onBip) aria-current="page" @endif
                class="flex min-h-11 items-center gap-2 rounded-md px-3 py-2 font-semibold transition {{ $onBip ? ($feerSide ? 'bg-brand-light font-bold text-ink' : 'bg-brand-light text-brand-dark') : 'text-ink hover:bg-gray-50' }} focus-visible:outline-2 focus-visible:outline-brand">
                <i class="fa-solid fa-landmark w-4 text-center text-[0.7rem]" aria-hidden="true"></i>
                Strona główna BIP
            </a>
        </li>

        @foreach ($bipNavItems as $item)
            @php
                $isCurrentItem = ltrim(parse_url($item->url, PHP_URL_PATH) ?? '', '/') === ltrim(request()->path(), '/');
                $isExtLink = str_starts_with($item->url ?? '', 'http');
            @endphp
            <li>
                <a href="{{ $item->url }}"
                    @if ($isExtLink) target="_blank" rel="noopener" @endif
                    @if ($isCurrentItem) aria-current="page" @endif
                    class="flex items-center gap-2 rounded px-3 py-2 transition {{ $isCurrentItem ? ($feerSide ? 'bg-brand-light font-bold text-ink' : 'bg-brand-light font-semibold text-brand-dark') : 'text-muted hover:bg-gray-50 hover:text-ink' }} focus-visible:outline-2 focus-visible:outline-brand">
                    @if ($item->icon)
                        <i class="{{ $item->icon }} w-4 text-center text-[0.7rem]" aria-hidden="true"></i>
                    @endif
                    {{ $item->label }}
                    @if ($isExtLink)
                        <i class="fa-solid fa-arrow-up-right-from-square ml-auto text-[0.55rem] text-muted" aria-hidden="true"></i>
                    @endif
                </a>
            </li>
        @endforeach
        @if (! $isExternalMode && ($bipSettings->bip_show_reports ?? true) && $bipSettings->isModuleEnabled('reports'))
            <li>
                <a href="{{ route('bip') }}#sprawozdania" class="flex min-h-11 items-center gap-2 rounded-md px-3 py-2 font-semibold text-ink transition hover:bg-gray-100">
                    <i class="fa-solid fa-file-invoice w-4 text-center text-[0.7rem]" aria-hidden="true"></i>Sprawozdania roczne
                </a>
            </li>
        @endif
        @unless ($isExternalMode)
            <li>
                <a href="{{ route('bip.changelog') }}" @if ($onChangelog) aria-current="page" @endif
                    class="flex min-h-11 items-center gap-2 rounded-md px-3 py-2 font-semibold transition {{ $onChangelog ? 'bg-brand-light font-bold text-ink' : 'text-ink hover:bg-gray-100' }}">
                    <i class="fa-solid fa-clock-rotate-left w-4 text-center text-[0.7rem]" aria-hidden="true"></i>Rejestr zmian
                </a>
            </li>
        @endunless
        <li>
            <a href="{{ route('bip.instructions') }}" @if (request()->routeIs('bip.instructions')) aria-current="page" @endif
                class="flex min-h-11 items-center gap-2 rounded-md px-3 py-2 font-semibold transition {{ request()->routeIs('bip.instructions') ? 'bg-brand-light font-bold text-ink' : 'text-ink hover:bg-gray-100' }}">
                <i class="fa-solid fa-circle-info w-4 text-center text-[0.7rem]" aria-hidden="true"></i>Instrukcja korzystania z BIP
            </a>
        </li>
        <li>
            <a href="{{ route('accessibility.show') }}" class="flex min-h-11 items-center gap-2 rounded-md px-3 py-2 font-semibold text-ink transition hover:bg-gray-100">
                <i class="fa-solid fa-universal-access w-4 text-center text-[0.7rem]" aria-hidden="true"></i>Deklaracja dostępności
            </a>
        </li>
    </ul>
</nav>

{{-- Dane identyfikacyjne podmiotu — wymóg § 10 MSWiA --}}
@php
    $hasSubjectData = $bipSettings->contact_email
        || $bipSettings->contact_phone
        || $bipSettings->contact_address
        || $bipSettings->hasRegistryData()
        || $bipSettings->bip_editor_name;
@endphp

@if ($hasSubjectData)
    <div class="mt-6 border-t border-gray-200 pt-5 text-sm" aria-label="Dane identyfikacyjne podmiotu BIP">
        <p class="mb-3 text-[0.65rem] font-bold uppercase tracking-wider text-muted">Podmiot prowadzący BIP</p>

        <p class="font-semibold text-ink">{{ $bipSettings->site_name }}</p>

        @if ($bipSettings->contact_address || $bipSettings->contact_city)
            <p class="mt-0.5 text-xs text-muted">
                {{ $bipSettings->contact_address }}
                @if ($bipSettings->contact_address && $bipSettings->contact_city), @endif
                {{ $bipSettings->contact_city }}
            </p>
        @endif

        @if ($bipSettings->contact_email || $bipSettings->contact_phone)
            <div class="mt-2 space-y-0.5 text-xs">
                @if ($bipSettings->contact_email)
                    <p>
                        <a href="mailto:{{ $bipSettings->contact_email }}"
                            class="text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-brand">
                            {{ $bipSettings->contact_email }}
                        </a>
                    </p>
                @endif
                @if ($bipSettings->contact_phone)
                    <p class="text-muted">{{ $bipSettings->contact_phone }}</p>
                @endif
            </div>
        @endif

        @if ($bipSettings->hasRegistryData())
            <div class="mt-2 space-y-0.5 text-xs text-muted">
                @if ($bipSettings->krs_number)
                    <p>KRS: <span class="font-mono font-semibold text-ink">{{ $bipSettings->krs_number }}</span></p>
                @endif
                @if ($bipSettings->nip_number)
                    <p>NIP: <span class="font-mono font-semibold text-ink">{{ $bipSettings->nip_number }}</span></p>
                @endif
                @if ($bipSettings->regon_number)
                    <p>REGON: <span class="font-mono font-semibold text-ink">{{ $bipSettings->regon_number }}</span></p>
                @endif
            </div>
        @endif

        @if ($bipSettings->bip_editor_name || $bipSettings->bip_editor_email)
            <div class="mt-3 border-t border-gray-100 pt-3 text-xs">
                <p class="mb-1 text-[0.6rem] font-bold uppercase tracking-wider text-muted">Redaktor BIP</p>
                @if ($bipSettings->bip_editor_name)
                    <p class="font-semibold text-ink">{{ $bipSettings->bip_editor_name }}</p>
                @endif
                @if ($bipSettings->bip_editor_email)
                    <p>
                        <a href="mailto:{{ $bipSettings->bip_editor_email }}"
                            class="text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-brand">
                            {{ $bipSettings->bip_editor_email }}
                        </a>
                    </p>
                @endif
            </div>
        @endif

        <div class="mt-3 border-t border-gray-100 pt-3">
            <a href="{{ $bipSettings->bip_gov_url ?: 'https://www.gov.pl/web/bip' }}" target="_blank" rel="noopener"
                class="inline-flex min-h-9 items-center gap-1.5 text-xs font-semibold text-brand-dark underline hover:text-ink focus-visible:outline-2 focus-visible:outline-brand">
                <i class="fa-solid fa-arrow-up-right-from-square text-[0.55rem]" aria-hidden="true"></i>
                {{ $bipSettings->bip_gov_url ? 'Podmiot na gov.pl/bip' : 'Główna strona BIP (gov.pl/bip)' }}<span class="sr-only"> (otwiera się w nowej karcie)</span>
            </a>
        </div>
    </div>
@endif
