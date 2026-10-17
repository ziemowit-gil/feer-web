{{-- Style kart materiałów (strona /materialy i lista [materialy] w treści) — wbudowane, bo push nie buduje Tailwinda. --}}
<style>
    /* Materiały FEER — style wbudowane (push na produkcję nie buduje Tailwinda). Płasko: bez ramek, cieni i etykiet. */
    .mat-notice { display: flex; gap: .5rem; align-items: baseline; max-width: 42rem; margin-top: 1rem; font-size: .875rem; line-height: 1.6; color: var(--color-muted, #4b5563); }
    .mat-notice > i { color: var(--color-brand-dark, #1752bf); }
    .mat-notice p { margin: 0; }
    .mat-notice a { color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 3px; }

    .mat-tools { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem 1.5rem; margin-bottom: 2.5rem; }
    .mat-tools[hidden] { display: none; }
    .mat-search { flex: 1 1 16rem; max-width: 26rem; }
    .mat-label { display: block; margin-bottom: .375rem; font-size: .875rem; font-weight: 700; color: var(--color-ink, #1a1a1a); }
    .mat-input { width: 100%; min-height: 2.75rem; padding: 0 .875rem; border: 1px solid #6b7280; border-radius: .375rem; background: #fff; color: var(--color-ink, #1a1a1a); font-size: 1rem; }
    .mat-input:focus { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 1px; border-color: var(--color-brand-dark, #1752bf); }
    .mat-filter { display: flex; flex-wrap: wrap; gap: .5rem; }
    .mat-chip { min-height: 2.75rem; padding: 0 1rem; border-radius: .375rem; background: #f3f4f6; color: var(--color-ink, #1a1a1a); font-size: .875rem; font-weight: 700; cursor: pointer; }
    .mat-chip:hover { background: #e5e7eb; }
    .mat-chip[aria-pressed="true"] { background: var(--color-ink, #1a1a1a); color: #fff; }
    .mat-chip:focus-visible { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 2px; }
    .mat-count { flex-basis: 100%; margin: 0; font-size: .875rem; color: var(--color-muted, #4b5563); }

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
    .mat-eyebrow { margin: 0 0 .25rem; font-size: .8125rem; font-weight: 700; color: var(--color-muted, #4b5563); }
    .mat-title { margin: 0; font-size: 1.125rem; font-weight: 700; line-height: 1.35; color: var(--color-ink, #1a1a1a); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .mat-link { color: inherit; text-decoration: none; }
    .mat-link:focus-visible { outline: none; }
    .mat-link::after { content: ''; position: absolute; inset: 0; z-index: 2; }
    .mat-meta { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin: .5rem 0 0; font-size: .875rem; color: var(--color-muted, #4b5563); }
    .mat-meta-item { display: inline-flex; align-items: center; gap: .375rem; }
    .mat-desc { margin: .75rem 0 0; font-size: .875rem; line-height: 1.6; color: var(--color-muted, #4b5563); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .mat-cta { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; margin-top: auto; padding-top: 1rem; font-size: .9375rem; font-weight: 700; color: var(--color-brand-dark, #1752bf); }
    .mat-card:hover .mat-cta, .mat-card:has(.mat-link:focus-visible) .mat-cta { text-decoration: underline; text-underline-offset: 4px; }
    .mat-empty { margin-top: 1rem; color: var(--color-ink, #1a1a1a); }
    .mat-empty[hidden] { display: none; }
    @media (prefers-reduced-motion: reduce) { .mat-card, .mat-thumb-img, .mat-play { transition: none; } .mat-card:hover .mat-thumb-img { transform: none; } }
</style>
