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

            @if (session('status'))
                <p class="mb-4 rounded-lg bg-green-50 px-4 py-2 text-sm font-bold text-green-700">{{ session('status') }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                @if ($inCart)
                    <span class="inline-flex items-center gap-2 rounded bg-gray-100 px-5 py-2.5 text-sm font-bold text-muted">
                        <i class="fa-solid fa-check" aria-hidden="true"></i> Materiał jest już w koszyku
                    </span>
                @else
                    <form method="POST" action="{{ route('sklep.cart.add', $material) }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                            <i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Dodaj do koszyka
                        </button>
                    </form>
                @endif

                <a href="{{ route('sklep.cart') }}" class="text-sm font-bold text-brand hover:underline">
                    Przejdź do koszyka →
                </a>
            </div>
        </div>
    </section>
@endsection
