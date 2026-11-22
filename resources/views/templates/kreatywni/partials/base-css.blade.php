{{--
    Style szablonu „kreatywni” (prefiks kc-), w zwykłym CSS, żeby nie zależały od zbudowanych klas Tailwinda. @once —
    dołączają je nagłówek, stopka i strona główna. Paleta ze strony fundacji (warsztatydladziecikrakow.pl) i logo:
    leśna zieleń #1b4a07, zieleń przycisków #007a38 (przyciemniona z #008a40, bo ta miała 4,46:1 z bielą), limonka #a2c523
    i żółć #fce98b (tła i ozdoby), krem #f2f2eb, mięta #edf5ed. Kontrast: #1b4a07 na bieli 10,4:1, na kremie 9,1:1,
    na żółci 8,4:1, na limonce 5,2:1; biały na #007a38 5,5:1, na #1b4a07 10,4:1; tekst #222 na kremie 13:1; szary
    #4a5a44 na bieli 7,1:1. Limonka i żółć nigdy nie są kolorem tekstu na jasnym tle. Fokus: obrys 3 px (na ciemnym
    tle żółty). Cele kliknięcia min. 44 px, wielkości w rem (działa powiększenie tekstu z panelu dostępności).
--}}
@once
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Montserrat:wght@800&display=swap">
<style>
    .kc { --kc-forest: #1b4a07; --kc-green: #007a38; --kc-lime: #a2c523; --kc-sun: #fce98b; --kc-gold: #f2d600;
        --kc-cream: #f2f2eb; --kc-mint: #edf5ed; --kc-ink: #222; --kc-muted: #4a5a44; --kc-line: #d9e3d3;
        --kc-radius: 1.5rem; font-family: Montserrat, Arial, sans-serif; color: var(--kc-ink); }
    .kc *, .kc *::before, .kc *::after { box-sizing: border-box; }
    .kc-wrap { max-width: 76rem; margin: 0 auto; padding: 0 1rem; }
    @media (min-width: 640px) { .kc-wrap { padding: 0 1.5rem; } }
    .kc [x-cloak] { display: none !important; }
    .kc-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .kc a:focus-visible, .kc button:focus-visible { outline: 3px solid var(--kc-forest); outline-offset: 3px; border-radius: .5rem; }
    .kc-dark a:focus-visible, .kc-dark button:focus-visible { outline-color: var(--kc-sun); }
    .kc-hand { font-family: Caveat, 'Comic Sans MS', cursive; font-weight: 700; }

    /* Przyciski */
    .kc-btn { display: inline-flex; align-items: center; justify-content: center; gap: .6rem; min-height: 3rem; padding: .7rem 1.5rem;
        border: 2px solid var(--kc-green); border-radius: 999px; background: var(--kc-green); color: #fff; font-size: 1rem; font-weight: 700;
        line-height: 1.2; text-decoration: none; cursor: pointer; transition: background-color .15s, border-color .15s, color .15s; }
    .kc-btn:hover { background: var(--kc-forest); border-color: var(--kc-forest); color: #fff; }
    .kc-btn-ghost { background: transparent; color: var(--kc-forest); border-color: var(--kc-forest); }
    .kc-btn-ghost:hover { background: var(--kc-forest); color: #fff; }
    .kc-btn-sun { background: var(--kc-sun); border-color: var(--kc-sun); color: var(--kc-forest); }
    .kc-btn-sun:hover { background: #fff; border-color: #fff; color: var(--kc-forest); }
    .kc-btn i { font-size: 1.1em; }

    /* ── Pasek dostępności (nad belką) ───────────────────────── */
    .kc-topbar { background: var(--kc-forest); color: #fff; }
    .kc-topbar-in { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .75rem; min-height: 2.75rem; padding-top: .25rem; padding-bottom: .25rem; }
    .kc-topbar-group { display: inline-flex; align-items: center; gap: .25rem; }
    .kc-topbar-label { margin-right: .25rem; font-size: .85rem; font-weight: 600; }
    .kc-top-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-width: 2.25rem; min-height: 2.25rem; padding: 0 .5rem;
        border: 1px solid rgba(255, 255, 255, .45); border-radius: 999px; background: transparent; color: #fff; font: inherit; font-size: .85rem; font-weight: 700; line-height: 1; cursor: pointer; }
    .kc-top-btn-wide { padding: 0 .85rem; }
    .kc-top-btn sup { font-size: .7em; }
    .kc-top-btn:hover, .kc-top-btn[aria-pressed="true"], .kc-top-btn[aria-expanded="true"] { background: var(--kc-sun); border-color: var(--kc-sun); color: var(--kc-forest); }
    .kc-top-btn[aria-disabled="true"] { opacity: 1; border-style: dashed; cursor: not-allowed; }
    .kc-topbar-contact { display: flex; flex-wrap: wrap; align-items: center; gap: 0 1.25rem; margin: 0 0 0 auto; padding: 0; list-style: none; }
    .kc-topbar-contact a { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.25rem; color: #fff; font-size: .88rem; font-weight: 600; text-decoration: none; white-space: nowrap; }
    .kc-topbar-contact a:hover { text-decoration: underline; text-underline-offset: 4px; }
    .kc-topbar-contact i { color: var(--kc-sun); }
    .kc-topbar a:focus-visible, .kc-topbar button:focus-visible { outline: 3px solid var(--kc-sun); outline-offset: 2px; }
    @media (min-width: 768px) { .kc-topbar-in { flex-wrap: nowrap; } .kc-top-btn { white-space: nowrap; } }
    @media (max-width: 1199px) { .kc-hide-md { display: none; } }
    @media (max-width: 1023px) { .kc-topbar-label { display: none; } }
    .kc-lbl-short { display: none; }
    @media (max-width: 767px) {
        .kc-hide-xs { display: none !important; }
        .kc-lbl-long { display: none; } .kc-lbl-short { display: inline; }
        .kc-topbar-in { flex-wrap: nowrap; }
    }

    /* ── Nagłówek ─────────────────────────────────────────────── */
    .kc-header { position: sticky; top: 0; z-index: 40; background: rgba(255, 255, 255, .97); border-bottom: 1px solid var(--kc-line); }
    .kc-top { display: flex; align-items: center; gap: 1rem; min-height: 5rem; }
    .kc-brand { display: flex; align-items: center; gap: .75rem; min-width: 0; color: var(--kc-forest); text-decoration: none; }
    .kc-brand img { display: block; width: 3.5rem; height: 3.5rem; flex: none; object-fit: contain; }
    .kc-brand-text { display: flex; flex-direction: column; line-height: 1.1; }
    .kc-brand-kind { font-size: .75rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--kc-muted); }
    .kc-brand-name { font-size: 1.15rem; font-weight: 800; color: var(--kc-forest); }
    .kc-nav { margin: 0 auto; }
    .kc-nav > ul { display: flex; flex-wrap: nowrap; align-items: center; gap: .15rem; margin: 0; padding: 0; list-style: none; white-space: nowrap; }
    .kc-nav-link { position: relative; display: inline-flex; align-items: center; gap: .4rem; min-height: 2.75rem; padding: .45rem 1rem; border: 0;
        background: transparent; color: var(--kc-ink); font: inherit; font-size: 1.06rem; font-weight: 700; text-decoration: none; cursor: pointer; }
    .kc-nav-link::after { content: ''; position: absolute; left: 50%; bottom: .1rem; width: .45rem; height: .45rem; border-radius: 50%; background: transparent; transform: translateX(-50%); }
    .kc-nav-link:hover { color: var(--kc-forest); }
    .kc-nav-link:hover::after { background: var(--kc-gold); }
    .kc-nav-link[aria-current="page"], .kc-nav-link.is-here, .kc-nav-link[aria-expanded="true"] { color: var(--kc-forest); font-weight: 800; }
    .kc-nav-link[aria-current="page"]::after, .kc-nav-link.is-here::after, .kc-nav-link[aria-expanded="true"]::after { background: var(--kc-lime); }
    .kc-nav-link .kc-chev { color: var(--kc-green); }
    .kc-nav-item { position: static; }
    .kc-mega { white-space: normal; position: absolute; left: 0; right: 0; top: 100%; z-index: 45; max-height: calc(100vh - 8rem); overflow-y: auto; border-top: 1px solid var(--kc-line);
        background: #fff; box-shadow: 0 24px 40px rgba(27, 74, 7, .12); }
    .kc-mega-in { display: grid; gap: 2rem; padding-top: 2rem; padding-bottom: 2.25rem; }
    @media (min-width: 1024px) { .kc-mega-in { grid-template-columns: 17rem minmax(0, 1fr); gap: 3rem; } }
    .kc-mega-intro { align-self: start; padding: 1.5rem; border-radius: 1.25rem; background: var(--kc-cream); }
    .kc-mega-intro p { margin: 0 0 1rem; line-height: 1.6; }
    .kc-mega-intro .kc-mega-title { margin-bottom: .5rem; font-size: 1.35rem; font-weight: 800; color: var(--kc-forest); }
    .kc-mega-groups { columns: 3 13rem; column-gap: 2.5rem; }
    .kc-mega-group { break-inside: avoid; margin-bottom: 1rem; }
    .kc-mega-group.is-long { column-span: all; margin-bottom: 1.5rem; }
    .kc-mega-group.is-long ul { columns: 3 12rem; column-gap: 2rem; }
    .kc-mega-group ul { margin: 0; padding: 0; list-style: none; }
    .kc-mega-group li { break-inside: avoid; }
    .kc-mega-head { display: inline-flex; align-items: center; min-height: 2.5rem; margin-bottom: .25rem; padding-bottom: .25rem; border-bottom: 3px solid var(--kc-lime);
        color: var(--kc-forest); font-size: 1rem; font-weight: 800; text-decoration: none; }
    .kc-mega-head:hover { border-bottom-color: var(--kc-forest); }
    span.kc-mega-head:hover { border-bottom-color: var(--kc-lime); }
    .kc-mega-group li a { display: flex; align-items: center; gap: .5rem; min-height: 2.5rem; padding: .3rem 0; color: var(--kc-ink); font-size: .95rem; font-weight: 600; text-decoration: none; }
    .kc-mega-group li a::before { content: ''; width: .4rem; height: .4rem; flex: none; border-radius: 50%; background: var(--kc-gold); }
    .kc-mega-group li a:hover, .kc-mega-group li a[aria-current="page"] { color: var(--kc-forest); text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
    .kc-drop { position: absolute; top: calc(100% + .35rem); left: 0; min-width: 15rem; margin: 0; padding: .5rem; list-style: none;
        background: #fff; border: 1px solid var(--kc-line); border-radius: 1rem; box-shadow: 0 12px 30px rgba(27, 74, 7, .12); }
    .kc-drop a { display: block; min-height: 2.75rem; padding: .7rem .85rem; border-radius: .6rem; color: var(--kc-ink); font-weight: 600; text-decoration: none; }
    .kc-drop a:hover, .kc-drop a[aria-current="page"] { background: var(--kc-mint); color: var(--kc-forest); }
    .kc-chev { display: inline-block; width: .45rem; height: .45rem; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: translateY(-.15rem) rotate(45deg); transition: transform .15s; }
    [aria-expanded="true"] > .kc-chev { transform: translateY(.1rem) rotate(-135deg); }
    .kc-tools { display: flex; align-items: center; gap: .35rem; }
    .kc-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; flex: none; border: 0; border-radius: 999px;
        background: var(--kc-mint); color: var(--kc-forest); font-size: 1.1rem; text-decoration: none; cursor: pointer; transition: background-color .15s, color .15s; }
    .kc-icon-btn:hover, .kc-icon-btn[aria-expanded="true"] { background: var(--kc-forest); color: #fff; }
    .kc-header .kc-btn { min-height: 2.75rem; padding: .5rem 1.15rem; font-size: .95rem; }
    /* Profile w belce i w menu mobilnym: przycisk z nazwą serwisu i znakiem w jego kolorze (znak to grafika, tekst obok
       ma pełny kontrast); biały znak na kolorze serwisu ≥ 3:1 (Facebook 4,2:1, YouTube 4:1). */
    .kc-net-pill { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.75rem; padding: .3rem .95rem .3rem .3rem; border: 2px solid var(--kc-line); border-radius: 999px;
        background: #fff; color: var(--kc-forest); font-size: .95rem; font-weight: 800; text-decoration: none; transition: border-color .15s, background-color .15s; }
    .kc-net-pill:hover { border-color: var(--kc-forest); background: var(--kc-mint); }
    .kc-net { display: inline-flex; align-items: center; justify-content: center; width: 2.05rem; height: 2.05rem; flex: none; border-radius: 50%; background: var(--kc-forest); color: #fff; font-size: 1.05rem; }
    .kc-net-fb { background: #1877f2; }
    .kc-net-ig { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285aeb 90%); }
    .kc-net-yt { background: #ff0000; }
    .kc-top { flex-wrap: nowrap; }
    .kc-brand-text, .kc-nav-link, .kc-net-pill, .kc-header .kc-btn { white-space: nowrap; }
    .kc-tools { flex: none; }
    @media (max-width: 1439px) { .kc-header .kc-net-label { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; } .kc-header .kc-top .kc-net-pill { padding: .3rem; } }
    @media (min-width: 1024px) and (max-width: 1199px) { .kc-nav-link { padding: .45rem .65rem; font-size: 1rem; } .kc-brand-name { font-size: 1.05rem; } .kc-header .kc-btn { padding: .5rem .9rem; } }
    @media (max-width: 479px) { .kc-header .kc-top .kc-net-pill { display: none; } }
    @media (forced-colors: active) { .kc-net { border: 1px solid CanvasText; } }
    .kc-burger { display: none; }
    .kc-mnav { border-top: 1px solid var(--kc-line); background: #fff; }
    .kc-mnav ul { margin: 0; padding: .5rem 0 1rem; list-style: none; }
    .kc-mnav a { display: flex; align-items: center; min-height: 3rem; padding: .5rem .75rem; border-radius: .75rem; color: var(--kc-ink); font-size: 1.05rem; font-weight: 700; text-decoration: none; }
    .kc-mnav a:hover, .kc-mnav a[aria-current="page"] { background: var(--kc-mint); color: var(--kc-forest); }
    .kc-mnav ul ul { padding: 0 0 .25rem 1rem; }
    .kc-mnav ul ul a { font-size: .98rem; font-weight: 600; }
    .kc-mnav-social { display: flex; flex-wrap: wrap; gap: .5rem; padding: 0 0 1.25rem; }
    @media (max-width: 1023px) {
        .kc-nav, .kc-tools .kc-hide-sm { display: none; }
        .kc-tools { margin-left: auto; }
        .kc-burger { display: inline-flex; }
    }
    @media (min-width: 1024px) { .kc-mnav { display: none !important; } }
    @media (max-width: 479px) { .kc-brand-kind { display: none; } .kc-brand-name { font-size: 1rem; } .kc-brand img { width: 3rem; height: 3rem; } }
    #a11y-panel { --a11y-bar-w: 76rem; }

    /* ── Sekcje strony głównej ────────────────────────────────── */
    .kc-sec { padding: 4.5rem 0; }
    .kc [id] { scroll-margin-top: 6rem; }
    @media (max-width: 639px) { .kc-sec { padding: 3rem 0; } }
    .kc-sec-cream { background: var(--kc-cream); }
    .kc-sec-mint { background: var(--kc-mint); }
    .kc-sec-sun { background: var(--kc-sun); }
    .kc-dark { background: var(--kc-forest); color: #fff; }
    .kc-eyebrow { display: block; margin: 0 0 .35rem; font-size: 1.7rem; line-height: 1.1; color: var(--kc-green); }
    .kc-dark .kc-eyebrow { color: var(--kc-sun); }
    .kc-h2 { margin: 0 0 1rem; font-size: clamp(1.75rem, 1.2rem + 2vw, 2.6rem); font-weight: 800; line-height: 1.15; color: var(--kc-forest); letter-spacing: -.01em; }
    .kc-dark .kc-h2 { color: #fff; }
    .kc-lead { max-width: 46rem; margin: 0 0 1rem; font-size: 1.1rem; line-height: 1.7; }
    .kc-lead + .kc-lead { margin-top: -.25rem; }
    .kc-center { text-align: center; }
    .kc-center .kc-lead { margin-left: auto; margin-right: auto; }
    .kc-head { margin-bottom: 2.5rem; }
    .kc-grid { display: grid; gap: 1.25rem; margin: 0; padding: 0; list-style: none; }
    @media (min-width: 640px) { .kc-grid-2, .kc-grid-3, .kc-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .kc-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .kc-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .kc-card { position: relative; height: 100%; padding: 1.5rem; border-radius: var(--kc-radius); background: #fff; }
    .kc-card h3 { margin: 0 0 .5rem; font-size: 1.15rem; font-weight: 800; line-height: 1.3; color: var(--kc-forest); }
    .kc-card p { margin: 0; line-height: 1.65; }
    .kc-badge { display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; margin-bottom: 1rem; border-radius: 1rem;
        background: var(--kc-sun); color: var(--kc-forest); font-size: 1.25rem; font-weight: 800; }
    .kc-badge-lime { background: var(--kc-lime); }
    .kc-badge-mint { background: var(--kc-mint); }

    /* Powitanie */
    .kc-hero { position: relative; overflow: hidden; padding: 4rem 0 4.5rem; background: var(--kc-cream); }
    .kc-hero::before, .kc-hero::after { content: ''; position: absolute; border-radius: 50%; pointer-events: none; }
    .kc-hero::before { width: 28rem; height: 28rem; right: -8rem; top: -10rem; background: var(--kc-sun); opacity: .7; }
    .kc-hero::after { width: 14rem; height: 14rem; left: -5rem; bottom: -6rem; background: var(--kc-lime); opacity: .35; }
    .kc-hero-in { position: relative; z-index: 1; display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 900px) { .kc-hero-in { grid-template-columns: 1.15fr .85fr; gap: 3rem; } }
    .kc-hero h1 { margin: 0 0 1.25rem; font-size: clamp(2.2rem, 1.4rem + 3.4vw, 3.9rem); font-weight: 800; line-height: 1.05; letter-spacing: -.02em; color: var(--kc-forest); }
    .kc-hero .kc-eyebrow { font-size: 2rem; }
    .kc-points { display: flex; flex-wrap: wrap; gap: .5rem; margin: 1.5rem 0 2rem; padding: 0; list-style: none; }
    .kc-points li { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .9rem; border-radius: 999px; background: #fff; color: var(--kc-forest); font-size: .95rem; font-weight: 700; }
    .kc-points i { color: var(--kc-green); }
    .kc-actions { display: flex; flex-wrap: wrap; gap: .75rem; }
    .kc-hero-art { position: relative; justify-self: center; width: min(100%, 26rem); aspect-ratio: 1; }
    .kc-hero-art::before { content: ''; position: absolute; inset: 6%; border-radius: 50%; background: #fff; box-shadow: 0 20px 60px rgba(27, 74, 7, .15); }
    .kc-hero-art img { position: relative; display: block; width: 100%; height: 100%; object-fit: contain; border-radius: 50%; }
    .kc-hero-art.is-photo img { inset: 6%; width: 88%; height: 88%; margin: 6%; object-fit: cover; }
    .kc-hero-art .kc-dot { position: absolute; border-radius: 50%; }
    .kc-hero-art .kc-dot-1 { width: 3.5rem; height: 3.5rem; right: 4%; bottom: 10%; background: var(--kc-gold); }
    .kc-hero-art .kc-dot-2 { width: 1.75rem; height: 1.75rem; left: 6%; top: 12%; background: var(--kc-green); }

    /* Slider (powitanie + misja) */
    .kc-slides { display: grid; }
    .kc-slide { grid-area: 1 / 1; transition: opacity .3s ease; }
    .kc-slide.is-off { visibility: hidden; opacity: 0; }
    .kc-slide-title { margin: 0 0 1rem; font-size: clamp(2rem, 1.3rem + 3vw, 3.4rem); font-weight: 800; line-height: 1.05; letter-spacing: -.02em; color: var(--kc-forest); }
    .kc-steps-hero li { border-top-color: rgba(27, 74, 7, .15); font-size: 1.1rem; }
    .kc-hero-art-icons { display: grid; place-items: center; }
    .kc-art-icon { position: relative; display: inline-flex; align-items: center; justify-content: center; font-size: clamp(5rem, 3rem + 8vw, 9rem); color: var(--kc-green); }
    .kc-slide-social { margin-top: 1.75rem; }
    .kc-net-pill-lg { min-height: 3.25rem; padding: .4rem 1.3rem .4rem .4rem; font-size: 1.05rem; }
    .kc-net-pill-lg .kc-net { width: 2.5rem; height: 2.5rem; font-size: 1.25rem; }
    .kc-art-nets { position: relative; display: flex; gap: 1.25rem; }
    .kc-art-nets .kc-net { width: clamp(5rem, 3rem + 6vw, 7.5rem); height: clamp(5rem, 3rem + 6vw, 7.5rem); border-radius: 1.75rem; font-size: clamp(2.5rem, 1.5rem + 3vw, 3.75rem); }
    .kc-art-nets .kc-net:nth-child(2) { transform: translateY(1.5rem) rotate(6deg); }
    .kc-slider-nav { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; margin-top: 1.75rem; }
    .kc-slider-count { margin: 0 .75rem 0 0; color: var(--kc-muted); font-size: .95rem; font-weight: 600; letter-spacing: .04em; }
    .kc-slider-count strong { color: var(--kc-forest); font-size: 1.15rem; font-weight: 800; }
    .kc-slider-arrow { background: #fff; }
    .kc-slider-arrow:hover { background: var(--kc-forest); color: #fff; }
    .kc-dots { display: flex; align-items: center; gap: .15rem; margin: 0; padding: 0; list-style: none; }
    .kc-dots button { position: relative; display: block; width: 1.75rem; height: 2.75rem; padding: 0; border: 0; background: transparent; cursor: pointer; transition: width .2s; }
    .kc-dots button::before { content: ''; position: absolute; left: 50%; top: 50%; width: .75rem; height: .75rem; transform: translate(-50%, -50%); border: 2px solid var(--kc-forest); border-radius: 999px; background: transparent; transition: width .2s, background-color .2s; }
    .kc-dots button.is-on { width: 3rem; }
    .kc-dots button.is-on::before { width: 2.5rem; background: var(--kc-forest); }
    .kc-dots button:hover:not(.is-on)::before { background: var(--kc-lime); }
    .kc-dots button:focus-visible { outline-offset: 0; }
    @media (forced-colors: active) { .kc-dots button::before { border-color: ButtonText; } .kc-dots button.is-on::before { background: Highlight; } }
    @media (prefers-reduced-motion: reduce) { .kc-slide { transition: none; } }

    /* Misja */
    .kc-mission { display: grid; gap: 2.5rem; }
    @media (min-width: 1024px) { .kc-mission { grid-template-columns: .9fr 1.1fr; gap: 3.5rem; align-items: start; } }
    .kc-steps { counter-reset: kc; margin: 1.5rem 0 0; padding: 0; list-style: none; }
    .kc-steps li { counter-increment: kc; display: flex; gap: 1rem; padding: 1rem 0; border-top: 1px solid var(--kc-line); font-size: 1.05rem; font-weight: 600; line-height: 1.55; }
    .kc-steps li::before { content: counter(kc, decimal-leading-zero); flex: none; font-size: 1.4rem; font-weight: 800; color: var(--kc-green); }
    .kc-area { padding: 1.25rem; background: var(--kc-mint); }
    .kc-area .kc-badge { width: 2.5rem; height: 2.5rem; margin-bottom: .75rem; border-radius: .8rem; font-size: 1.05rem; }
    .kc-area h3 { font-size: 1.05rem; }
    .kc-area:nth-child(2), .kc-area:nth-child(3) { background: var(--kc-cream); }

    /* Social media */
    .kc-social { display: grid; gap: 1rem; margin: 2.5rem 0 0; padding: 0; list-style: none; }
    @media (min-width: 640px) { .kc-social { grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); } }
    .kc-social a { display: flex; align-items: center; gap: 1rem; height: 100%; min-height: 6rem; padding: 1.25rem 1.5rem; border-radius: var(--kc-radius);
        background: #fff; color: var(--kc-forest); text-decoration: none; transition: transform .15s, background-color .15s; }
    .kc-social a:hover { background: var(--kc-sun); transform: translateY(-2px); }
    .kc-social-icon { display: inline-flex; align-items: center; justify-content: center; width: 3.5rem; height: 3.5rem; flex: none; border-radius: 1rem; background: var(--kc-forest); color: #fff; font-size: 1.6rem; }
    .kc-social-text { display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
    .kc-social-name { font-size: 1.2rem; font-weight: 800; }
    .kc-social-cta { font-size: .95rem; font-weight: 600; color: var(--kc-muted); }
    .kc-social a { min-height: 7rem; padding: 1.5rem 1.75rem; }
    .kc-social-icon { width: 4rem; height: 4rem; border-radius: 1.1rem; font-size: 1.9rem; }
    .kc-social-icon.kc-net-fb { background: #1877f2; }
    .kc-social-icon.kc-net-ig { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285aeb 90%); }
    .kc-social-icon.kc-net-yt { background: #ff0000; }
    .kc-social-name { font-size: 1.35rem; }
    .kc-social-follow { display: inline-flex; align-items: center; gap: .45rem; margin-left: auto; padding: .55rem 1.1rem; border-radius: 999px; background: var(--kc-green); color: #fff; font-size: .95rem; font-weight: 800; white-space: nowrap; }
    .kc-social a:hover .kc-social-follow { background: var(--kc-forest); }
    @media (max-width: 479px) { .kc-social a { flex-wrap: wrap; } .kc-social-follow { margin-left: 0; } }

    /* Oferta */
    .kc-price { display: inline-flex; flex-wrap: wrap; align-items: baseline; gap: .35rem .75rem; margin: .5rem 0 0; padding: .75rem 1.25rem; border-radius: 1rem; background: var(--kc-sun); color: var(--kc-forest); }
    .kc-price strong { font-size: 1.25rem; font-weight: 800; }
    .kc-price span { font-size: .95rem; font-weight: 600; }
    .kc-offer-card { display: flex; flex-direction: column; }
    .kc-offer-card .kc-badge { width: 2.5rem; height: 2.5rem; margin-bottom: .85rem; border-radius: .8rem; font-size: .95rem; }
    .kc-more { margin-top: 2.5rem; display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; }
    .kc-chips { display: flex; flex-wrap: wrap; gap: .6rem; margin: 0; padding: 0; list-style: none; }
    .kc-chips li { display: inline-flex; align-items: center; gap: .55rem; padding: .6rem 1.1rem; border-radius: 999px; background: #fff; color: var(--kc-forest); font-weight: 700; line-height: 1.3; }
    .kc-chips li::before { content: ''; width: .6rem; height: .6rem; flex: none; border-radius: 50%; background: var(--kc-lime); }
    .kc-chips li:nth-child(3n+2)::before { background: var(--kc-gold); }
    .kc-chips li:nth-child(3n)::before { background: var(--kc-green); }

    /* Blok sprzedażowy oferty (partials/offer-sales) */
    .kc-sales { display: grid; gap: 1.5rem; align-items: start; }
    @media (min-width: 1024px) { .kc-sales { grid-template-columns: 22rem minmax(0, 1fr); gap: 2rem; } .kc-price-card { position: sticky; top: 7rem; } }
    /* Blok oferty płasko („mniej AI”): karta bez cienia i pigułki, korzyści z krótką kreską, warsztaty jako zwykłe listy. */
    .kc-price-card { padding: 2rem; border-radius: .75rem; }
    .kc-price-name { margin: 0 0 .75rem; font-size: 1.05rem; font-weight: 700; color: var(--kc-sun); }
    .kc-price-big { margin: 0; line-height: 1; }
    .kc-price-big strong { font-size: clamp(2.6rem, 2rem + 2vw, 3.4rem); font-weight: 800; color: #fff; letter-spacing: -.02em; }
    .kc-price-big span { font-size: 1.1rem; font-weight: 700; color: var(--kc-sun); white-space: nowrap; }
    .kc-price-note { margin: .6rem 0 0; font-size: .95rem; color: #fff; }
    .kc-price-features { display: grid; gap: .7rem; margin: 1.5rem 0; padding: 1.25rem 0 0; list-style: none; border-top: 1px solid rgba(255, 255, 255, .25); }
    .kc-price-features li { position: relative; padding-left: 1.15rem; font-weight: 600; line-height: 1.45; }
    .kc-price-features li::before { content: ''; position: absolute; left: 0; top: .72em; width: .6rem; height: 2px; background: var(--kc-sun); }
    .kc-btn-block { width: 100%; }
    .kc-price-phone { margin: 1rem 0 0; text-align: center; font-size: .95rem; }
    .kc-price-phone a { color: #fff; font-weight: 800; text-decoration: underline; text-decoration-color: var(--kc-sun); text-underline-offset: 4px; }
    .kc-collections { display: grid; gap: 2rem 2.5rem; padding-top: .25rem; }
    @media (min-width: 768px) { .kc-collections { grid-template-columns: repeat(2, minmax(0, 1fr)); } .kc-collection.is-main { grid-column: 1 / -1; } }
    .kc-collection-head { margin: 0 0 .75rem; padding-bottom: .45rem; border-bottom: 3px solid var(--kc-lime); font-size: 1.2rem; font-weight: 800; color: var(--kc-forest); }
    .kc-plain-list { margin: 0; padding: 0; list-style: none; }
    .kc-plain-list li { padding: .3rem 0; font-size: 1.05rem; line-height: 1.45; break-inside: avoid; }
    @media (min-width: 640px) { .kc-collection.is-main .kc-plain-list { columns: 2; column-gap: 2.5rem; } }
    @media (min-width: 1280px) { .kc-collection.is-main .kc-plain-list { columns: 3; } }
    .kc-offer-page { padding-top: 2.5rem; }

    /* Dlaczego my */
    .kc-why li { display: flex; gap: .85rem; }
    .kc-why .kc-check { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; flex: none; margin-top: .1rem; border-radius: 50%; background: var(--kc-lime); color: var(--kc-forest); font-size: .95rem; }
    .kc-why h3 { margin: 0 0 .3rem; font-size: 1.05rem; font-weight: 800; color: var(--kc-forest); }
    .kc-why p { margin: 0; line-height: 1.6; color: var(--kc-muted); }
    .kc-how { margin-top: 3rem; padding: 2rem; border-radius: var(--kc-radius); }
    @media (min-width: 768px) { .kc-how { display: grid; grid-template-columns: 16rem 1fr; gap: 2rem; padding: 2.5rem 3rem; } }
    .kc-how h3 { margin: 0 0 1rem; font-size: 1.6rem; font-weight: 800; color: #fff; }
    .kc-how p { margin: 0; font-size: 1.05rem; line-height: 1.75; }

    /* Galeria */
    .kc-gallery { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0; padding: 0; list-style: none; }
    @media (min-width: 768px) { .kc-gallery { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .kc-gallery img { display: block; width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 1rem; cursor: zoom-in; }

    /* Zespół */
    .kc-person { display: flex; flex-direction: column; gap: 1rem; }
    @media (min-width: 640px) { .kc-person { flex-direction: row; } }
    .kc-avatar { display: inline-flex; align-items: center; justify-content: center; width: 5rem; height: 5rem; flex: none; border-radius: 50%; background: var(--kc-forest); color: var(--kc-sun); font-size: 1.6rem; font-weight: 800; }
    .kc-role { margin: 0 0 .6rem !important; font-size: .95rem; font-weight: 700; color: var(--kc-green); }

    /* Opinie */
    .kc-reviews { row-gap: 2.5rem; padding-top: 1.25rem; }
    .kc-review { display: flex; flex-direction: column; height: 100%; margin: 0; }
    .kc-bubble { position: relative; flex: 1; margin: 0; padding: 2.25rem 1.6rem 1.6rem; border-radius: 1.5rem; background: #fff; }
    .kc-bubble::after { content: ''; position: absolute; left: 2.25rem; bottom: -.9rem; border-width: 1rem .9rem 0 0; border-style: solid; border-color: #fff transparent transparent transparent; }
    .kc-bubble p { margin: 0; font-size: 1.05rem; line-height: 1.7; }
    .kc-quote-icon { position: absolute; top: -1.35rem; left: 1.5rem; display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border-radius: 50%; background: var(--kc-green); color: #fff; font-size: 1rem; }
    .kc-review figcaption { display: flex; align-items: center; gap: .75rem; margin-top: 1.4rem; padding-left: 1.25rem; }
    .kc-review-avatar { display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; flex: none; border-radius: 50%; background: var(--kc-forest); color: var(--kc-sun); font-weight: 800; }
    .kc-review-name { font-weight: 800; color: var(--kc-forest); }

    /* Wsparcie */
    .kc-split { display: grid; gap: 2rem; }
    @media (min-width: 900px) { .kc-split { grid-template-columns: 1fr 1fr; gap: 4rem; align-items: start; } }
    .kc-list { margin: 0; padding: 0; list-style: none; border-top: 1px solid var(--kc-line); }
    .kc-list > li { display: flex; gap: 1rem; align-items: flex-start; padding: 1.1rem 0; border-bottom: 1px solid var(--kc-line); }
    .kc-list-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; flex: none; border-radius: 50%; background: var(--kc-lime); color: var(--kc-forest); font-size: 1rem; }
    .kc-list h3 { margin: 0 0 .2rem; font-size: 1.1rem; font-weight: 800; color: var(--kc-forest); }
    .kc-list h3 a { color: inherit; text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
    .kc-list h3 a:hover { text-decoration-color: var(--kc-forest); }
    .kc-list p { margin: 0; line-height: 1.6; color: var(--kc-muted); }
    .kc-krs { display: inline-block; margin-top: .5rem; padding: .2rem .6rem; border-radius: .5rem; background: var(--kc-sun); color: var(--kc-forest); font-weight: 800; }

    /* Kontakt */
    .kc-contact { display: grid; gap: 2rem; align-items: center; }
    @media (min-width: 900px) { .kc-contact { grid-template-columns: 1.1fr .9fr; gap: 3rem; } }
    .kc-contact-list { margin: 0; padding: 0; list-style: none; border-top: 1px solid rgba(255, 255, 255, .25); }
    .kc-contact-list li { border-bottom: 1px solid rgba(255, 255, 255, .25); }
    .kc-contact-list a { display: flex; align-items: center; gap: 1rem; min-height: 3.5rem; padding: .9rem 0; color: #fff; font-size: 1.2rem; font-weight: 700; text-decoration: none; overflow-wrap: anywhere; }
    .kc-contact-list a:hover span { text-decoration: underline; text-decoration-color: var(--kc-sun); text-underline-offset: 5px; }
    .kc-contact-list i { width: 1.5rem; color: var(--kc-sun); font-size: 1.2rem; text-align: center; }
    .kc-dark .kc-lead { color: #fff; }

    /* ── Stopka ───────────────────────────────────────────────── */
    .kc-footer { background: #143805; color: #fff; }
    .kc-foot { display: grid; gap: 2rem; padding-top: 3.5rem; padding-bottom: 2.5rem; }
    @media (min-width: 768px) { .kc-foot { grid-template-columns: 1.4fr 1fr 1fr; } }
    .kc-foot-brand { display: flex; gap: 1rem; align-items: center; color: #fff; text-decoration: none; }
    .kc-foot-brand img { width: 4.5rem; height: 4.5rem; flex: none; border-radius: 50%; background: #fff; object-fit: contain; }
    .kc-foot-name { font-size: 1.25rem; font-weight: 800; line-height: 1.2; }
    .kc-foot p { margin: .75rem 0 0; line-height: 1.6; }
    .kc-foot h2 { margin: 0 0 .75rem; font-size: 1rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--kc-sun); }
    .kc-footer ul { margin: 0; padding: 0; list-style: none; }
    .kc-foot-addr { display: inline-flex; align-items: center; gap: .6rem; min-height: 2.75rem; }
    .kc-footer li a, .kc-footer li button { display: inline-flex; align-items: center; gap: .6rem; min-height: 2.75rem; padding: 0; border: 0; background: none; color: #fff; font: inherit; font-weight: 600; text-decoration: underline; text-decoration-color: rgba(255, 255, 255, .45); text-underline-offset: 4px; cursor: pointer; overflow-wrap: anywhere; }
    .kc-footer li a:hover, .kc-footer li button:hover { text-decoration-color: var(--kc-sun); }
    .kc-foot-social { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem !important; }
    .kc-footer .kc-foot-social a { display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border-radius: 50%; background: rgba(255, 255, 255, .12); color: #fff; font-size: 1.15rem; text-decoration: none; }
    .kc-footer .kc-foot-social a:hover { background: var(--kc-sun); color: var(--kc-forest); }
    .kc-foot-bottom { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; align-items: center; justify-content: space-between; padding-top: 1.25rem; padding-bottom: 1.25rem; border-top: 1px solid rgba(255, 255, 255, .2); font-size: .92rem; }
    .kc-foot-bottom p { margin: 0; }
    .kc-foot-bottom ul { display: flex; flex-wrap: wrap; gap: 0 1.25rem; }
    /* Podpis wykonawcy z ikonką weCMS (partials/wecms-mark) — w prawym dolnym rogu, na telefonie pod linkami. */
    .kc-foot-credit { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .9rem; margin-left: auto !important; }
    .kc-foot-credit .wecms-mark { display: inline-flex; align-items: center; gap: .45rem; font-weight: 600; }
    .kc-foot-credit .wecms-mark svg { flex: none; }
    .kc-foot-credit .wecms-mark-name b { font-weight: 800; }
    .kc-foot-credit a { display: inline-flex; align-items: center; min-height: 2.75rem; color: #fff; font-weight: 600; text-decoration: underline; text-decoration-color: rgba(255, 255, 255, .45); text-underline-offset: 4px; }
    .kc-foot-credit a:hover { text-decoration-color: var(--kc-sun); }
    .kc-foot-credit a:focus-visible { outline: 3px solid var(--kc-sun); outline-offset: 2px; }

    /* ── Podstrona „O nas” (partials/about) ──────────────────── */
    .kc-about-mission-sec { padding: 3.5rem 0; }
    .kc-about-mission { max-width: 58rem; margin: 0; padding: .25rem 0 .25rem 1.5rem; border-left: .4rem solid var(--kc-lime); }
    .kc-about-mission p { margin: 0; color: var(--kc-forest); font-size: clamp(1.3rem, 1rem + 1.2vw, 1.85rem); font-weight: 700; line-height: 1.45; }
    .kc-about-person { padding: 2rem; }
    .kc-about-person-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.1rem; }
    .kc-avatar-lg { width: 4.5rem; height: 4.5rem; font-size: 1.5rem; }
    .kc-about-person h3 { margin: 0 0 .4rem; font-size: 1.3rem; }
    .kc-about-role { display: inline-block; margin: 0 !important; padding: .2rem .75rem; border-radius: 999px; background: var(--kc-sun); color: var(--kc-forest); font-size: .85rem; font-weight: 700; line-height: 1.4; }
    .kc-about-person > p { font-size: 1rem; line-height: 1.75; }
    .kc-about-why .kc-card h3 { font-size: 1.12rem; }
    .kc-about-points { display: grid; gap: .45rem; margin: .8rem 0 0; padding: 0; list-style: none; }
    .kc-about-points li { display: flex; gap: .55rem; line-height: 1.5; }
    .kc-about-points li::before { content: ''; width: .45rem; height: .45rem; flex: none; margin-top: .5rem; border-radius: 50%; background: var(--kc-green); }
    .kc-about-story { display: grid; gap: 1.5rem; }
    @media (min-width: 900px) { .kc-about-story { grid-template-columns: 19rem minmax(0, 1fr); gap: 3.5rem; align-items: start; } }
    .kc-about-story-text { max-width: 50rem; }
    .kc-about-story-text p { margin: 0 0 1.1rem; font-size: 1.06rem; line-height: 1.8; }
    .kc-about-story-text p:first-child { color: var(--kc-forest); font-size: 1.22rem; font-weight: 600; line-height: 1.65; }

    /* ── Strona kontaktu (contact.blade.php) ─────────────────── */
    .kc-page-head { padding: 1.5rem 0 2.75rem; background: var(--kc-cream); }
    .kc-page-head h1 { margin: 0 0 .75rem; font-size: clamp(2rem, 1.4rem + 2.4vw, 3.2rem); font-weight: 800; line-height: 1.1; color: var(--kc-forest); }
    .kc-page-head .kc-lead { margin-bottom: 0; }
    .kc-contact-cards h2 { margin: 0 0 .15rem; font-size: .8rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--kc-green); }
    .kc-contact-cards p { color: var(--kc-ink); font-size: 1.2rem; font-weight: 700; overflow-wrap: anywhere; }
    .kc-contact-cards a { color: var(--kc-forest); text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 5px; }
    .kc-contact-cards a:hover { text-decoration-color: var(--kc-forest); }
    .kc-small-h { margin: 2rem 0 .75rem; font-size: 1rem; font-weight: 800; color: var(--kc-forest); }
    .kc-social-compact { margin-top: 0; }
    @media (min-width: 640px) { .kc-social-compact { grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr)); } }
    .kc-social-compact a { min-height: 4rem; padding: .75rem 1rem; background: var(--kc-mint); }
    .kc-social-compact .kc-social-icon { width: 2.5rem; height: 2.5rem; border-radius: .75rem; font-size: 1.15rem; }
    .kc-social-compact .kc-social-name { font-size: 1rem; }
    .kc-form-card { padding: 1.75rem; border-radius: var(--kc-radius); background: var(--kc-cream); }
    .kc-form-card h2 { margin: 0 0 1rem; font-size: 1.5rem; font-weight: 800; color: var(--kc-forest); }

    /* ── Okruszki (partials/breadcrumbs): w kremowym pasie razem z tytułem podstrony ── */
    .kc-crumbs { background: var(--kc-cream); }
    .kc-crumbs ol { display: flex; flex-wrap: wrap; align-items: center; gap: .15rem .5rem; margin: 0; padding: 1rem 0 0; list-style: none; font-size: .85rem; }
    .kc-crumbs li { display: inline-flex; align-items: center; gap: .5rem; }
    .kc-crumbs li + li::before { content: '/'; color: var(--kc-muted); }
    .kc-crumbs a { display: inline-flex; align-items: center; min-height: 2rem; color: var(--kc-forest); font-weight: 600; text-decoration: underline; text-decoration-color: var(--kc-lime); text-underline-offset: 4px; }
    .kc-crumbs a:hover { text-decoration-color: var(--kc-forest); }
    .kc-crumbs [aria-current="page"] { color: var(--kc-muted); font-weight: 600; }

    /* ── Podstrona (page.blade.php) ──────────────────────────── */
    .kc-page-head { position: relative; overflow: hidden; }
    .kc-page-head::after { content: ''; position: absolute; right: -6rem; top: -8rem; width: 20rem; height: 20rem; border-radius: 50%; background: var(--kc-sun); opacity: .55; pointer-events: none; }
    .kc-page-head .kc-wrap { position: relative; z-index: 1; }
    .kc-page-grid { padding-top: 3rem; padding-bottom: 4rem; }
    .kc-page-main { min-width: 0; max-width: 52rem; }
    .kc-prose { color: var(--kc-ink); font-size: 1.05rem; line-height: 1.75; }
    .kc-prose h2 { margin-top: 2.75rem; margin-bottom: 1.25rem; color: var(--kc-forest); font-size: clamp(1.5rem, 1.2rem + 1.2vw, 2rem); font-weight: 800; line-height: 1.2; }
    .kc-prose h2::after { content: ''; display: block; width: 3.5rem; height: .3rem; margin-top: .6rem; border-radius: 999px; background: var(--kc-lime); }
    .kc-prose h3 { display: flex; align-items: center; gap: .75rem; margin-top: 2rem; margin-bottom: .5rem; padding-top: 1.25rem;
        border-top: 1px dashed var(--kc-line); color: var(--kc-forest); font-size: 1.25rem; font-weight: 800; line-height: 1.3; }
    .kc-prose h3::before { content: ''; width: .8rem; height: .8rem; flex: none; border-radius: 50%; background: var(--kc-lime); }
    .kc-prose h3:nth-of-type(3n+2)::before { background: var(--kc-gold); }
    .kc-prose h3:nth-of-type(3n)::before { background: var(--kc-green); }
    .kc-prose h2 + h3 { margin-top: 1rem; border-top: 0; padding-top: 0; }
    .kc-prose h3 + p { margin-top: 0; padding-left: 1.55rem; color: #3c4a37; }
    .kc-prose a { color: var(--kc-forest); text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
    .kc-prose ul > li::marker { color: var(--kc-green); }
    .kc-child-cards { margin-top: 2.5rem !important; }
    .kc-child-card { display: flex; flex-direction: column; gap: .4rem; background: var(--kc-mint); color: var(--kc-ink); text-decoration: none; transition: background-color .15s; }
    .kc-child-card:hover { background: var(--kc-sun); }
    .kc-child-title { font-size: 1.1rem; font-weight: 800; color: var(--kc-forest); }
    .kc-child-text { line-height: 1.6; }
    .kc-child-more { margin-top: auto; padding-top: .5rem; font-weight: 700; color: var(--kc-forest); }
    /* Karty działań (partials/project-card): zdjęcie u góry na całą szerokość karty, treść pod nim. */
    .kc-project-card { padding: 0; overflow: hidden; gap: 0; }
    .kc-project-img { display: flex; align-items: center; justify-content: center; aspect-ratio: 4 / 3; background: var(--kc-cream); color: var(--kc-green); font-size: 2.5rem; }
    .kc-project-img img { width: 100%; height: 100%; object-fit: cover; }
    .kc-project-body { display: flex; flex: 1; flex-direction: column; gap: .4rem; padding: 1.25rem 1.5rem 1.5rem; }
    .kc-project-body .kc-child-title { margin: 0; line-height: 1.3; }
    .kc-project-card:focus-visible { outline: 3px solid var(--kc-forest); outline-offset: 3px; }
    .kc-sec-cream .kc-project-card { background: #fff; }
    .kc-sec-cream .kc-project-card:hover { background: var(--kc-sun); }
    .kc-typed { padding-bottom: 3rem; }
    .kc-page-cta { padding: 3.5rem 0; }
    .kc-page-cta-in { display: grid; gap: 1.5rem; align-items: center; }
    @media (min-width: 900px) { .kc-page-cta-in { grid-template-columns: 1.2fr 1fr; gap: 3rem; } .kc-page-cta .kc-actions { justify-content: flex-end; } }
    .kc-btn-outline-light { background: transparent; border-color: #fff; color: #fff; }
    .kc-btn-outline-light:hover { background: #fff; border-color: #fff; color: var(--kc-forest); }

    @media (prefers-reduced-motion: reduce) { .kc *, .kc *::before, .kc *::after { transition: none !important; } .kc-social a:hover { transform: none; } }
    @media (forced-colors: active) { .kc-btn, .kc-icon-btn, .kc-card, .kc-social a { border: 1px solid CanvasText; } }
</style>
@endonce
