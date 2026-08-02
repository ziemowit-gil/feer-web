<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="h-1.5 w-full" style="background: var(--accent)" aria-hidden="true"></div>

    <dl class="divide-y divide-gray-100">
        <div class="flex items-start gap-3 px-5 py-4">
            <i class="fa-solid fa-calendar-day mt-0.5 flex-none text-base" style="color: var(--accent)" aria-hidden="true"></i>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Termin</dt>
                <dd class="mt-0.5 font-bold text-ink">
                    <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->dateRangeLabel() }}</time>
                </dd>
            </div>
        </div>

        <div class="flex items-start gap-3 px-5 py-4">
            <i class="fa-solid fa-location-dot mt-0.5 flex-none text-base" style="color: var(--accent)" aria-hidden="true"></i>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Tryb i miejsce</dt>
                <dd class="mt-0.5 font-bold text-ink">
                    {{ $event->modeLabel() }}
                    @if ($event->location)
                        <span class="block font-normal text-gray-700">{{ $event->location }}</span>
                    @endif
                </dd>
            </div>
        </div>

        @if ($event->price_info)
            <div class="flex items-start gap-3 px-5 py-4">
                <i class="fa-solid fa-tag mt-0.5 flex-none text-base" style="color: var(--accent)" aria-hidden="true"></i>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Koszt</dt>
                    <dd class="mt-0.5 font-bold text-ink">{{ $event->price_info }}</dd>
                </div>
            </div>
        @endif

        @if ($event->contact_email)
            <div class="flex items-start gap-3 px-5 py-4">
                <i class="fa-solid fa-envelope mt-0.5 flex-none text-base" style="color: var(--accent)" aria-hidden="true"></i>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Kontakt</dt>
                    <dd class="mt-0.5">
                        <a href="mailto:{{ $event->contact_email }}" class="break-all font-bold" style="color: var(--accent)">
                            {{ $event->contact_email }}
                        </a>
                    </dd>
                </div>
            </div>
        @endif
    </dl>

    <div class="px-5 pb-5 pt-2">
        @if (! $event->isPast() && $event->registrationHref())
            <a href="{{ $event->registrationHref() }}"
               @if($event->registration_url) target="_blank" rel="noopener" @endif
               class="flex w-full items-center justify-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
               style="background: var(--accent); outline-color: var(--accent)">
                {{ $event->registration_cta_label }}
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        @elseif ($event->isPast())
            <p class="rounded-lg bg-gray-100 px-4 py-3 text-center text-sm text-gray-600">
                Zapisy zamknięte — wydarzenie minęło.
            </p>
        @else
            <p class="rounded-lg bg-gray-100 px-4 py-3 text-center text-sm text-gray-600">
                Szczegóły zapisów podamy wkrótce.
            </p>
        @endif
    </div>
</div>
