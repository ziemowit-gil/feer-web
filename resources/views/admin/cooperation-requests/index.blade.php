@extends('admin.layout')

@section('title', 'Zgłoszenia współpracy')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-ink">Zgłoszenia współpracy</h1>
            <p class="text-sm text-muted">Wiadomości z formularzy na stronach współpracy</p>
        </div>
        @if ($pages->count() > 1)
            <form method="GET" class="flex items-center gap-2">
                <label for="strona" class="text-sm font-medium text-muted">Strona:</label>
                <select id="strona" name="strona" onchange="this.form.submit()"
                        class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <option value="">Wszystkie</option>
                    @foreach ($pages as $p)
                        <option value="{{ $p->id }}" {{ (string)$pageId === (string)$p->id ? 'selected' : '' }}>
                            {{ $p->title }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('status') }}
        </div>
    @endif

    @if ($requests->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-10 text-center text-muted">
            <i class="fa-solid fa-inbox mb-3 text-3xl text-gray-300" aria-hidden="true"></i>
            <p class="font-medium">Brak zgłoszeń</p>
            <p class="mt-1 text-sm">Gdy ktoś wypełni formularz współpracy, zgłoszenie pojawi się tutaj.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-muted">Zgłaszający</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-muted">Sektor / formy</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-muted md:table-cell">Data</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($requests as $req)
                        <tr class="group transition hover:bg-gray-50 {{ $req->isUnread() ? 'bg-blue-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($req->isUnread())
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-brand" title="Nieprzeczytane" aria-label="Nowe"></span>
                                    @else
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-transparent"></span>
                                    @endif
                                    <div>
                                        <p class="font-bold text-ink">{{ $req->name }}</p>
                                        @if ($req->organization)
                                            <p class="text-xs text-muted">{{ $req->organization }}</p>
                                        @endif
                                        <p class="text-xs text-muted">{{ $req->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted">
                                @if ($req->sector)
                                    <span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs">{{ $req->sector }}</span>
                                @endif
                                @if (!empty($req->cooperation_types))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach ($req->cooperation_types as $type)
                                            <span class="inline-block rounded-full bg-brand-light px-2 py-0.5 text-xs text-brand">{{ $type }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-muted md:table-cell">
                                {{ $req->created_at->locale('pl')->isoFormat('D MMM YYYY, HH:mm') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.wspolpraca-zgloszenia.show', $req) }}"
                                   class="rounded px-3 py-1 text-xs font-bold text-brand hover:bg-brand-light focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand">
                                    Szczegóły
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $requests->links() }}</div>
    @endif
@endsection
