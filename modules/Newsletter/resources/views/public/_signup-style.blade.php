@once
{{-- Style stron zapisu (/newsletter, /newsletter/{slug}, /newsletter/{slug}/dolacz). Własny CSS, bo push nie przebudowuje Tailwinda.
     Płasko: grupowanie tłem, bez ramek i cieni; obramowania mają tylko pola formularza (WCAG 1.4.11). Kontrast tekstu ≥ 4,5:1. --}}
<style>
.nls{--ink:#1D1D1A;--muted:#4A4A47;--brand:#1E6DFF;--brand-dark:#1752BF;--brand-deep:#123F94;--tint:#E8F0FF;--soft:#F3F6FB;--field:#6B6B67;--error:#B3261E;--error-bg:#FCEBEA;--ok:#0F5132;--ok-bg:#E6F4EA;font-family:Montserrat,Arial,Helvetica,sans-serif;color:var(--ink)}
.nls *{box-sizing:border-box}
.nls a{color:var(--brand-dark);font-weight:700;text-decoration:underline;text-underline-offset:3px}
.nls a:hover{color:var(--ink)}
.nls :focus-visible{outline:3px solid var(--brand);outline-offset:3px}
.nls-wrap{max-width:1120px;margin:0 auto;padding:0 16px}
.nls-crumbs{margin:0;padding:24px 0 0;font-size:14px;color:var(--muted)}
.nls-crumbs a{font-weight:400}
.nls-eyebrow{margin:0 0 10px;font-size:14px;font-weight:700;color:var(--brand-dark)}
.nls h1,.nls h2,.nls h3{font-weight:700;line-height:1.15;margin:0}
.nls-title{font-size:clamp(30px,4vw,44px)}
.nls-lead{margin:16px 0 0;font-size:18px;line-height:1.6;color:var(--muted)}
.nls-h2{font-size:clamp(22px,2.6vw,28px)}
.nls-list{list-style:none;margin:24px 0 0;padding:0;display:grid;gap:12px}
.nls-list li{position:relative;padding-left:32px;font-size:17px;line-height:1.5;font-weight:600}
.nls-list li::before{content:"";position:absolute;left:2px;top:.3em;width:18px;height:18px;background:var(--brand-dark);clip-path:polygon(14% 44%,0 65%,50% 100%,100% 16%,80% 0,43% 62%)}
.nls-facts{list-style:none;margin:24px 0 0;padding:0;display:flex;flex-wrap:wrap;gap:8px 24px;font-size:15px;color:var(--muted)}
.nls-facts li{display:flex;align-items:center;gap:8px}
.nls-facts svg{width:20px;height:20px;color:var(--brand-dark);flex:none}
.nls-steps{list-style:none;margin:16px 0 0;padding:0;display:grid;gap:14px;counter-reset:nls}
.nls-steps li{counter-increment:nls;display:grid;grid-template-columns:28px 1fr;gap:10px;font-size:16px;line-height:1.5}
.nls-steps li::before{content:counter(nls) ".";font-weight:700;color:var(--brand-dark)}
.nls-steps strong{display:block}
.nls-faq{margin:0;display:grid;gap:4px}
.nls-faq details{background:var(--soft);border-radius:10px}
.nls-faq summary{cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:16px;padding:16px 18px;font-weight:700;font-size:16px;line-height:1.4;border-radius:10px}
.nls-faq summary::-webkit-details-marker{display:none}
.nls-faq summary::after{content:"+";font-size:22px;line-height:1;color:var(--brand-dark);flex:none}
.nls-faq details[open] summary::after{content:"−"}
.nls-faq summary:hover{background:var(--tint)}
.nls-faq p{margin:0;padding:0 18px 18px;font-size:16px;line-height:1.6;color:var(--muted)}
.nls-note{margin:0;font-size:15px;line-height:1.6;color:var(--muted)}

/* Formularz */
.nls-panel{background:var(--tint);border-radius:16px;padding:clamp(20px,3vw,32px)}
.nls-panel__title{font-size:22px;margin:0 0 4px}
.nls-panel__sub{margin:0 0 20px;font-size:15px;line-height:1.5;color:var(--muted)}
.nls-form{display:grid;gap:20px}
.nls-form[hidden],.nls-done[hidden],.nls-field[hidden]{display:none}
.nls-label{display:block;margin:0 0 6px;font-size:16px;font-weight:700}
.nls-opt{font-weight:400;color:var(--muted)}
.nls-hint{margin:6px 0 0;font-size:14px;line-height:1.5;color:var(--muted)}
.nls-input{display:block;width:100%;min-height:52px;padding:10px 14px;font:inherit;font-size:17px;color:var(--ink);background:#fff;border:2px solid var(--field);border-radius:8px}
.nls-input:hover{border-color:var(--ink)}
.nls-input--short{max-width:180px}
.nls-input[aria-invalid="true"]{border-color:var(--error);border-width:3px}
.nls-error{margin:6px 0 0;font-size:15px;font-weight:700;color:var(--error)}
.nls-error[hidden]{display:none}
.nls-row{display:grid;gap:20px;grid-template-columns:1fr}
.nls-topics{border:0;margin:0;padding:0;min-width:0}
.nls-topics legend{padding:0}
.nls-topics__list{display:grid;gap:6px;margin-top:10px}
.nls-check{display:grid;grid-template-columns:24px 1fr;gap:12px;align-items:start;padding:12px 14px;background:#fff;border-radius:10px;cursor:pointer;font-size:16px;line-height:1.45}
.nls-check:hover{background:var(--soft)}
.nls-check input,.nls-consent input{appearance:none;-webkit-appearance:none;width:24px;height:24px;margin:0;border:2px solid var(--field);border-radius:6px;background:#fff;display:grid;place-content:center;cursor:pointer}
.nls-check input::before,.nls-consent input::before{content:"";width:13px;height:13px;background:#fff;clip-path:polygon(14% 44%,0 65%,50% 100%,100% 16%,80% 0,43% 62%);transform:scale(0)}
.nls-check input:checked,.nls-consent input:checked{background:var(--brand-dark);border-color:var(--brand-dark)}
.nls-check input:checked::before,.nls-consent input:checked::before{transform:scale(1)}
.nls-check input:focus-visible,.nls-consent input:focus-visible{outline:3px solid var(--brand);outline-offset:3px}
.nls-check strong{font-weight:700}
.nls-check small{display:block;margin-top:2px;font-size:14px;color:var(--muted)}
.nls-topics[aria-invalid="true"] .nls-check input,.nls-consent input[aria-invalid="true"]{border-color:var(--error)}
.nls-consent{display:grid;grid-template-columns:24px 1fr;gap:12px;align-items:start;font-size:15px;line-height:1.55;cursor:pointer}
.nls-next{margin:0;padding:14px 16px;background:#fff;border-radius:10px;font-size:15px;line-height:1.55}
.nls-next strong{display:block;margin-bottom:2px}
.nls-btn{display:inline-flex;width:100%;align-items:center;justify-content:center;gap:10px;min-height:56px;padding:12px 28px;font:inherit;font-size:18px;font-weight:700;color:#fff;background:var(--brand-dark);border:0;border-radius:10px;cursor:pointer;text-decoration:none}
.nls a.nls-btn{color:#fff;text-decoration:none}
.nls-btn:hover,.nls a.nls-btn:hover{background:var(--brand-deep);color:#fff}
.nls-btn[aria-busy="true"]{background:var(--brand-deep);cursor:progress}
.nls-btn svg{width:20px;height:20px}
.nls-btn--ghost,.nls a.nls-btn--ghost{width:auto;color:var(--ink);background:#fff}
.nls-btn--ghost:hover,.nls a.nls-btn--ghost:hover{background:var(--soft);color:var(--ink)}
.nls-summary{padding:14px 16px;background:var(--error-bg);border-radius:10px;color:var(--error);font-size:15px;line-height:1.5}
.nls-summary[hidden]{display:none}
.nls-summary h3{font-size:16px;margin:0 0 6px}
.nls-summary ul{margin:0;padding-left:20px;list-style:disc}
.nls-summary a{color:var(--error)}
.nls-status{margin:0;font-size:15px;font-weight:700;color:var(--error)}
.nls-status:empty{display:none}
.nls-hp{position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden}
.nls-done{display:grid;gap:14px}
.nls-done__title{font-size:24px}
.nls-done p{margin:0;font-size:16px;line-height:1.6}
.nls-done .nls-steps{margin:0}
.nls-done__mark{display:inline-flex;width:56px;height:56px;align-items:center;justify-content:center;border-radius:14px;background:var(--ok-bg);color:var(--ok)}
.nls-done__mark svg{width:30px;height:30px}

/* Strona zapisu */
.nls-page{padding:24px 0 72px}
.nls-page__grid{display:grid;gap:40px;grid-template-columns:1fr;margin-top:28px}
@media(min-width:960px){.nls-page__grid{grid-template-columns:minmax(0,1fr) minmax(0,520px);gap:56px;align-items:start}}
.nls-section{margin-top:40px}
.nls-section > .nls-h2{margin-bottom:8px}

/* Landing */
.nls-hero{background:var(--brand-dark);color:#fff;min-height:calc(100svh - 120px);display:flex;align-items:center;padding:56px 0}
@media(min-width:960px){.nls-hero__copy{position:sticky;top:24px;padding-top:24px}}
.nls-hero .nls-wrap{width:100%}
.nls-hero__grid{display:grid;gap:40px;grid-template-columns:1fr;align-items:start}
@media(min-width:960px){.nls-hero__grid{grid-template-columns:minmax(0,1fr) minmax(0,500px);gap:64px}}
.nls-hero .nls-eyebrow{color:#fff}
.nls-hero .nls-title{font-size:clamp(34px,5vw,56px);color:#fff}
.nls-hero .nls-lead{color:#fff;font-size:19px}
.nls-hero .nls-list li{color:#fff}
.nls-hero .nls-list li::before{background:#fff}
.nls-hero :focus-visible{outline-color:#fff}
.nls-hero .nls-panel :focus-visible{outline-color:var(--brand)}
.nls-hero .nls-panel{background:#fff;color:var(--ink)}
.nls-hero .nls-panel .nls-check,.nls-hero .nls-panel .nls-next{background:var(--soft)}
.nls-hero .nls-panel .nls-check:hover{background:var(--tint)}
.nls-hero__more{display:inline-block;margin-top:28px;font-size:16px}
.nls .nls-hero a.nls-hero__more{color:#fff}
.nls .nls-hero a.nls-hero__more:hover{color:#fff;text-decoration-thickness:3px}
.nls-band{padding:72px 0}
.nls-band--tint{background:var(--soft)}
.nls-band__head{max-width:720px;margin-bottom:32px}
.nls-band__head p{margin:10px 0 0;font-size:17px;line-height:1.6;color:var(--muted)}
.nls-cards{display:grid;gap:16px;grid-template-columns:1fr;list-style:none;margin:0;padding:0}
@media(min-width:720px){.nls-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(min-width:1040px){.nls-cards{grid-template-columns:repeat(4,minmax(0,1fr))}}
.nls-cards li{padding:24px;background:var(--soft);border-radius:14px;font-size:17px;line-height:1.5;font-weight:600}
.nls-cards span{display:block;margin-bottom:12px;font-size:15px;font-weight:700;color:var(--brand-dark)}
.nls-split{display:grid;gap:40px;grid-template-columns:1fr;align-items:start}
@media(min-width:960px){.nls-split{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:64px}}
.nls-mail{background:#fff;border-radius:16px;overflow:hidden}
.nls-mail__meta{display:grid;gap:4px;padding:16px 20px;background:var(--tint);font-size:14px;color:var(--muted)}
.nls-mail__meta b{color:var(--ink)}
.nls-mail__body{padding:24px 20px}
.nls-mail__brand{margin:0 0 4px;font-size:14px;font-weight:700;color:var(--brand-dark)}
.nls-mail__title{font-size:22px;font-weight:700;line-height:1.2;margin:0 0 20px}
.nls-mail__items{list-style:none;margin:0;padding:0;display:grid;gap:18px}
.nls-mail__items li{display:grid;gap:4px}
.nls-mail__items h4{margin:0;font-size:17px;line-height:1.35}
.nls-mail__items p{margin:0;font-size:15px;line-height:1.55;color:var(--muted)}
.nls-mail__foot{margin:24px 0 0;font-size:13px;color:var(--muted)}
.nls-cta{background:var(--tint)}
.nls-cta .nls-panel{background:#fff}
.nls-cta .nls-panel .nls-check,.nls-cta .nls-panel .nls-next{background:var(--soft)}
@media(max-width:619px){.nls-hero{min-height:0;padding:32px 0}.nls-band{padding:48px 0}}
@media(prefers-reduced-motion:no-preference){html:has(.nls-landing){scroll-behavior:smooth}}
</style>
@endonce
