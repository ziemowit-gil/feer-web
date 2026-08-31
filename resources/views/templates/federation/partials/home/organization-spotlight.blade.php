{{-- "Poznaj naszą organizację" — losowo wybrana organizacja członkowska, funkcja włączana/wyłączana z panelu. --}}
@if ($siteSettings->federation_show_org_spotlight ?? true)
    @php
        $spotlightOrg = \App\Models\Organization::where('is_test', false)->inRandomOrder()->first();
    @endphp
    @if ($spotlightOrg)
        <section class="py-10" aria-labelledby="org-spotlight-heading">
            <div class="mx-auto max-w-[1400px] px-4">
                <div class="flex flex-col gap-6 rounded-lg border border-gray-100 bg-white p-8 shadow-sm sm:flex-row sm:items-center">
                    <span class="flex h-16 w-16 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                        <i class="fa-solid fa-people-roof text-2xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-extrabold uppercase tracking-widest text-brand">Poznaj naszą organizację</p>
                        <h2 id="org-spotlight-heading" class="mt-1 text-xl font-extrabold leading-tight text-ink sm:text-2xl">{{ $spotlightOrg->name }}</h2>
                        <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted">
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-ink/70">{{ $spotlightOrg->type }}</span>
                            <span><i class="fa-solid fa-location-dot mr-1" aria-hidden="true"></i>{{ $spotlightOrg->town }}</span>
                        </p>
                        @if ($spotlightOrg->description)
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ $spotlightOrg->description }}</p>
                        @endif
                    </div>
                    <a href="{{ route('federation.organizations.show', $spotlightOrg) }}"
                        class="flex-none self-start rounded-md border-2 px-5 py-2.5 text-sm font-extrabold transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 sm:self-center"
                        style="border-color:{{ $siteSettings->brandColorN(1) }}; color:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                        Poznaj tę organizację
                    </a>
                </div>
            </div>
        </section>
    @endif
@endif
