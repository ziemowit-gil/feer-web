@extends('layouts.site')

@section('title', 'Rejestr zmian BIP — ' . $siteSettings->site_name)
@section('meta_description', 'Publiczny rejestr wszystkich zmian dokumentów Biuletynu Informacji Publicznej.')

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
                            <span class="mx-1" aria-hidden="true">›</span>
                        </p>
                        <p class="mt-0.5 font-semibold text-ink">Rejestr zmian</p>
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

    {{-- ── Układ: boczne menu + treść ── --}}
    <div class="mx-auto max-w-5xl px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-[220px_1fr]">

            {{-- ── Boczne menu ── --}}
            <aside class="lg:border-r lg:border-gray-100 lg:pr-6">
                @include('bip._sidebar')
            </aside>

            {{-- ── Treść: rejestr zmian ── --}}
            <div>
                <header class="mb-6">
                    <h1 class="text-2xl font-extrabold text-ink sm:text-3xl">Rejestr zmian BIP</h1>
                    <p class="mt-3 text-muted leading-relaxed">
                        Publiczny dziennik wszystkich operacji na dokumentach Biuletynu Informacji Publicznej.
                        Każda zmiana jest rejestrowana automatycznie wraz z datą i osobą, która jej dokonała.
                    </p>
                </header>

                @if ($entries->isEmpty())
                    <p class="rounded-lg border border-gray-200 bg-gray-50 px-6 py-8 text-center text-muted">
                        Brak zarejestrowanych zmian.
                    </p>
                @else
                    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Rejestr zmian dokumentów BIP posortowany od najnowszych</caption>
                            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Data i czas</th>
                                    <th scope="col" class="px-4 py-3">Operacja</th>
                                    <th scope="col" class="px-4 py-3">Dokument</th>
                                    <th scope="col" class="px-4 py-3">Autor zmiany</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($entries as $entry)
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-4 py-3 text-muted">
                                            <time datetime="{{ $entry->created_at->toIso8601String() }}">
                                                {{ $entry->created_at->locale('pl')->isoFormat('D MMM YYYY, HH:mm') }}
                                            </time>
                                        </td>
                                        <td class="px-4 py-3">
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
                                        <td class="px-4 py-3 font-medium text-ink">
                                            @if ($documentMap[$entry->subject_id] ?? null)
                                                <a href="{{ route('bip.document', $documentMap[$entry->subject_id]) }}"
                                                    class="text-brand hover:text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                                    {{ $entry->subject_label }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ $entry->subject_label }}</span>
                                                @if ($entry->event === 'deleted')
                                                    <span class="ml-1 text-xs text-muted">(usunięty)</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-muted">
                                            {{ $entry->user_name ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($entries->hasPages())
                        <div class="mt-6">
                            {{ $entries->links() }}
                        </div>
                    @endif
                @endif

                <div class="mt-8 border-t border-gray-100 pt-6">
                    <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="h-7 w-auto object-contain opacity-50">
                </div>
            </div>

        </div>
    </div>
@endsection
