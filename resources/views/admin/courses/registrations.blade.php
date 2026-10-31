@extends('admin.layout')

@section('title', 'Uczestnicy: '.$edition->course->title)

@php
    $in = 'mt-1 block w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
    $lbl = 'block text-sm font-bold text-ink';
    $quick = ['pending' => ['confirmed' => 'Potwierdź', 'waitlist' => 'Na rezerwową', 'cancelled' => 'Anuluj'],
              'waitlist' => ['confirmed' => 'Przenieś na listę', 'cancelled' => 'Anuluj'],
              'confirmed' => ['completed' => 'Ukończył(a)', 'cancelled' => 'Anuluj'],
              'completed' => ['confirmed' => 'Cofnij'], 'cancelled' => ['pending' => 'Przywróć']];
@endphp

@section('content')
    <p class="mb-1 text-sm"><a href="{{ route('admin.kursy.edit', $edition->course) }}" class="font-bold text-brand-dark underline">← {{ $edition->course->title }}</a></p>
    <h2 class="text-xl font-bold text-ink">Termin: {{ $edition->datesLabel() }}@if ($edition->schedule), {{ $edition->schedule }}@endif</h2>
    <p class="mt-1 text-sm text-ink">Zajęte miejsca: <strong>{{ $edition->taken_seats }}</strong> z {{ $edition->capacity }} (wolne: {{ $edition->free_seats }}). Lista rezerwowa: {{ $counts['waitlist'] ?? 0 }}.</p>

    @if ($errors->any())<div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <nav aria-label="Filtr statusu" class="mt-5 flex flex-wrap gap-2">
        <a href="{{ route('admin.kursy.registrations.index', $edition) }}" @if (! $filter) aria-current="true" @endif class="rounded px-3 py-1.5 text-sm font-bold {{ ! $filter ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-gray-100' }}">Wszystkie ({{ $counts->sum() }})</a>
        @foreach (\App\Models\CourseRegistration::STATUSES as $k => $l)
            <a href="{{ route('admin.kursy.registrations.index', [$edition, 'status' => $k]) }}" @if ($filter === $k) aria-current="true" @endif class="rounded px-3 py-1.5 text-sm font-bold {{ $filter === $k ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-gray-100' }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>
        @endforeach
    </nav>

    <div class="mt-4 overflow-x-auto rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Zgłoszenia na wybrany termin</caption>
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr><th scope="col" class="px-4 py-3">Uczestnik</th><th scope="col" class="px-4 py-3">Kontakt</th><th scope="col" class="px-4 py-3">Potrzeby</th><th scope="col" class="px-4 py-3">Status</th><th scope="col" class="px-4 py-3">Kod</th><th scope="col" class="px-4 py-3 text-right">Akcje</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($registrations as $r)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $r->fullName() }}<span class="block text-xs font-normal text-muted">{{ $r->created_at->format('d.m.Y H:i') }}@if ($r->source !== 'web') · {{ ['phone' => 'telefon', 'email' => 'e-mail', 'other' => 'inne'][$r->source] ?? $r->source }}@endif</span></td>
                        <td class="px-4 py-3"><a href="mailto:{{ $r->email }}" class="text-brand-dark underline">{{ $r->email }}</a>@if ($r->phone)<span class="block">{{ $r->phone }}</span>@endif</td>
                        <td class="max-w-xs px-4 py-3 text-ink">{{ $r->accessibility_needs ?: '—' }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-ink">{{ $r->statusLabel() }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $r->payment_reference }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @foreach ($quick[$r->status] ?? [] as $to => $label)
                                    <form method="POST" action="{{ route('admin.kursy.registrations.status', $r) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="{{ $to }}">
                                        <button type="submit" class="rounded border border-brand-dark px-2 py-1 text-xs font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $label }}<span class="sr-only"> — {{ $r->fullName() }}</span></button>
                                    </form>
                                @endforeach
                                <form method="POST" action="{{ route('admin.kursy.registrations.destroy', $r) }}" onsubmit="return confirm('Usunąć zgłoszenie?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" aria-label="Usuń zgłoszenie: {{ $r->fullName() }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-ink">Brak zgłoszeń.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.kursy.registrations.store', $edition) }}" class="mt-8 max-w-3xl rounded-md bg-white p-5">
        @csrf
        <h3 class="text-base font-bold text-ink">Dodaj uczestnika ręcznie</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div><label for="m-fn" class="{{ $lbl }}">Imię *</label><input id="m-fn" name="first_name" required value="{{ old('first_name') }}" class="{{ $in }}"></div>
            <div><label for="m-ln" class="{{ $lbl }}">Nazwisko *</label><input id="m-ln" name="last_name" required value="{{ old('last_name') }}" class="{{ $in }}"></div>
            <div><label for="m-em" class="{{ $lbl }}">E-mail *</label><input id="m-em" type="email" name="email" required value="{{ old('email') }}" class="{{ $in }}"></div>
            <div><label for="m-ph" class="{{ $lbl }}">Telefon</label><input id="m-ph" name="phone" value="{{ old('phone') }}" class="{{ $in }}"></div>
            <div><label for="m-src" class="{{ $lbl }}">Skąd zgłoszenie *</label>
                <select id="m-src" name="source" class="{{ $in }}"><option value="phone">Telefon</option><option value="email">E-mail</option><option value="other">Inne</option></select></div>
            <div><label for="m-st" class="{{ $lbl }}">Status *</label>
                <select id="m-st" name="status" class="{{ $in }}">@foreach (\App\Models\CourseRegistration::STATUSES as $k => $l)<option value="{{ $k }}" @selected($k === 'confirmed')>{{ $l }}</option>@endforeach</select></div>
        </div>
        <div class="mt-4"><label for="m-needs" class="{{ $lbl }}">Potrzeby i dostosowania</label><textarea id="m-needs" name="accessibility_needs" rows="2" class="{{ $in }}">{{ old('accessibility_needs') }}</textarea></div>
        <p class="mt-3 text-xs text-muted">Gdy w terminie nie ma wolnych miejsc, uczestnik trafi na listę rezerwową.</p>
        <button type="submit" class="mt-3 rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Dodaj uczestnika</button>
    </form>
@endsection
