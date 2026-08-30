{{-- Dane teleadresowe + opcjonalny box promocyjny. --}}
@php
    // $wideLayout = true układa pozycje w siatkę na całą szerokość zamiast
    // wąskiej kolumny bocznej (używa tego wariant instytucjonalny).
    $wideLayout = $wideLayout ?? false;
@endphp

<aside aria-label="Dane kontaktowe">
    <p class="mb-5 text-xl font-bold text-ink">{{ $siteSettings->site_name }}</p>
    @php
        // Gdy podano osobny adres biura, rozdzielamy pozycje: rejestrowy (dane
        // formalne) i biuro/korespondencja (tam realnie trafia poczta).
        $hasOffice       = $siteSettings->officeDiffersFromRegistered();
        $registeredLabel = $hasOffice ? 'Adres rejestrowy' : 'Adres';
    @endphp

    <ul class="{{ $wideLayout ? 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3' : 'space-y-5' }}">
        <li>
            <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->registeredAddressLine()) }}"
                target="_blank" rel="noopener"
                aria-label="{{ $registeredLabel }}: {{ $siteSettings->registeredAddressLine() }} (otwiera mapę w nowej karcie)"
                class="group flex items-start gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 rounded">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                    <i class="fa-solid {{ $hasOffice ? 'fa-building-columns' : 'fa-location-dot' }}"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">{{ $registeredLabel }}</span>
                    <span class="font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}</span>
                    @if ($hasOffice)
                        <span class="mt-0.5 block text-xs text-muted">Dane do faktur i pism formalnych — nie wysyłaj tu przesyłek.</span>
                    @endif
                </span>
            </a>
        </li>

        @if ($hasOffice)
            <li>
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid fa-location-dot"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">Biuro / korespondencja</span>
                        @if (filled($siteSettings->contact_office_building))
                            <span class="block font-bold text-ink">{{ $siteSettings->contact_office_building }}</span>
                        @endif
                        <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->officeAddressLine()) }}"
                           target="_blank" rel="noopener"
                           aria-label="Biuro: {{ trim($siteSettings->contact_office_building.' '.$siteSettings->officeAddressLine()) }} (otwiera mapę w nowej karcie)"
                           class="font-medium text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            {{ $siteSettings->contact_office_address }}<br>{{ $siteSettings->contact_office_city }}
                        </a>
                        @if (filled($siteSettings->contact_office_note))
                            <p class="mt-1.5 text-xs leading-relaxed text-muted">
                                <i class="fa-solid fa-circle-info mr-1 text-brand" aria-hidden="true"></i>
                                {!! nl2br(e($siteSettings->contact_office_note)) !!}
                            </p>
                        @endif
                        @if (($withOfficePhoto ?? true) && ($photo = $siteSettings->officePhotoUrl()))
                            <img src="{{ $photo }}" loading="lazy"
                                 alt="{{ $siteSettings->contact_office_photo_alt }}"
                                 class="mt-3 w-full max-w-xs rounded-lg object-cover ring-1 ring-gray-200">
                        @endif
                    </div>
                </div>
            </li>
        @endif
        <li>
            <a href="mailto:{{ $siteSettings->contact_email }}" class="group flex items-start gap-3 rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                    <i class="fa-solid fa-envelope"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">E-mail</span>
                    <span class="block break-all font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_email }}</span>
                </span>
            </a>
        </li>
        @if ($siteSettings->contact_office_hours)
            <li class="flex items-start gap-3">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                    <i class="fa-regular fa-clock"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">Godziny pracy</span>
                    <span class="block font-medium text-ink">{{ $siteSettings->contact_office_hours }}</span>
                </span>
            </li>
        @endif
        @if ($siteSettings->contact_phone)
            <li>
                <a href="tel:{{ $siteSettings->contact_phone }}" class="group flex items-start gap-3 rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid fa-phone"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">Telefon</span>
                        <span class="font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_phone }}</span>
                    </span>
                </a>
            </li>
        @endif
        @if ($siteSettings->contact_edelivery_address)
            <li class="flex items-start gap-3">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">Adres do e-Doręczeń</span>
                    <span class="block break-all font-mono text-sm font-medium text-ink">{{ $siteSettings->contact_edelivery_address }}</span>
                </span>
            </li>
        @endif
    </ul>

    @if ($withCorrespondence ?? false)
        <div class="mt-5">
            @include('partials.correspondence-note', ['variant' => 'inline'])
        </div>
    @endif

    @if ($siteSettings->contactBoxIsVisible())
        <div class="mt-8 rounded-xl border border-brand/30 bg-brand-light p-5">
            @if ($siteSettings->contact_box_text)
                <p class="text-sm text-ink">{{ $siteSettings->contact_box_text }}</p>
            @endif
            @if ($siteSettings->contact_box_link_url && $siteSettings->contact_box_link_label)
                @php $external = \Illuminate\Support\Str::startsWith($siteSettings->contact_box_link_url, ['http://', 'https://']); @endphp
                <a href="{{ $siteSettings->contact_box_link_url }}" @if ($external) target="_blank" rel="noopener" @endif
                    class="{{ $siteSettings->contact_box_text ? 'mt-3' : '' }} inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                    {{ $siteSettings->contact_box_link_label }}
                    <i class="fa-solid {{ $external ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right' }}" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif
</aside>
