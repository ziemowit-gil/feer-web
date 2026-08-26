{{--
    Dodatkowe dane organizacji do panelu kontaktowego: numery rejestrowe,
    numery kont i profile social. Każdy blok pokazuje się tylko wtedy, gdy
    jest czym go wypełnić.
--}}
@php
    $registryRows = collect([
        ['label' => 'KRS',   'value' => $siteSettings->krs_number],
        ['label' => 'NIP',   'value' => $siteSettings->nip_number],
        ['label' => 'REGON', 'value' => $siteSettings->regon_number],
    ])->filter(fn ($row) => filled($row['value']))->values();

    $accountRows = collect([
        ['label' => 'Numer konta',              'value' => $siteSettings->bank_account_number],
        ['label' => 'Konto na 1,5% podatku',    'value' => $siteSettings->bank_account_tax_number],
    ])->filter(fn ($row) => filled($row['value']))->values();

    $panelSocials = $siteSettings->socialLinks();
@endphp

@if ($registryRows->isNotEmpty())
    <div class="mt-6 border-t border-gray-200 pt-5">
        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Dane rejestrowe</h3>
        <dl class="space-y-1 text-sm">
            @foreach ($registryRows as $row)
                <div class="flex items-baseline gap-2">
                    <dt class="w-14 flex-none text-muted">{{ $row['label'] }}</dt>
                    <dd class="font-mono font-medium text-ink">{{ $row['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif

@if ($accountRows->isNotEmpty())
    <div class="mt-6 border-t border-gray-200 pt-5">
        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Wpłaty</h3>
        <ul class="space-y-3 text-sm">
            @foreach ($accountRows as $row)
                <li>
                    <p class="text-muted">{{ $row['label'] }}</p>
                    <p class="overflow-x-auto whitespace-nowrap font-mono font-medium text-ink">{{ $row['value'] }}</p>
                    <button type="button" data-copy-button data-copy-value="{{ $row['value'] }}"
                        class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if ($panelSocials)
    <div class="mt-6 border-t border-gray-200 pt-5">
        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Znajdziesz nas też tutaj</h3>
        <nav aria-label="Media społecznościowe">
            @include('partials.social-icons', ['socialIcons' => $panelSocials])
        </nav>
    </div>
@endif
