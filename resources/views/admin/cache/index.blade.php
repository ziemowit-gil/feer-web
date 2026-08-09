@extends('admin.layout')

@section('title', 'Cache — konfiguracja')

@section('content')
    @php
        $driverLabels = [
            'redis'    => ['label' => 'Redis',    'color' => 'text-green-700',  'bg' => 'bg-green-50',  'border' => 'border-green-200'],
            'database' => ['label' => 'Baza DB',  'color' => 'text-blue-700',   'bg' => 'bg-blue-50',   'border' => 'border-blue-200'],
            'file'     => ['label' => 'Plik',     'color' => 'text-amber-700',  'bg' => 'bg-amber-50',  'border' => 'border-amber-200'],
            'array'    => ['label' => 'Array',    'color' => 'text-gray-600',   'bg' => 'bg-gray-50',   'border' => 'border-gray-200'],
        ];
        $dc = $driverLabels[$driver] ?? ['label' => $driver, 'color' => 'text-gray-600', 'bg' => 'bg-gray-50', 'border' => 'border-gray-200'];

        // Wszystkie unikalne klucze TTL jakie pojawią się w formularzu.
        // Każdy renderujemy dokładnie raz.
        $defaults = \App\Models\SiteSetting::CACHE_DEFAULTS;
    @endphp

    {{-- Nagłówek --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-ink">Cache</h1>
            <p class="text-sm text-muted">Konfiguracja, status i czyszczenie cache aplikacji.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full border {{ $dc['border'] }} {{ $dc['bg'] }} px-3 py-1 text-xs font-bold {{ $dc['color'] }}">
                <i class="fa-solid fa-server" aria-hidden="true"></i>
                Sterownik: {{ $dc['label'] }}
            </span>
            <form method="POST" action="{{ route('admin.cache.flush-all') }}"
                  onsubmit="return confirm('Wyczyścić cały cache aplikacji? Dotyczy wszystkich kluczy.')">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1">
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    Wyczyść cały cache
                </button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div role="alert" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            <i class="fa-solid fa-circle-xmark mr-1.5" aria-hidden="true"></i>
            @foreach ($errors->all() as $error) {{ $error }} @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.cache.update') }}">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            @foreach ($groups as $id => $group)
                @php
                    // Klucze TTL unikalne dla named keys tej grupy (inne niż item TTL grupy)
                    $uniqueNamedTtlKeys = collect($group['named'])
                        ->where('ttl_key', '!=', $group['ttl_key'])
                        ->unique('ttl_key')
                        ->values();

                    // Named keys dzielące TTL z elementami grupy
                    $sharedTtlNamedKeys = collect($group['named'])
                        ->where('ttl_key', $group['ttl_key'])
                        ->values();
                @endphp

                <div class="rounded-xl border border-gray-200 bg-white">
                    {{-- Nagłówek grupy --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-light text-brand">
                                <i class="fa-solid {{ $group['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="font-bold text-ink">{{ $group['label'] }}</h2>
                                @if ($group['item_count'] !== null)
                                    <p class="text-xs text-muted">{{ $group['item_count'] }} {{ $group['item_count'] === 1 ? 'element' : ($group['item_count'] < 5 ? 'elementy' : 'elementów') }} w cache</p>
                                @else
                                    <p class="text-xs text-muted">Liczba elementów niedostępna dla sterownika {{ $driver }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Toggle włącz/wyłącz --}}
                            <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                                <input type="hidden"   name="{{ $id }}_enabled" value="0">
                                <input type="checkbox" name="{{ $id }}_enabled" value="1"
                                    @checked($group['enabled'])
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                Cache aktywny
                            </label>

                            {{-- Flush grupy --}}
                            <form method="POST" action="{{ route('admin.cache.flush', $id) }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-muted transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1">
                                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                    Wyczyść
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="px-5 py-4 space-y-4">
                        {{-- Klucze nazwane — status hit/miss --}}
                        @if (count($group['named']) > 0)
                            <div class="space-y-2">
                                @foreach ($group['named'] as $namedKey)
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 text-sm">
                                            <span @class([
                                                'flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold',
                                                'bg-green-100 text-green-700' => $namedKey['cached'],
                                                'bg-gray-100 text-gray-500'   => !$namedKey['cached'],
                                            ])>
                                                <i class="fa-solid {{ $namedKey['cached'] ? 'fa-check' : 'fa-minus' }}" aria-hidden="true"></i>
                                            </span>
                                            <span class="font-medium text-ink">{{ $namedKey['label'] }}</span>
                                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-[11px] text-muted">{{ $namedKey['key'] }}</code>
                                        </div>
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                            'bg-green-100 text-green-700' => $namedKey['cached'],
                                            'bg-gray-100 text-gray-500'   => !$namedKey['cached'],
                                        ])>
                                            {{ $namedKey['cached'] ? 'W cache' : 'Brak' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- TTL sliders --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            {{-- Unikalne named TTL (np. news_categories, events_upcoming) --}}
                            @foreach ($uniqueNamedTtlKeys as $namedTtlMeta)
                                @php
                                    $ttlFieldName = $namedTtlMeta['ttl_key'] . '_ttl';
                                    $ttlMax = $namedTtlMeta['ttl_key'] === 'news_categories' ? 604800 : 86400;
                                    $ttlMaxLabel = $namedTtlMeta['ttl_key'] === 'news_categories' ? '7 dni' : '24 h';
                                    $ttlCurrent = old($ttlFieldName, $namedTtlMeta['ttl']);
                                    // Pierwsza named key z tym ttl_key
                                    $ttlLabel = collect($group['named'])->firstWhere('ttl_key', $namedTtlMeta['ttl_key'])['label'];
                                @endphp
                                <div>
                                    <label for="{{ $ttlFieldName }}" class="mb-1 block text-xs font-semibold text-ink">
                                        TTL — {{ $ttlLabel }}
                                    </label>
                                    <input type="range" id="{{ $ttlFieldName }}" name="{{ $ttlFieldName }}"
                                        min="60" max="{{ $ttlMax }}" step="60"
                                        value="{{ $ttlCurrent }}"
                                        @input="document.getElementById('{{ $ttlFieldName }}_display').textContent = formatTtl($event.target.value)"
                                        class="w-full accent-brand">
                                    <div class="mt-0.5 flex justify-between text-[10px] text-muted">
                                        <span>1 min</span>
                                        <span id="{{ $ttlFieldName }}_display">{{ \App\Http\Controllers\Admin\CacheController::formatTtlStatic($ttlCurrent) }}</span>
                                        <span>{{ $ttlMaxLabel }}</span>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Item TTL (obejmuje też named keys dzielące ten ttl_key) --}}
                            @php
                                $itemTtlKey   = $group['ttl_key'] . '_ttl';
                                $itemTtlValue = old($itemTtlKey, $group['item_ttl']);
                                $sharedLabels = $sharedTtlNamedKeys->pluck('label')->all();
                                $itemTtlLabel = count($sharedLabels) > 0
                                    ? 'Elementy + ' . implode(' + ', $sharedLabels)
                                    : 'Poszczególne elementy';
                            @endphp
                            <div>
                                <label for="{{ $itemTtlKey }}" class="mb-1 block text-xs font-semibold text-ink">
                                    TTL — {{ $itemTtlLabel }}
                                </label>
                                <input type="range" id="{{ $itemTtlKey }}" name="{{ $itemTtlKey }}"
                                    min="60" max="86400" step="60"
                                    value="{{ $itemTtlValue }}"
                                    @input="document.getElementById('{{ $itemTtlKey }}_display').textContent = formatTtl($event.target.value)"
                                    class="w-full accent-brand">
                                <div class="mt-0.5 flex justify-between text-[10px] text-muted">
                                    <span>1 min</span>
                                    <span id="{{ $itemTtlKey }}_display">{{ \App\Http\Controllers\Admin\CacheController::formatTtlStatic($itemTtlValue) }}</span>
                                    <span>24 h</span>
                                </div>
                            </div>
                        </div>

                        @foreach (['news_item_ttl', 'news_categories_ttl', 'event_item_ttl', 'events_upcoming_ttl', 'page_item_ttl'] as $errKey)
                            @if ($errors->has($errKey) && str_starts_with($errKey, $id === 'news' ? 'news' : ($id === 'events' ? 'event' : 'page')))
                                <p class="text-xs text-red-600">{{ $errors->first($errKey) }}</p>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Zapisz --}}
        <div class="mt-6 flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Zapisz konfigurację
            </button>
        </div>
    </form>

    <script>
    function formatTtl(seconds) {
        seconds = parseInt(seconds, 10)
        if (seconds < 60)    return seconds + ' s'
        if (seconds < 3600)  return Math.round(seconds / 60) + ' min'
        if (seconds < 86400) {
            const h = Math.floor(seconds / 3600)
            const m = Math.round((seconds % 3600) / 60)
            return m > 0 ? h + ' h ' + m + ' min' : h + ' h'
        }
        const d = Math.floor(seconds / 86400)
        const h = Math.round((seconds % 86400) / 3600)
        return h > 0 ? d + ' d ' + h + ' h' : d + ' d'
    }
    </script>
@endsection
