@extends('layouts.site')

@section('title', $bipDocument->title . ' — BIP — ' . $siteSettings->site_name)
@section('meta_description', $bipDocument->summary ?: 'Dokument Biuletynu Informacji Publicznej: ' . $bipDocument->title)

@section('content')
    @php $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg'); @endphp

    {{-- Breadcrumb --}}
    <nav class="border-b border-gray-100 bg-gray-50" aria-label="Okruszki nawigacji">
        <ol class="mx-auto flex max-w-4xl flex-wrap items-center gap-1 px-4 py-3 text-sm text-muted">
            <li><a href="{{ route('home') }}" class="hover:text-brand hover:underline">Strona główna</a></li>
            <li aria-hidden="true"><span class="mx-1">›</span></li>
            <li><a href="{{ route('bip') }}" class="hover:text-brand hover:underline">BIP</a></li>
            <li aria-hidden="true"><span class="mx-1">›</span></li>
            <li aria-current="page" class="text-ink font-semibold">{{ $bipDocument->title }}</li>
        </ol>
    </nav>

    <article class="mx-auto max-w-4xl px-4 py-10" aria-labelledby="doc-title">

        {{-- Nagłówek dokumentu --}}
        <header class="mb-8">
            <div class="mb-3">
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                    {{ $bipDocument->categoryLabel() }}
                </span>
            </div>

            <h1 id="doc-title" class="text-3xl font-extrabold text-ink leading-snug">
                {{ $bipDocument->title }}
            </h1>

            @if ($bipDocument->summary)
                <p class="mt-4 text-lg text-muted leading-relaxed">{{ $bipDocument->summary }}</p>
            @endif

            {{-- Metadane BIP — wymóg ustawowy: kto i kiedy wprowadził/zmienił --}}
            <dl class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-xs text-muted border-t border-gray-100 pt-4">
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-user text-[0.65rem]" aria-hidden="true"></i>
                    <dt class="font-semibold">Wprowadził/-a:</dt>
                    <dd>{{ $bipDocument->creator?->name ?? 'redakcja' }}</dd>
                </div>
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-plus text-[0.65rem]" aria-hidden="true"></i>
                    <dt class="font-semibold">Data dodania:</dt>
                    <dd>
                        <time datetime="{{ $bipDocument->created_at->toIso8601String() }}">
                            {{ $bipDocument->created_at->locale('pl')->isoFormat('D MMMM YYYY') }}
                        </time>
                    </dd>
                </div>
                @if ($bipDocument->updated_at->ne($bipDocument->created_at))
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-pen text-[0.65rem]" aria-hidden="true"></i>
                        <dt class="font-semibold">Ostatnia zmiana:</dt>
                        <dd>
                            <time datetime="{{ $bipDocument->updated_at->toIso8601String() }}">
                                {{ $bipDocument->updated_at->locale('pl')->isoFormat('D MMMM YYYY') }}
                            </time>
                            @if ($bipDocument->updater)
                                — {{ $bipDocument->updater->name }}
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </header>

        {{-- Treść dokumentu --}}
        @if ($bipDocument->content)
            <div class="prose max-w-none text-ink [&_h2]:text-ink [&_h3]:text-brand [&_li::marker]:text-brand [&_li::marker]:font-bold [&_a]:text-brand [&_a:hover]:text-brand-dark">
                {!! $bipDocument->content !!}
            </div>
        @endif

        {{-- Pliki do pobrania --}}
        @if ($bipDocument->attachedFiles()->isNotEmpty())
            <section class="mt-10" aria-labelledby="files-heading">
                <h2 id="files-heading" class="mb-4 text-lg font-bold text-ink">
                    <i class="fa-solid fa-paperclip mr-1 text-brand" aria-hidden="true"></i>
                    Pliki do pobrania
                </h2>
                <ul class="space-y-2" role="list">
                    @foreach ($bipDocument->attachedFiles() as $media)
                        <li class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <i class="fa-solid {{ $bipDocument->fileIcon($media) }} text-xl text-brand flex-none" aria-hidden="true"></i>
                            <div class="min-w-0 flex-1">
                                <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener"
                                    class="font-semibold text-brand hover:text-brand-dark hover:underline break-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    {{ $media->file_name }}
                                </a>
                                <span class="ml-2 text-xs text-muted">({{ $media->human_readable_size }})</span>
                            </div>
                            <a href="{{ $media->getUrl() }}" download
                                class="flex-none rounded-full bg-brand/10 px-3 py-1 text-xs font-bold text-brand hover:bg-brand hover:text-white transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                aria-label="Pobierz {{ $media->file_name }}">
                                <i class="fa-solid fa-download mr-1" aria-hidden="true"></i> Pobierz
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Logo BIP i powrót --}}
        <footer class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 pt-6">
            <a href="{{ route('bip') }}"
                class="inline-flex items-center gap-2 text-sm text-brand hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Wróć do BIP
            </a>
            <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="h-8 w-auto object-contain opacity-60">
        </footer>

    </article>
@endsection
