{{--
    Style szablonu „podcasts” (prefiks pc-), inline, bo wdrożenie nie buduje CSS Tailwinda. @once, bo dołączają je nagłówek,
    stopka i strona główna. Kontrast: biały na #1d1d1a 16:1, bursztyn #ea8f00 na #1d1d1a 7,3:1, biały na niebieskim
    #1546c8 7,6:1, niebieski #1546c8 na #f2f7f8 6,9:1, tekst #1d1d1a na bieli 16:1. Najmniejszy tekst 0,875 rem.
--}}
@once
<style>
    .pc, .pc input, .pc button { font-family: Montserrat, Arial, sans-serif; }
    .pc { --pc-navy: #1d1d1a; --pc-blue: #1546c8; --pc-green: #ea8f00; --pc-ink: #1d1d1a; --pc-bg: #f2f7f8; }
    .pc-wrap { max-width: 1160px; margin: 0 auto; padding: 0 1rem; }
    .pc a:focus-visible, .pc button:focus-visible, .pc summary:focus-visible, .pc input:focus-visible { outline: 3px solid var(--pc-green); outline-offset: 2px; }
    .pc-sec a:focus-visible, .pc-sec button:focus-visible, .pc-sec summary:focus-visible { outline-color: var(--pc-ink); }

    .pc-header { position: sticky; top: 0; z-index: 40; background: var(--pc-navy); color: #fff; }
    .pc-header-in { display: flex; align-items: center; gap: .5rem 1.5rem; flex-wrap: wrap; min-height: 3.5rem; padding-top: .35rem; padding-bottom: .35rem; }
    .pc-brand { display: flex; align-items: center; gap: .6rem; color: #fff; text-decoration: none; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .pc-brand img { height: 2.25rem; width: auto; max-width: 4rem; object-fit: contain; }
    .pc-nav { flex: 1; }
    .pc-nav ul { display: flex; flex-wrap: wrap; justify-content: center; gap: 0 .5rem; margin: 0; padding: 0; list-style: none; }
    .pc-nav a { display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0 .9rem; color: #fff; font-size: .9rem; font-weight: 600; text-decoration: none; }
    .pc-nav a:hover, .pc-nav a[aria-current="page"] { color: var(--pc-green); text-decoration: underline; text-underline-offset: 5px; }
    .pc-tools { display: flex; align-items: center; gap: .25rem; margin-left: auto; }
    .pc-back, .pc-tool { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.75rem; padding: 0 .75rem; border: 0; background: transparent; color: #fff; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer; }
    .pc-back:hover, .pc-tool:hover { color: var(--pc-green); }
    .pc-tool[aria-pressed="true"], .pc-tool[aria-expanded="true"] { background: #fff; color: var(--pc-navy); }

    .pc-hero { position: relative; overflow: hidden; background: var(--pc-navy); color: #fff; }
    .pc-hero-in { position: relative; z-index: 1; display: grid; gap: 2rem; padding-top: 4.5rem; padding-bottom: 6rem; max-width: 48rem; }
    .pc-hero h1 { margin: 0; font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 800; line-height: 1.1; text-transform: uppercase; }
    .pc-hero-cta { display: flex; flex-wrap: wrap; gap: .75rem; }
    .pc-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; padding: 0 1.25rem; border: 2px solid transparent; font-size: .9rem; font-weight: 700; text-transform: uppercase; text-decoration: none; cursor: pointer; }
    .pc-btn-green { background: var(--pc-green); color: var(--pc-navy); }
    .pc-btn-blue { background: var(--pc-blue); color: #fff; }
    .pc-btn:hover { text-decoration: underline; text-underline-offset: 4px; }
    .pc-lead { display: flex; gap: 1rem; max-width: 40rem; margin: 0; font-size: 1rem; line-height: 1.6; }
    .pc-lead::before { content: ""; flex: none; width: 2px; background: var(--pc-green); transform: skewX(-25deg); }
    .pc-hero-img { position: absolute; inset: 0 0 0 55%; background-size: cover; background-position: center; clip-path: polygon(12% 0, 100% 0, 100% 88%, 88% 100%, 0 100%); opacity: .55; }
    .pc-hero::after { content: ""; position: absolute; left: 0; bottom: 0; width: 100%; height: 3rem; background: var(--pc-bg); clip-path: polygon(0 100%, 100% 100%, 100% 70%, 6% 70%); }
    @media (max-width: 800px) { .pc-hero-img { display: none; } }

    .pc-sec { background: var(--pc-bg); color: var(--pc-ink); padding: 3.5rem 0; }
    .pc-sec-white { background: #fff; }
    .pc-sec h2.pc-title { margin: 0 0 2.5rem; text-align: center; color: var(--pc-blue); font-size: clamp(1.6rem, 3.5vw, 2.25rem); font-weight: 800; text-transform: uppercase; }
    .pc-cat { display: grid; grid-template-columns: 15rem 1fr; gap: 1.5rem 2rem; padding: 1.5rem 0; }
    .pc-cat h3 { margin: 0 0 .6rem; color: var(--pc-blue); font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
    .pc-cat p { margin: 0 0 1rem; font-size: .9rem; line-height: 1.5; }
    @media (max-width: 800px) { .pc-cat { grid-template-columns: 1fr; } }
    .pc-cards { display: flex; gap: 1rem; margin: 0; padding: 0 0 .75rem; list-style: none; overflow-x: auto; scroll-snap-type: x mandatory; }
    .pc-card { position: relative; flex: 0 0 15rem; display: flex; flex-direction: column; scroll-snap-align: start; background: #fff; border-bottom: 4px solid var(--pc-blue); box-shadow: 0 2px 8px rgba(11, 42, 111, .12); }
    .pc-card-top { padding: 1rem 1rem 0; font-size: .875rem; font-weight: 700; text-transform: uppercase; color: var(--pc-blue); }
    .pc-card h4 { flex: 1; margin: .5rem 1rem 0; font-size: 1rem; font-weight: 700; line-height: 1.35; }
    .pc-card h4 a { color: var(--pc-ink); text-decoration: none; }
    .pc-card h4 a::after { content: ""; position: absolute; inset: 0; }
    .pc-card h4 a:hover { text-decoration: underline; }
    .pc-card:focus-within { outline: 3px solid var(--pc-ink); outline-offset: 2px; }
    .pc-card h4 a:focus-visible { outline: none; }
    .pc-card-meta { display: flex; flex-wrap: wrap; gap: .25rem .75rem; margin: 0; padding: .75rem 1rem 1rem; list-style: none; font-size: .875rem; color: #3f3f3b; }
    .pc-new { align-self: flex-start; margin: 1rem 1rem 0; padding: .1rem .5rem; background: var(--pc-green); color: var(--pc-navy); font-size: .875rem; font-weight: 800; text-transform: uppercase; }

    .pc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); max-width: 46rem; margin: 0 auto; padding: 1.75rem 1rem; background: #fff; list-style: none; text-align: center; }
    .pc-stats strong { display: block; color: var(--pc-blue); font-size: 2.5rem; font-weight: 800; line-height: 1.1; }
    .pc-stats span { font-size: .9rem; }

    .pc-aud { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 2rem; margin: 0; padding: 0; list-style: none; counter-reset: aud; }
    .pc-aud li { counter-increment: aud; padding-bottom: 1.25rem; border-bottom: 3px solid var(--pc-green); font-size: 1rem; font-weight: 700; color: var(--pc-blue); line-height: 1.4; }
    .pc-aud li::before { content: counter(aud); display: block; color: #7a4a00; font-size: 2.75rem; font-weight: 800; line-height: 1.1; }

    .pc-people { display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: 1.25rem; margin: 0; padding: 0; list-style: none; }
    .pc-person { background: #fff; border-bottom: 4px solid var(--pc-blue); text-align: center; }
    .pc-person img, .pc-person-ph { display: block; width: 100%; aspect-ratio: 1; object-fit: cover; filter: grayscale(1); background: #dfe8ec; }
    .pc-person strong { display: block; padding: .75rem .5rem 0; color: var(--pc-blue); font-size: .9rem; }
    .pc-person span { display: block; padding: .15rem .5rem .85rem; font-size: .875rem; }

    .pc-partners { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin: 0; padding: 0; list-style: none; }
    .pc-partners li { display: flex; align-items: center; justify-content: center; width: 9rem; height: 4.5rem; padding: .5rem; border: 1px solid #cdd9de; background: #fff; }
    .pc-partners img { max-width: 100%; max-height: 100%; object-fit: contain; }

    .pc-faq { max-width: 48rem; margin: 0 auto; }
    .pc-faq details { border-bottom: 1px solid #b8c7ce; }
    .pc-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 3rem; padding: .75rem 0; font-weight: 700; cursor: pointer; }
    .pc-faq summary::after { content: "+"; font-size: 1.5rem; color: var(--pc-blue); }
    .pc-faq details[open] summary::after { content: "−"; }
    .pc-faq .pc-faq-a { padding: 0 0 1rem; line-height: 1.6; }

    .pc-foot { background: var(--pc-navy); color: #fff; padding: 2.5rem 0 0; }
    .pc-foot a { color: #fff; }
    .pc-foot-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 2rem; padding-bottom: 2rem; font-size: .9rem; line-height: 1.6; }
    .pc-foot h2 { margin: 0 0 .6rem; color: var(--pc-green); font-size: .9rem; font-weight: 800; text-transform: uppercase; }
    .pc-foot ul { margin: 0; padding: 0; list-style: none; }
    .pc-foot address { font-style: normal; }
    .pc-foot-bar { border-top: 1px solid rgba(255, 255, 255, .3); }
    .pc-foot-bar-in { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; min-height: 3rem; font-size: .875rem; }
    .pc-foot-bar button { min-height: 2.75rem; border: 0; background: transparent; color: #fff; font-size: .875rem; text-decoration: underline; cursor: pointer; }
    .pc-foot a:focus-visible, .pc-foot button:focus-visible { outline-color: var(--pc-green); }
    @media (prefers-reduced-motion: no-preference) { .pc-cards { scroll-behavior: smooth; } }
    .pc-hero-in { max-width: 1160px; display: grid; grid-template-columns: 1fr 18rem; gap: 2rem 3rem; align-items: center; }
    .pc-hero-ep { display: grid; gap: 1rem; }
    .pc-hero .pc-kicker { margin: 0; color: var(--pc-green); font-size: .9rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
    .pc-hero h2.pc-ep-title { margin: 0; font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; line-height: 1.2; }
    .pc-hero h2.pc-ep-title a { color: #fff; }
    .pc-hero audio { width: 100%; max-width: 32rem; }
    .pc-cover { display: block; width: 100%; aspect-ratio: 1; object-fit: cover; background: #3a3a35; }
    .pc-hero::after { display: none; }
    .pc-hero-in { padding-bottom: 4rem; }
    @media (max-width: 800px) { .pc-hero-in { grid-template-columns: 1fr; } .pc-hero .pc-cover { max-width: 14rem; } }
    .pc-eps { display: grid; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); gap: 1.25rem; margin: 0; padding: 0; list-style: none; }
    .pc-ep { position: relative; display: flex; flex-direction: column; background: #fff; border-bottom: 4px solid var(--pc-green); box-shadow: 0 2px 8px rgba(29, 29, 26, .12); }
    .pc-ep .pc-cover { filter: none; }
    .pc-ep-body { display: flex; flex: 1; flex-direction: column; gap: .35rem; padding: 1rem; }
    .pc-ep-meta { margin: 0; font-size: .875rem; color: #3f3f3b; }
    .pc-ep h3 { margin: 0; font-size: 1.05rem; font-weight: 700; line-height: 1.3; }
    .pc-ep h3 a { color: var(--pc-ink); text-decoration: none; }
    .pc-ep h3 a::after { content: ""; position: absolute; inset: 0; }
    .pc-ep h3 a:hover { text-decoration: underline; }
    .pc-ep:focus-within { outline: 3px solid var(--pc-ink); outline-offset: 2px; }
    .pc-ep h3 a:focus-visible { outline: none; }
    .pc-more { margin-top: 2rem; text-align: center; }
    .pc-more .pc-btn { background: var(--pc-ink); color: #fff; }
</style>
@endonce
