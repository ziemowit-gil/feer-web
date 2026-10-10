@once
<style>
.np{--np-text:#1D1D1A;--np-muted:#4A4A47;--np-brand:var(--color-brand,#1E6DFF);--np-dark:#1752BF;--np-accent:#EA8F00;--np-line:#D6DEEA;--np-border:#8E8E8A;font-family:Montserrat,Arial,Helvetica,sans-serif;color:var(--np-text);max-width:1040px;margin:0 auto;padding:32px 16px 64px}
.np *{box-sizing:border-box}
.np__head{display:grid;gap:18px;grid-template-columns:1fr;align-items:start;margin-bottom:28px}
@media(min-width:760px){.np__head{grid-template-columns:1fr auto}}
.np__eyebrow{margin:0 0 8px;font-size:13px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--np-dark)}
.np__title{margin:0 0 10px;font-size:clamp(26px,3.4vw,38px);line-height:1.1;font-weight:800}
.np__lead{margin:0;font-size:16px;line-height:1.6;color:var(--np-muted)}
.np__lead strong{color:var(--np-text)}
.np__state{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;font-size:14px;font-weight:700;white-space:nowrap}
.np__state--ok{background:#E6F4EA;color:#0F5132}.np__state--warn{background:#FEF3C7;color:#78350F}.np__state--off{background:#E5E7EB;color:#374151}
.np__state svg{width:16px;height:16px}
.np__flash{margin:0 0 20px;padding:14px 16px;border-radius:10px;background:#E6F4EA;color:#0F5132;font-weight:700}
.np__err{margin:0 0 20px;padding:14px 16px;border-radius:10px;background:#FCEBEA;color:#B3261E;font-weight:700}
.np__grid{display:grid;gap:24px;grid-template-columns:1fr}
@media(min-width:900px){.np__grid{grid-template-columns:7fr 5fr;align-items:start}}
.np__card{background:#fff;border:1px solid var(--np-line);border-radius:14px;box-shadow:0 10px 30px rgba(29,29,26,.06);padding:26px}
.np__card+.np__card{margin-top:20px}
.np__h2{display:flex;align-items:center;gap:10px;margin:0 0 6px;font-size:19px;font-weight:800}
.np__h2 span{display:inline-flex;width:34px;height:34px;align-items:center;justify-content:center;border-radius:10px;background:#EAF1FF;color:var(--np-dark)}
.np__h2 svg{width:18px;height:18px}
.np__hint{margin:0 0 18px;font-size:14px;color:var(--np-muted);line-height:1.5}
.np__label{display:block;margin-bottom:6px;font-size:15px;font-weight:700}
.np__input{width:100%;min-height:50px;padding:10px 14px;font:inherit;font-size:16px;color:var(--np-text);background:#fff;border:2px solid var(--np-border);border-radius:8px}
.np__input:focus-visible,.np__btn:focus-visible,.np__link:focus-visible{outline:3px solid var(--np-brand);outline-offset:3px}
.np__tiles{display:grid;gap:10px;grid-template-columns:1fr}
@media(min-width:560px){.np__tiles{grid-template-columns:1fr 1fr}}
.np__tile{position:relative;display:grid;grid-template-columns:26px 1fr;gap:12px;align-items:start;padding:14px;border:2px solid var(--np-line);border-radius:12px;cursor:pointer;background:#fff;transition:border-color .15s,background .15s}
.np__tile:hover{border-color:var(--np-text)}
.np__tile input{appearance:none;-webkit-appearance:none;width:24px;height:24px;margin-top:1px;border:2px solid var(--np-border);border-radius:7px;background:#fff;display:grid;place-content:center;cursor:pointer}
.np__tile input::before,.np__check input::before{content:"";width:13px;height:13px;clip-path:polygon(14% 44%,0 65%,50% 100%,100% 16%,80% 0,43% 62%);background:#fff;transform:scale(0)}
.np__tile input:checked,.np__check input:checked{background:var(--np-dark);border-color:var(--np-dark)}
.np__tile input:checked::before,.np__check input:checked::before{transform:scale(1)}
.np__tile:has(input:checked){border-color:var(--np-dark);background:#EAF1FF}
.np__tile:has(input:focus-visible){outline:3px solid var(--np-brand);outline-offset:3px}
.np__tile strong{display:flex;align-items:center;gap:8px;font-size:15px}
.np__tile strong i{color:var(--np-dark);font-size:14px;width:16px;text-align:center}
.np__tile small{display:block;margin-top:3px;font-size:13px;line-height:1.45;color:var(--np-muted)}
.np__check{display:grid;grid-template-columns:24px 1fr;gap:12px;align-items:start;padding:12px 0;border-top:1px solid var(--np-line);font-size:15px;line-height:1.5}
.np__check:first-of-type{border-top:0}
.np__check input{appearance:none;-webkit-appearance:none;width:24px;height:24px;margin-top:1px;border:2px solid var(--np-border);border-radius:7px;background:#fff;display:grid;place-content:center;cursor:pointer}
.np__check input:disabled{background:#E5E7EB;border-color:#B9B9B6;cursor:not-allowed}
.np__check input:disabled::before{background:#374151}
.np__check small{display:block;color:var(--np-muted);font-size:13px}
.np__actions{position:sticky;bottom:0;display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-top:20px;padding:14px 0;background:rgba(255,255,255,.96);border-top:1px solid var(--np-line)}
.np__btn{display:inline-flex;align-items:center;gap:10px;min-height:52px;padding:12px 28px;font:inherit;font-size:16px;font-weight:800;border-radius:10px;cursor:pointer;text-decoration:none;border:2px solid transparent}
.np__btn--primary{background:var(--np-dark);color:#fff}.np__btn--primary:hover{background:#123F94}
.np__btn--ghost{background:#fff;color:var(--np-text);border-color:var(--np-border)}.np__btn--ghost:hover{background:#F3F6FB}
.np__btn--danger{background:#fff;color:#9F1D18;border-color:#D9534F}.np__btn--danger:hover{background:#FCEBEA}
.np__btn svg{width:16px;height:16px}
.np__meta{margin:0;padding:0;list-style:none;font-size:14px;color:var(--np-muted)}
.np__meta li{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-top:1px solid var(--np-line)}
.np__meta li:first-child{border-top:0}
.np__meta b{color:var(--np-text);font-weight:700;text-align:right}
.np__link{color:var(--np-dark);font-weight:700;text-decoration:underline;text-underline-offset:3px}
.np__rodo{background:#F3F6FB;border-color:#E3EAF6;box-shadow:none}
@media (prefers-reduced-motion:reduce){.np__tile{transition:none}}

.np--narrow{max-width:720px}
.np__hero{display:grid;gap:18px;justify-items:start;padding:34px;background:#fff;border:1px solid var(--np-line);border-radius:16px;box-shadow:0 10px 30px rgba(29,29,26,.06)}
.np__icon{display:inline-flex;width:64px;height:64px;align-items:center;justify-content:center;border-radius:18px;background:#EAF1FF;color:var(--np-dark)}
.np__icon--ok{background:#E6F4EA;color:#0F5132}.np__icon--warn{background:#FEF3C7;color:#78350F}.np__icon--off{background:#E5E7EB;color:#374151}
.np__icon svg{width:30px;height:30px}
.np__hero h1{margin:0;font-size:clamp(26px,3.4vw,36px);line-height:1.15;font-weight:800}
.np__hero p{margin:0;font-size:16px;line-height:1.6;color:var(--np-muted)}
.np__hero p strong{color:var(--np-text)}
.np__chips{display:flex;flex-wrap:wrap;gap:8px;margin:0;padding:0;list-style:none}
.np__chips li{padding:6px 14px;border-radius:999px;background:#EAF1FF;color:var(--np-dark);font-size:14px;font-weight:700}
.np__row{display:flex;flex-wrap:wrap;gap:12px}
.np__steps{display:grid;gap:10px;margin:0;padding:0;list-style:none;counter-reset:s}
.np__steps li{display:grid;grid-template-columns:30px 1fr;gap:12px;align-items:start;font-size:15px;line-height:1.5;color:var(--np-text)}
.np__steps li::before{counter-increment:s;content:counter(s);display:inline-flex;width:28px;height:28px;align-items:center;justify-content:center;border-radius:50%;background:#EAF1FF;color:var(--np-dark);font-weight:800;font-size:13px}
.np__select{width:100%;min-height:50px;padding:10px 14px;font:inherit;font-size:16px;color:var(--np-text);background:#fff;border:2px solid var(--np-border);border-radius:8px}
.np__select:focus-visible{outline:3px solid var(--np-brand);outline-offset:3px}
.np__box{width:100%;padding:18px;border-radius:12px;background:#F3F6FB}
.np__foot{margin:18px 0 0;font-size:14px;color:var(--np-muted)}
</style>
@endonce