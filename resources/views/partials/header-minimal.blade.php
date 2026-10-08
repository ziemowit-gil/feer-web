{{-- Minimalny nagłówek: logo i nazwa organizacji z linkiem do strony głównej, bez menu. Używany przez @section('minimal_header'). --}}
<header class="site-header-minimal" style="background:#fff;border-bottom:1px solid #d1d5db">
    <div style="max-width:72rem;margin:0 auto;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem">
        <a href="{{ site_route('home') }}" aria-label="{{ $siteSettings->site_name }} — strona główna"
           style="display:inline-flex;align-items:center;gap:.75rem;min-height:2.75rem;color:#1d1d1a;text-decoration:none;border-radius:.375rem"
           class="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}" style="height:2.75rem;width:auto;max-width:14rem;object-fit:contain">
            @else
                <span aria-hidden="true" style="display:inline-flex;height:2.75rem;width:2.75rem;align-items:center;justify-content:center;border-radius:.5rem;background:var(--color-brand-dark);color:#fff;font-size:1.25rem;font-weight:800">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                <span style="font-weight:800">{{ $siteSettings->site_name }}</span>
            @endif
        </a>
        <a href="{{ site_route('home') }}" style="display:inline-flex;align-items:center;gap:.5rem;min-height:2.75rem;padding:0 .75rem;font-size:.875rem;font-weight:700;color:#1d1d1a;text-decoration:underline;text-underline-offset:4px;border-radius:.375rem"
           class="hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Strona główna organizacji
        </a>
    </div>
</header>
