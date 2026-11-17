@php
    /**
     * Prawy panel: karta wybranej pozycji menu (jak karta strony w Stronach Nawigatorze), układ mega menu i podpozycje.
     * Zmienne: $selected, $navItems (pozycje najwyższego poziomu), $location, $health (MenuLinkCheck), $history.
     */
    $item = $selected;
    $parent = $item->parent_id ? $item->parent : null;
    $siblings = $parent ? $parent->allChildren : $navItems;
    $pos = $siblings->search(fn ($s) => $s->id === $item->id);
    $isFirst = $pos === 0;
    $isLast = $pos === $siblings->count() - 1;
    $children = $parent ? collect() : $item->allChildren;
    $canHold = ! $parent && $item->location === 'main' && ! $item->is_button && in_array($item->type, ['dropdown', 'link', 'projects'], true);
    $canIndent = ! $parent && $pos > 0 && $item->location === 'main' && $item->type === 'link' && ! $item->is_button && $children->isEmpty();
    $canOutdent = (bool) $parent;
    // Lekkie przyciski bez obrysu (tło dopiero po najechaniu) — jak w karcie strony.
    $ghost = 'inline-flex min-h-9 items-center gap-1.5 rounded px-2.5 py-1.5 text-xs font-bold text-ink hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark disabled:cursor-not-allowed disabled:text-gray-400 disabled:hover:bg-transparent';
    $iconBtn = 'inline-flex h-7 min-w-7 items-center justify-center rounded px-1.5 text-xs text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark disabled:cursor-not-allowed disabled:text-gray-300 disabled:hover:bg-transparent';
    $mega = $item->isMega() ? $item->megaSections() : [];
    $h = $health[$item->id] ?? ['state' => 'ok', 'kind' => 'page', 'issue' => false, 'label' => 'widoczna', 'page' => null];
    $page = $h['page'] ?? $item->editablePage();
    $canEditPage = $page && auth()->user()->canAccessModule('pages');
    $blockEditor = \App\Models\SiteSetting::current()->isModuleEnabled('block_editor');
    $publicUrl = match ($h['kind']) {
        'external', 'mail', 'phone' => (string) $item->url,
        'anchor' => url('/').$item->url,
        'none' => '',
        default => \Illuminate\Support\Str::startsWith((string) $item->url, ['http://', 'https://']) ? (string) $item->url : url((string) $item->url),
    };
    $typeIcons = ['dropdown' => 'fa-folder', 'projects' => 'fa-diagram-project', 'pages' => 'fa-sitemap', 'volunteering' => 'fa-people-carry-box', 'events' => 'fa-calendar', 'faq' => 'fa-circle-question'];
@endphp

{{-- Ścieżka --}}
<nav aria-label="Ścieżka pozycji" class="mb-2 text-xs text-muted">
    <span>{{ \Illuminate\Support\Str::before(\App\Models\NavItem::LOCATIONS[$location] ?? $location, ' (') }}</span>
    @if ($parent)
        <span aria-hidden="true" class="mx-1">›</span>
        <a href="{{ route('admin.pozycje-menu.index', ['location' => $location, 'pozycja' => $parent->id]) }}" class="hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $parent->label }}</a>
    @endif
    <span aria-hidden="true" class="mx-1">›</span><span class="font-semibold text-ink" aria-current="page">{{ $item->label }}</span>
</nav>

