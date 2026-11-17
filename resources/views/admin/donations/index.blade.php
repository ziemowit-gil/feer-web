@extends('admin.layout')

@section('title', 'Wpłaty')

@php
    use App\Models\Donation;
    use App\Support\Money;
    $field = 'w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
    $exportQuery = array_filter($filters, fn ($v) => $v !== '');
@endphp

@section('content')
    @if (session('status'))
        <div role="status" class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink">{{ session('status') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink" aria-live="polite">
            Wyników: <strong>{{ $totalCount }}</strong> · zaksięgowane: <strong>{{ Money::grosze($paidSum) }}</strong>
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.darowizny.export', $exportQuery + ['format' => 'csv']) }}" class="rounded bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light">
                <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksport CSV
            </a>
            <a href="{{ route('admin.darowizny.export', $exportQuery + ['format' => 'xlsx']) }}" class="rounded bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i> Eksport XLSX
            </a>
            <a href="{{ route('admin.darowizny.create', ctype_digit((string) $filters['kampania']) ? ['kampania' => $filters['kampania']] : []) }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpłatę ręcznie
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.darowizny.index') }}" class="mb-4 grid gap-3 rounded-md bg-white p-4 sm:grid-cols-2 lg:grid-cols-4" role="search" aria-label="Filtry wpłat">
        <div>
            <label for="f-q" class="mb-1 block text-xs font-bold text-ink">Szukaj</label>
            <input type="search" id="f-q" name="q" value="{{ $filters['q'] }}" placeholder="Nazwisko, e-mail, firma, NIP, nr P24" class="{{ $field }}">
        </div>
        <div>
            <label for="f-kampania" class="mb-1 block text-xs font-bold text-ink">Zbiórka</label>
            <select id="f-kampania" name="kampania" class="{{ $field }}">
                <option value="">Wszystkie</option>
                <option value="ogolne" @selected($filters['kampania'] === 'ogolne')>Ogólne (cele statutowe)</option>
                @foreach ($campaigns as $c)
                    <option value="{{ $c->id }}" @selected((string) $filters['kampania'] === (string) $c->id)>{{ $c->title }}</option>
                @endforeach
            </select>
        </div>
        @foreach ([['status', 'Status', Donation::STATUSES], ['typ', 'Rodzaj', Donation::TYPES], ['zrodlo', 'Źródło', Donation::SOURCES]] as [$name, $text, $options])
            <div>
                <label for="f-{{ $name }}" class="mb-1 block text-xs font-bold text-ink">{{ $text }}</label>
                <select id="f-{{ $name }}" name="{{ $name }}" class="{{ $field }}">
                    <option value="">Wszystkie</option>
                    @foreach ($options as $value => $optionLabel)
                        <option value="{{ $value }}" @selected($filters[$name] === $value)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div>
            <label for="f-od" class="mb-1 block text-xs font-bold text-ink">Od</label>
            <input type="date" id="f-od" name="od" value="{{ $filters['od'] }}" class="{{ $field }}">
        </div>
        <div>
            <label for="f-do" class="mb-1 block text-xs font-bold text-ink">Do</label>
            <input type="date" id="f-do" name="do" value="{{ $filters['do'] }}" class="{{ $field }}">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink">Filtruj</button>
            <a href="{{ route('admin.darowizny.index') }}" class="rounded bg-gray-100 px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light">Wyczyść</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Wpłaty, od najnowszych</caption>
            <thead class="bg-gray-100 text-xs font-bold text-ink">
                <tr>
                    <th scope="col" class="px-4 py-3">Data</th>
                    <th scope="col" class="px-4 py-3">Darczyńca</th>
                    <th scope="col" class="px-4 py-3">Zbiórka</th>
                    <th scope="col" class="px-4 py-3 text-right">Kwota</th>
                    <th scope="col" class="px-4 py-3">Rodzaj / źródło</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($donations as $d)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ ($d->paid_at ?? $d->created_at)?->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="font-bold">{{ $d->fullName() ?: $d->company_name }}</span>
                            @if ($d->company_name && $d->fullName())<span class="block text-xs">{{ $d->company_name }}</span>@endif
                            <span class="block text-xs">{{ $d->email }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $d->campaign?->title ?? 'Cele statutowe' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-bold">{{ $d->amountLabel() }}</td>
                        <td class="px-4 py-3 text-xs">{{ Donation::TYPES[$d->type ?? 'one_time'] ?? '' }}<br>{{ Donation::SOURCES[$d->source ?? 'online'] ?? '' }}</td>
                        <td class="px-4 py-3 text-xs font-bold">{{ Donation::STATUSES[$d->status] ?? $d->status }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.darowizny.edit', $d) }}" class="font-bold text-brand-dark underline">Szczegóły<span class="sr-only">: wpłata {{ $d->amountLabel() }} od {{ $d->fullName() ?: $d->company_name }}</span></a>
                        </td>
                    </tr>
                @empty
                    @include('admin.partials.empty-state', ['icon' => 'fa-receipt', 'title' => 'Brak wpłat', 'text' => 'Wpłaty online pojawią się tu po płatności. Przelewy tradycyjne dodasz ręcznie.', 'route' => 'admin.darowizny.create', 'action' => 'Dodaj wpłatę ręcznie', 'colspan' => 7])
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $donations->links() }}</div>
@endsection
