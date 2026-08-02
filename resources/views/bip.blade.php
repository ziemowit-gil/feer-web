@extends('layouts.site')

@section('title', 'Biuletyn Informacji Publicznej — ' . $siteSettings->site_name)
@section('meta_description', 'Co znajdziesz w Biuletynie Informacji Publicznej Fundacji FEER i dlaczego go prowadzimy.')

@section('content')
    @php
        $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg');

        // Blok identyfikacyjny podmiotu — wymóg § 10 rozporządzenia MSWiA.
        $hasSubjectData = $siteSettings->contact_email
            || $siteSettings->contact_phone
            || $siteSettings->contact_address
            || $siteSettings->hasRegistryData()
            || $siteSettings->bip_editor_name;

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

    {{-- ── Identyfikacja podmiotu — wymóg § 10 rozporządzenia MSWiA ── --}}
    @if ($hasSubjectData)
        <section class="border-b border-gray-200 bg-gray-50 px-4 py-6" aria-label="Dane identyfikacyjne podmiotu BIP">
            <div class="mx-auto max-w-4xl">
                <div class="flex flex-wrap gap-x-10 gap-y-4 text-sm">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted mb-1">Podmiot prowadzący BIP</p>
                        <p class="font-semibold text-ink text-base">{{ $siteSettings->site_name }}</p>
                        @if ($siteSettings->contact_address || $siteSettings->contact_city)
                            <p class="text-muted mt-0.5">
                                {{ $siteSettings->contact_address }}
                                @if ($siteSettings->contact_address && $siteSettings->contact_city), @endif
                                {{ $siteSettings->contact_city }}
                            </p>
                        @endif
                    </div>

                    @if ($siteSettings->contact_email || $siteSettings->contact_phone)
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-muted mb-1">Kontakt</p>
                            @if ($siteSettings->contact_email)
                                <p>
                                    <a href="mailto:{{ $siteSettings->contact_email }}"
                                        class="text-brand hover:text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-brand">
                                        {{ $siteSettings->contact_email }}
                                    </a>
                                </p>
                            @endif
                            @if ($siteSettings->contact_phone)
                                <p class="text-muted">{{ $siteSettings->contact_phone }}</p>
                            @endif
                        </div>
                    @endif

                    @if ($siteSettings->hasRegistryData())
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-muted mb-1">Dane rejestrowe</p>
                            @if ($siteSettings->krs_number)
                                <p class="text-muted">KRS: <span class="font-mono font-semibold text-ink">{{ $siteSettings->krs_number }}</span></p>
                            @endif
                            @if ($siteSettings->nip_number)
                                <p class="text-muted">NIP: <span class="font-mono font-semibold text-ink">{{ $siteSettings->nip_number }}</span></p>
                            @endif
                            @if ($siteSettings->regon_number)
                                <p class="text-muted">REGON: <span class="font-mono font-semibold text-ink">{{ $siteSettings->regon_number }}</span></p>
                            @endif
                        </div>
                    @endif

                    @if ($siteSettings->bip_editor_name || $siteSettings->bip_editor_email)
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-muted mb-1">Redaktor BIP</p>
                            @if ($siteSettings->bip_editor_name)
                                <p class="font-semibold text-ink">{{ $siteSettings->bip_editor_name }}</p>
                            @endif
                            @if ($siteSettings->bip_editor_email)
                                <p>
                                    <a href="mailto:{{ $siteSettings->bip_editor_email }}"
                                        class="text-brand hover:text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-brand">
                                        {{ $siteSettings->bip_editor_email }}
                                    </a>
                                </p>
                            @endif
                        </div>
                    @endif

                    <div class="ml-auto flex flex-col items-end justify-center gap-2">
                        <a href="{{ route('bip.changelog') }}"
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand transition focus-visible:outline-2 focus-visible:outline-brand">
                            <i class="fa-solid fa-clock-rotate-left text-[0.65rem]" aria-hidden="true"></i>
                            Rejestr zmian BIP
                        </a>
                        @if ($siteSettings->bip_gov_url)
                            <a href="{{ $siteSettings->bip_gov_url }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted hover:border-brand hover:text-brand transition focus-visible:outline-2 focus-visible:outline-brand">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[0.65rem]" aria-hidden="true"></i>
                                Podmiot w rejestrze gov.pl
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

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

    {{-- ── Dokumenty BIP (gdy moduł włączony i istnieją opublikowane dokumenty) ── --}}
    @if ($documents->isNotEmpty())
        <section class="border-t border-gray-100 bg-gray-50 px-4 py-16" aria-labelledby="bip-documents-heading">
            <div class="mx-auto max-w-4xl">
                <h2 id="bip-documents-heading" class="mb-2 text-2xl font-extrabold text-ink">
                    Dokumenty publiczne
                </h2>
                <p class="mb-10 text-muted">
                    Dokumenty uporządkowane według kategorii — kliknij tytuł, aby zobaczyć pełną treść lub pobrać pliki.
                </p>

                @foreach (\App\Models\BipDocument::CATEGORIES as $catKey => $catLabel)
                    @if ($documents->has($catKey))
                        <div class="mb-10">
                            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-brand">
                                <span class="h-px flex-1 bg-brand/20"></span>
                                {{ $catLabel }}
                                <span class="h-px flex-1 bg-brand/20"></span>
                            </h3>

                            <ul class="space-y-3" role="list">
                                @foreach ($documents[$catKey] as $doc)
                                    <li class="group rounded-xl border border-gray-200 bg-white px-5 py-4 transition hover:border-brand/30 hover:shadow-sm">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <a href="{{ route('bip.document', $doc->slug) }}"
                                                    class="text-base font-semibold text-ink group-hover:text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                                    {{ $doc->title }}
                                                </a>
                                                @if ($doc->summary)
                                                    <p class="mt-1 text-sm text-muted leading-snug">{{ $doc->summary }}</p>
                                                @endif
                                            </div>
                                            @php $files = $doc->getMedia('files'); @endphp
                                            @if ($files->isNotEmpty())
                                                <span class="flex-none rounded-full bg-brand/10 px-2.5 py-1 text-xs font-bold text-brand">
                                                    <i class="fa-solid fa-paperclip mr-1" aria-hidden="true"></i>
                                                    {{ $files->count() }} {{ trans_choice('plik|pliki|plików', $files->count()) }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Metadane BIP — wymóg ustawowy --}}
                                        <dl class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-muted">
                                            <div class="flex items-center gap-1">
                                                <dt class="font-semibold">Dodano:</dt>
                                                <dd>
                                                    <time datetime="{{ $doc->created_at->toIso8601String() }}">
                                                        {{ $doc->created_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                                    </time>
                                                </dd>
                                            </div>
                                            @if ($doc->creator)
                                                <div class="flex items-center gap-1">
                                                    <dt class="font-semibold">Wprowadził/-a:</dt>
                                                    <dd>{{ $doc->creator->name }}</dd>
                                                </div>
                                            @endif
                                            @if ($doc->updated_at->ne($doc->created_at))
                                                <div class="flex items-center gap-1">
                                                    <dt class="font-semibold">Zmieniono:</dt>
                                                    <dd>
                                                        <time datetime="{{ $doc->updated_at->toIso8601String() }}">
                                                            {{ $doc->updated_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                                        </time>
                                                    </dd>
                                                </div>
                                            @endif
                                        </dl>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif
@endsection
