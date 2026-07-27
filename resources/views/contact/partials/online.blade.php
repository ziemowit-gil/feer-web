<div class="rounded-lg border border-gray-200 p-5">
    <div class="mb-3 flex items-center gap-3">
        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand text-white" aria-hidden="true">
            <i class="fa-solid fa-video"></i>
        </span>
        <h3 class="text-lg font-bold text-ink">Spotkanie online</h3>
    </div>
    <p class="mb-4 text-sm text-muted">{{ $onlineText }}</p>
    <a href="{{ $onlineUrl }}" @if ($onlineExternal) target="_blank" rel="noopener" @endif
        class="inline-flex w-fit items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> {{ $onlineLabel }}
        @if ($onlineExternal)<i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>@endif
    </a>
</div>
