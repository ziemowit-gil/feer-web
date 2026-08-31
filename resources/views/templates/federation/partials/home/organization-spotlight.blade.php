{{-- "Poznaj naszą organizację" — losowo wybrana organizacja członkowska, funkcja włączana/wyłączana z panelu. --}}
@if ($siteSettings->federation_show_org_spotlight ?? false)
    @php
        $spotlightOrg = \App\Models\Organization::where('is_test', false)->inRandomOrder()->first();
        $spotlightPhoto = $spotlightOrg?->getFirstMedia('photos');
    @endphp
    @if ($spotlightOrg)
        <section class="py-10" aria-labelledby="org-spotlight-heading">
            <div class="mx-auto max-w-[1400px] px-4">
                <div class="grid overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm sm:grid-cols-[minmax(0,15rem)_1fr]">
                    {{-- Panel w kolorze marki: zdjęcie organizacji (jeśli dodała) albo duża ikona --}}
                    <div class="relative flex min-h-[10rem] items-center justify-center p-8" style="background:{{ $siteSettings->brandColorN(1) }}">
                        @if ($spotlightPhoto)
                            <img src="{{ $spotlightPhoto->getAvailableUrl(['thumb']) }}" alt=""
                                class="absolute inset-0 h-full w-full object-cover opacity-90">
                        @else
                            <i class="fa-solid fa-people-roof text-5xl text-white/90" aria-hidden="true"></i>
                        @endif
                        <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-extrabold uppercase tracking-widest text-ink">
                            <i class="fa-solid fa-shuffle mr-1" aria-hidden="true"></i>Losowo wybrana
                        </span>
                    </div>

                    <div class="flex flex-col justify-center gap-3 p-8">
                        <p class="text-sm font-extrabold uppercase tracking-widest text-brand">Poznaj naszą organizację</p>
                        <h2 id="org-spotlight-heading" class="text-xl font-extrabold leading-tight text-ink sm:text-2xl">{{ $spotlightOrg->name }}</h2>
                        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted">
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-ink/70">{{ $spotlightOrg->type }}</span>
                            <span><i class="fa-solid fa-location-dot mr-1" aria-hidden="true"></i>{{ $spotlightOrg->town }}</span>
                        </p>
                        @if ($spotlightOrg->description)
                            <p class="max-w-2xl text-sm leading-relaxed text-muted">{{ $spotlightOrg->description }}</p>
                        @endif
                        <a href="{{ route('federation.organizations.show', $spotlightOrg) }}"
                            class="mt-2 inline-flex w-fit items-center gap-1.5 rounded-md border-2 px-5 py-2.5 text-sm font-extrabold transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            style="border-color:{{ $siteSettings->brandColorN(1) }}; color:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                            Poznaj tę organizację
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endif
