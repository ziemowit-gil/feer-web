{{--
    Formularz kontaktowy: z kreatora formularzy (Formularze → „Kontakt”), a gdy kreator jest niedostępny —
    dawny formularz zapisany w kodzie. Nagłówek „Napisz do nas” jest w widoku strony, więc osadzenie go nie powtarza.
--}}
@php $contactForm = \App\Support\ContactForm::definition(); @endphp
@if ($contactForm)
    @if ($contactForm->is_active)
        @include('formularz._embed', ['form' => $contactForm, 'hideHeading' => true])
    @endif
@else
    @include('contact.partials.legacy-form')
@endif
