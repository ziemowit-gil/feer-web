@extends('admin.layout')

@section('title', 'Scal osoby')

@section('content')
    @include('admin.partials.content-nav-tabs')

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.osoby.index') }}"
            class="text-sm text-muted hover:text-brand focus-visible:rounded focus-visible:outline-2 focus-visible:outline-brand">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy osób
        </a>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-bold text-ink">Scal osoby</span>
    </div>

    <h1 class="mb-1 text-xl font-bold text-ink">Scal osoby</h1>
    <p class="mb-8 text-sm text-muted">
        Wybierz drugą osobę, a potem zdecyduj które pola zachować. Wybrany duplikat zostanie usunięty.
    </p>

    {{-- ══════════════════════ KROK 1: wybór drugiej osoby ══════════════════════ --}}
    @if (! $target)
        <div class="rounded-lg border border-gray-200 bg-white p-6 sm:max-w-md">
            <p class="mb-4 text-sm font-bold text-ink">Scalasz: <span class="text-brand">{{ $page->title }}</span></p>

            <form method="GET" action="{{ route('admin.osoby.scal', $page) }}">
                <label for="with" class="mb-1 block text-sm font-bold">Wybierz drugą osobę do scalenia</label>
                <select id="with" name="with"
                    class="mb-4 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"
                    required>
                    <option value="">— wybierz —</option>
                    @foreach ($candidates as $c)
                        <option value="{{ $c->id }}">{{ $c->title }}{{ $c->person_role ? ' – ' . $c->person_role : '' }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Dalej <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                </button>
            </form>
        </div>

    {{-- ══════════════════════ KROK 2: porównanie pól ══════════════════════ --}}
    @else
        @php
            $mergeFields = [
                'title'               => 'Imię i nazwisko',
                'person_role'         => 'Stanowisko / rola',
                'person_bio'          => 'Krótkie o mnie',
                'person_email'        => 'E-mail kontaktowy',
                'person_phone'        => 'Nr telefonu',
                'person_member_label' => 'Etykieta członkostwa',
                'content'             => 'Treść (biografia)',
                'content_image'       => 'Zdjęcie (URL)',
                'content_image_alt'   => 'Alt zdjęcia',
                'person_department'   => 'Działy / sekcje',
                'person_social'       => 'Social media',
            ];
        @endphp

        <form method="POST" action="{{ route('admin.osoby.scal.wykonaj', $page) }}">
            @csrf
            <input type="hidden" name="target_id" value="{{ $target->id }}">

            {{-- Nagłówki kolumn --}}
            <div class="mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-4">
                <div class="rounded-lg border-2 border-brand bg-brand-light px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand">Zachowaj (baza)</p>
                    <p class="mt-0.5 font-bold text-ink">{{ $page->title }}</p>
                    @if ($page->person_role)
                        <p class="text-xs text-muted">{{ $page->person_role }}</p>
                    @endif
                </div>
                <div class="text-center text-sm font-bold text-muted">vs</div>
                <div class="rounded-lg border-2 border-red-200 bg-red-50 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-red-500">Duplikat (do usunięcia)</p>
                    <p class="mt-0.5 font-bold text-ink">{{ $target->title }}</p>
                    @if ($target->person_role)
                        <p class="text-xs text-muted">{{ $target->person_role }}</p>
                    @endif
                </div>
            </div>

            {{-- Pola do porównania --}}
            <div class="space-y-3">
                @foreach ($mergeFields as $field => $label)
                    @php
                        $baseVal   = $page->{$field};
                        $targetVal = $target->{$field};
                        if (is_array($baseVal))   $baseVal   = implode(', ', $baseVal);
                        if (is_array($targetVal))  $targetVal = implode(', ', array_keys(array_filter((array) $targetVal))
                            ?: (array) $targetVal);
                        $baseText   = filled($baseVal)   ? (is_string($baseVal)   ? strip_tags($baseVal)   : '') : '';
                        $targetText = filled($targetVal) ? (is_string($targetVal) ? strip_tags($targetVal) : '') : '';
                        $identical  = $baseText === $targetText;
                    @endphp

                    <div class="rounded-lg border {{ $identical ? 'border-gray-100 bg-gray-50/50' : 'border-gray-200 bg-white' }} p-4">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</p>

                        @if ($identical)
                            <p class="text-sm text-muted italic">{{ $baseText ?: '(puste u obu)' }}
                                <span class="ml-2 text-green-600 font-bold">✓ identyczne</span>
                            </p>
                            <input type="hidden" name="keep[{{ $field }}]" value="base">
                        @else
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex cursor-pointer items-start gap-2 rounded border border-gray-200 p-3 hover:border-brand has-[:checked]:border-brand has-[:checked]:bg-brand-light/30">
                                    <input type="radio" name="keep[{{ $field }}]" value="base" checked
                                        class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                                    <span class="text-xs text-ink">{{ filled($baseText) ? $baseText : '(puste)' }}</span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded border border-gray-200 p-3 hover:border-brand has-[:checked]:border-brand has-[:checked]:bg-brand-light/30">
                                    <input type="radio" name="keep[{{ $field }}]" value="target"
                                        class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                                    <span class="text-xs text-ink">{{ filled($targetText) ? $targetText : '(puste)' }}</span>
                                </label>
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Fundator / wyróżnienie --}}
                <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-4">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-amber-700">Fundator — wprowadzenie</p>
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                        <input type="checkbox" name="is_featured" value="1"
                            {{ ($page->is_featured || $target->is_featured) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="font-semibold text-ink">Zaznacz scalony rekord jako fundatora</span>
                    </label>
                    @if ($page->is_featured || $target->is_featured)
                        <p class="mt-1 pl-6 text-xs text-muted">Jedna z łączonych stron była oznaczona jako fundator.</p>
                    @endif
                </div>
            </div>

            {{-- Przyciski --}}
            <div class="mt-6 flex items-center gap-4 border-t border-gray-100 pt-6">
                <button type="submit"
                    data-confirm="Scal te dwie osoby? Duplikat zostanie trwale usuniety."
                    class="rounded bg-red-600 px-5 py-2 text-sm font-bold text-white hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                    <i class="fa-solid fa-code-merge mr-1" aria-hidden="true"></i> Scal i usuń duplikat
                </button>
                <a href="{{ route('admin.osoby.index') }}"
                    class="text-sm text-muted hover:text-ink">
                    Anuluj
                </a>
            </div>
        </form>
    @endif
@endsection
