{{--
    „Pytania i kontakt”: po lewej pięć pierwszych pytań z FAQ (natywne <details>), po prawej karta z danymi kontaktowymi
    z ustawień. Bez jednego z nich drugi zajmuje całą szerokość; bez obu blok się nie pokazuje.
--}}
@php
    $faqItems ??= collect();
    $phone = $siteSettings->contact_phone;
    $email = $siteSettings->contact_email;
    // Adres, pod którym przyjmujemy osoby: biuro, a gdy go nie podano, adres rejestrowy.
    $address = $siteSettings->hasOfficeAddress() ? $siteSettings->officeAddressLine() : $siteSettings->registeredAddressLine();
    $hours = $siteSettings->contact_office_hours;
    $hasContact = filled($phone) || filled($email) || filled($address);
    $hasFaq = $faqItems->isNotEmpty();
@endphp
@if ($hasFaq || $hasContact)
    <section class="fis-sec" aria-labelledby="{{ $hasFaq ? 'fis-faq-h' : 'fis-contact-h' }}">
        <div class="fis-wrap">
            <div class="fis-qa {{ $hasFaq && $hasContact ? 'fis-qa--both' : '' }}">
                @if ($hasFaq)
                    <div class="fis-faq">
                        <div class="fis-head">
                            <h2 id="fis-faq-h">Najczęstsze pytania</h2>
                            @if (\Illuminate\Support\Facades\Route::has('faq.index'))
                                <a class="fis-link" href="{{ route('faq.index') }}">Wszystkie pytania<span aria-hidden="true"> →</span></a>
                            @endif
                        </div>
                        @foreach ($faqItems as $faq)
                            <details>
                                <summary>{{ $faq->question }}</summary>
                                <div>{!! nl2br(e($faq->answer)) !!}</div>
                            </details>
                        @endforeach
                    </div>
                @endif

                @if ($hasContact)
                    <div class="fis-contact">
                        <h2 id="fis-contact-h">Masz pytania?</h2>
                        @if (filled($phone))
                            <p>Zadzwoń:<br><a class="fis-tel" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></p>
                        @endif
                        @if (filled($email))
                            <p>Napisz:<br><a href="mailto:{{ $email }}">{{ $email }}</a></p>
                        @endif
                        @if (filled($address))<p>{{ $address }}</p>@endif
                        @if (filled($hours))<p>{!! nl2br(e($hours)) !!}</p>@endif
                        <a class="fis-btn fis-btn--light" href="{{ route('contact.show') }}">Formularz kontaktowy</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
