@extends('layouts.site')

@section('title', $bipDocument->title . ' — BIP — ' . $siteSettings->site_name)
@section('meta_description', $bipDocument->summary ?: 'Dokument Biuletynu Informacji Publicznej: ' . $bipDocument->title)

@section('content')
    @php $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg'); @endphp

    {{-- ── Nagłówek BIP ── --}}
    <div class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="h-12 w-auto flex-none object-contain">
                    <div>
                        <p class="text-xs text-muted">
                            <a href="{{ route('bip') }}" class="hover:text-brand hover:underline focus-visible:outline-2 focus-visible:outline-brand">
                                Biuletyn Informacji Publicznej
                            </a>
                            <span class="mx-1 text-muted" aria-hidden="true">›</span>
                        </p>
                        <p class="mt-0.5 font-semibold text-ink leading-snug">{{ $bipDocument->title }}</p>
                    </div>
                </div>
                <a href="{{ route('home') }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted transition hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                    <i class="fa-solid fa-arrow-left text-[0.65rem]" aria-hidden="true"></i>
                    Strona główna organizacji
                </a>
            </div>
        </div>
    </div>

    {{-- ── Układ: boczne menu + treść dokumentu ── --}}
    <div class="mx-auto max-w-5xl px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-[220px_1fr]">

            {{-- ── Boczne menu ── --}}
            <aside class="lg:border-r lg:border-gray-100 lg:pr-6">
                @include('bip._sidebar')
            </aside>

            {{-- ── Treść dokumentu ── --}}
            <article aria-labelledby="doc-title">

                <header class="mb-8">
                    <div class="mb-3">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                            {{ $bipDocument->categoryLabel() }}
                        </span>
                    </div>

                    <h1 id="doc-title" class="text-2xl font-extrabold text-ink leading-snug sm:text-3xl">
                        {{ $bipDocument->title }}
                    </h1>

                    @if ($bipDocument->summary)
                        <p class="mt-4 text-lg text-muted leading-relaxed">{{ $bipDocument->summary }}</p>
                    @endif

                    {{-- Metadane BIP — wymóg ustawowy --}}
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
                    <div class="prose max-w-none text-ink [&_h2]:text-ink [&_h3]:text-brand [&_li::marker]:font-bold [&_li::marker]:text-brand [&_a]:text-brand [&_a:hover]:text-brand-dark">
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
                                    <i class="fa-solid {{ $bipDocument->fileIcon($media) }} flex-none text-xl text-brand" aria-hidden="true"></i>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener"
                                            class="break-all font-semibold text-brand hover:text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            {{ $media->file_name }}
                                        </a>
                                        <span class="ml-2 text-xs text-muted">({{ $media->human_readable_size }})</span>
                                    </div>
                                    <a href="{{ $media->getUrl() }}" download
                                        class="flex-none rounded-full bg-brand/10 px-3 py-1 text-xs font-bold text-brand transition hover:bg-brand hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                        aria-label="Pobierz {{ $media->file_name }}">
                                        <i class="fa-solid fa-download mr-1" aria-hidden="true"></i> Pobierz
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- ── Historia edycji dokumentu ── --}}
                @if ($history->isNotEmpty())
                    <section class="mt-10" aria-labelledby="history-heading">
                        <h2 id="history-heading" class="mb-4 flex items-center gap-2 text-base font-bold text-ink">
                            <i class="fa-solid fa-clock-rotate-left text-brand text-sm" aria-hidden="true"></i>
                            Historia edycji
                        </h2>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                            <table class="w-full text-left text-sm">
                                <caption class="sr-only">Historia edycji dokumentu {{ $bipDocument->title }}</caption>
                                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5">Data</th>
                                        <th scope="col" class="px-4 py-2.5">Operacja</th>
                                        <th scope="col" class="px-4 py-2.5">Autor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($history as $entry)
                                        <tr class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-4 py-2.5 text-muted">
                                                <time datetime="{{ $entry->created_at->toIso8601String() }}">
                                                    {{ $entry->created_at->locale('pl')->isoFormat('D MMM YYYY, HH:mm') }}
                                                </time>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @php
                                                    $badge = match($entry->event) {
                                                        'created' => ['bg-green-100 text-green-700', 'fa-plus', 'Dodanie'],
                                                        'updated' => ['bg-blue-100 text-blue-700', 'fa-pen', 'Edycja'],
                                                        'deleted' => ['bg-red-100 text-red-700', 'fa-trash', 'Usunięcie'],
                                                        default   => ['bg-gray-100 text-gray-600', 'fa-circle', $entry->eventLabel()],
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $badge[0] }}">
                                                    <i class="fa-solid {{ $badge[1] }} text-[0.6rem]" aria-hidden="true"></i>
                                                    {{ $badge[2] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-muted">
                                                {{ $entry->user_name ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <footer class="mt-10 border-t border-gray-100 pt-6">
                    <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="h-7 w-auto object-contain opacity-50">
                </footer>

            </article>

        </div>
    </div>
@endsection
