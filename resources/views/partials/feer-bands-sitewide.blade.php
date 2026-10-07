{{--
    Paski z modułu „FEER Paski" na wszystkich podstronach (poza stroną główną, która ma własne miejsca) — w dowolnym szablonie.
    Parametr: $where = 'site_top' (nad treścią) albo 'site_bottom' (pod treścią, nad stopką).
--}}
@php
    $__bands = collect();
    if (! request()->routeIs('home') && $siteSettings->isModuleEnabled('feer_bands')) {
        static $__all = null;
        try {
            $__all ??= \App\Models\FeerBand::forCurrentSite()->active()->whereIn('placement', ['site_top', 'site_bottom'])->orderBy('order')->orderBy('id')->get()->groupBy('placement');
        } catch (\Throwable $e) {
            $__all = collect(); // tabela modułu jeszcze nie zmigrowana
        }
        $__bands = $__all[$where] ?? collect();
    }
@endphp
@foreach ($__bands as $band)
    @include('partials.feer-band', ['band' => $band])
@endforeach
