@extends('layouts.site')

@section('title', 'Potwierdzenie zamówienia — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12">
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center sm:p-8">
            @if ($order->isPaid())
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                </span>
                <h1 class="mb-2 text-2xl font-bold text-ink">Dziękujemy za zakup!</h1>
                <p class="mb-6 text-muted">Płatność została zaksięgowana. Wysłaliśmy dostęp do materiału na adres {{ $order->buyer_email }}.</p>
                <a href="{{ route('sklep.download', $order->access_token) }}"
                    class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                    <i class="fa-solid fa-download" aria-hidden="true"></i> Odbierz materiał teraz
                </a>
            @else
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-2xl text-amber-700">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                </span>
                <h1 class="mb-2 text-2xl font-bold text-ink">Przetwarzamy płatność</h1>
                <p class="text-muted">Gdy tylko Przelewy24 potwierdzi wpłatę, wyślemy link do materiału na adres {{ $order->buyer_email }}. Zwykle trwa to kilka minut.</p>
            @endif
        </div>
    </section>
@endsection
