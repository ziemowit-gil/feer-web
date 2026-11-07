{{--
    Formularz kontaktowy: z kreatora formularzy (Formularze → „Kontakt”), a gdy kreator jest niedostępny —
    dawny formularz zapisany w kodzie. Nagłówek „Napisz do nas” jest w widoku strony, więc osadzenie go nie powtarza.
--}}
@php $formOptions = $contactOptions ?? \Modules\Contact\Support\ContactOptions::for($siteSettings); @endphp
@if (! $formOptions->formEnabled())
    {{-- Moduł Kontakt: formularz wyłączony w Komunikacja → Kontakt — zostaje informacja, jak napisać inaczej. --}}
    <p class="contact-form-off leading-relaxed text-ink">
        Formularz jest teraz wyłączony. Napisz do nas na adres <strong class="break-all">{{ $siteSettings->contact_email }}</strong>@if (filled($siteSettings->contact_phone)) albo zadzwoń: <strong class="whitespace-nowrap">{{ $siteSettings->contact_phone }}</strong>@endif.
    </p>
@else
@php $contactForm = \App\Support\ContactForm::definition(); @endphp
@if ($contactForm)
    @if ($contactForm->is_active)
        @include('formularz._embed', ['form' => $contactForm, 'hideHeading' => true])
    @endif
@else
    @include('contact.partials.legacy-form')
@endif
@endif
