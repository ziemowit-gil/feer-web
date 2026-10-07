@extends('layouts.site')

@section('title', 'Potwierdzenie zamówienia — ' . $siteSettings->site_name)

@section('content')
    @php
        $feer = ($siteSettings->site_template ?? 'default') === 'feer';
        $card = $feer ? 'rounded-md bg-gray-50' : 'rounded-xl border border-gray-200 bg-white';
        $inp = $feer ? 'w-full rounded-t-md rounded-b-none border-0 border-b-2 border-gray-500 bg-gray-100 text-base text-ink focus:border-brand focus:bg-white focus:outline-none focus:ring-0' : 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand';
        $btn = $feer ? 'rounded-md' : 'rounded';
    @endphp
    <section class="mx-auto max-w-2xl px-4 py-12">
        <div class="{{ $card }} p-6 text-center sm:p-8">
            @if ($order->isPaid())
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center {{ $feer ? 'rounded-md' : 'rounded-full' }} bg-green-100 text-2xl text-green-800">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                </span>
                <h1 class="mb-2 text-2xl font-bold text-ink">Dziękujemy za zakup!</h1>
                <p class="mb-6 text-muted">Płatność została zaksięgowana. Wysłaliśmy dostęp do materiałów na adres {{ $order->buyer_email }}.</p>
                <a href="{{ route('sklep.download', $order->access_token) }}"
                    class="inline-flex min-h-11 items-center gap-2 {{ $btn }} bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                    <i class="fa-solid fa-download" aria-hidden="true"></i> Odbierz materiały teraz
                </a>
            @else
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center {{ $feer ? 'rounded-md' : 'rounded-full' }} bg-amber-100 text-2xl text-amber-900">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                </span>
                <h1 class="mb-2 text-2xl font-bold text-ink">Przetwarzamy płatność</h1>
                <p class="text-muted">Gdy tylko Przelewy24 potwierdzi wpłatę, wyślemy link do materiałów na adres {{ $order->buyer_email }}. Zwykle trwa to kilka minut.</p>
            @endif
        </div>
    </section>
@endsection
