@extends('layouts.site')

@section('title', 'Biuletyn Informacji Publicznej — ' . $siteSettings->site_name)
@section('meta_description', 'Co znajdziesz w Biuletynie Informacji Publicznej Fundacji FEER i dlaczego go prowadzimy.')

@section('content')
    @php
        $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg');
        $bipDefault = <<<'HTML'
<h2>Co znajdziesz w Biuletynie Informacji Publicznej Fundacji FEER?</h2>
<p>Fundacja FEER stawia na pełną transparentność, jawność działania oraz budowanie zaufania. Choć przepisy prawa nie nakładają na organizacje pozarządowe sztywnego obowiązku prowadzenia Biuletynu Informacji Publicznej, wierzymy, że otwartość wobec naszych darczyńców, partnerów, uczestników projektów oraz instytucji publicznych to fundament nowoczesnego i odpowiedzialnego trzeciego sektora.</p>
<p>W tym miejscu udostępniamy kluczowe dokumenty, informacje o podejmowanych działaniach, strukturze organizacyjnej oraz gospodarowaniu środkami.</p>
<h3>Co publikujemy w naszym BIP?</h3>
<ul>
<li><strong>Aktualne dokumenty rejestrowe i prawne:</strong> Statut fundacji, wypisy z KRS oraz regulaminy wewnętrzne.</li>
<li><strong>Sprawozdawczość:</strong> Roczne sprawozdania merytoryczne i finansowe z działalności naszej organizacji.</li>
<li><strong>Informacje o realizowanych projektach:</strong> Transparentne podsumowania zadań publicznych, grantów oraz inicjatyw edukacyjnych i społecznych.</li>
<li><strong>Oświadczenia i komunikaty:</strong> Oficjalne stanowiska zarządu oraz ogłoszenia dotyczące bieżącej działalności fundacji.</li>
</ul>
<p>Masz pytanie dotyczące naszej działalności lub poszukujesz konkretnej informacji publicznej? <a href="/kontakt">Skontaktuj się z nami bezpośrednio</a> – chętnie udzielimy wszelkich wyjaśnień.</p>
HTML;
    @endphp

    <section class="relative overflow-hidden px-4 py-20">
        {{-- Dekoracje w tle: półprzezroczyste logo BIP oraz pionowy napis w rogu --}}
        <img src="{{ $bipLogo }}" alt="" aria-hidden="true"
            class="pointer-events-none absolute -right-20 top-4 w-[30rem] max-w-none opacity-[0.05]">
        <span aria-hidden="true"
            class="pointer-events-none absolute bottom-3 left-3 hidden origin-bottom-left -rotate-90 whitespace-nowrap text-4xl font-extrabold uppercase tracking-[0.35em] text-ink opacity-[0.05] lg:block">
            Biuletyn Informacji Publicznej
        </span>

        <div class="relative mx-auto max-w-3xl">
            <div class="text-center">
                <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="mx-auto mb-6 h-20 w-auto object-contain">
                <span class="mx-auto block h-1 w-16 rounded-full bg-brand" aria-hidden="true"></span>
            </div>

            <div class="prose mx-auto mt-10 max-w-none text-ink [&_h2]:text-ink [&_h3]:text-brand [&_li::marker]:text-brand [&_li::marker]:font-bold">
                {!! $siteSettings->bip_intro ?: $bipDefault !!}
            </div>

            <div class="mt-10 text-center">
                @if ($siteSettings->bip_url)
                    <a href="{{ $siteSettings->bip_url }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 rounded-full bg-brand px-7 py-3 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        Przejdź do pełnego BIP <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </a>
                @else
                    <p class="text-sm text-muted">Adres BIP nie został jeszcze skonfigurowany w ustawieniach serwisu.</p>
                @endif
            </div>
        </div>
    </section>
@endsection
