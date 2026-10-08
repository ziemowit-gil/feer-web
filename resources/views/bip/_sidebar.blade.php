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

@php
    $bmLink = function (string $url, string $label, string $icon, bool $current = false, bool $external = false) {
        return compact('url', 'label', 'icon', 'current', 'external');
    };
    $groups = [];
    $main = [$bmLink(route('bip'), 'Strona główna BIP', 'fa-solid fa-landmark', $onBip)];
    foreach ($bipNavItems as $item) {
        $main[] = $bmLink(
            (string) $item->url,
            (string) $item->label,
            $item->icon ?: 'fa-solid fa-file-lines',
            ltrim(parse_url($item->url, PHP_URL_PATH) ?? '', '/') === ltrim(request()->path(), '/'),
            str_starts_with($item->url ?? '', 'http'),
        );
    }
    $groups['Dokumenty i informacje'] = $main;

    $reg = [];
    if (! $isExternalMode && ($bipSettings->bip_show_reports ?? true) && $bipSettings->isModuleEnabled('reports')) {
        $reg[] = $bmLink(route('bip').'#sprawozdania', 'Sprawozdania roczne', 'fa-solid fa-file-invoice');
    }
    if (! $isExternalMode) {
        $reg[] = $bmLink(route('bip.changelog'), 'Rejestr zmian', 'fa-solid fa-clock-rotate-left', $onChangelog);
    }
    if ($reg) { $groups['Rejestry i sprawozdania'] = $reg; }

    $groups['Pomoc'] = [
        $bmLink(route('bip.instructions'), 'Instrukcja korzystania z BIP', 'fa-solid fa-circle-info', request()->routeIs('bip.instructions')),
        $bmLink(route('accessibility.show'), 'Deklaracja dostępności', 'fa-solid fa-universal-access'),
    ];
@endphp

<style>
    .bm-nav { border-radius: .5rem; background: #f9fafb; }
    .bm-toggle { display: flex; width: 100%; min-height: 3rem; align-items: center; justify-content: space-between; gap: .5rem; padding: .5rem 1rem; list-style: none; cursor: pointer; font-weight: 800; color: #1d1d1a; }
    .bm-toggle::-webkit-details-marker { display: none; }
    .bm-toggle:focus-visible { outline: 3px solid #1d1d1a; outline-offset: -3px; border-radius: .5rem; }
    .bm-body { padding: 0 .5rem .75rem; }
    .bm-group-h { margin: .75rem .5rem .25rem; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #4b5563; }
    .bm-list { list-style: none; margin: 0; padding: 0; }
    .bm-link { display: flex; min-height: 2.75rem; align-items: center; gap: .65rem; padding: .5rem .75rem; border-left: 4px solid transparent; border-radius: 0 .375rem .375rem 0; font-size: .9rem; font-weight: 600; color: #1d1d1a; text-decoration: none; }
    .bm-link i { width: 1.1rem; flex: none; text-align: center; font-size: .8rem; color: var(--color-brand-dark); }
    .bm-link:hover { background: #e5e7eb; }
    .bm-link:focus-visible { outline: 3px solid #1d1d1a; outline-offset: -3px; }
    .bm-link[aria-current="page"] { border-left-color: var(--color-brand-dark); background: var(--color-brand-light); font-weight: 800; }
    @media (min-width: 1024px) { .bm-toggle { display: none; } .bm-nav { position: sticky; top: 1rem; } .bm-body { padding-top: .5rem; } }
</style>
<nav aria-label="Nawigacja BIP" class="bm-nav">
    <details id="bm-details" open>
        <summary class="bm-toggle"><span><i class="fa-solid fa-bars mr-2" aria-hidden="true"></i>Menu BIP</span><i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i></summary>
        <div class="bm-body">
            @foreach ($groups as $gTitle => $links)
                <p class="bm-group-h" id="bm-g-{{ $loop->index }}">{{ $gTitle }}</p>
                <ul class="bm-list" aria-labelledby="bm-g-{{ $loop->index }}">
                    @foreach ($links as $l)
                        <li>
                            <a href="{{ $l['url'] }}" class="bm-link" @if ($l['current']) aria-current="page" @endif @if ($l['external']) target="_blank" rel="noopener" @endif>
                                <i class="{{ $l['icon'] }}" aria-hidden="true"></i><span class="min-w-0 flex-1">{{ $l['label'] }}@if ($l['external'])<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif</span>
                                @if ($l['external'])<i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.6rem" aria-hidden="true"></i>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </details>
    <script>
        (function () {
            var d = document.getElementById('bm-details');
            if (! d || ! window.matchMedia) return;
            var mq = window.matchMedia('(min-width: 1024px)');
            function sync() { d.open = mq.matches; }
            sync();
            (mq.addEventListener ? mq.addEventListener('change', sync) : mq.addListener(sync));
        })();
    </script>
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
