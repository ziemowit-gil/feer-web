{{-- Kolumny strony kontaktu FEER (treść + menu boczne) jako własny CSS, żeby układ działał bez przebudowy Tailwinda (jak .proj-cols na stronie działania). Na telefonie menu jest nad treścią. --}}
<style>
    .contact-cols { display: grid; gap: 2.5rem; align-items: start; }
    .contact-cols > .contact-main { min-width: 0; }
    .contact-cols > .contact-aside { order: -1; }
    @media (min-width: 1024px) {
        .contact-cols--nav { grid-template-columns: minmax(0, 1fr) 20rem; }
        .contact-cols--nav > .contact-main { grid-column: 1; grid-row: 1; order: 0; }
        .contact-cols--nav > .contact-aside { grid-column: 2; grid-row: 1; order: 0; position: sticky; top: 1.5rem; }
    }
</style>
