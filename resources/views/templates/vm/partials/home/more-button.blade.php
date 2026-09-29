{{-- Przycisk „Czytaj więcej…" pod sekcją; $context uzupełnia nazwę dla czytników ekranu (WCAG 2.4.4). --}}
<a href="{{ $url }}"
   class="vm-display inline-flex min-h-11 items-center rounded-2xl bg-brand px-6 text-xs text-white shadow-md transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
    {{ $label }}@if (! empty($context))<span class="sr-only"> — {{ $context }}</span>@endif
</a>
