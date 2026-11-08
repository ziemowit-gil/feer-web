{{--
    Pozycje jednej grupy menu bocznego (bloki z nagłówkami, pozycje, podpozycje) — wspólne dla układu z nagłówkami grup
    i układu z kaflami grup (styl Metro). Zmienna: $group (element z App\Support\AdminMenu::for()).
--}}
@foreach ($group['blocks'] as $block)
    @if ($block['heading'])<p class="tm-sub" id="tm-sub-{{ $group['key'] }}-{{ $loop->index }}">{{ $block['heading'] }}</p>@endif
    <ul class="space-y-0.5" role="list" @if ($block['heading']) aria-labelledby="tm-sub-{{ $group['key'] }}-{{ $loop->index }}" @endif>
        @foreach ($block['items'] as $item)
            @php $hasChildren = $item['children'] !== []; $childrenId = 'nav-children-' . $item['key']; @endphp
            <li x-data="{ open: {{ $hasChildren && $item['active'] ? 'true' : 'false' }} }">
                <div class="relative flex items-center gap-2 rounded-lg px-2 {{ ($item['premium'] ?? false) ? 'tm-premium-item' : '' }} {{ $item['active'] ? 'bg-brand-light text-brand-dark' : 'text-ink hover:bg-gray-100 hover:text-ink' }}" style="min-height: 2rem" @if ($item['premium'] ?? false) title="Moduł premium" @endif>
                    <a href="{{ $item['url'] }}" class="flex min-w-0 flex-1 items-center gap-2.5 py-1 font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-1 rounded"
                       @if ($item['active'] && ! $hasChildren) aria-current="page" @endif>
                        <i class="fa-solid {{ $item['icon'] }} w-4 flex-none text-center text-[0.8rem] {{ $item['active'] ? 'text-brand-dark' : 'text-gray-400' }}" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                    </a>
                    @if ($item['badge'])
                        <span class="inline-flex h-5 min-w-5 flex-none items-center justify-center rounded-full px-1.5 text-[11px] font-bold leading-none {{ $item['badge_tone'] === 'muted' ? 'bg-gray-200 text-gray-700' : 'bg-brand-dark text-white' }}"
                              aria-label="{{ $item['badge'] }} do obsłużenia">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                    @if ($hasChildren)
                        <button type="button" @click.stop="open = ! open" :aria-expanded="open.toString()" aria-controls="{{ $childrenId }}"
                                class="-mr-1 flex h-7 w-7 flex-none items-center justify-center rounded text-gray-400 hover:bg-white/60 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <span class="sr-only">Pokaż podpozycje: {{ $item['label'] }}</span>
                            <i class="fa-solid fa-chevron-down text-[0.55rem] transition-transform duration-200" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
                @if ($hasChildren)
                    <ul id="{{ $childrenId }}" class="ml-4 mt-0.5 space-y-0.5 border-l border-gray-200 pl-2.5" role="list" x-show="open" @unless ($item['active']) style="display:none" @endunless>
                        @php $prevGroup = null; @endphp
                        @foreach ($item['children'] as $child)
                            @if (($child['group'] ?? null) && $child['group'] !== $prevGroup)
                                <li class="tm-sub" role="presentation" style="margin: .5rem .5rem .125rem">{{ $child['group'] }}</li>
                                @php $prevGroup = $child['group']; @endphp
                            @endif
                            <li>
                                <a href="{{ $child['url'] }}"
                                   class="flex items-center gap-2 rounded-md px-2 py-1 text-[13px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $child['active'] ? 'bg-brand-light font-semibold text-brand-dark' : 'text-muted hover:bg-gray-100 hover:text-ink' }}"
                                   @if ($child['active']) aria-current="page" @endif>
                                    @if ($child['icon'])<i class="fa-solid {{ $child['icon'] }} text-[0.7rem]" aria-hidden="true"></i>@endif
                                    <span class="truncate">{{ $child['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
@endforeach
