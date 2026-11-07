{{-- Zdanie o czasie odpowiedzi pod „Napisz do nas” (Komunikacja → Kontakt); puste albo wyłączony formularz = nic. --}}
@php $rtOptions = $contactOptions ?? \Modules\Contact\Support\ContactOptions::for($siteSettings); @endphp
@if ($rtOptions->formEnabled() && $rtOptions->responseTime() !== '')
    <p class="{{ $class ?? 'mb-6 text-sm text-muted' }}">{{ $rtOptions->responseTime() }}</p>
@endif
