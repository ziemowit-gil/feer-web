@extends('layouts.site')

@section('title', 'Panel rezerwacji — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Panel rezerwacji', 'url' => null],
    ]])
@endsection

@section('content')
@php
    $member = auth('member')->user();
    $weekdays = \App\Models\SiteSetting::WEEKDAYS;
@endphp

{{-- Pasek identyfikacji użytkownika --}}
<div class="border-b border-brand/20 bg-brand-light">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
        <span class="flex items-center gap-2 text-brand-dark">
            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            Panel rezerwacji —
            @if ($member->avatar)
                <img src="{{ $member->avatar }}" alt="" class="h-5 w-5 rounded-full object-cover" aria-hidden="true">
            @endif
            <strong>{{ $member->name }}</strong>
        </span>
        <form method="POST" action="{{ route('member.logout') }}" class="inline">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1 text-brand hover:text-brand-dark">
                <i class="fa-solid fa-right-from-bracket text-xs" aria-hidden="true"></i> Wyloguj
            </button>
        </form>
    </div>
</div>

<div class="mx-auto max-w-6xl px-4 py-8">

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">
            <i class="fa-solid fa-circle-check mt-0.5 flex-none" aria-hidden="true"></i>
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-0.5 flex-none" aria-hidden="true"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Statystyki --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-2xl font-bold text-ink">{{ $signups->count() }}</p>
            <p class="mt-0.5 text-xs text-muted">Wszystkich zgłoszeń</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-2xl font-bold text-ink">{{ count($upcoming) }}</p>
            <p class="mt-0.5 text-xs text-muted">Nadchodzące terminy</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-2xl font-bold text-ink">{{ $byTerm->keys()->filter()->count() }}</p>
            <p class="mt-0.5 text-xs text-muted">Terminy z zapisami</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4">
            @php $newest = $signups->first(); @endphp
            <p class="text-2xl font-bold text-ink">{{ $newest ? $newest->created_at->diffForHumans() : '—' }}</p>
            <p class="mt-0.5 text-xs text-muted">Ostatnie zgłoszenie</p>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_360px]">

        {{-- ─── Lewa: Harmonogram terminów ─────────────────────────────── --}}
        <div>
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-lg font-bold text-ink">
                    {{ $settings->contact_schedule_title ?: 'Harmonogram terminów' }}
                </h2>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('rezerwacje.notify') }}">
                        @csrf
                        <button type="submit"
                            onclick="return confirm('Wyślij e-mail z aktualnym harmonogramem do wszystkich zapisanych?')"
                            class="inline-flex items-center gap-1.5 rounded border border-brand px-3 py-1.5 text-xs font-bold text-brand transition hover:bg-brand hover:text-white">
                            <i class="fa-solid fa-bell" aria-hidden="true"></i> Powiadom zapisanych
                        </button>
                    </form>
                    <a href="{{ route('rezerwacje.export') }}"
                        class="inline-flex items-center gap-1.5 rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-muted transition hover:border-brand hover:text-brand">
                        <i class="fa-solid fa-download" aria-hidden="true"></i> Eksport CSV
                    </a>
                </div>
            </div>

            {{-- Lista terminów --}}
            @if ($schedule)
                <div class="mb-6 space-y-3">
                    @foreach ($schedule as $idx => $entry)
                        @php
                            $type = $entry['type'] ?? 'date';
                            $sigCount = 0;
                            if ($type === 'date') {
                                $dateLabel = \Illuminate\Support\Carbon::parse($entry['date'] ?? '')->locale('pl')->isoFormat('D MMMM YYYY');
                            } else {
                                $wdNum = (int) ($entry['weekday'] ?? 1);
                                $dateLabel = 'W '.($weekdays[$wdNum] ?? '');
                            }
                            $timeLabel = trim($entry['time'] ?? '');
                            $whereLabel = trim($entry['where'] ?? '');
                            $noteLabel = trim($entry['note'] ?? '');
                            // Count signups that mention this term
                            $termLabel = $dateLabel . ($timeLabel ? ', ' . $timeLabel : '') . ($whereLabel ? ' — ' . $whereLabel : '');
                            $sigCount = $byTerm->filter(fn($g, $k) => str_contains((string)$k, $dateLabel))->flatten()->count();
                        @endphp
                        <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4">
                            <div class="flex h-10 w-10 flex-none items-center justify-center rounded-lg {{ $type === 'weekly' ? 'bg-purple-100 text-purple-700' : 'bg-brand-light text-brand' }}">
                                <i class="fa-solid {{ $type === 'weekly' ? 'fa-rotate' : 'fa-calendar-day' }} text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-ink">{{ $dateLabel }}{{ $timeLabel ? ', '.$timeLabel : '' }}</p>
                                @if ($whereLabel)<p class="text-sm text-muted"><i class="fa-solid fa-location-dot mr-1 text-xs" aria-hidden="true"></i>{{ $whereLabel }}</p>@endif
                                @if ($noteLabel)<p class="text-sm text-muted">{{ $noteLabel }}</p>@endif
                                @if ($sigCount > 0)
                                    <p class="mt-1 text-xs font-bold text-brand">{{ $sigCount }} {{ $sigCount === 1 ? 'zgłoszenie' : ($sigCount < 5 ? 'zgłoszenia' : 'zgłoszeń') }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('rezerwacje.termin.destroy', $idx) }}" class="flex-none">
                                @csrf
                                <button type="submit"
                                    onclick="return confirm('Usunąć ten termin z harmonogramu?')"
                                    class="flex min-h-8 min-w-8 items-center justify-center rounded text-gray-400 hover:bg-red-50 hover:text-red-600"
                                    aria-label="Usuń termin">
                                    <i class="fa-solid fa-trash text-sm" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mb-6 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-6 text-center text-sm text-muted">
                    Brak terminów w harmonogramie.
                </p>
            @endif

            {{-- Formularz dodawania terminu --}}
            <div x-data="{ open: false, type: 'date' }" class="rounded-xl border border-gray-200 bg-white">
                <button type="button" @click="open = !open"
                    class="flex w-full items-center gap-2 px-5 py-4 text-left text-sm font-bold text-brand">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Dodaj termin
                    <i class="fa-solid fa-chevron-down ml-auto text-xs transition-transform" :class="open && 'rotate-180'" aria-hidden="true"></i>
                </button>

                <form x-show="open" x-cloak method="POST" action="{{ route('rezerwacje.termin.store') }}"
                    class="border-t border-gray-100 px-5 py-5">
                    @csrf

                    <div class="mb-4 flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="type" value="date" x-model="type" class="text-brand"> Konkretna data
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="type" value="weekly" x-model="type" class="text-brand"> Cykl tygodniowy
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div x-show="type === 'date'">
                            <label class="mb-1 block text-xs font-bold">Data</label>
                            <input type="date" name="date" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div x-show="type === 'weekly'">
                            <label class="mb-1 block text-xs font-bold">Dzień tygodnia</label>
                            <select name="weekday" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                @foreach ($weekdays as $num => $label)
                                    <option value="{{ $num }}">{{ ucfirst($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold">Godziny <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" name="time" placeholder="np. 10:00–14:00"
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold">Gdzie <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" name="where" placeholder="np. Kraków, ul. Przykładowa 1"
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-bold">Dopisek <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" name="note" placeholder="np. wejście od podwórza"
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                            Zapisz termin
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ─── Prawa: lista zgłoszeń ───────────────────────────────────── --}}
        <div>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-ink">Zgłoszenia</h2>
                <span class="rounded-full bg-brand-light px-2.5 py-0.5 text-xs font-bold text-brand">{{ $signups->count() }}</span>
            </div>

            @if ($signups->isEmpty())
                <p class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-8 text-center text-sm text-muted">
                    Brak zgłoszeń.
                </p>
            @else
                <div class="space-y-2" x-data="{ filter: '' }">
                    <div class="relative mb-3">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                        <input type="search" x-model="filter" placeholder="Filtruj po nazwisku lub e-mailu…"
                            class="w-full rounded-lg border-gray-300 pl-8 text-sm focus:border-brand focus:ring-brand">
                    </div>

                    @foreach ($signups as $signup)
                        @php $termKey = strtolower($signup->name.' '.$signup->email.' '.($signup->term ?? '').' '.($signup->phone ?? '')); @endphp
                        <div class="rounded-lg border bg-white p-3 text-sm {{ $signup->isConfirmed() ? 'border-green-200' : 'border-gray-200' }}"
                            x-show="!filter || '{{ $termKey }}'.includes(filter.toLowerCase())"
                            x-cloak>
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-ink truncate">{{ $signup->name }}</p>
                                        @if ($signup->isConfirmed())
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold text-green-700">
                                                <i class="fa-solid fa-check text-[9px]" aria-hidden="true"></i> Potwierdzone
                                            </span>
                                        @endif
                                    </div>
                                    <a href="mailto:{{ $signup->email }}" class="text-xs text-brand hover:underline">{{ $signup->email }}</a>
                                    @if ($signup->phone)
                                        <p class="text-xs text-muted">
                                            <a href="tel:{{ $signup->phone }}" class="hover:text-brand"><i class="fa-solid fa-phone mr-1 text-[10px]" aria-hidden="true"></i>{{ $signup->phone }}</a>
                                        </p>
                                    @endif
                                    @if ($signup->term)
                                        <p class="mt-1 text-xs text-muted">
                                            <i class="fa-regular fa-calendar mr-1" aria-hidden="true"></i>{{ $signup->term }}
                                        </p>
                                    @endif
                                    @if ($signup->message)
                                        <p class="mt-1 text-xs text-muted line-clamp-2">{{ $signup->message }}</p>
                                    @endif
                                    <p class="mt-1 text-[11px] text-gray-400">{{ $signup->created_at->locale('pl')->diffForHumans() }}</p>
                                </div>
                                <div class="flex flex-none flex-col gap-1">
                                    @unless ($signup->isConfirmed())
                                        <form method="POST" action="{{ route('rezerwacje.signup.confirm', $signup) }}">
                                            @csrf
                                            <button type="submit"
                                                title="Potwierdź spotkanie — wyślij e-mail do {{ $signup->name }}"
                                                class="flex min-h-7 min-w-7 items-center justify-center rounded text-gray-400 hover:bg-green-50 hover:text-green-600"
                                                aria-label="Potwierdź spotkanie">
                                                <i class="fa-solid fa-circle-check text-sm" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('rezerwacje.signup.destroy', $signup) }}">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('Usunąć zgłoszenie {{ addslashes($signup->name) }}?')"
                                            class="flex min-h-7 min-w-7 items-center justify-center rounded text-gray-300 hover:bg-red-50 hover:text-red-500"
                                            aria-label="Usuń zgłoszenie">
                                            <i class="fa-solid fa-xmark text-sm" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
