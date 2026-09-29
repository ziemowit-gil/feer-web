@extends('layouts.site')

@section('title', 'Dziękujemy za darowiznę — ' . $siteSettings->site_name)

@php $display = 'vm-display'; @endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 py-16 text-center">
    @if ($donation->isPaid())
        <span class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-brand-light text-3xl text-brand" aria-hidden="true">
            <i class="fa-solid fa-heart"></i>
        </span>
        <h1 class="{{ $display }} mb-4 text-3xl text-ink">Dziękujemy!</h1>
        <p class="text-lg leading-relaxed text-ink">
            Twoja darowizna w wysokości <strong>{{ $donation->amountLabel() }}</strong> wpłynęła.
            Potwierdzenie płatności wysłał serwis Przelewy24 na adres {{ $donation->email }}.
        </p>
    @elseif ($donation->status === 'pending')
        <span class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-3xl text-gray-600" aria-hidden="true">
            <i class="fa-solid fa-hourglass-half"></i>
        </span>
        <h1 class="{{ $display }} mb-4 text-3xl text-ink">Czekamy na potwierdzenie płatności</h1>
        <p class="text-lg leading-relaxed text-ink" role="status">
            Przelewy24 zwykle potwierdza wpłatę w ciągu kilku sekund. Jeśli płatność się udała,
            nic więcej nie musisz robić — dziękujemy!
        </p>
        <a href="{{ route('donation.thanks', $donation) }}"
           class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-2xl border-2 border-brand px-6 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-rotate" aria-hidden="true"></i> Sprawdź ponownie
        </a>
    @else
        <h1 class="{{ $display }} mb-4 text-3xl text-ink">Płatność nie doszła do skutku</h1>
        <p class="text-lg leading-relaxed text-ink" role="alert">
            Nie pobraliśmy żadnych środków. Możesz spróbować jeszcze raz albo przekazać darowiznę tradycyjnym przelewem.
        </p>
        <a href="{{ route('donation.show') }}"
           class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-2xl bg-brand px-6 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            Spróbuj ponownie
        </a>
    @endif

    <p class="mt-10"><a href="{{ site_route('home') }}" class="font-bold text-brand underline hover:text-brand-dark">Wróć na stronę główną</a></p>
</div>
@endsection
