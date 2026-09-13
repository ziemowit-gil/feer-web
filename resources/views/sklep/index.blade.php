@extends('layouts.site')

@section('title', 'Sklep — ' . $siteSettings->site_name)
@section('meta_description', 'Sklep z materiałami edukacyjnymi ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Sklep', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-bold text-ink">Sklep</h1>
            <a href="{{ route('sklep.cart') }}"
                class="inline-flex items-center gap-2 rounded border-2 border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light">
                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                Koszyk @if (count($cartIds)) ({{ count($cartIds) }}) @endif
            </a>
        </div>

        @if (session('status'))
            <p class="mb-6 rounded-lg bg-green-50 px-4 py-2 text-sm font-bold text-green-700">{{ session('status') }}</p>
        @endif

        @if ($materials->isEmpty())
            <p class="text-muted">Obecnie brak materiałów dostępnych do zakupu.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($materials as $material)
                    <article class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-brand/40 hover:shadow-lg">
                        <a href="{{ route('sklep.show', $material) }}" class="relative flex aspect-video items-center justify-center bg-gray-100">
                            <i class="fa-solid {{ $material->typeIcon() }} text-6xl text-brand/30" aria-hidden="true"></i>
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-brand shadow-sm">
                                <i class="fa-solid {{ $material->typeIcon() }}" aria-hidden="true"></i>
                                {{ \App\Models\EducationalMaterial::TYPES[$material->type] ?? $material->type }}
                            </span>
                        </a>
                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="mb-1 text-lg font-bold text-ink">
                                <a href="{{ route('sklep.show', $material) }}" class="hover:text-brand">{{ $material->title }}</a>
                            </h2>
                            <p class="mb-4 flex-1 text-sm text-muted">{{ $material->description }}</p>
                            <div class="mt-auto flex items-center justify-between gap-3">
                                <span class="text-lg font-bold text-brand">{{ $material->priceFormatted }}</span>
                                @if (in_array($material->id, $cartIds, true))
                                    <span class="inline-flex items-center gap-2 rounded bg-gray-100 px-4 py-2 text-sm font-bold text-muted">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i> W koszyku
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('sklep.cart.add', $material) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                                            <i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Do koszyka
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
