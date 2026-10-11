{{-- Prosta paginacja list naborów / zapytań (?strona=N, z zachowaniem filtra). Zmienne: $currentPage, $lastPage, $anchor, $label. --}}
@if ($lastPage > 1)
    @php $link = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark'; @endphp
    <nav aria-label="{{ $label }}" class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            @if ($currentPage > 1)
                <a href="{{ request()->fullUrlWithQuery(['strona' => $currentPage - 1 > 1 ? $currentPage - 1 : null]) }}#{{ $anchor }}" class="{{ $link }}" rel="prev">
                    <i class="fa-solid fa-arrow-left text-sm" aria-hidden="true"></i> Poprzednia strona
                </a>
            @endif
        </div>
        <p class="text-ink">Strona {{ $currentPage }} z {{ $lastPage }}</p>
        <div>
            @if ($currentPage < $lastPage)
                <a href="{{ request()->fullUrlWithQuery(['strona' => $currentPage + 1]) }}#{{ $anchor }}" class="{{ $link }}" rel="next">
                    Następna strona <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </nav>
@endif
