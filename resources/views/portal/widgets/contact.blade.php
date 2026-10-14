{{-- Widżet portalu: koordynatorzy działań kategorii (kontakt). --}}
@if ($coordinators->isNotEmpty())
    <section aria-labelledby="portal-w-contact">
        <h2 id="portal-w-contact" class="text-xl font-bold leading-tight text-ink md:text-2xl">Kontakt</h2>
        <span class="mt-2 block h-1 w-12 bg-brand" aria-hidden="true"></span>
        <ul role="list" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($coordinators as $c)
                <li class="flex gap-4 rounded-md bg-gray-50 p-4">
                    @if ($c->photo)<img src="{{ $c->photo }}" alt="" class="h-14 w-14 flex-none rounded-full object-cover" loading="lazy">@endif
                    <div class="min-w-0 text-sm text-ink">
                        @if ($c->name)<p class="font-bold">{{ $c->name }}</p>@endif
                        <p class="text-muted">{{ $c->project }}</p>
                        @if ($c->email)<p class="mt-1 break-all"><a href="mailto:{{ $c->email }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $c->email }}</a></p>@endif
                        @if ($c->phone)<p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->phone) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $c->phone }}</a></p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
