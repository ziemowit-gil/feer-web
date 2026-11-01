{{--
    Style bloków strony głównej „FEER IS” (prefiks fis-). Płasko: bez cieni i ramek (obramowanie zostaje tylko na polach
    formularza, granica kontrolki wg WCAG 1.4.11), kolory z palety witryny (--color-brand*), tekst #1d1d1a.
    Kontrast: ink na bieli 16,9:1, brand-dark na bieli i biały na brand-dark ≥ 6,5:1. Inline, bo wdrożenie nie buduje CSS.
--}}
<style>
    .fis-wrap { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
    .fis-sec { padding: 3rem 0; color: #1d1d1a; }
    .fis-sec--tint { background: #f3f4f6; }
    .fis-sec h2, .fis-h2 { margin: 0 0 1.5rem; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; line-height: 1.2; color: #1d1d1a; }
    .fis-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .5rem 1.5rem; margin-bottom: 1.5rem; }
    .fis-head h2 { margin: 0; }
    .fis-sec a { text-underline-offset: 4px; }
    .fis-sec a:focus-visible, .fis-sec button:focus-visible, .fis-sec select:focus-visible, .fis-sec summary:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
    .fis-link { font-weight: 700; color: var(--color-brand-dark); text-decoration: underline; }
    .fis-link:hover { text-decoration: none; }
    .fis-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 3rem; padding: .5rem 1.75rem; border: 0; border-radius: .375rem; background: var(--color-brand-dark); color: #fff; font-size: 1rem; font-weight: 800; text-decoration: none; cursor: pointer; }
    .fis-btn:hover { background: #1d1d1a; color: #fff; }
    .fis-btn--light { background: #fff; color: #1d1d1a; }
    .fis-btn--light:hover { background: #e5e7eb; color: #1d1d1a; }
    .fis-accent::before { content: ""; display: block; width: 4rem; height: .375rem; margin-bottom: 1.25rem; background: var(--color-brand); }

    {{-- Szybkie wyszukiwanie szkoleń. --}}
    .fis-find { background: var(--color-brand-light); padding: 2rem 0; color: #1d1d1a; }
    .fis-find h2 { margin: 0 0 1rem; font-size: clamp(1.4rem, 3vw, 1.9rem); font-weight: 800; line-height: 1.2; }
    .fis-find-form { display: grid; gap: 1rem; grid-template-columns: 1fr; align-items: end; }
    @media (min-width: 800px) { .fis-find-form { grid-template-columns: 1fr 1fr auto; } }
    .fis-find label { display: block; margin-bottom: .35rem; font-weight: 700; font-size: 1rem; }
    .fis-find select { display: block; width: 100%; min-height: 3.25rem; padding: .5rem .75rem; border: 2px solid #1d1d1a; border-radius: .5rem; background: #fff; color: #1d1d1a; font-size: 1.05rem; }
    .fis-find .fis-btn { min-height: 3.25rem; }
    .fis-find p { margin: 1rem 0 0; font-size: 1rem; }

    {{-- Ścieżki odbiorców: kolumny z listą usług. --}}
    .fis-aud-grid { display: grid; gap: 2.5rem 3rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 19rem), 1fr)); }
    .fis-aud-col h3 { display: flex; align-items: center; gap: .75rem; margin: 0 0 1rem; font-size: 1.4rem; font-weight: 800; line-height: 1.25; }
    .fis-aud-col h3 a { color: #1d1d1a; text-decoration: none; }
    .fis-aud-col h3 a:hover { text-decoration: underline; }
    .fis-aud-col h3 span { color: var(--color-brand-dark); }
    .fis-aud-list { margin: 0; padding: 0; list-style: none; display: grid; gap: 1rem; }
    .fis-aud-list a { font-weight: 700; color: var(--color-brand-dark); text-decoration: underline; }
    .fis-aud-list a:hover { text-decoration: none; }
    .fis-aud-list p { margin: .2rem 0 0; font-size: .95rem; line-height: 1.5; }
    .fis-aud-more { margin: 1.25rem 0 0; }
    .fis-aud-all { margin: 2.5rem 0 0; }

    {{-- Jak się zapisać. --}}
    .fis-steps { margin: 0; padding: 0; list-style: none; display: grid; gap: 2rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr)); counter-reset: fis-step; }
    .fis-steps li { counter-increment: fis-step; }
    .fis-steps li::before { content: counter(fis-step); display: block; margin-bottom: .5rem; font-size: 2.75rem; font-weight: 800; line-height: 1; color: var(--color-brand-dark); }
    .fis-steps h3 { margin: 0 0 .4rem; font-size: 1.25rem; font-weight: 800; line-height: 1.3; }
    .fis-steps p { margin: 0; font-size: 1.05rem; line-height: 1.6; }
    .fis-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2.25rem; }

    {{-- Prowadzący. --}}
    .fis-trainers { margin: 0; padding: 0; list-style: none; display: grid; gap: 2rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr)); }
    .fis-trainer-photo { display: block; width: 7rem; height: 7rem; margin-bottom: 1rem; border-radius: 50%; object-fit: cover; background: var(--color-brand-light); }
    .fis-trainer-photo--none { display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; color: var(--color-brand-dark); }
    .fis-trainers h3 { margin: 0; font-size: 1.2rem; font-weight: 800; line-height: 1.3; }
    .fis-trainers .fis-role { margin: .15rem 0 0; font-size: 1rem; color: #374151; }
    .fis-trainers ul { margin: .75rem 0 0; padding: 0; list-style: none; display: grid; gap: .35rem; }
    .fis-trainers ul a { font-size: .95rem; font-weight: 700; color: var(--color-brand-dark); text-decoration: underline; }
    .fis-trainers ul a:hover { text-decoration: none; }

    {{-- Aktualności. --}}
    .fis-news { margin: 0; padding: 0; list-style: none; display: grid; gap: 2rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 18rem), 1fr)); }
    .fis-news li { position: relative; }
    .fis-news img { display: block; width: 100%; aspect-ratio: 16 / 10; object-fit: cover; border-radius: .375rem; margin-bottom: .75rem; }
    .fis-news time { font-size: .95rem; color: #374151; }
    .fis-news h3 { margin: .25rem 0 0; font-size: 1.15rem; font-weight: 800; line-height: 1.35; }
    .fis-news h3 a { color: var(--color-brand-dark); text-decoration: underline; }
    .fis-news h3 a::after { content: ""; position: absolute; inset: 0; }
    .fis-news h3 a:hover { text-decoration: none; }

    {{-- Pytania i kontakt. --}}
    .fis-qa { display: grid; gap: 2.5rem; grid-template-columns: 1fr; }
    @media (min-width: 900px) { .fis-qa--both { grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); } }
    .fis-faq details { border-bottom: 1px solid #d1d5db; }
    .fis-faq summary { display: flex; justify-content: space-between; gap: 1rem; padding: 1rem 0; font-size: 1.1rem; font-weight: 700; cursor: pointer; list-style: none; }
    .fis-faq summary::-webkit-details-marker { display: none; }
    .fis-faq summary::after { content: "+"; flex: none; font-size: 1.5rem; line-height: 1; }
    .fis-faq details[open] summary::after { content: "–"; }
    .fis-faq details > div { padding: 0 0 1.25rem; font-size: 1.05rem; line-height: 1.65; }
    .fis-contact { padding: 1.75rem; border-radius: .375rem; background: var(--color-brand-dark); color: #fff; align-self: start; }
    .fis-contact h2 { color: #fff; margin-bottom: 1rem; }
    .fis-contact p { margin: 0 0 .75rem; font-size: 1.05rem; line-height: 1.55; }
    .fis-contact a { color: #fff; font-weight: 700; text-decoration: underline; }
    .fis-contact a:hover { text-decoration: none; }
    .fis-contact a.fis-btn { color: #1d1d1a; text-decoration: none; margin-top: .5rem; }
    .fis-contact .fis-tel { font-size: 1.5rem; }

    {{-- Partnerzy. --}}
    .fis-partners { margin: 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; align-items: center; gap: 1.5rem 3rem; }
    .fis-partners img { display: block; height: 3rem; width: auto; max-width: 10rem; object-fit: contain; }
</style>
