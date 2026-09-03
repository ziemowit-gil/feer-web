@extends('layouts.site')

@section('title', $material->title . ' — Sklep — ' . $siteSettings->site_name)
@section('meta_description', $material->description)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Sklep', 'url' => route('sklep.index')],
        ['label' => $material->title, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12">
        <div class="rounded-xl border border-gray-200 bg-white p-6 sm:p-8">
            <span class="mb-3 inline-flex items-center gap-1 rounded-full bg-brand-light px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-brand">
                <i class="fa-solid {{ $material->typeIcon() }}" aria-hidden="true"></i>
                {{ \App\Models\EducationalMaterial::TYPES[$material->type] ?? $material->type }}
            </span>

            <h1 class="mb-2 text-2xl font-bold text-ink">{{ $material->title }}</h1>
            <p class="mb-6 text-muted">{{ $material->description }}</p>

            <p class="mb-6 text-2xl font-bold text-brand">{{ $material->priceFormatted }}</p>

            @if (session('error'))
                <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm font-bold text-red-700">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ session('error') }}
                </p>
            @endif

            <form method="POST" action="{{ route('sklep.checkout', $material) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="buyer_email" class="mb-1 block text-sm font-bold">Adres e-mail</label>
                    <input type="email" id="buyer_email" name="buyer_email" value="{{ old('buyer_email', auth()->user()->email ?? '') }}" required
                        placeholder="twoj@email.pl" autocomplete="email"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('buyer_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-muted">Na ten adres wyślemy link do pobrania materiału po zaksięgowaniu wpłaty.</p>
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
    </section>
@endsection
