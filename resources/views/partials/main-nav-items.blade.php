@php
    $navItems ??= collect();
    $mobile ??= false;
    $onBrand ??= false;
    $navDarkText ??= false;
    // Substyl paska: 'underline' (domyślny), 'icons' (ikona nad etykietą), 'pills' (zakładki).
    $navStyle ??= (($iconsNav ?? false) ? 'icons' : 'underline');
    $iconsNav = $navStyle === 'icons';
@endphp

<ul @class([
    'flex items-center gap-5 text-lg font-bold uppercase tracking-wide xl:gap-6' => ! $mobile,
    'nav-icons gap-1 xl:gap-2' => ! $mobile && $navStyle === 'icons',
    'nav-pills gap-1 xl:gap-1.5 normal-case tracking-normal' => ! $mobile && $navStyle === 'pills',
    'nav-on-brand text-white' => ! $mobile && $onBrand && ! $navDarkText,
    'nav-on-brand-dark text-gray-900' => ! $mobile && $onBrand && $navDarkText,
    'text-ink' => ! $mobile && ! $onBrand,
    'flex flex-col gap-1 pt-3 text-lg font-bold uppercase tracking-wide text-ink' => $mobile,
])>
    @foreach ($navItems as $item)
        @php
            $__partial = (! $mobile && $item->isMega()) ? 'partials.nav-mega'
                : ($item->type === 'projects' ? 'partials.nav-projects-dropdown'
                : ($item->type === 'pages' ? 'partials.nav-pages'
                : ($item->type === 'dropdown' ? 'partials.nav-dropdown' : 'partials.nav-item')));
            $__html = $__env->make($__partial, array_merge(
                \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path', '__html', '__partial', '__accent', '__pal']),
                ['item' => $item, 'mobile' => $mobile],
            ))->render();

            // Kolor pozycji (Panel → Menu → „Kolor pozycji"): wskaźnik pod pozycją, a po najechaniu i na aktywnej — wypełnienie
            // kolorem; tło i tekst wypełnienia dobiera Color::button (kontrast ≥ 4,5:1). Style: resources/css/app.css.
            $__accent = (! $mobile && ! $item->parent_id && ! $item->is_button && \App\Support\Color::isValid($item->accent_color)) ? $item->accent_color : null;
            if ($__accent) {
                $__pal = \App\Support\Color::button($__accent);
                $__html = preg_replace('/<li\b/', '<li data-nav-accent style="--nav-accent: '.e($__accent).'; --nav-accent-bg: '.e($__pal['bg']).'; --nav-accent-text: '.e($__pal['text']).'"', $__html, 1);
            }
        @endphp
        {!! $__html !!}
    @endforeach
</ul>
