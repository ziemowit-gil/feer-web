@extends('layouts.site')

@section('title', 'Koszyk — Sklep — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Sklep', 'url' => route('sklep.index')],
        ['label' => 'Koszyk', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="mb-8 text-3xl font-bold text-ink">Koszyk</h1>

        @if (session('status'))
            <p class="mb-6 rounded-lg bg-green-50 px-4 py-2 text-sm font-bold text-green-700">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="mb-6 rounded-lg bg-red-50 px-4 py-2 text-sm font-bold text-red-700">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ session('error') }}
            </p>
        @endif

        @if ($items->isEmpty())
            <p class="text-muted">Twój koszyk jest pusty.
                <a href="{{ route('sklep.index') }}" class="font-bold text-brand hover:underline">Przejdź do sklepu</a>.
            </p>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                @foreach ($items as $material)
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 p-4 last:border-b-0">
                        <div class="min-w-0">
                            <p class="truncate font-bold text-ink">{{ $material->title }}</p>
                            <p class="text-sm text-muted">{{ $material->priceFormatted }}</p>
                        </div>
                        <form method="POST" action="{{ route('sklep.cart.remove', $material) }}">
                            @csrf
                            <button type="submit" class="text-muted hover:text-red-600" title="Usuń z koszyka" aria-label="Usuń {{ $material->title }} z koszyka">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
                <form method="POST" action="{{ route('sklep.cart.discount') }}" class="mb-6 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label for="code" class="mb-1 block text-sm font-bold">Kod rabatowy</label>
                        <input type="text" id="code" name="code" placeholder="np. TEST10"
                            class="w-full rounded border-gray-300 uppercase focus:border-brand focus:ring-brand">
                    </div>
                    <button type="submit" class="rounded border-2 border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light">
                        Zastosuj
                    </button>
                </form>

                <dl class="space-y-2 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Suma częściowa</dt>
                        <dd>{{ number_format($subtotal / 100, 2, ',', ' ') }} zł</dd>
                    </div>
                    @if ($discountCode)
                        <div class="flex justify-between text-green-700">
                            <dt>Rabat ({{ $discountCode->code }})</dt>
                            <dd>-{{ number_format($discountAmount / 100, 2, ',', ' ') }} zł</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-lg font-bold text-ink">
                        <dt>Razem</dt>
                        <dd>{{ number_format($total / 100, 2, ',', ' ') }} zł</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
                <form method="POST" action="{{ route('sklep.checkout') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="buyer_email" class="mb-1 block text-sm font-bold">Adres e-mail</label>
                        <input type="email" id="buyer_email" name="buyer_email" value="{{ old('buyer_email', auth()->user()->email ?? '') }}" required
                            placeholder="twoj@email.pl" autocomplete="email"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('buyer_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-muted">Na ten adres wyślemy link do pobrania materiałów po zaksięgowaniu wpłaty.</p>
                    </div>

                    <div>
                        <label for="buyer_name" class="mb-1 block text-sm font-bold">Imię i nazwisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="buyer_name" name="buyer_name" value="{{ old('buyer_name', auth()->user()->name ?? '') }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('buyer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i> Przejdź do płatności (Przelewy24)
                    </button>
                </form>
            </div>
        @endif
    </section>
@endsection
