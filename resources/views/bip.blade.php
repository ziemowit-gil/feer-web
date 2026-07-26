@extends('layouts.site')

@section('title', 'Biuletyn Informacji Publicznej — ' . $siteSettings->site_name)
@section('meta_description', 'Przejdź do Biuletynu Informacji Publicznej ' . $siteSettings->site_name . '.')

@section('content')
    @php
        $bipDefault = "Świadomie rozdzielamy naszą bieżącą działalność od kwestii formalnych. Na tej stronie pokazujemy to, co robimy — projekty, wydarzenia i efekty naszej pracy. Natomiast wszystkie sprawy urzędowe: dokumenty, sprawozdania i informacje wymagane prawem prowadzimy w osobnym Biuletynie Informacji Publicznej.\n\nDzięki temu podziałowi obie części są przejrzyste: działania pozostają czytelne dla odbiorców, a dokumentacja formalna jest zawsze dostępna w ustandaryzowanej, trwałej formie zgodnej z wymogami.\n\nKliknij poniżej, aby przejść do pełnego Biuletynu Informacji Publicznej.";
    @endphp

    <section class="mx-auto max-w-2xl px-4 py-16 text-center">
        <span class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-brand-light text-2xl text-brand" aria-hidden="true">
            <i class="fa-solid fa-landmark"></i>
        </span>
        <h1 class="mb-4 text-3xl font-bold text-ink">Biuletyn Informacji Publicznej</h1>

        <div class="prose mx-auto mb-8 max-w-none text-left text-ink">
            {!! nl2br(e($siteSettings->bip_intro ?: $bipDefault)) !!}
        </div>

        @if ($siteSettings->bip_url)
            <a href="{{ $siteSettings->bip_url }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Przejdź do BIP <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
        @else
            <p class="text-muted">Adres BIP nie został jeszcze skonfigurowany w ustawieniach serwisu.</p>
        @endif
    </section>
@endsection
