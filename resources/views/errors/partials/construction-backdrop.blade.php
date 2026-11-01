{{-- Dekoracyjne tło stron „przerwa techniczna” i „w budowie”: plan na niebieskiej siatce, żuraw, koła zębate, klucz.
     Kolory z palety witryny (--brand*), ruch tylko gdy użytkownik go nie wyłączył. --}}
<style>
    .cb-backdrop { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
    .cb-backdrop svg { position: absolute; inset: 0; width: 100%; height: 100%; }
    .cb-gear-a { transform-origin: 170px 610px; animation: cb-spin 40s linear infinite; }
    .cb-gear-b { transform-origin: 262px 540px; animation: cb-spin 26s linear infinite reverse; }
    .cb-gear-c { transform-origin: 1060px 640px; animation: cb-spin 50s linear infinite; }
    .cb-hook { transform-origin: 900px 150px; animation: cb-swing 7s ease-in-out infinite; }
    @keyframes cb-spin { to { transform: rotate(360deg); } }
    @keyframes cb-swing { 0%, 100% { transform: rotate(-3deg); } 50% { transform: rotate(3deg); } }
    @media (prefers-reduced-motion: reduce) { .cb-gear-a, .cb-gear-b, .cb-gear-c, .cb-hook { animation: none; } }
    body > main, body > .card { position: relative; z-index: 1; }
</style>
<div class="cb-backdrop" aria-hidden="true">
    <svg viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <defs>
            <pattern id="cb-grid-s" width="30" height="30" patternUnits="userSpaceOnUse"><path d="M30 0H0V30" fill="none" stroke="var(--brand)" stroke-opacity=".10" stroke-width="1"/></pattern>
            <pattern id="cb-grid-l" width="150" height="150" patternUnits="userSpaceOnUse"><path d="M150 0H0V150" fill="none" stroke="var(--brand)" stroke-opacity=".18" stroke-width="1.5"/></pattern>
            <g id="cb-gear">
                <path d="M0-62 L12-62 L16-48 L30-54 L40-44 L33-30 L47-24 L48-10 L62-6 L62 6 L48 10 L47 24 L33 30 L40 44 L30 54 L16 48 L12 62 L-12 62 L-16 48 L-30 54 L-40 44 L-33 30 L-47 24 L-48 10 L-62 6 L-62-6 L-48-10 L-47-24 L-33-30 L-40-44 L-30-54 L-16-48 L-12-62Z" />
                <circle r="22" fill="#fff" fill-opacity=".9"/>
            </g>
        </defs>
        <rect width="1200" height="800" fill="url(#cb-grid-s)"/>
        <rect width="1200" height="800" fill="url(#cb-grid-l)"/>

        {{-- Żuraw --}}
        <g fill="none" stroke="var(--brand)" stroke-opacity=".35" stroke-width="6" stroke-linejoin="round">
            <path d="M980 800V160M1040 800V160M980 160H1040M980 240H1040M980 320H1040M980 400H1040M980 480H1040M980 560H1040M980 640H1040M980 720H1040M980 240L1040 320M980 320L1040 400M980 400L1040 480M980 480L1040 560M980 560L1040 640M980 640L1040 720"/>
            <path d="M700 150H1180M1010 110V150M1010 110L760 150M1010 110L1130 150"/>
            <path d="M1150 150V200" stroke-width="10"/>
        </g>
        <rect x="1130" y="196" width="40" height="34" rx="4" fill="var(--brand)" fill-opacity=".30"/>
        <g class="cb-hook" stroke="var(--brand)" stroke-opacity=".40" stroke-width="4" fill="none">
            <path d="M900 150V330"/>
            <path d="M900 330v18a14 14 0 1 1-14 14" stroke-width="6" stroke-linecap="round"/>
            <rect x="840" y="400" width="120" height="70" rx="4" fill="var(--brand)" fill-opacity=".18"/>
            <path d="M900 330L850 400M900 330L950 400"/>
        </g>

        {{-- Koła zębate --}}
        <g class="cb-gear-a" fill="var(--brand)" fill-opacity=".20"><use href="#cb-gear" transform="translate(170 610) scale(1.9)"/></g>
        <g class="cb-gear-b" fill="var(--brand-dark)" fill-opacity=".18"><use href="#cb-gear" transform="translate(262 540)"/></g>
        <g class="cb-gear-c" fill="var(--brand)" fill-opacity=".16"><use href="#cb-gear" transform="translate(1060 640)"/></g>

        {{-- Klucz --}}
        <g transform="translate(120 170) rotate(-35)" fill="var(--brand)" fill-opacity=".22">
            <path d="M0 0h200a34 34 0 0 1 0 68H0a34 34 0 0 1 0-68zm14 20a14 14 0 1 0 0 28 14 14 0 0 0 0-28z" fill-rule="evenodd"/>
            <rect x="196" y="22" width="150" height="24" rx="6"/>
        </g>

        {{-- Plan: znaczniki wymiarów --}}
        <g stroke="var(--brand)" stroke-opacity=".30" stroke-width="2" fill="none">
            <path d="M360 740H700M360 730v20M700 730v20"/>
            <path d="M60 300V520M50 300h20M50 520h20"/>
        </g>
        <g fill="var(--brand-dark)" fill-opacity=".45" font-family="Montserrat, system-ui, sans-serif" font-size="16" font-weight="700">
            <text x="470" y="728">w budowie</text>
        </g>
    </svg>
</div>
