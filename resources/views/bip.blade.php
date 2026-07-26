@extends('layouts.site')

@section('title', 'Biuletyn Informacji Publicznej — ' . $siteSettings->site_name)
@section('meta_description', 'Dlaczego prowadzimy osobny Biuletyn Informacji Publicznej i jak do niego przejść.')

@section('content')
    @php
        $bipDefault = "Prowadzimy Biuletyn Informacji Publicznej, choć organizacje pozarządowe co do zasady nie mają takiego obowiązku. Wiele z nich i tak publikuje sprawozdania i najważniejsze dokumenty — my robimy to świadomie i konsekwentnie.\n\nRobimy to, aby oddzielić naszą bieżącą działalność od spraw formalnych. Na tej stronie pokazujemy, co robimy: projekty, wydarzenia i efekty pracy. W Biuletynie zbieramy natomiast dokumenty urzędowe, sprawozdania i informacje wymagane prawem — w jednym, trwałym i ustandaryzowanym miejscu.\n\nDzięki temu obie części pozostają przejrzyste, a Ty łatwo znajdziesz to, czego szukasz.";
        $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg');
    @endphp

    <section class="bg-gray-50 px-4 py-16">
        <div class="mx-auto max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-8 pt-10 pb-8 text-center">
                <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="mx-auto mb-5 h-20 w-auto object-contain">
                <h1 class="text-2xl font-bold text-ink md:text-3xl">Biuletyn Informacji Publicznej</h1>
            </div>

            <div class="px-8 py-8">
                <div class="prose max-w-none text-ink">
                    {!! nl2br(e($siteSettings->bip_intro ?: $bipDefault)) !!}
                </div>

                <div class="mt-8 text-center">
                    @if ($siteSettings->bip_url)
                        <a href="{{ $siteSettings->bip_url }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full bg-brand px-7 py-3 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            Przejdź do BIP <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        </a>
                    @else
                        <p class="text-sm text-muted">Adres BIP nie został jeszcze skonfigurowany w ustawieniach serwisu.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
