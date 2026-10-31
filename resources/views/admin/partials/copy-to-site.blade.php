{{-- Kopiuj do witryny: lista rozwijana z wyborem witryny docelowej (tylko administrator, gdy jest inna witryna).
     Parametry: $action (adres POST), $siteId (witryna elementu), $label (nazwa elementu, dla czytników ekranu),
     $note (opcjonalna uwaga pod listą). --}}
@php
    $copyTargets = auth()->user()?->isAdmin()
        ? \App\Models\SiteSetting::query()->notTrashed()->where('id', '!=', $siteId)->orderBy('site_name')->get(['id', 'site_name', 'slug'])
        : collect();
    $copyId = 'copy-to-site-'.md5($action);
@endphp
@if ($copyTargets->isNotEmpty())
    <div class="relative inline-block" x-data="{ open: false }" @keydown.escape="open = false" @click.outside="open = false">
        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
            title="Kopiuj do witryny" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="{{ $copyId }}">
            <i class="fa-solid fa-share-from-square" aria-hidden="true"></i><span class="sr-only">Kopiuj do witryny: {{ $label }}</span>
        </button>
        <form id="{{ $copyId }}" x-show="open" x-cloak method="POST" action="{{ $action }}"
            class="absolute right-0 top-full z-30 mt-2 w-72 space-y-3 rounded-lg border border-gray-200 bg-white p-4 text-left shadow-xl">
            @csrf
            <div>
                <label for="{{ $copyId }}-site" class="mb-1 block text-xs font-bold text-ink">Witryna docelowa</label>
                <select id="{{ $copyId }}-site" name="target_site_id" required class="w-full rounded border-gray-500 py-1.5 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    <option value="" disabled selected>— wybierz witrynę —</option>
                    @foreach ($copyTargets as $site)
                        <option value="{{ $site->id }}">{{ $site->site_name ?: ($site->slug ?: 'Witryna '.$site->id) }}</option>
                    @endforeach
                </select>
            </div>
            @isset($note)<p class="text-xs text-ink">{{ $note }}</p>@endisset
            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded bg-brand-dark px-3 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-copy" aria-hidden="true"></i> Kopiuj
            </button>
        </form>
    </div>
@endif
