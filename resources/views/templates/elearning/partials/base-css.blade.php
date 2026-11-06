{{--
    Style szablonu „elearning” (prefiks el-), inline, bo wdrożenie nie buduje CSS Tailwinda. @once, bo dołączają je nagłówek,
    stopka i strona główna. Kontrast: biały na granacie #0b2a6f 14:1, zieleń #3ee0a1 na granacie 8:1, biały na niebieskim
    #1546c8 7,6:1, niebieski #1546c8 na #f2f7f8 6,9:1, tekst #1d1d1a na bieli 16:1. Najmniejszy tekst 0,875 rem.
--}}
@once
<style>
    .el, .el input, .el button { font-family: Montserrat, Arial, sans-serif; }
    .el { --el-navy: #0b2a6f; --el-blue: #1546c8; --el-green: #3ee0a1; --el-ink: #1d1d1a; --el-bg: #f2f7f8; }
    .el-wrap { max-width: 1160px; margin: 0 auto; padding: 0 1rem; }
    .el a:focus-visible, .el button:focus-visible, .el summary:focus-visible, .el input:focus-visible { outline: 3px solid var(--el-green); outline-offset: 2px; }
    .el-sec a:focus-visible, .el-sec button:focus-visible, .el-sec summary:focus-visible { outline-color: var(--el-ink); }

    .el-header { position: sticky; top: 0; z-index: 40; background: var(--el-navy); color: #fff; }
    .el-header-in { display: flex; align-items: center; gap: .5rem 1.5rem; flex-wrap: wrap; min-height: 3.5rem; padding-top: .35rem; padding-bottom: .35rem; }
    .el-brand { display: flex; align-items: center; gap: .6rem; color: #fff; text-decoration: none; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .el-brand img { height: 2.25rem; width: auto; max-width: 4rem; object-fit: contain; }
    .el-nav { flex: 1; }
    .el-nav ul { display: flex; flex-wrap: wrap; justify-content: center; gap: 0 .5rem; margin: 0; padding: 0; list-style: none; }
    .el-nav a { display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0 .9rem; color: #fff; font-size: .9rem; font-weight: 600; text-decoration: none; }
    .el-nav a:hover, .el-nav a[aria-current="page"] { color: var(--el-green); text-decoration: underline; text-underline-offset: 5px; }
    .el-tools { display: flex; align-items: center; gap: .25rem; margin-left: auto; }
    .el-back, .el-tool { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.75rem; padding: 0 .75rem; border: 0; background: transparent; color: #fff; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer; }
    .el-back:hover, .el-tool:hover { color: var(--el-green); }
    .el-tool[aria-pressed="true"], .el-tool[aria-expanded="true"] { background: #fff; color: var(--el-navy); }

    .el-hero { position: relative; overflow: hidden; background: var(--el-navy); color: #fff; }
    .el-hero-in { position: relative; z-index: 1; display: grid; gap: 2rem; padding-top: 4.5rem; padding-bottom: 6rem; max-width: 48rem; }
    .el-hero h1 { margin: 0; font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 800; line-height: 1.1; text-transform: uppercase; }
    .el-hero-cta { display: flex; flex-wrap: wrap; gap: .75rem; }
    .el-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; padding: 0 1.25rem; border: 2px solid transparent; font-size: .9rem; font-weight: 700; text-transform: uppercase; text-decoration: none; cursor: pointer; }
    .el-btn-green { background: var(--el-green); color: var(--el-navy); }
    .el-btn-blue { background: var(--el-blue); color: #fff; }
    .el-btn:hover { text-decoration: underline; text-underline-offset: 4px; }
    .el-lead { display: flex; gap: 1rem; max-width: 40rem; margin: 0; font-size: 1rem; line-height: 1.6; }
    .el-lead::before { content: ""; flex: none; width: 2px; background: var(--el-green); transform: skewX(-25deg); }
    .el-hero-img { position: absolute; inset: 0 0 0 55%; background-size: cover; background-position: center; clip-path: polygon(12% 0, 100% 0, 100% 88%, 88% 100%, 0 100%); opacity: .55; }
    .el-hero::after { content: ""; position: absolute; left: 0; bottom: 0; width: 100%; height: 3rem; background: var(--el-bg); clip-path: polygon(0 100%, 100% 100%, 100% 70%, 6% 70%); }
    @media (max-width: 800px) { .el-hero-img { display: none; } }

    .el-sec { background: var(--el-bg); color: var(--el-ink); padding: 3.5rem 0; }
    .el-sec-white { background: #fff; }
    .el-sec h2.el-title { margin: 0 0 2.5rem; text-align: center; color: var(--el-blue); font-size: clamp(1.6rem, 3.5vw, 2.25rem); font-weight: 800; text-transform: uppercase; }
    .el-cat { display: grid; grid-template-columns: 15rem 1fr; gap: 1.5rem 2rem; padding: 1.5rem 0; }
    .el-cat h3 { margin: 0 0 .6rem; color: var(--el-blue); font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    .el-cat p { margin: 0 0 1rem; font-size: .9rem; line-height: 1.5; }
    @media (max-width: 800px) { .el-cat { grid-template-columns: 1fr; } }
    .el-cards { display: flex; gap: 1rem; margin: 0; padding: 0 0 .75rem; list-style: none; overflow-x: auto; scroll-snap-type: x mandatory; }
    .el-card { position: relative; flex: 0 0 15rem; display: flex; flex-direction: column; scroll-snap-align: start; background: #fff; border-bottom: 4px solid var(--el-blue); box-shadow: 0 2px 8px rgba(11, 42, 111, .12); }
    .el-card-top { padding: 1rem 1rem 0; font-size: .875rem; font-weight: 700; text-transform: uppercase; color: var(--el-blue); }
    .el-card h4 { flex: 1; margin: .5rem 1rem 0; font-size: 1rem; font-weight: 700; line-height: 1.35; }
    .el-card h4 a { color: var(--el-ink); text-decoration: none; }
    .el-card h4 a::after { content: ""; position: absolute; inset: 0; }
    .el-card h4 a:hover { text-decoration: underline; }
    .el-card:focus-within { outline: 3px solid var(--el-ink); outline-offset: 2px; }
    .el-card h4 a:focus-visible { outline: none; }
    .el-card-meta { display: flex; flex-wrap: wrap; gap: .25rem .75rem; margin: 0; padding: .75rem 1rem 1rem; list-style: none; font-size: .875rem; color: #3f3f3b; }
    .el-new { align-self: flex-start; margin: 1rem 1rem 0; padding: .1rem .5rem; background: var(--el-green); color: var(--el-navy); font-size: .875rem; font-weight: 800; text-transform: uppercase; }

    .el-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); max-width: 46rem; margin: 0 auto; padding: 1.75rem 1rem; background: #fff; list-style: none; text-align: center; }
    .el-stats strong { display: block; color: var(--el-blue); font-size: 2.5rem; font-weight: 800; line-height: 1.1; }
    .el-stats span { font-size: .9rem; }

    .el-aud { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 2rem; margin: 0; padding: 0; list-style: none; counter-reset: aud; }
    .el-aud li { counter-increment: aud; padding-bottom: 1.25rem; border-bottom: 3px solid var(--el-green); font-size: 1rem; font-weight: 700; color: var(--el-blue); line-height: 1.4; }
    .el-aud li::before { content: counter(aud); display: block; color: #0b7a52; font-size: 2.75rem; font-weight: 800; line-height: 1.1; }

    .el-people { display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: 1.25rem; margin: 0; padding: 0; list-style: none; }
    .el-person { background: #fff; border-bottom: 4px solid var(--el-blue); text-align: center; }
    .el-person img, .el-person-ph { display: block; width: 100%; aspect-ratio: 1; object-fit: cover; filter: grayscale(1); background: #dfe8ec; }
    .el-person strong { display: block; padding: .75rem .5rem 0; color: var(--el-blue); font-size: .9rem; }
    .el-person span { display: block; padding: .15rem .5rem .85rem; font-size: .875rem; }

    .el-partners { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin: 0; padding: 0; list-style: none; }
    .el-partners li { display: flex; align-items: center; justify-content: center; width: 9rem; height: 4.5rem; padding: .5rem; border: 1px solid #cdd9de; background: #fff; }
    .el-partners img { max-width: 100%; max-height: 100%; object-fit: contain; }

    .el-faq { max-width: 48rem; margin: 0 auto; }
    .el-faq details { border-bottom: 1px solid #b8c7ce; }
    .el-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 3rem; padding: .75rem 0; font-weight: 700; cursor: pointer; }
    .el-faq summary::after { content: "+"; font-size: 1.5rem; color: var(--el-blue); }
    .el-faq details[open] summary::after { content: "−"; }
    .el-faq .el-faq-a { padding: 0 0 1rem; line-height: 1.6; }

    .el-foot { background: var(--el-navy); color: #fff; padding: 2.5rem 0 0; }
    .el-foot a { color: #fff; }
    .el-foot-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 2rem; padding-bottom: 2rem; font-size: .9rem; line-height: 1.6; }
    .el-foot h2 { margin: 0 0 .6rem; color: var(--el-green); font-size: .9rem; font-weight: 800; text-transform: uppercase; }
    .el-foot ul { margin: 0; padding: 0; list-style: none; }
    .el-foot address { font-style: normal; }
    .el-foot-bar { border-top: 1px solid rgba(255, 255, 255, .3); }
    .el-foot-bar-in { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; min-height: 3rem; font-size: .875rem; }
    .el-foot-bar button { min-height: 2.75rem; border: 0; background: transparent; color: #fff; font-size: .875rem; text-decoration: underline; cursor: pointer; }
    .el-foot a:focus-visible, .el-foot button:focus-visible { outline-color: var(--el-green); }
    @media (prefers-reduced-motion: no-preference) { .el-cards { scroll-behavior: smooth; } }
</style>
@endonce
