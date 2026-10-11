{{--
    Wyszukiwarka wpisów Wiem FEER (GET /wiem-feer?q=). Widoczna etykieta, przycisk tekstowy.
    Zmienne: $q, $formId (unikalny — formularz bywa na stronie dwa razy), opcjonalnie $panel (szare tło).
--}}
@php $panel = $panel ?? true; @endphp
<div role="search" aria-label="Wyszukiwarka Wiem FEER" class="{{ $panel ? 'rounded-md bg-gray-50 p-5' : '' }}">
    <form method="GET" action="{{ route('blog.index') }}">
        <label for="{{ $formId }}" class="mb-2 block text-sm font-bold text-ink">Szukaj we wpisach</label>
        <div class="flex gap-2">
            <input type="search" id="{{ $formId }}" name="q" value="{{ $q ?? '' }}" maxlength="100" autocomplete="off"
                class="min-h-11 w-full min-w-0 rounded-md border-0 border-b-2 border-gray-500 bg-white px-3 text-base text-ink focus:border-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-dark">
            <button type="submit"
                class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Szukaj
            </button>
        </div>
    </form>
</div>