{{-- Karta pozycji --}}
<div class="rounded-md bg-white p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 id="nav-pane-heading" class="text-xl font-bold text-ink">{{ $item->label }}</h2>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                @if ($item->is_active)
                    <span class="inline-flex items-center gap-1 font-bold text-ink"><i class="fa-solid fa-circle-check text-green-700" aria-hidden="true"></i> Widoczna</span>
                @else
                    <span class="inline-flex items-center gap-1 font-bold text-ink"><i class="fa-solid fa-eye-slash text-muted" aria-hidden="true"></i> Ukryta</span>
                @endif
                <span class="font-bold text-muted">{{ \Illuminate\Support\Str::before(\App\Models\NavItem::TYPES[$item->type] ?? $item->type, ' (') }}</span>
                @if ($item->is_button)<span class="font-bold text-ink"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Przycisk (CTA)</span>@endif
                @if ($item->is_column_heading)<span class="font-bold text-ink"><i class="fa-solid fa-table-columns" aria-hidden="true"></i> Nagłówek kolumny</span>@endif
                @if ($item->is_mega && ! $parent)<span class="font-bold text-ink"><i class="fa-solid fa-border-all" aria-hidden="true"></i> Mega menu</span>@endif
                @if ($item->highlight)<span class="inline-flex items-center gap-1 font-bold text-ink"><i class="fa-solid fa-star text-amber-500" aria-hidden="true"></i> Wyróżniona</span>@endif
                @if ($canEditPage)
                    <span class="inline-flex items-center gap-1 text-ink"><i class="fa-solid fa-file-lines text-gray-500" aria-hidden="true"></i> strona „{{ $page->title }}”@unless ($page->is_published) (nieopublikowana)@endunless</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-nav-first-action data-nav-edit @click="openEdit($event)"
                @include('admin.nav-items._edit-attrs', ['item' => $item])
                class="inline-flex min-h-9 items-center gap-1.5 rounded bg-brand-dark px-4 py-1.5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj pozycję
            </button>
            @if ($publicUrl !== '')
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="{{ $ghost }}">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Zobacz na stronie<span class="sr-only"> (nowa karta)</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Stan celu linku (jak ostrzeżenia w TYPO3): pozycja widoczna, a strona niedostępna; pozycja ukryta przez moduł. --}}
    @if ($h['issue'] && $item->is_active)
        <div class="mt-3 flex flex-wrap items-center gap-2 rounded-md {{ $h['state'] === 'page_scheduled' ? 'bg-brand-light' : 'bg-amber-50' }} px-3 py-2 text-xs text-ink" role="status">
            @php $ov = \App\Support\MenuLinkCheck::overlay($h['state']); @endphp
            <span class="min-w-0 flex-1"><i class="fa-solid {{ $ov[0] ?? 'fa-circle-exclamation' }} mr-1 {{ $ov[1] ?? 'text-amber-800' }}" aria-hidden="true"></i>
                @switch($h['state'])
                    @case('page_missing') Pozycja jest widoczna w menu, ale adres <span class="font-mono">{{ $item->url }}</span> nie prowadzi do żadnej strony — odwiedzający zobaczą błąd 404. Popraw adres w edycji pozycji albo ukryj ją. @break
                    @case('page_draft') Pozycja jest widoczna w menu, ale strona „{{ $page->title }}” to szkic — odwiedzający zobaczą błąd 404. Opublikuj stronę albo ukryj pozycję. @break
                    @case('page_disabled') Pozycja jest widoczna w menu, ale strona „{{ $page->title }}” jest wyłączona. Włącz stronę albo ukryj pozycję. @break
                    @case('page_scheduled') Strona „{{ $page->title }}” opublikuje się {{ $page->publish_at->format('d.m.Y H:i') }} — do tego czasu link w menu prowadzi do błędu 404. @break
                @endswitch
            </span>
            @if ($canEditPage)
                <a href="{{ route('admin.podstrony.index', ['wybrana' => $page->id]) }}" class="{{ $ghost }}"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Pokaż stronę w Nawigatorze</a>
            @endif
        </div>
    @elseif ($h['state'] === 'module_off')
        <p class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-xs text-ink" role="status">
            <i class="fa-solid fa-ban mr-1 text-red-700" aria-hidden="true"></i>
            Pozycja nie pokazuje się na stronie, bo moduł „{{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }}” jest wyłączony (Ustawienia → Moduły). Wróci sama po jego włączeniu.
        </p>
    @endif

    <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
        <div><dt class="text-xs font-semibold text-muted">Adres</dt>
            <dd class="mt-0.5 break-all font-mono text-xs">
                @if ($h['kind'] === 'none') <span class="font-sans text-muted">— rozwijane menu bez własnego adresu</span>
                @else {{ $item->url ?: '—' }}@if ($h['kind'] === 'external') <span class="font-sans text-muted">(zewnętrzny)</span>@endif @endif
            </dd></div>
        <div><dt class="text-xs font-semibold text-muted">Pozycja</dt>
            <dd class="mt-0.5 flex flex-wrap items-center gap-1">
                <span><span class="font-bold">{{ $pos !== false ? $pos + 1 : '—' }}</span> z {{ $siblings->count() }}</span>
                <span class="text-xs text-muted">{{ $parent ? 'w „'.$parent->label.'”' : 'na pasku menu' }}</span>
                @if ($siblings->count() > 1)
                    <span class="inline-flex items-center" role="group" aria-label="Kolejność pozycji">
                        @foreach ([['up', 'fa-arrow-up', 'Wyżej', $isFirst], ['down', 'fa-arrow-down', 'Niżej', $isLast]] as [$act, $ico, $lbl, $dis])
                            <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">@csrf @method('PATCH')
                                <input type="hidden" name="action" value="{{ $act }}">
                                <button type="submit" class="{{ $iconBtn }}" @disabled($dis) title="{{ $lbl }}"><i class="fa-solid {{ $ico }}" aria-hidden="true"></i><span class="sr-only">{{ $lbl }}: „{{ $item->label }}”</span></button>
                            </form>
                        @endforeach
                    </span>
                @endif
            </dd></div>
        <div><dt class="text-xs font-semibold text-muted">Ostatnia zmiana</dt><dd class="mt-0.5">{{ $item->updated_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
        @if ($canEditPage)
            {{-- Etykieta a tytuł strony: w TYPO3 menu pokazuje tytuł strony albo osobny „tytuł w nawigacji”. --}}
            <div class="sm:col-span-3"><dt class="text-xs font-semibold text-muted">Strona</dt>
                <dd class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <a href="{{ route('admin.podstrony.index', ['wybrana' => $page->id]) }}" class="font-semibold text-brand-dark hover:underline">{{ $page->title }}</a>
                    @if ($page->title !== $item->label)
                        <span class="text-xs text-muted">— w menu jako „{{ $item->label }}” (krótsza etykieta, jak „tytuł w nawigacji” w TYPO3)</span>
                        <form method="POST" action="{{ route('admin.pozycje-menu.etykieta', $item) }}">@csrf @method('PATCH')
                            <button type="submit" class="{{ $ghost }}"><i class="fa-solid fa-heading" aria-hidden="true"></i> Użyj tytułu strony</button>
                        </form>
                    @endif
                </dd></div>
        @endif
        @if ($item->module)<div><dt class="text-xs font-semibold text-muted">Widoczna, gdy moduł włączony</dt><dd class="mt-0.5">{{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }}</dd></div>@endif
        @php $__c = $item->is_button ? $item->button_color : $item->accent_color; @endphp
        @if (\App\Support\Color::isValid($__c))
            <div><dt class="text-xs font-semibold text-muted">{{ $item->is_button ? 'Kolor przycisku' : 'Kolor pozycji' }}</dt>
                <dd class="mt-0.5 inline-flex items-center gap-2"><span class="inline-block h-4 w-4 rounded-sm border border-gray-300" style="background: {{ $__c }}" aria-hidden="true"></span><span class="font-mono text-xs">{{ $__c }}</span></dd></div>
        @endif
        @if ($item->icon)<div><dt class="text-xs font-semibold text-muted">Ikona</dt><dd class="mt-0.5">{!! icon_html($item->icon, '') !!} <span class="font-mono text-xs">{{ $item->icon }}</span></dd></div>@endif
        @if ($item->is_mega && ! $parent)<div><dt class="text-xs font-semibold text-muted">Wielkość w mega menu</dt><dd class="mt-0.5">{{ \Illuminate\Support\Str::before(\App\Models\NavItem::MEGA_SIZES[$item->megaSize()] ?? $item->megaSize(), ' (') }}</dd></div>@endif
        @if ($item->description)<div class="sm:col-span-3"><dt class="text-xs font-semibold text-muted">Opis w mega menu</dt><dd class="mt-0.5">{{ $item->description }}</dd></div>@endif
    </dl>

    {{-- Strona, do której prowadzi pozycja --}}
    @if ($canEditPage)
        <div class="mt-3 flex flex-wrap items-center gap-1" role="group" aria-label="Strona „{{ $page->title }}”">
            @if ($blockEditor)
                <a href="{{ route('admin.podstrony.bloki.edit', $page) }}" class="{{ $ghost }}"><i class="fa-solid fa-cubes" aria-hidden="true"></i> Edytuj w edytorze blokowym<span class="sr-only"> — strona „{{ $page->title }}”</span></a>
            @endif
            <a href="{{ route('admin.podstrony.edit', $page) }}" class="{{ $ghost }}"><i class="fa-solid fa-file-pen" aria-hidden="true"></i> Ustawienia strony<span class="sr-only"> „{{ $page->title }}”</span></a>
            <a href="{{ route('admin.podstrony.index', ['wybrana' => $page->id]) }}" class="{{ $ghost }}"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Pokaż w Stronach Nawigatorze</a>
        </div>
    @endif

    {{-- Historia zmian (jak „Historia” w TYPO3): kto i kiedy zmienił pozycję. --}}
    @if ($history->isNotEmpty())
        <details class="mt-2 text-sm" id="historia-pozycji">
            <summary class="inline-flex min-h-9 cursor-pointer items-center gap-1.5 text-xs font-bold text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i> Historia zmian
                <span class="font-normal text-muted">— ostatnio {{ $history->first()['at']?->diffForHumans() }}, {{ $history->first()['user'] }}</span></summary>
            <ol class="mt-1 divide-y divide-gray-100 rounded-md bg-gray-50" role="list">
                @foreach ($history as $rev)
                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-1.5 text-xs">
                        @if ($rev['at'])<time datetime="{{ $rev['at']->toIso8601String() }}" class="whitespace-nowrap font-bold text-ink">{{ $rev['at']->format('d.m.Y H:i') }}</time>@endif
                        <span class="text-ink">{{ $rev['user'] }}</span>
                        <span class="text-muted">{{ $rev['event'] }}</span>
                    </li>
                @endforeach
            </ol>
        </details>
    @endif

    {{-- Szybkie akcje --}}
    <div class="mt-4 flex flex-wrap items-center gap-1 border-t border-gray-100 pt-3" role="group" aria-label="Szybkie akcje pozycji">
        <form method="POST" action="{{ route('admin.pozycje-menu.aktywna', $item) }}">@csrf @method('PATCH')
            <button type="submit" class="{{ $ghost }}">
                <i class="fa-solid {{ $item->is_active ? 'fa-eye-slash' : 'fa-eye text-green-700' }}" aria-hidden="true"></i> {{ $item->is_active ? 'Ukryj w menu' : 'Pokaż w menu' }}
            </button>
        </form>
        <form method="POST" action="{{ route('admin.pozycje-menu.kopiuj', $item) }}" data-confirm="Zduplikować „{{ $item->label }}”{{ $children->isNotEmpty() ? ' razem z podpozycjami' : '' }}? Kopia stanie zaraz za nią i będzie ukryta.">
            @csrf <input type="hidden" name="z_podpozycjami" value="1">
            <button type="submit" class="{{ $ghost }}"><i class="fa-solid fa-clone" aria-hidden="true"></i> Duplikuj</button>
        </form>
        @if ($canIndent)
            <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">@csrf @method('PATCH')
                <input type="hidden" name="action" value="indent">
                <button type="submit" class="{{ $ghost }}"><i class="fa-solid fa-indent" aria-hidden="true"></i> Zagnieźdź w poprzedniej<span class="sr-only"> „{{ $item->label }}”</span></button>
            </form>
        @endif
        @if ($canOutdent)
            <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">@csrf @method('PATCH')
                <input type="hidden" name="action" value="outdent">
                <button type="submit" class="{{ $ghost }}"><i class="fa-solid fa-outdent" aria-hidden="true"></i> Wysuń na pasek<span class="sr-only"> „{{ $item->label }}”</span></button>
            </form>
        @endif
        @if ($publicUrl !== '')
            <button type="button" class="{{ $ghost }}" data-copy-url="{{ $publicUrl }}"><i class="fa-solid fa-link" aria-hidden="true"></i> Kopiuj adres</button>
        @endif
        <form method="POST" action="{{ route('admin.pozycje-menu.destroy', $item) }}" style="margin-left:auto"
            data-confirm="Usunąć „{{ $item->label }}” z menu?{{ $children->isNotEmpty() ? ' Usunięte zostaną też podpozycje.' : '' }} Strona, do której prowadzi, zostaje.">@csrf @method('DELETE')
            <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded px-2.5 py-1.5 text-xs font-bold text-red-800 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
            </button>
        </form>
    </div>
</div>

{{-- Podgląd kolumn mega menu --}}
@if ($mega)
    <section class="mt-4 rounded-md bg-white" aria-labelledby="nav-mega-h">
        <div class="border-b border-gray-100 px-3 py-1.5">
            <h3 id="nav-mega-h" class="text-sm font-bold text-ink">Układ w mega menu <span class="font-normal text-muted">({{ count($mega) }} {{ trans_choice('kolumna|kolumny|kolumn', count($mega)) }})</span></h3>
        </div>
        <div class="p-3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.75rem">
            @foreach ($mega as $section)
                <div class="rounded bg-gray-50 px-3 py-2">
                    <p class="text-xs font-bold text-ink">{{ $section['heading']['label'] ?? 'Bez nagłówka' }}</p>
                    <ul class="mt-1 space-y-0.5 text-xs text-muted" role="list">
                        @foreach (array_slice($section['links'], 0, 6) as $link)<li class="truncate">{{ $link[1] }}</li>@endforeach
                        @if (count($section['links']) > 6)<li>… i {{ count($section['links']) - 6 }} więcej</li>@endif
                        @if ($section['links'] === [])<li>—</li>@endif
                    </ul>
                </div>
            @endforeach
        </div>
        <p class="px-3 pb-2 text-xs text-muted">Nagłówek kolumny ustawisz w edycji podpozycji (pole „Nagłówek kolumny”).</p>
    </section>
@endif

{{-- Podpozycje: jeden biały blok — nagłówek z liczbą i „Dodaj podpozycję”, pod nim zwarta tabela. --}}
@unless ($parent)
    @if ($canHold || $children->isNotEmpty())
        <section class="mt-4 rounded-md bg-white" aria-labelledby="nav-children-h">
            <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-3 py-1.5">
                <h3 id="nav-children-h" class="text-sm font-bold text-ink">Podpozycje <span class="font-normal text-muted">({{ $children->count() }})</span></h3>
                @if ($canHold)
                    @include('admin.nav-items._add-menu', ['menuId' => 'nav-add-sub-menu', 'location' => $item->location, 'parentId' => $item->id, 'primary' => false, 'btnClass' => $ghost, 'btnStyle' => ''])
                @endif
            </div>
            @if ($children->isEmpty())
                <p class="px-3 py-6 text-center text-sm text-muted">Ta pozycja nie ma jeszcze podpozycji.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Podpozycje „{{ $item->label }}”</caption>
                        <thead class="border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-3 py-1.5 text-xs font-semibold text-muted">Pozycja</th>
                                <th scope="col" class="px-3 py-1.5 text-xs font-semibold text-muted">Stan</th>
                                <th scope="col" class="px-3 py-1.5 text-xs font-semibold text-muted">Kolejność</th>
                                <th scope="col" class="px-3 py-1.5 text-right text-xs font-semibold text-muted"><span class="sr-only">Akcje</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($children as $child)
                                @php
                                    $ch = $health[$child->id] ?? ['state' => 'ok', 'kind' => 'page', 'issue' => false, 'label' => 'widoczna', 'page' => null];
                                    $childOv = \App\Support\MenuLinkCheck::overlay($ch['state']);
                                    $childPage = auth()->user()->canAccessModule('pages') ? ($ch['page'] ?? null) : null;
                                    $childIcon = $child->is_column_heading ? 'fa-table-columns' : match ($ch['kind']) { 'external' => 'fa-globe', 'anchor' => 'fa-hashtag', 'mail' => 'fa-envelope', 'phone' => 'fa-phone', 'home' => 'fa-house', 'none' => 'fa-link', default => $typeIcons[$child->type] ?? 'fa-file-lines' };
                                    $childOff = in_array($ch['state'], ['hidden', 'module_off'], true);
                                @endphp
                                <tr class="group hover:bg-gray-50">
                                    <th scope="row" class="px-3 py-1 font-normal">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="tree-icon relative inline-flex w-4 flex-none justify-center" aria-hidden="true">
                                                <i class="fa-solid {{ $childIcon }} text-xs text-gray-500"></i>
                                                @if ($childOv)<i class="tree-ov fa-solid {{ $childOv[0] }} {{ $childOv[1] }}"></i>@endif
                                            </span>
                                            <a href="{{ route('admin.pozycje-menu.index', ['location' => $location, 'pozycja' => $child->id]) }}"
                                                class="min-w-0 truncate font-semibold {{ $childOff ? 'text-muted' : 'text-ink' }} hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $child->label }}</a>
                                            @if ($child->is_column_heading)<span class="flex-none text-[11px] text-muted">nagłówek kolumny</span>@endif
                                            <span class="hidden min-w-0 truncate font-mono text-xs text-muted sm:inline">{{ $ch['kind'] === 'none' ? '' : $child->url }}</span>
                                        </div>
                                    </th>
                                    <td class="whitespace-nowrap px-3 py-1 text-xs {{ $ch['issue'] && $child->is_active ? 'font-bold text-amber-800' : ($ch['state'] === 'ok' ? 'text-ink' : 'text-muted') }}">
                                        @if ($child->is_active && ! $ch['issue'] && $ch['state'] !== 'module_off') Widoczna
                                        @elseif (! $child->is_active) <i class="fa-solid fa-eye-slash mr-0.5" aria-hidden="true"></i> ukryta
                                        @else {{ $ch['label'] }} @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-1">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs" style="min-width:2.75rem"><span class="font-bold">{{ $loop->iteration }}</span> <span class="text-muted">z {{ $loop->count }}</span></span>
                                            @if ($loop->count > 1)
                                                @foreach ([['up', 'fa-arrow-up', 'Wyżej', $loop->first], ['down', 'fa-arrow-down', 'Niżej', $loop->last]] as [$act, $ico, $lbl, $dis])
                                                    <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $child) }}">@csrf @method('PATCH')
                                                        <input type="hidden" name="action" value="{{ $act }}">
                                                        <button type="submit" class="{{ $iconBtn }}" @disabled($dis) title="{{ $lbl }}"><i class="fa-solid {{ $ico }}" aria-hidden="true"></i><span class="sr-only">{{ $lbl }}: „{{ $child->label }}”</span></button>
                                                    </form>
                                                @endforeach
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-2 py-1">
                                        <div class="flex items-center justify-end gap-0.5">
                                            <button type="button" @click="openEdit($event)" @include('admin.nav-items._edit-attrs', ['item' => $child])
                                                class="inline-flex min-h-7 items-center gap-1.5 rounded px-2 py-1 text-xs font-bold text-ink hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj<span class="sr-only"> „{{ $child->label }}”</span></button>
                                            @if ($childPage && $blockEditor)
                                                <a href="{{ route('admin.podstrony.bloki.edit', $childPage) }}" class="inline-flex min-h-7 items-center rounded px-2 py-1 text-xs text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" title="Edytuj stronę w edytorze blokowym">
                                                    <i class="fa-solid fa-cubes" aria-hidden="true"></i><span class="sr-only">Edytor blokowy — strona „{{ $childPage->title }}”</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endunless
