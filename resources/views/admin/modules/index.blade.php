@extends('admin.layout')

@section('title', 'Moduły')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-bold text-ink">Moduły systemu</h1>
            <p class="text-sm text-muted">Zarządzaj wtyczkami i rozszerzeniami CMS-a</p>
        </div>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-muted">
            {{ $modules->count() }} {{ $modules->count() === 1 ? 'moduł' : ($modules->count() < 5 ? 'moduły' : 'modułów') }}
        </span>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
            <i class="fa-solid fa-circle-xmark text-red-500" aria-hidden="true"></i>
            {{ session('error') }}
        </div>
    @endif

    @if ($modules->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <i class="fa-solid fa-puzzle-piece text-3xl text-gray-300" aria-hidden="true"></i>
            <p class="mt-3 font-bold text-ink">Brak modułów</p>
            <p class="mt-1 text-sm text-muted">Umieść moduły w katalogu <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">modules/</code> i odśwież stronę.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($modules as $id => $manifest)
                @php
                    $status = app(\App\Modules\ModuleManager::class)->status($id);
                    $isActive   = $status === 'active';
                    $isInactive = $status === 'inactive';
                    // null = not installed
                @endphp
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">

                        {{-- Metadane modułu --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-ink">{{ $manifest->name }}</h2>

                                @if ($isActive)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-[11px] font-bold text-green-700">
                                        <span class="relative flex h-1.5 w-1.5">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-60"></span>
                                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        </span>
                                        Aktywny
                                    </span>
                                @elseif ($isInactive)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">Nieaktywny</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-bold text-gray-500">Niezainstalowany</span>
                                @endif

                                <span class="text-[11px] text-muted">v{{ $manifest->version }}</span>
                            </div>

                            @if ($manifest->description)
                                <p class="mt-1 text-sm text-muted">{{ $manifest->description }}</p>
                            @endif

                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-muted">
                                @if ($manifest->author)
                                    <span><i class="fa-solid fa-user-pen mr-1" aria-hidden="true"></i>{{ $manifest->author }}</span>
                                @endif
                                <span class="font-mono">{{ $id }}</span>
                                @if ($manifest->requires)
                                    <span class="text-amber-600">
                                        <i class="fa-solid fa-link mr-1" aria-hidden="true"></i>Wymaga: {{ implode(', ', $manifest->requires) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Akcje --}}
                        <div class="flex flex-none items-center gap-2">
                            @if ($isActive)
                                <form method="POST" action="{{ route('admin.moduly.deactivate', $id) }}"
                                    data-confirm="Dezaktywować moduł „{{ $manifest->name }}"? Jego funkcje przestaną działać.">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 transition hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1">
                                        <i class="fa-solid fa-pause text-[10px]" aria-hidden="true"></i>
                                        Dezaktywuj
                                    </button>
                                </form>
                            @elseif ($isInactive)
                                <form method="POST" action="{{ route('admin.moduly.activate', $id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-1.5 text-xs font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1">
                                        <i class="fa-solid fa-play text-[10px]" aria-hidden="true"></i>
                                        Aktywuj
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.moduly.install', $id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-ink transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-1">
                                        <i class="fa-solid fa-download text-[10px]" aria-hidden="true"></i>
                                        Zainstaluj
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($isActive)
                        <div class="border-t border-gray-100 bg-gray-50 px-5 py-2">
                            <p class="text-[11px] text-muted">
                                <i class="fa-solid fa-code mr-1" aria-hidden="true"></i>
                                Provider: <code class="font-mono">{{ $manifest->provider }}</code>
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
