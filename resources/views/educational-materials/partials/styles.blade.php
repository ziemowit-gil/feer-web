{{-- Style kart materiałów (strona /materialy i lista [materialy] w treści) — wbudowane, bo push nie buduje Tailwinda. --}}
<style>
    /* Materiały FEER — style wbudowane (push na produkcję nie buduje Tailwinda). Płasko: bez ramek, cieni i etykiet. */
    .mat-notice { display: flex; gap: .5rem; align-items: baseline; max-width: 42rem; margin-top: 1rem; font-size: .875rem; line-height: 1.6; color: var(--color-muted, #4b5563); }
    .mat-notice > i { color: var(--color-brand-dark, #1752bf); }
    .mat-notice p { margin: 0; }
    .mat-notice a { color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 3px; }

    /* Nagłówek strony /materialy: brand-light, duży H1, skróty typów jako płaskie kafelki (biel na brand-light). */
    .mat-hero { background: var(--color-brand-light, #e8f0ff); padding: 3rem 0 2.5rem; }
    @media (min-width: 768px) { .mat-hero { padding: 4rem 0 3rem; } }
    .mat-hero-eyebrow { margin: 0 0 .5rem; font-size: .8125rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-dark, #1752bf); }
    .mat-hero-title { margin: 0; font-family: var(--font-heading); font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 800; line-height: 1.1; letter-spacing: -.01em; color: var(--color-ink, #1a1a1a); }
    .mat-hero-intro { max-width: 42rem; margin-top: 1rem; font-size: 1.125rem; line-height: 1.65; color: var(--color-ink, #1a1a1a); }
    .mat-hero-intro p { margin: 0 0 .75rem; }
    .mat-hero-intro a { color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 3px; }
    .mat-hero .mat-notice { color: var(--color-ink, #1a1a1a); }
    .mat-stats { display: flex; flex-wrap: wrap; gap: .75rem; margin: 2rem 0 0; padding: 0; list-style: none; }
    .mat-stat { display: inline-flex; align-items: center; gap: .625rem; min-height: 3rem; padding: .375rem 1rem .375rem .375rem; border-radius: .5rem; background: #fff; color: var(--color-ink, #1a1a1a); font-size: .9375rem; text-decoration: none; transition: background-color .15s, color .15s; }
    .mat-stats li:first-child .mat-stat { padding-left: 1rem; }
    .mat-stat-n { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; }
    .mat-stat:hover { background: var(--color-ink, #1a1a1a); color: #fff; }
    .mat-stat:focus-visible { outline: 3px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; }

    /* Ikona typu: pełny kafelek w kolorze typu + nazwa typu tekstem (typ nie zależy od samego koloru). */
    .mat-type-icon { display: inline-flex; flex-shrink: 0; width: 2.25rem; height: 2.25rem; align-items: center; justify-content: center; border-radius: .375rem; background: var(--color-brand-dark, #1752bf); color: #fff; font-size: .9375rem; }
    .mat-stat--video .mat-type-icon, .mat-card--video .mat-type-icon { background: var(--color-ink, #1a1a1a); color: #fff; }
    .mat-stat--scenariusz .mat-type-icon, .mat-card--scenariusz .mat-type-icon { background: #ea8f00; color: var(--color-ink, #1a1a1a); }
    .mat-stat:hover .mat-type-icon { background: #fff; color: var(--color-ink, #1a1a1a); }

    /* Układ: filtry po lewej (przyklejone na dużych ekranach), wyniki po prawej. */
    .mat-layout { display: grid; gap: 2.5rem; }
    @media (min-width: 1024px) { .mat-layout { grid-template-columns: 16rem minmax(0, 1fr); gap: 3rem; } .mat-aside { position: sticky; top: 1.5rem; align-self: start; } }
    .mat-aside { padding: 1.25rem; border-radius: .5rem; background: #f9fafb; }
    .mat-aside-h { margin: 0 0 1rem; font-family: var(--font-heading); font-size: 1.125rem; font-weight: 800; color: var(--color-ink, #1a1a1a); }
    .mat-filters { display: grid; gap: 1.25rem; }
    .mat-field { margin: 0; min-width: 0; }
    .mat-fieldset { padding: 0; border: 0; }
    .mat-label { display: block; margin-bottom: .375rem; padding: 0; font-size: .875rem; font-weight: 700; color: var(--color-ink, #1a1a1a); }
    .mat-input { width: 100%; min-height: 2.75rem; padding: 0 .875rem; border: 1px solid #6b7280; border-radius: .375rem; background: #fff; color: var(--color-ink, #1a1a1a); font-size: 1rem; }
    .mat-select { padding-right: 2.25rem; }
    .mat-input:focus { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 1px; border-color: var(--color-brand-dark, #1752bf); }
    .mat-options { display: grid; gap: .25rem; }
    .mat-option { display: flex; align-items: center; gap: .625rem; min-height: 2.75rem; padding: 0 .75rem; border-radius: .375rem; font-size: .9375rem; color: var(--color-ink, #1a1a1a); cursor: pointer; }
    .mat-option:hover { background: #f3f4f6; }
    .mat-option input { width: 1.125rem; height: 1.125rem; margin: 0; accent-color: var(--color-brand-dark, #1752bf); }
    .mat-option input:focus-visible { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 2px; }
    .mat-option:has(input:checked) { background: var(--color-ink, #1a1a1a); color: #fff; font-weight: 700; }
    .mat-option:has(input:checked) input { accent-color: #fff; }
    .mat-option:has(input:checked) .mat-option-n { color: #fff; }
    .mat-option-n { margin-left: auto; font-size: .875rem; color: var(--color-muted, #4b5563); }
    .mat-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; }
    .mat-btn { display: inline-flex; min-height: 2.75rem; align-items: center; padding: 0 1rem; border-radius: .375rem; background: var(--color-brand-dark, #1752bf); color: #fff; font-size: .9375rem; font-weight: 700; cursor: pointer; }
    .mat-btn:hover { background: var(--color-ink, #1a1a1a); }
    .mat-btn:focus-visible { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 2px; }
    .mat-btn[hidden], .mat-reset[hidden] { display: none; }
    .mat-reset { display: inline-flex; min-height: 2.75rem; align-items: center; font-size: .9375rem; font-weight: 700; color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 4px; }
    .mat-reset:hover { text-decoration: none; }
    .mat-reset:focus-visible, .mat-aside-more a:focus-visible { outline: 3px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; border-radius: .25rem; }
    .mat-aside-more { margin: 1rem 0 0; font-size: .875rem; }
    .mat-aside-more a { display: inline-flex; min-height: 2.75rem; align-items: center; font-weight: 700; color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 4px; }
    .mat-aside-more a:hover { text-decoration: none; }

    .mat-results-bar { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .5rem 1rem; margin-bottom: 1.5rem; }
    .mat-count { margin: 0; font-size: .9375rem; font-weight: 700; color: var(--color-ink, #1a1a1a); }
    .mat-groups > [hidden] { display: none; }
    .mat-groups > * + * { margin-top: 4rem; }
    .mat-group-h { display: flex; flex-wrap: wrap; align-items: baseline; gap: .25rem .75rem; margin-bottom: 1.25rem; font-size: 1.5rem; font-weight: 800; line-height: 1.25; color: var(--color-ink, #1a1a1a); }
    .mat-group-count { font-family: var(--font-sans); font-size: .9375rem; font-weight: 400; color: var(--color-muted, #4b5563); }
    .mat-grid { display: grid; gap: 1.5rem; }
    @media (min-width: 640px) { .mat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1280px) { .mat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .mat-grid > li { display: flex; }
    .mat-grid > li[hidden] { display: none; }

    .mat-card { position: relative; display: flex; flex-direction: column; width: 100%; overflow: hidden; border-radius: .5rem; background: #f9fafb; transition: background-color .15s; }
    .mat-card:hover { background: #f3f4f6; }
    .mat-card:has(.mat-link:focus-visible) { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 3px; }
    .mat-thumb { position: relative; aspect-ratio: 16 / 9; overflow: hidden; }
    .mat-thumb--video { background: var(--color-ink, #1a1a1a); }
    .mat-thumb--doc { background: var(--color-brand-light, #e8f0ff); }
    .mat-thumb-img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .mat-card:hover .mat-thumb-img { transform: scale(1.03); }
    .mat-play { position: absolute; left: 1rem; bottom: 1rem; display: flex; width: 2.75rem; height: 2.75rem; align-items: center; justify-content: center; border-radius: .375rem; background: #fff; color: var(--color-ink, #1a1a1a); transition: background-color .15s, color .15s; }
    .mat-card:hover .mat-play { background: var(--color-brand-dark, #1752bf); color: #fff; }
    .mat-doc-icon { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--color-brand-dark, #1752bf); }
    .mat-thumb-canvas { position: relative; z-index: 1; display: block; width: 100%; height: 100%; object-fit: contain; }

    .mat-body { display: flex; flex: 1; flex-direction: column; padding: 1.25rem; }
    .mat-thumb--scenariusz { background: #fdf1de; }
    .mat-thumb--scenariusz .mat-doc-icon { color: var(--color-ink, #1a1a1a); }
    .mat-type { display: flex; align-items: center; gap: .625rem; margin: 0 0 .75rem; font-size: .875rem; font-weight: 700; color: var(--color-ink, #1a1a1a); }
    .mat-eyebrow { margin: 0 0 .25rem; font-size: .8125rem; font-weight: 700; color: var(--color-muted, #4b5563); }
    .mat-title { margin: 0; font-size: 1.125rem; font-weight: 700; line-height: 1.35; color: var(--color-ink, #1a1a1a); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .mat-link { color: inherit; text-decoration: none; }
    .mat-link:focus-visible { outline: none; }
    .mat-link::after { content: ''; position: absolute; inset: 0; z-index: 2; }
    .mat-meta { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin: .5rem 0 0; font-size: .875rem; color: var(--color-muted, #4b5563); }
    .mat-meta-item { display: inline-flex; align-items: center; gap: .375rem; }
    .mat-desc { margin: .75rem 0 0; font-size: .875rem; line-height: 1.6; color: var(--color-muted, #4b5563); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .mat-cta { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; margin-top: auto; padding-top: 1rem; font-size: .9375rem; font-weight: 700; color: var(--color-brand-dark, #1752bf); }
    .mat-type-format { margin-left: .25rem; font-weight: 400; color: var(--color-muted, #4b5563); }
    .mat-type-format::before { content: '· '; }
    .mat-actions-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .25rem 1rem; margin-top: auto; padding-top: 1rem; }
    .mat-actions-row .mat-cta { margin-top: 0; padding-top: 0; }
    .mat-download { position: relative; z-index: 3; display: inline-flex; min-height: 2.75rem; align-items: center; gap: .5rem; padding: 0 .875rem; border-radius: .375rem; background: #fff; color: var(--color-ink, #1a1a1a); font-size: .875rem; font-weight: 700; text-decoration: none; }
    .mat-download:hover { background: var(--color-ink, #1a1a1a); color: #fff; }
    .mat-download:hover .mat-download-meta { color: #fff; }
    .mat-download:focus-visible { outline: 3px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; }
    .mat-download-meta { font-weight: 400; color: var(--color-muted, #4b5563); }
    .mat-card:hover .mat-cta, .mat-card:has(.mat-link:focus-visible) .mat-cta { text-decoration: underline; text-underline-offset: 4px; }
    .mat-empty { margin-top: 1rem; padding: 1.5rem; border-radius: .5rem; background: #f9fafb; color: var(--color-ink, #1a1a1a); }
    .mat-empty p { margin: 0 0 .5rem; }
    .mat-empty[hidden] { display: none; }
    @media (prefers-reduced-motion: reduce) { .mat-stat, .mat-card, .mat-thumb-img, .mat-play { transition: none; } .mat-card:hover .mat-thumb-img { transform: none; } }
</style>
