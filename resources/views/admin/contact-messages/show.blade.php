@extends('admin.layout')

@section('title', 'Wiadomość od ' . $contactMessage->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.wiadomosci-kontaktowe.index') }}"
               class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-muted hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            </a>
            <h1 class="text-lg font-bold text-ink">Wiadomość kontaktowa</h1>
        </div>

        <div class="flex items-center gap-2">
            @if (! $contactMessage->replied_at)
                <form method="POST" action="{{ route('admin.wiadomosci-kontaktowe.replied', $contactMessage) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 px-3 py-1.5 text-xs font-bold text-green-700 hover:bg-green-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-green-500">
                        <i class="fa-solid fa-reply" aria-hidden="true"></i>
                        Oznacz jako odpowiedziano
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.wiadomosci-kontaktowe.destroy', $contactMessage) }}"
                  data-confirm="Usunąć tę wiadomość? Operacja jest nieodwracalna.">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-red-500">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    Usuń
                </button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Treść --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Dane nadawcy</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <dt class="w-28 shrink-0 font-bold text-ink">Imię i nazwisko</dt>
                        <dd>{{ $contactMessage->name }}</dd>
                    </div>
                    <div class="flex items-start gap-3">
                        <dt class="w-28 shrink-0 font-bold text-ink">E-mail</dt>
                        <dd><a href="mailto:{{ $contactMessage->email }}" class="text-brand hover:underline">{{ $contactMessage->email }}</a></dd>
                    </div>
                    @if ($contactMessage->phone)
                        <div class="flex items-start gap-3">
                            <dt class="w-28 shrink-0 font-bold text-ink">Telefon</dt>
                            <dd><a href="tel:{{ $contactMessage->phone }}" class="text-brand hover:underline">{{ $contactMessage->phone }}</a></dd>
                        </div>
                    @endif
                    @if ($contactMessage->subject)
                        <div class="flex items-start gap-3">
                            <dt class="w-28 shrink-0 font-bold text-ink">Temat</dt>
                            <dd>{{ $contactMessage->subject }}</dd>
                        </div>
                    @endif
                    @if ($contactMessage->coordinator_name)
                        <div class="flex items-start gap-3">
                            <dt class="w-28 shrink-0 font-bold text-ink">Do koordynatora</dt>
                            <dd>
                                {{ $contactMessage->coordinator_name }}
                                @if ($contactMessage->coordinator_email)
                                    <span class="text-muted">— {{ $contactMessage->coordinator_email }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Wiadomość</h2>
                <p class="whitespace-pre-wrap text-sm leading-relaxed text-ink">{{ $contactMessage->message }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <a href="mailto:{{ $contactMessage->email }}?subject={{ urlencode('Re: ' . ($contactMessage->subject ?: 'Twoja wiadomość')) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand">
                    <i class="fa-solid fa-reply" aria-hidden="true"></i>
                    Odpowiedz przez klienta pocztowego
                </a>
            </div>
        </div>

        {{-- Metadane --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm shadow-sm">
                <h2 class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Szczegóły</h2>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-xs text-muted">Otrzymano</dt>
                        <dd class="font-medium text-ink">{{ $contactMessage->created_at->format('d.m.Y o H:i') }}</dd>
                        <dd class="text-xs text-muted">{{ $contactMessage->created_at->diffForHumans() }}</dd>
                    </div>
                    <div>
                        <dt class="mt-3 text-xs text-muted">Status</dt>
                        @if ($contactMessage->replied_at)
                            <dd class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">
                                <i class="fa-solid fa-check text-[10px]" aria-hidden="true"></i>
                                Odpowiedziano {{ $contactMessage->replied_at->format('d.m.Y') }}
                            </dd>
                        @elseif ($contactMessage->read_at)
                            <dd class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-muted">
                                Przeczytano
                            </dd>
                        @else
                            <dd class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-brand">
                                Nowe
                            </dd>
                        @endif
                    </div>
                    <div>
                        <dt class="mt-3 text-xs text-muted">E-mail powiadomienia</dt>
                        @if ($contactMessage->email_sent_at)
                            <dd class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                <i class="fa-solid fa-paper-plane text-[10px]" aria-hidden="true"></i>
                                Wysłano {{ $contactMessage->email_sent_at->format('d.m.Y H:i') }}
                            </dd>
                        @else
                            <dd class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">
                                <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i>
                                Nie wysłano
                            </dd>
                        @endif
                    </div>
                    @if ($contactMessage->ip_address)
                        <div>
                            <dt class="mt-3 text-xs text-muted">IP nadawcy</dt>
                            <dd class="font-mono text-xs text-ink">{{ $contactMessage->ip_address }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
@endsection
