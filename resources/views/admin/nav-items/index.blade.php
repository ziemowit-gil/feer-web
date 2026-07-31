@extends('admin.layout')

@section('title', 'Menu nawigacyjne')

@section('content')
    @include('admin.partials.content-nav-tabs')

    @php
        // Po błędach walidacji z modala odtwarzamy jego stan ze starych danych,
        // żeby użytkownik nie tracił wpisanych wartości i pozostał w kontekście.
        $reopen = null;
        if ($errors->any()) {
            $eid = old('editing_id');
            $reopen = [
                'editingId' => (string) ($eid ?? ''),
                'action' => $eid ? route('admin.pozycje-menu.update', $eid) : route('admin.pozycje-menu.store'),
                'label' => old('label', ''),
                'url' => old('url', ''),
                'type' => old('type', 'link'),
                'location' => old('location', $location),
                'parentId' => (string) old('parent_id', ''),
                'module' => old('module', ''),
                'isButton' => (bool) old('is_button'),
                'buttonColor' => old('button_color') ?: '#2563eb',
                'buttonColorEnabled' => (bool) old('button_color'),
                'isTransparent' => (bool) old('is_transparent_dropdown'),
                'isActive' => (bool) old('is_active'),
            ];
        }
    @endphp

    <div x-data="menuBuilder({{ Js::from($reopen) }}, {{ Js::from(route('admin.pozycje-menu.store')) }})"
        @keydown.escape.window="close()">
        <p class="mb-4 text-sm text-muted">
            Pozycje menu wyświetlanego w nagłówku strony. Pozycja typu „Rozwijane menu” może mieć własne podpozycje (submenu),
            a „Menu projektów” pobiera zawartość automatycznie z kategorii projektów (<a href="{{ route('admin.kategorie.index') }}" class="rounded text-brand hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">zarządzaj kategoriami</a>).
            Kolejność i zagnieżdżenie zmieniasz przyciskami strzałek — bez przeciągania myszą.
        </p>

        <div class="mb-4 flex justify-end">
            <button type="button" @click="openCreate($event)" data-location="{{ $location }}"
                class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pozycję menu
            </button>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-2">
            @forelse ($navItems as $index => $item)
                @if ($loop->first)
                    <ul role="tree" aria-label="Struktura menu — {{ \App\Models\NavItem::LOCATIONS[$location] ?? $location }}" class="divide-y divide-gray-100">
                @endif
                    @include('admin.nav-items._row', ['item' => $item, 'level' => 1, 'position' => $index + 1, 'setsize' => $navItems->count()])
                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <p class="px-4 py-6 text-center text-muted">Brak pozycji menu. Dodaj pierwszą powyżej.</p>
            @endforelse
        </div>

        {{-- ============================ MODAL EDYCJI ============================ --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6" style="display: none">
            {{-- Tło przyciemniające; kliknięcie zamyka modal --}}
            <div class="fixed inset-0 bg-ink/60" @click="close()" aria-hidden="true"></div>

            <div x-ref="panel" role="dialog" aria-modal="true" :aria-label="form.editingId ? 'Edytuj pozycję menu' : 'Nowa pozycja menu'"
                @keydown.tab="trapTab($event)"
                class="relative z-10 my-4 w-full max-w-xl rounded-lg border border-gray-200 bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-bold" x-text="form.editingId ? 'Edytuj pozycję menu' : 'Nowa pozycja menu'"></h2>
                    <button type="button" @click="close()"
                        class="rounded p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span class="sr-only">Zamknij okno bez zapisywania</span>
                    </button>
                </div>

                <form method="POST" :action="form.action" class="space-y-5 px-6 py-5">
                    @csrf
                    <input type="hidden" name="_method" :value="form.editingId ? 'PUT' : 'POST'">
                    <input type="hidden" name="editing_id" :value="form.editingId">

                    @include('admin.nav-items._fields')

                    <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                        <button type="submit"
                            class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            Zapisz
                        </button>
                        <button type="button" @click="close()"
                            class="rounded px-2 py-2 text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            Anuluj
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Po operacji przenoszenia przywracamy fokus na przeniesioną pozycję, aby
         użytkownik klawiatury nie tracił swojego miejsca w drzewie. --}}
    @if (session('focus_nav'))
        <script>
            (function () {
                var row = document.getElementById('nav-item-{{ (int) session('focus_nav') }}');
                if (!row) return;
                var target = row.querySelector('[data-nav-first-action]');
                if (target) {
                    target.focus();
                    row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            })();
        </script>
    @endif

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('menuBuilder', (reopen = null, storeUrl = '') => ({
                open: false,
                trigger: null,
                form: {
                    editingId: '', action: storeUrl, label: '', icon: '', url: '', type: 'link',
                    location: 'main', parentId: '', module: '', isButton: false,
                    buttonColor: '#2563eb', buttonColorEnabled: false,
                    isTransparent: false, isActive: true,
                },

                init() {
                    // Ponowne otwarcie modala po błędach walidacji (bez utraty danych).
                    if (reopen) {
                        this.form = { ...this.form, ...reopen };
                        this.$nextTick(() => this.reveal());
                    }
                },

                blankForm(overrides = {}) {
                    return {
                        editingId: '', action: storeUrl, label: '', url: '', type: 'link',
                        location: 'main', parentId: '', module: '', isButton: false,
                        buttonColor: '#2563eb', buttonColorEnabled: false,
                        isTransparent: false, isActive: true, ...overrides,
                    };
                },

                openCreate(event) {
                    const el = event.currentTarget;
                    this.trigger = el;
                    const overrides = { location: el.dataset.location || 'main' };
                    // „Dodaj podpozycję": z góry ustawiamy rodzica i typ „link".
                    if (el.dataset.parent) {
                        overrides.parentId = el.dataset.parent;
                        overrides.type = 'link';
                    }
                    this.form = this.blankForm(overrides);
                    this.reveal();
                },

                openEdit(event) {
                    const el = event.currentTarget;
                    const d = el.dataset;
                    this.trigger = el;
                    this.form = {
                        editingId: d.id || '',
                        action: d.action,
                        label: d.label || '',
                        icon: d.icon || '',
                        url: d.url || '',
                        type: d.type || 'link',
                        location: d.location || 'main',
                        parentId: d.parent || '',
                        module: d.module || '',
                        isButton: d.button === '1',
                        buttonColor: d.color || '#2563eb',
                        buttonColorEnabled: !!d.color,
                        isTransparent: d.transparent === '1',
                        isActive: d.active === '1',
                    };
                    this.reveal();
                },

                reveal() {
                    this.open = true;
                    // Fokus natychmiast na pierwsze widoczne pole formularza
                    // (a nie na przycisk zamknięcia w nagłówku).
                    this.$nextTick(() => {
                        const form = this.$refs.panel.querySelector('form');
                        const first = this.focusables(form)[0];
                        if (first) first.focus();
                    });
                },

                close() {
                    if (!this.open) return;
                    this.open = false;
                    // Fokus wraca dokładnie do elementu, który otworzył modal.
                    this.$nextTick(() => this.trigger && this.trigger.focus());
                },

                // Widoczne, aktywne elementy sterujące (domyślnie w całym panelu
                // modala; opcjonalnie w węższym zakresie, np. samym formularzu).
                focusables(root = null) {
                    const selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
                    return Array.from((root || this.$refs.panel).querySelectorAll(selector))
                        .filter((el) => el.offsetParent !== null || el === document.activeElement);
                },

                // Pułapka fokusu (WCAG 2.4.3): Tab z ostatniego elementu wraca na
                // pierwszy, a Shift+Tab z pierwszego — na ostatni.
                trapTab(event) {
                    const items = this.focusables();
                    if (items.length === 0) return;
                    const first = items[0];
                    const last = items[items.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }));
        });
    </script>
@endsection
