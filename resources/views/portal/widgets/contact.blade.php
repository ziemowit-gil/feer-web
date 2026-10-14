{{-- Widżet portalu: kontakt do zespołu portalu (z panelu) i koordynatorzy działań kategorii. --}}
@php
    $areaText = $category->portalOption('contact_text');
    $areaEmail = $category->portalOption('contact_email');
    $areaPhone = $category->portalOption('contact_phone');
    $hasArea = $areaText || $areaEmail || $areaPhone;
    $contactLink = 'font-bold text-brand-dark underline underline-offset-2 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($hasArea || $coordinators->isNotEmpty())
    <section aria-labelledby="portal-w-contact">
        <h2 id="portal-w-contact" class="text-xl font-bold leading-tight text-ink md:text-2xl">{{ $category->portalWidgetTitle('contact') }}</h2>
        <span class="mt-2 block h-1 w-12 bg-brand" aria-hidden="true"></span>
        <div class="mt-6 grid gap-8 {{ $hasArea && $coordinators->isNotEmpty() && ! ($compact ?? false) ? 'lg:grid-cols-[20rem_minmax(0,1fr)]' : '' }}">
            @if ($hasArea)
                <div class="rounded-md bg-gray-50 p-6 text-ink">
                    @if ($areaText)<p class="text-lg leading-relaxed">{{ $areaText }}</p>@endif
                    <ul role="list" class="{{ $areaText ? 'mt-4' : '' }} space-y-2">
                        @if ($areaEmail)
                            <li class="flex items-center gap-3 break-all"><i class="fa-regular fa-envelope flex-none" aria-hidden="true"></i><span class="sr-only">E-mail: </span><a href="mailto:{{ $areaEmail }}" class="{{ $contactLink }}">{{ $areaEmail }}</a></li>
                        @endif
                        @if ($areaPhone)
                            <li class="flex items-center gap-3"><i class="fa-solid fa-phone flex-none" aria-hidden="true"></i><span class="sr-only">Telefon: </span><a href="tel:{{ preg_replace('/[^0-9+]/', '', $areaPhone) }}" class="{{ $contactLink }}">{{ $areaPhone }}</a></li>
                        @endif
                    </ul>
                </div>
            @endif
            @if ($coordinators->isNotEmpty())
                <ul role="list" class="grid gap-4 sm:grid-cols-2 {{ $hasArea || ($compact ?? false) ? '' : 'lg:grid-cols-4' }}">
                    @foreach ($coordinators as $c)
                        <li class="flex gap-4 rounded-md bg-gray-50 p-4">
                            @if ($c->photo)<img src="{{ $c->photo }}" alt="" class="h-14 w-14 flex-none rounded-full object-cover" loading="lazy">@endif
                            <div class="min-w-0 text-sm text-ink">
                                @if ($c->name)<p class="font-bold">{{ $c->name }}</p>@endif
                                <p>{{ $c->project }}</p>
                                @if ($c->email)<p class="mt-1 break-all"><a href="mailto:{{ $c->email }}" class="{{ $contactLink }}">{{ $c->email }}</a></p>@endif
                                @if ($c->phone)<p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->phone) }}" class="{{ $contactLink }}">{{ $c->phone }}</a></p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
@endif
