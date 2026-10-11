@extends('admin.layout')

@section('title', 'Wiadomość od ' . $contactMessage->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.wiadomosci-kontaktowe.index') }}"
               class="flex h-8 w-8 items-center justify-center rounded-md text-muted focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-dark bg-white hover:bg-brand-light">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            </a>
            <h1 class="text-lg font-bold text-ink">Wiadomość kontaktowa</h1>
        </div>

        <div class="flex items-center gap-2">
            @if (! $contactMessage->replied_at)
                <form method="POST" action="{{ route('admin.wiadomosci-kontaktowe.replied', $contactMessage) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold text-ink focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-green-500 bg-white hover:bg-brand-light">
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
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold text-red-800 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-red-500 bg-red-50 hover:bg-red-100">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    Usuń
                </button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 rounded-md bg-brand-light px-4 py-3 text-sm font-medium text-ink" role="alert">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Treść --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="p-6 rounded-md bg-white">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Dane nadawcy</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <dt class="w-28 shrink-0 font-bold text-ink">Imię i nazwisko</dt>
                        <dd>{{ $contactMessage->name }}</dd>
                    </div>
                    <div class="flex items-start gap-3">
                        <dt class="w-28 shrink-0 font-bold text-ink">E-mail</dt>
                        <dd><a href="mailto:{{ $contactMessage->email }}" class="text-brand-dark hover:underline">{{ $contactMessage->email }}</a></dd>
                    </div>
                    @if ($contactMessage->phone)
                        <div class="flex items-start gap-3">
                            <dt class="w-28 shrink-0 font-bold text-ink">Telefon</dt>
                            <dd><a href="tel:{{ $contactMessage->phone }}" class="text-brand-dark hover:underline">{{ $contactMessage->phone }}</a></dd>
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

            <div class="p-6 rounded-md bg-white">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Wiadomość</h2>
                <p class="whitespace-pre-wrap text-sm leading-relaxed text-ink">{{ $contactMessage->message }}</p>
            </div>

            <div class="p-5 rounded-md bg-white">
                <a href="mailto:{{ $contactMessage->email }}?subject={{ urlencode('Re: ' . ($contactMessage->subject ?: 'Twoja wiadomość')) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-dark">
                    <i class="fa-solid fa-reply" aria-hidden="true"></i>
                    Odpowiedz przez klienta pocztowego
                </a>
            </div>
        </div>

        {{-- Metadane --}}
        <div class="space-y-4">
            <div class="p-5 text-sm rounded-md bg-white">
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
                            <dd class="mt-0.5 inline-flex items-center gap-1 text-xs font-bold text-ink">
                                <i class="fa-solid fa-check text-[10px]" aria-hidden="true"></i>
                                Odpowiedziano {{ $contactMessage->replied_at->format('d.m.Y') }}
                            </dd>
                        @elseif ($contactMessage->read_at)
                            <dd class="mt-0.5 inline-flex items-center gap-1 text-xs font-bold text-muted">
                                Przeczytano
                            </dd>
                        @else
                            <dd class="mt-0.5 inline-flex items-center gap-1 text-xs font-bold text-brand-dark"><i class="fa-solid fa-circle-info mr-1 text-brand-dark" aria-hidden="true"></i>
                                Nowe
                            </dd>
                        @endif
                    </div>
                    <div>
                        <dt class="mt-3 text-xs text-muted">E-mail powiadomienia</dt>
                        @if ($contactMessage->email_sent_at)
                            <dd class="mt-0.5 inline-flex items-center gap-1 text-xs font-bold text-ink">
                                <i class="fa-solid fa-paper-plane text-[10px]" aria-hidden="true"></i>
                                Wysłano {{ $contactMessage->email_sent_at->format('d.m.Y H:i') }}
                            </dd>
                        @else
                            <dd class="mt-0.5 inline-flex items-center gap-1 text-xs font-bold text-ink">
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
