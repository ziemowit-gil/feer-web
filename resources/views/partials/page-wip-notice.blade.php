{{--
    Info banner shown above content that is in "under construction" notice mode
    (the content stays visible). Used by pages and blog articles.
    Expects: $message (string)
    Optional: $heading (default: "Strona w przygotowaniu")
--}}
<div class="mx-auto mb-8 flex max-w-5xl items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3" role="status" aria-label="Informacja o trwających pracach">
    <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-700" aria-hidden="true"></i>
    <div>
        <p class="font-bold text-amber-900">{{ $heading ?? 'Strona w przygotowaniu' }}</p>
        <p class="text-sm text-amber-900">{{ $message }}</p>
    </div>
</div>
