@php $mobile ??= false; @endphp

<li class="relative" x-data="{ open: false }" x-id="['dropdown']"
    @mouseenter="if (!{{ $mobile ? 'true' : 'false' }}) open = true" @mouseleave="if (!{{ $mobile ? 'true' : 'false' }}) open = false"
    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
    @keydown.escape="open = false; $refs.dropdownTrigger.focus()"
    @click.outside="open = false">
    <button type="button" x-ref="dropdownTrigger" @click="open = !open"
        :aria-expanded="open.toString()" :aria-controls="$id('dropdown')"
        @php $ob = $onBrand ?? false; @endphp
        class="flex w-full items-center gap-1 border-b-2 py-2 uppercase transition-colors hover:border-brand hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ $item->isCurrent() ? ($ob ? 'border-white' : 'border-brand text-brand') : 'border-transparent' }} {{ $mobile ? 'justify-between' : 'pb-1' }}" :class="open ? '{{ $ob ? 'border-white' : 'border-brand text-brand' }}' : ''">
        {{ $item->label }} <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i>
    </button>

    <div :id="$id('dropdown')" x-show="open" x-cloak x-transition
        @class([
            'z-50 rounded-lg border border-gray-200 py-2 normal-case tracking-normal shadow-lg',
            'bg-white/90 backdrop-blur-sm' => $item->is_transparent_dropdown,
            'bg-white' => ! $item->is_transparent_dropdown,
            'absolute left-0 top-full mt-1 w-60' => ! $mobile,
            'static mt-1 w-full' => $mobile,
        ])>
        @forelse ($item->children as $child)
            @include('partials.nav-item', ['item' => $child, 'mobile' => $mobile])
        @empty
            <p class="px-4 py-2 text-sm normal-case text-muted">Brak podpozycji.</p>
        @endforelse
    </div>
</li>
