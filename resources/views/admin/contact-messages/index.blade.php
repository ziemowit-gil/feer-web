@extends('admin.layout')

@section('title', 'Wiadomości kontaktowe')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-ink">Wiadomości kontaktowe</h1>
            <p class="text-sm text-muted">Wiadomości z formularza na stronie /kontakt</p>
        </div>
        <div class="flex items-center gap-2">
            @foreach (['wszystkie' => 'Wszystkie', 'nieprzeczytane' => 'Nieprzeczytane', 'przeczytane' => 'Przeczytane'] as $val => $label)
                <a href="{{ route('admin.wiadomosci-kontaktowe.index', ['filtr' => $val]) }}"
                   class="rounded-lg border px-3 py-1.5 text-xs font-bold transition
                          {{ $filter === $val ? 'border-brand bg-brand text-white' : 'border-gray-200 bg-white text-muted hover:bg-gray-50' }}">
                    {{ $label }}
                    @if ($val === 'nieprzeczytane' && $unreadCount > 0)
                        <span class="ml-1 inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/30 text-[10px]">{{ $unreadCount }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('status') }}
        </div>
    @endif

    @if ($messages->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-10 text-center text-muted">
            <i class="fa-solid fa-inbox mb-3 text-3xl text-gray-300" aria-hidden="true"></i>
            <p class="font-medium">Brak wiadomości</p>
            <p class="mt-1 text-sm">Wiadomości z formularza kontaktowego pojawią się tutaj.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-muted">Nadawca</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-muted md:table-cell">Temat</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-muted lg:table-cell">Data</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($messages as $msg)
                        <tr class="group transition hover:bg-gray-50 {{ $msg->isUnread() ? 'bg-blue-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($msg->isUnread())
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-brand" title="Nieprzeczytane" aria-label="Nowe"></span>
                                    @else
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-transparent"></span>
                                    @endif
                                    <div>
                                        <p class="font-bold text-ink {{ $msg->isUnread() ? '' : 'font-medium' }}">{{ $msg->name }}</p>
                                        <p class="text-xs text-muted">{{ $msg->email }}</p>
                                        @if ($msg->phone)
                                            <p class="text-xs text-muted">{{ $msg->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 text-muted md:table-cell">
                                @if ($msg->subject)
                                    <span class="font-medium text-ink">{{ Str::limit($msg->subject, 50) }}</span><br>
                                @endif
                                <span class="text-xs">{{ Str::limit($msg->message, 80) }}</span>
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-muted lg:table-cell">
                                {{ $msg->created_at->format('d.m.Y H:i') }}
                                @if ($msg->replied_at)
                                    <span class="mt-0.5 block text-green-600"><i class="fa-solid fa-reply text-[10px]" aria-hidden="true"></i> Odpowiedziano</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.wiadomosci-kontaktowe.show', $msg) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1 text-xs font-bold text-muted hover:bg-gray-50 hover:text-ink">
                                    Otwórz
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $messages->links() }}
        </div>
    @endif
@endsection
