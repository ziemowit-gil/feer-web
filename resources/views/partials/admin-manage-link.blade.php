{{--
    Link „Zarządzaj …” widoczny tylko dla administratora — skrót z publicznej strony do panelu.
    Parametry: $route (nazwa trasy admina), $label (np. „Zarządzaj działaniami").
--}}
@if (auth()->check() && auth()->user()->isAdmin())
    <a href="{{ route($route) }}" class="inline-flex min-h-9 shrink-0 items-center gap-1.5 text-sm font-bold text-ink underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
        <i class="fa-solid fa-gear" aria-hidden="true"></i>{{ $label }}
    </a>
@endif
