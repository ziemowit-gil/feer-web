@php $mobile ??= false; @endphp

@if ($item->parent_id)
    {{-- Nested row inside a dropdown panel — the <li> wrapper lives in the parent partial. --}}
    <a href="{{ $item->url }}" @if ($item->isCurrent()) aria-current="page" @endif
        class="block px-4 py-2 text-sm font-medium normal-case {{ $item->isCurrent() ? 'text-brand' : 'text-ink' }} hover:bg-gray-50 hover:text-brand focus-visible:bg-gray-50">
        {{ $item->label }}
    </a>
@elseif (! $item->is_button && (
        $item->children->isNotEmpty()
        || (($linkedPage = $item->linkedPage()) && ($linkedPage->publishedChildren->isNotEmpty() || ($linkedPage->type === 'about' && $siteSettings->isModuleEnabled('faq'))))
    ))
    {{-- Link z podmenu: ręcznie dodane podpozycje + automatyczne podstrony
         powiązanej strony + (dla „O organizacji") pozycja FAQ. --}}
    @include('partials.nav-link-dropdown', ['item' => $item, 'mobile' => $mobile])
@else
    <li>
        @if ($item->is_button && !($onBrand ?? false))
            @if (\App\Support\Color::isValid($item->button_color))
                @php $cta = \App\Support\Color::button($item->button_color); @endphp
                <a href="{{ $item->url }}"
                    class="block rounded px-5 py-2.5 text-center text-base font-bold uppercase tracking-wide transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                    style="background-color: {{ $cta['bg'] }}; color: {{ $cta['text'] }};"
                    onmouseover="this.style.backgroundColor='{{ $cta['hover'] }}'" onmouseout="this.style.backgroundColor='{{ $cta['bg'] }}'">
                    {{ $item->label }}
                </a>
            @else
                <a href="{{ $item->url }}"
                    class="block rounded px-5 py-2.5 text-center text-base font-bold uppercase tracking-wide text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ $item->isCurrent() ? 'bg-brand-dark' : 'bg-brand hover:bg-brand-dark' }}">
                    {{ $item->label }}
                </a>
            @endif
        @else
            @php
                $ob = $onBrand ?? false;
                $hoverW  = $ob && ($siteSettings->wide_mission_nav_hover_white  ?? true);
                $activeW = $ob && ($siteSettings->wide_mission_nav_active_white ?? true);
                $hoverCls  = $hoverW  ? 'hover:border-white hover:text-white' : 'hover:border-brand hover:text-brand';
                $activeCls = $item->isCurrent() ? ($activeW ? 'border-white' : 'border-brand text-brand') : 'border-transparent';
            @endphp
            <a href="{{ $item->url }}"
                @if ($item->isCurrent()) aria-current="page" @endif
                class="block border-b-2 py-2 transition-colors {{ $hoverCls }} focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ $activeCls }} {{ $mobile ? '' : 'pb-1' }}">
                {{ $item->label }}
            </a>
        @endif
    </li>
@endif
