@php
    $mobile ??= false;
    $transparent = $item->is_transparent_dropdown ?? false;
    $linkedPage = $item->linkedPage();
    $pageChildren = $linkedPage ? $linkedPage->publishedChildren : collect();
    $showFaq = $linkedPage && $linkedPage->type === 'about' && $siteSettings->isModuleEnabled('faq');
    // „Bieżąca", gdy sam link albo któraś z podpozycji jest aktualną stroną.
    $isCurrent = $item->isCurrent() || $item->children->contains(fn ($c) => $c->isCurrent());
@endphp

<li class="relative" x-data="{ open: false }"
    @mouseenter="if (!{{ $mobile ? 'true' : 'false' }}) open = true" @mouseleave="if (!{{ $mobile ? 'true' : 'false' }}) open = false"
    @focusin="open = true"
    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
    @keydown.escape="open = false; $refs.linkTrigger.focus()"
    @click.outside="open = false">
    <div class="flex items-center gap-1 border-b-2 transition-colors {{ $isCurrent ? 'border-brand text-brand' : 'border-transparent' }} {{ $mobile ? 'w-full justify-between' : 'pb-1' }}"
        :class="open ? 'border-brand text-brand' : ''">
        {{-- Nagłówek działa jak zwykły link — klik prowadzi pod adres pozycji. --}}
        <a href="{{ $item->url }}" x-ref="linkTrigger"
            class="uppercase transition-colors hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ $mobile ? 'py-2' : 'pt-2' }}">
            {{ $item->label }}
        </a>
        {{-- Strzałka rozwija/zamyka podmenu (działa też na mobile). --}}
        <button type="button" @click="open = !open" x-ref="linkToggle"
            aria-haspopup="true" :aria-expanded="open.toString()" aria-label="Rozwiń podmenu: {{ $item->label }}"
            class="flex items-center px-1 text-brand hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current {{ $mobile ? 'py-2' : 'pt-2' }}">
            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition
        @class([
            'z-50 rounded-lg border border-gray-200 py-2 normal-case tracking-normal shadow-lg',
            'bg-white/90 backdrop-blur-sm' => $transparent,
            'bg-white' => ! $transparent,
            'absolute left-0 top-full mt-1 w-60' => ! $mobile,
            'static mt-1 w-full' => $mobile,
        ])>
        {{-- Ręcznie dodane podpozycje menu. --}}
        @foreach ($item->children as $child)
            <a href="{{ $child->url }}" class="block px-4 py-2 text-sm font-medium normal-case {{ $child->isCurrent() ? 'text-brand' : 'text-ink' }} hover:bg-gray-50 hover:text-brand focus:bg-gray-50">
                {{ $child->label }}
            </a>
        @endforeach

        {{-- Automatyczne podstrony powiązanej strony (jeśli link prowadzi do strony z podstronami). --}}
        @foreach ($pageChildren as $child)
            <a href="{{ route('page.show', $child) }}" class="block px-4 py-2 text-sm font-medium normal-case {{ request()->routeIs('page.show') && request()->route('page')?->id === $child->id ? 'text-brand' : 'text-ink' }} hover:bg-gray-50 hover:text-brand focus:bg-gray-50">
                {{ $child->title }}
            </a>
        @endforeach

        @if ($showFaq)
            <a href="{{ route('faq.index') }}" class="block px-4 py-2 text-sm font-medium normal-case {{ request()->routeIs('faq.index') ? 'text-brand' : 'text-ink' }} hover:bg-gray-50 hover:text-brand focus:bg-gray-50">
                Najczęstsze pytania (FAQ)
            </a>
        @endif
    </div>
</li>
