{{-- Płaski styl stron informacyjnych (nowy styl: pasek w kolorze marki, obrysowane karty, lista z ramkami).
     Wspólny CSS ładowany raz na żądanie; zwykły CSS, niezależny od zbudowanych klas Tailwinda. --}}
@if (! request()->attributes->get('fp_css'))
    @php request()->attributes->set('fp_css', true); @endphp
    <style>
        .fp-head { max-width: 72rem; margin: 0 auto; padding: 2.5rem 1rem 1rem; }
        .fp-h1 { margin: 0; font-size: 2.25rem; line-height: 1.15; font-weight: 800; color: #1d1d1a; }
        .fp-bar { display: block; width: 3.5rem; height: 4px; margin: .9rem 0 1.25rem; background: var(--color-brand-dark); }
        .fp-lead { max-width: 44rem; margin: 0; font-size: 1.15rem; line-height: 1.6; color: #1d1d1a; }
        .fp-wrap { max-width: 56rem; margin: 0 auto; padding: 1rem 1rem 3rem; }
        .fp-h2 { margin: 2.5rem 0 .75rem; padding-left: .75rem; border-left: 4px solid var(--color-brand-dark); font-size: 1.4rem; font-weight: 800; line-height: 1.25; color: #1d1d1a; }
        .fp-h2 i { margin-right: .35rem; color: var(--color-brand-dark); font-size: 1.1rem; }
        .fp-p { margin: 0 0 .75rem; line-height: 1.65; color: #1d1d1a; }
        .fp-p a, .fp-link { color: #1d1d1a; font-weight: 800; text-decoration: underline; text-underline-offset: 3px; }
        .fp-p a:hover, .fp-link:hover { color: var(--color-brand-dark); }
        .fp-p a:focus-visible, .fp-link:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; border-radius: .25rem; }
        .fp-box { padding: 1.25rem 1.4rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #fff; color: #1d1d1a; }
        .fp-box-note { border-color: var(--color-brand-dark); background: var(--color-brand-light); }
        .fp-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; }
        .fp-item { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem 1rem; padding: 1rem 1.2rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #fff; }
        .fp-item:hover { border-color: #1d1d1a; }
        .fp-item-t { font-size: 1.05rem; font-weight: 800; color: #1d1d1a; }
        .fp-item-d { margin: .25rem 0 0; font-size: .95rem; color: #1d1d1a; }
        .fp-tag { display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .6rem; border: 2px solid #1d1d1a; border-radius: .25rem; font-size: .75rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #1d1d1a; background: #fff; }
        .fp-btn { display: inline-flex; min-height: 2.75rem; align-items: center; gap: .5rem; padding: 0 1.25rem; border: 2px solid var(--color-brand-dark); border-radius: .375rem; background: var(--color-brand-dark); color: #fff; font-weight: 800; text-decoration: none; }
        .fp-btn:hover { background: #1d1d1a; border-color: #1d1d1a; color: #fff; }
        .fp-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
        .fp-empty { padding: 1.25rem; border: 2px dashed #9ca3af; border-radius: .5rem; color: #1d1d1a; }
        .fp-center { text-align: center; }
    </style>
@endif
