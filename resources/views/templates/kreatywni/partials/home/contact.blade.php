{{-- Kontakt: ciemny pas z tekstem, przyciskiem do formularza (strona kontaktu) oraz telefonem i e-mailem z ustawień. --}}
@php
    $kcPhone = trim((string) $siteSettings->contact_phone);
    $kcEmail = trim((string) $siteSettings->contact_email);
    $kcForm = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
@endphp
@if ($kcPhone !== '' || $kcEmail !== '' || $kcForm)
<section id="kontakt" class="kc-sec kc-dark" aria-labelledby="kc-contact-title">
    <div class="kc-wrap kc-contact">
        <div>
            <h2 id="kc-contact-title" class="kc-h2">{{ $texts->get('contact_title') }}</h2>
            @foreach ($texts->paragraphs('contact_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
            @if ($kcForm && $texts->get('contact_button') !== '')
                <div class="kc-actions" style="margin-top:1.5rem">
                    <a href="{{ $kcForm }}" class="kc-btn kc-btn-sun"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> {{ $texts->get('contact_button') }}</a>
                </div>
            @endif
        </div>
        @if ($kcPhone !== '' || $kcEmail !== '')
            <ul class="kc-contact-list">
                @if ($kcPhone !== '')
                    <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $kcPhone) }}"><i class="fa-solid fa-phone" aria-hidden="true"></i><span><span class="kc-sr">Telefon: </span>{{ $kcPhone }}</span></a></li>
                @endif
                @if ($kcEmail !== '')
                    <li><a href="mailto:{{ $kcEmail }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i><span><span class="kc-sr">E-mail: </span>{{ $kcEmail }}</span></a></li>
                @endif
            </ul>
        @endif
    </div>
</section>
@endif
