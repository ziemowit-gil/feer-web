{{-- Węzeł drzewa działań (rekurencyjny). Zmienne: $node (Project), $byParent, $selected, $openIds. --}}
@php
    $kids = $byParent->get($node->id, collect());
    $isSel = $selected && $selected->id === $node->id;
    $isOpen = in_array($node->id, $openIds, true);
@endphp
<li data-tree-node data-title="{{ \Illuminate\Support\Str::lower($node->title) }}" @if ($kids->isNotEmpty()) x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }" @endif>
    <div class="pt-row">
        @if ($kids->isNotEmpty())
            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="pt-branch-{{ $node->id }}"
                class="pt-toggle" aria-label="Rozwiń lub zwiń: {{ $node->title }}">
                <i class="fa-solid fa-chevron-right" :class="open ? 'pt-rot' : ''" aria-hidden="true"></i>
            </button>
        @else
            <span class="pt-toggle" aria-hidden="true"></span>
        @endif
        <a href="{{ route('admin.projekty.index', ['wybrana' => $node->id]) }}" @if ($isSel) aria-current="true" @endif class="pt-link {{ $isSel ? 'is-sel' : '' }}">
            <i class="fa-solid {{ $kids->isNotEmpty() ? 'fa-diagram-project' : 'fa-folder-closed' }} pt-ico" aria-hidden="true"></i>
            <span class="pt-title {{ $node->is_published ? '' : 'is-off' }}">{{ $node->title }}</span>
            @if ($node->isPaidOffer())<i class="fa-solid fa-briefcase pt-mini" title="Usługa wyłącznie odpłatna" aria-hidden="true"></i><span class="sr-only">(usługa odpłatna)</span>
            @elseif ($node->is_paid)<i class="fa-solid fa-coins pt-mini" title="Odpłatne" aria-hidden="true"></i><span class="sr-only">(odpłatne)</span>@endif
            @if (! $node->is_published)<i class="fa-solid fa-pen pt-mini" title="Szkic" aria-hidden="true"></i><span class="sr-only">(szkic)</span>@endif
            @if ($kids->isNotEmpty())<span class="pt-count" aria-label="{{ $kids->count() }} poddziałań">{{ $kids->count() }}</span>@endif
        </a>
    </div>
    @if ($kids->isNotEmpty())
        <ul id="pt-branch-{{ $node->id }}" role="list" class="pt-branch" @if ($kids->isNotEmpty()) x-show="open" @unless ($isOpen) style="display:none" @endunless @endif>
            @foreach ($kids as $child)
                @include('admin.projects.partials.tree-node', ['node' => $child, 'byParent' => $byParent, 'selected' => $selected, 'openIds' => $openIds])
            @endforeach
        </ul>
    @endif
</li>
