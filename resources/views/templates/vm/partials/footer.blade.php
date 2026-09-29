@php
    $footerNavItems ??= collect();
    $vmFooterLink = 'rounded transition hover:text-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white';
    $socials = $siteSettings->socialLinks();
@endphp

<x-banner-zone name="footer" />

{{-- Ciemna stopka szablonu "vm": dane organizacji | Informacje | Portale społecznościowe. --}}
<footer class="bg-[#181818] text-white" aria-label="Stopka strony">
    <div class="mx-auto max-w-[1200px] px-4 py-12">
        <div class="grid gap-10 md:grid-cols-3">

            {{-- Dane organizacji --}}
            <div>
                <h2 class="vm-display mb-4 text-xl leading-tight">{{ $siteSettings->site_name }}</h2>
                <address class="space-y-4 text-sm font-semibold not-italic uppercase leading-relaxed text-white/90">
                    @if ($siteSettings->contact_address)
                        <p>
                            Adres:<br>
                            {{ $siteSettings->contact_address }}<br>
                            {{ $siteSettings->contact_city }}
                        </p>
                    @endif
                    @if ($siteSettings->contact_phone || $siteSettings->contact_email)
                        <p>
                            @if ($siteSettings->contact_phone)
                                Telefon: <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}" class="{{ $vmFooterLink }}">{{ $siteSettings->contact_phone }}</a><br>
                            @endif
                            @if ($siteSettings->contact_email)
                                E-mail: <a href="mailto:{{ $siteSettings->contact_email }}" class="break-all {{ $vmFooterLink }}">{{ $siteSettings->contact_email }}</a>
                            @endif
                        </p>
                    @endif
                </address>

                @if ($siteSettings->bank_account_number)
                    <p class="mt-4 text-sm font-semibold leading-relaxed text-white/90">
                        <span class="uppercase">Rachunek bankowy:</span><br>
                        <span class="tabular-nums">{{ $siteSettings->bank_account_number }}</span>
                    </p>
                @endif

                @if ($siteSettings->hasRegistryData())
                    <dl class="mt-4 space-y-0.5 text-sm font-semibold text-white/90">
                        @foreach (['KRS' => $siteSettings->krs_number, 'REGON' => $siteSettings->regon_number, 'NIP' => $siteSettings->nip_number] as $label => $value)
                            @if ($value)
                                <div class="flex gap-1"><dt>{{ $label }}:</dt><dd>{{ $value }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                @endif
            </div>

            {{-- Informacje --}}
            <nav aria-labelledby="vm-footer-info-heading">
                <h2 id="vm-footer-info-heading" class="vm-display mb-4 text-xl">Informacje</h2>
                <ul class="list-disc space-y-1 pl-5 text-sm font-semibold marker:text-white/70">
                    @foreach ($footerNavItems as $item)
                        <li><a href="{{ $item->url }}" class="{{ $vmFooterLink }}">{{ $item->label }}</a></li>
                    @endforeach
                    <li><a href="{{ route('accessibility.show') }}" class="{{ $vmFooterLink }}">Deklaracja dostępności</a></li>
                    <li><a href="{{ route('sitemap.page') }}" class="{{ $vmFooterLink }}">Mapa witryny</a></li>
                </ul>
            </nav>

            {{-- Portale społecznościowe --}}
            @if ($socials)
                <div>
                    <h2 class="vm-display mb-4 text-xl leading-tight">Portale społecznościowe</h2>
                    <ul class="flex flex-wrap gap-3" aria-label="Media społecznościowe">
                        @foreach ($socials as [$socialUrl, $socialIcon, $socialLabel])
                            <li>
                                <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
                                   class="flex h-11 w-11 items-center justify-center rounded-lg text-3xl text-white transition hover:text-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                   aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                                    <i class="{{ $socialIcon }}" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto max-w-[1200px] space-y-2 px-4 py-6 text-center text-xs font-semibold text-white/80">
            @if (filled($siteSettings->vm_footer_note))
                <p class="mx-auto max-w-3xl">{{ $siteSettings->vm_footer_note }}</p>
            @endif
            <p>
                {{ $siteSettings->site_name }} | Wszelkie prawa zastrzeżone &copy; {{ now()->year }}
                @if ($siteSettings->show_cms_credit ?? true)
                    <span class="block opacity-60 transition-opacity hover:opacity-100 focus-within:opacity-100 sm:inline">
                        <span class="hidden sm:inline">&middot;</span> Napędzane przez <span class="font-bold">weCMS</span>
                        &middot; Projekt i wykonanie <a href="mailto:ziemowit.gil@gmail.com" class="{{ $vmFooterLink }}">Ziemowit Gil</a>
                    </span>
                @endif
            </p>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex rounded text-white/30 transition hover:text-white/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
               aria-label="Panel administracyjny">
                <i class="fa-solid fa-gear text-sm" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</footer>
