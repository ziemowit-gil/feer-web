{{--
    Czytelniejsze formularze panelu (projekty, aktualności, wydarzenia): większe etykiety i pola, więcej oddechu, karty
    z zaokrągleniem. Działa na elementach z atrybutem data-readable — nie rusza struktury ani skryptów formularza.
--}}
<style>
    [data-readable] label.block.text-sm, [data-readable] label.mb-1.block { font-size: .95rem; color: #1a1a1a; margin-bottom: .4rem; }
    [data-readable] input[type="text"], [data-readable] input[type="url"], [data-readable] input[type="number"], [data-readable] input[type="email"],
    [data-readable] input[type="date"], [data-readable] input[type="datetime-local"], [data-readable] select, [data-readable] textarea { font-size: .95rem; padding-top: .6rem; padding-bottom: .6rem; border-radius: .5rem; }
    [data-readable] p.text-xs.text-muted { font-size: .8125rem; line-height: 1.45; }
    [data-readable] .space-y-5 > * + * { margin-top: 1.75rem; }
    [data-readable] div.rounded-lg.border.bg-white.p-6, [data-readable].rounded-lg.border.bg-white { border-radius: .75rem; padding: 1.75rem; }
    [data-readable] [data-sticky-actions] { position: sticky; bottom: 0; z-index: 20; margin-top: 1.5rem; padding: .85rem 1.25rem; background: rgba(255,255,255,.96); border-top: 1px solid #e5e7eb; border-radius: .75rem .75rem 0 0; }
    [data-readable] [role="tablist"] [data-ftab-btn] { font-size: .875rem; }
</style>
