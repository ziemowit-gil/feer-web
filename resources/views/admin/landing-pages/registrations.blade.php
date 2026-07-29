@extends('admin.layout')

@section('title', 'Zapisy — ' . $page->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.lp.index') }}" class="text-sm text-brand hover:text-brand-dark"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Landing pages</a>
            <p class="mt-1 text-sm text-muted">Zapisy na: <strong class="text-ink">{{ $page->title }}</strong> ({{ $registrations->count() }})</p>
        </div>
        @if ($registrations->isNotEmpty())
            <a href="{{ route('admin.lp.registrations.export', $page) }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksportuj CSV
            </a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Imię i nazwisko</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Telefon</th>
                    @foreach ($page->formFields() as $f)
                        <th class="px-4 py-3">{{ $f['label'] }}</th>
                    @endforeach
                    <th class="px-4 py-3">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($registrations as $r)
                    <tr>
                        <td class="px-4 py-3 font-medium text-ink">{{ $r->name }}</td>
                        <td class="px-4 py-3"><a href="mailto:{{ $r->email }}" class="text-brand hover:text-brand-dark">{{ $r->email }}</a></td>
                        <td class="px-4 py-3 text-muted">{{ $r->phone ?: '—' }}</td>
                        @foreach ($page->formFields() as $f)
                            <td class="px-4 py-3 text-muted">{{ $r->extra[$f['key']] ?? '—' }}</td>
                        @endforeach
                        <td class="px-4 py-3 text-muted">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + count($page->formFields()) }}" class="px-4 py-8 text-center text-muted">Brak zapisów.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
