@extends('layouts.site')

@section('title', 'Biuletyn Informacji Publicznej — ' . $siteSettings->site_name)
@section('meta_description', 'Biuletyn Informacji Publicznej ' . $siteSettings->site_name . ' — dokumenty publiczne, informacje o organizacji i rejestr zmian.')

@section('content')
    @php
        $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg');

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

    {{-- ── Nagłówek strony BIP ── --}}
    <div class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="h-14 w-auto flex-none object-contain">
                    <div>
                        <h1 class="text-xl font-extrabold leading-tight text-ink sm:text-2xl">Biuletyn Informacji Publicznej</h1>
                        <p class="mt-0.5 text-sm text-muted">{{ $siteSettings->site_name }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if (! $isExternal)
                        <a href="{{ route('bip.changelog') }}"
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-ink transition hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                            <i class="fa-solid fa-clock-rotate-left text-[0.65rem]" aria-hidden="true"></i>
                            Rejestr zmian
                        </a>
                    @endif
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted transition hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                        <i class="fa-solid fa-arrow-left text-[0.65rem]" aria-hidden="true"></i>
                        Strona główna organizacji
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Treść główna + panel boczny ── --}}
    <div class="mx-auto max-w-5xl px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-[1fr_280px]">

            {{-- ── Kolumna główna ── --}}
            <main>
                {{-- Treść opisowa BIP --}}
                <div class="prose max-w-none text-ink [&_h2]:text-ink [&_h3]:text-brand [&_li::marker]:font-bold [&_li::marker]:text-brand">
                    {!! $siteSettings->bip_intro ?: $bipDefault !!}
                </div>

                @if ($isExternal)
                    {{-- Tryb zewnętrzny: przycisk do zewnętrznego BIP --}}
                    <div class="mt-8">
                        @if ($siteSettings->bip_url)
                            <a href="{{ $siteSettings->bip_url }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-full bg-brand px-7 py-3 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                Przejdź do pełnego BIP <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                            </a>
                        @else
                            <p class="text-sm text-muted">Adres BIP nie został jeszcze skonfigurowany w ustawieniach serwisu.</p>
                        @endif
                    </div>
                @elseif ($documents->isNotEmpty())
                    {{-- Tryb wbudowany: lista dokumentów --}}
                    <div class="mt-10">
                        <h2 class="mb-1 text-xl font-extrabold text-ink">Dokumenty publiczne</h2>
                        <p class="mb-8 text-sm text-muted">
                            Kliknij tytuł dokumentu, aby zobaczyć pełną treść lub pobrać pliki.
                        </p>

                        @foreach (\App\Models\BipDocument::CATEGORIES as $catKey => $catLabel)
                            @if ($documents->has($catKey))
                                <div class="mb-8">
                                    <h3 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-brand">
                                        <span class="h-px flex-1 bg-brand/20" aria-hidden="true"></span>
                                        {{ $catLabel }}
                                        <span class="h-px flex-1 bg-brand/20" aria-hidden="true"></span>
                                    </h3>

                                    <ul class="space-y-2" role="list">
                                        @foreach ($documents[$catKey] as $doc)
                                            <li class="group rounded-lg border border-gray-200 bg-white px-4 py-3 transition hover:border-brand/30 hover:shadow-sm">
                                                <div class="flex flex-wrap items-start justify-between gap-2">
                                                    <div class="min-w-0 flex-1">
                                                        <a href="{{ route('bip.document', $doc->slug) }}"
                                                            class="font-semibold text-ink group-hover:text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                                            {{ $doc->title }}
                                                        </a>
                                                        @if ($doc->summary)
                                                            <p class="mt-0.5 text-sm text-muted leading-snug">{{ $doc->summary }}</p>
                                                        @endif
                                                    </div>
                                                    @php $files = $doc->getMedia('files'); @endphp
                                                    @if ($files->isNotEmpty())
                                                        <span class="flex-none rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand">
                                                            <i class="fa-solid fa-paperclip mr-1" aria-hidden="true"></i>
                                                            {{ $files->count() }} {{ trans_choice('plik|pliki|plików', $files->count()) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Metadane — wymóg ustawowy --}}
                                                <dl class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-muted">
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
                                                            <dt class="font-semibold">Przez:</dt>
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
                @endif
            </main>

            {{-- ── Panel boczny: dane identyfikacyjne podmiotu (§ 10 MSWiA) ── --}}
            @if ($hasSubjectData)
                <aside aria-label="Dane identyfikacyjne podmiotu BIP">
                    <div class="sticky top-4 rounded-xl border border-gray-200 bg-gray-50 p-5 text-sm">
                        <h2 class="mb-4 text-xs font-bold uppercase tracking-wide text-muted">Podmiot prowadzący BIP</h2>

                        <p class="font-semibold text-ink">{{ $siteSettings->site_name }}</p>

                        @if ($siteSettings->contact_address || $siteSettings->contact_city)
                            <p class="mt-0.5 text-muted">
                                {{ $siteSettings->contact_address }}
                                @if ($siteSettings->contact_address && $siteSettings->contact_city), @endif
                                {{ $siteSettings->contact_city }}
                            </p>
                        @endif

                        @if ($siteSettings->contact_email || $siteSettings->contact_phone)
                            <div class="mt-3 space-y-0.5">
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
                            <div class="mt-3 space-y-0.5">
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
                            <div class="mt-3 border-t border-gray-200 pt-3">
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

                        @if ($siteSettings->bip_gov_url)
                            <div class="mt-3 border-t border-gray-200 pt-3">
                                <a href="{{ $siteSettings->bip_gov_url }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-muted transition hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[0.6rem]" aria-hidden="true"></i>
                                    Podmiot w rejestrze gov.pl
                                </a>
                            </div>
                        @endif
                    </div>
                </aside>
            @endif

        </div>
    </div>
@endsection
