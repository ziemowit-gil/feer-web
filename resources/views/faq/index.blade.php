@extends('layouts.site')

@section('title', 'FAQ — najczęstsze pytania — ' . $siteSettings->site_name)
@section('meta_description', 'Odpowiedzi na najczęściej zadawane pytania — ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'FAQ', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Najczęstsze pytania</h1>
        <p class="mb-8 text-muted">Odpowiedzi na pytania, które słyszymy najczęściej. Nie znalazłeś swojego? <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">Napisz do nas</a>.</p>

        @forelse ($groups as $category => $items)
            <div class="mb-8">
                @if ($category !== '')
                    <h2 class="mb-3 text-lg font-bold text-brand">{{ $category }}</h2>
                @endif
                <div class="space-y-2">
                    @foreach ($items as $faq)
                        <details class="group rounded-xl border border-gray-200 bg-white [&[open]]:border-gray-300">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 font-bold text-ink focus-visible:rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                                <span>{{ $faq->question }}</span>
                                <i class="fa-solid fa-chevron-down flex-none text-sm text-muted transition-transform group-open:rotate-180" aria-hidden="true"></i>
                            </summary>
                            <div class="-mt-1 whitespace-pre-line px-5 pb-4 text-gray-700">{{ $faq->answer }}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-muted">
                Nie dodaliśmy jeszcze żadnych pytań. Zajrzyj wkrótce albo <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">napisz do nas</a>.
            </div>
        @endforelse
    </section>
@endsection
