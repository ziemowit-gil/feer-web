@extends('admin.layout')

@section('title', 'Szybkie akcje')

@section('content')

    {{-- ===== Wizualny edytor siatki: przeciągnij kafel, zmień szerokość, pasek, negatyw — podgląd jak na stronie głównej ===== --}}
    @php
        $gridData = $quickActions->map(fn ($a) => [
            'id' => $a->id, 'label' => $a->label, 'icon' => $a->icon, 'color' => $a->color,
            'cols' => (int) ($a->cols ?? 1), 'strip' => (bool) $a->strip, 'neg' => (bool) $a->is_negative,
            'edit' => route('admin.szybkie-akcje.edit', $a),
        ])->values();
    @endphp
    @if ($quickActions->isNotEmpty())
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5" aria-labelledby="qa-grid-h"
        x-data="quickGrid(@js($gridData), @js(route('admin.szybkie-akcje.uklad')), @js(csrf_token()))">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="qa-grid-h" class="text-base font-bold text-ink">Układ siatki</h2>
                <p class="text-sm text-muted">Przeciągnij kafel, aby zmienić kolejność. Przyciskami pod kaflem ustawisz szerokość, tryb paska i negatyw. Siatka ma 4 kolumny (jak na komputerze).</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm" :class="status === 'error' ? 'text-red-700' : 'text-muted'" role="status" x-text="message"></span>
                <button type="button" @click="save()" :disabled="! dirty || saving"
                    class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Zapisz układ
                </button>
            </div>
        </div>

        <ul role="list" class="gap-3 overflow-x-auto rounded-lg bg-gray-50 p-3" style="display:grid;grid-template-columns:repeat(4,minmax(10rem,1fr))" @dragover.prevent>
            <template x-for="(t, i) in tiles" :key="t.id">
                <li :style="'grid-column: span ' + Math.min(t.cols, 4)" :class="dragOver === i ? 'ring-2 ring-brand ring-offset-2' : ''"
                    class="rounded-md" draggable="true"
                    @dragstart="dragFrom = i; $event.dataTransfer.effectAllowed = 'move'" @dragenter.prevent="dragOver = i"
                    @dragend="dragFrom = null; dragOver = null"
                    @drop.prevent="drop(i)">
                    <div class="flex cursor-grab items-center gap-3 rounded-t-md px-4"
                        :class="t.strip ? 'min-h-14 py-2' : 'min-h-20 py-4'"
                        :style="t.neg ? ('background:' + hex(t) + ';color:#fff') : ('border:2px solid ' + hex(t) + ';border-bottom:0;background:#fff;color:#1d1d1a')">
                        <i :class="iconClass(t)" class="w-6 flex-none text-center text-xl" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1 font-bold leading-snug" x-text="t.label"></span>
                        <span aria-hidden="true">→</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1 rounded-b-md border border-gray-200 bg-white p-1.5 text-xs">
                        <button type="button" @click="move(i, -1)" :disabled="i === 0" class="qa-btn" :aria-label="'Przesuń wcześniej: ' + t.label"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                        <button type="button" @click="move(i, 1)" :disabled="i === tiles.length - 1" class="qa-btn" :aria-label="'Przesuń później: ' + t.label"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                        <span class="mx-1 h-4 w-px bg-gray-200" aria-hidden="true"></span>
                        <template x-for="n in [1, 2, 3]" :key="n">
                            <button type="button" @click="t.cols = n; touch()" class="qa-btn" :class="t.cols === n ? 'is-on' : ''" :aria-pressed="(t.cols === n).toString()" :aria-label="'Szerokość ' + n + ' kolumn: ' + t.label" x-text="n"></button>
                        </template>
                        <span class="mx-1 h-4 w-px bg-gray-200" aria-hidden="true"></span>
                        <button type="button" @click="t.strip = ! t.strip; touch()" class="qa-btn" :class="t.strip ? 'is-on' : ''" :aria-pressed="t.strip.toString()" :aria-label="'Pasek (niski kafel): ' + t.label">Pasek</button>
                        <button type="button" @click="t.neg = ! t.neg; touch()" class="qa-btn" :class="t.neg ? 'is-on' : ''" :aria-pressed="t.neg.toString()" :aria-label="'Negatyw (wypełniony): ' + t.label">Negatyw</button>
                        <a :href="t.edit" class="qa-btn ml-auto" :aria-label="'Edytuj: ' + t.label"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                    </div>
                </li>
            </template>
        </ul>
    </section>
    <style>
        .qa-btn { display: inline-flex; min-width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; border-radius: .375rem; border: 1px solid #d1d5db; padding: 0 .4rem; font-weight: 700; color: #1d1d1a; background: #fff; }
        .qa-btn:hover:not(:disabled) { background: #f3f4f6; }
        .qa-btn:disabled { opacity: .4; cursor: not-allowed; }
        .qa-btn.is-on { background: #1d1d1a; border-color: #1d1d1a; color: #fff; }
        .qa-btn:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 2px; }
    </style>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('quickGrid', (initial, url, csrf) => ({
                tiles: initial, dragFrom: null, dragOver: null, dirty: false, saving: false, status: '', message: '',
                named: { blue: '#1e6dff', dark: '#1d1d1a', green: '#166534', purple: '#1e6dff', orange: '#ea8f00', red: '#b91c1c' },
                hex(t) { return /^#[0-9a-f]{6}$/i.test(t.color || '') ? t.color : (this.named[t.color] || '#1e6dff'); },
                iconClass(t) { return (t.icon || '').includes('fa-') || (t.icon || '').startsWith('bi ') ? t.icon : 'bi ' + (t.icon || 'bi-lightning'); },
                touch() { this.dirty = true; this.message = 'Niezapisane zmiany'; this.status = ''; },
                move(i, d) { const j = i + d; if (j < 0 || j >= this.tiles.length) return; const [x] = this.tiles.splice(i, 1); this.tiles.splice(j, 0, x); this.touch(); },
                drop(i) { if (this.dragFrom === null || this.dragFrom === i) return; const [x] = this.tiles.splice(this.dragFrom, 1); this.tiles.splice(i, 0, x); this.dragFrom = null; this.dragOver = null; this.touch(); },
                async save() {
                    this.saving = true; this.message = 'Zapisuję…';
                    try {
                        const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ items: this.tiles.map(t => ({ id: t.id, cols: t.cols, strip: t.strip, is_negative: t.neg })) }) });
                        if (! r.ok) throw new Error();
                        this.dirty = false; this.status = 'ok'; this.message = 'Układ zapisany.';
                    } catch (e) { this.status = 'error'; this.message = 'Nie udało się zapisać układu.'; }
                    this.saving = false;
                },
            }));
        });
    </script>
    @endif

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.szybkie-akcje.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj szybką akcję
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Ikona</th>
                    <th class="px-4 py-3">Etykieta</th>
                    <th class="px-4 py-3">Link</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($quickActions as $action)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-light text-brand">
                                <i class="bi {{ $action->icon }}"></i>
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $action->label }}</td>
                        <td class="px-4 py-3 text-muted">{{ $action->url }}</td>
                        <td class="px-4 py-3 text-muted">{{ $action->order }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.szybkie-akcje.edit', $action) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.szybkie-akcje.destroy', $action) }}" onsubmit="return confirm('Usunąć szybką akcję &quot;{{ $action->label }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak szybkich akcji. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
