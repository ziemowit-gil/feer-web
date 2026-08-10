@extends('admin.layout')

@section('title', 'Zgłoszenie: ' . $cooperationRequest->name)

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.wspolpraca-zgloszenia.index') }}"
               class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-muted hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            </a>
            <h1 class="text-lg font-bold text-ink">Zgłoszenie współpracy</h1>
        </div>
        <form method="POST" action="{{ route('admin.wspolpraca-zgloszenia.destroy', $cooperationRequest) }}"
              data-confirm="Usunąć to zgłoszenie? Operacja jest nieodwracalna.">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-red-500">
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                Usuń
            </button>
        </form>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Dane główne --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Dane kontaktowe</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <dt class="w-32 shrink-0 font-bold text-ink">Imię i nazwisko</dt>
                        <dd>{{ $cooperationRequest->name }}</dd>
                    </div>
                    <div class="flex items-start gap-3">
                        <dt class="w-32 shrink-0 font-bold text-ink">E-mail</dt>
                        <dd><a href="mailto:{{ $cooperationRequest->email }}" class="text-brand hover:underline">{{ $cooperationRequest->email }}</a></dd>
                    </div>
                    @if ($cooperationRequest->organization)
                    <div class="flex items-start gap-3">
                        <dt class="w-32 shrink-0 font-bold text-ink">Organizacja</dt>
                        <dd>{{ $cooperationRequest->organization }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            @if ($cooperationRequest->sector || !empty($cooperationRequest->cooperation_types))
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-muted">Zainteresowania</h2>
                @if ($cooperationRequest->sector)
                    <p class="mb-3 text-sm"><span class="font-bold text-ink">Sektor:</span> {{ $cooperationRequest->sector }}</p>
                @endif
                @if (!empty($cooperationRequest->cooperation_types))
                    <p class="mb-2 text-sm font-bold text-ink">Formy współpracy:</p>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($cooperationRequest->cooperation_types as $type)
                            <li class="rounded-full bg-brand-light px-3 py-1 text-xs font-medium text-brand">{{ $type }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            @endif

            @if ($cooperationRequest->message)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Wiadomość</h2>
                <p class="whitespace-pre-wrap text-sm leading-relaxed text-ink">{{ $cooperationRequest->message }}</p>
            </div>
            @endif
        </div>

        {{-- Metadane --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm text-sm">
                <h2 class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Szczegóły</h2>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-xs text-muted">Strona</dt>
                        <dd class="font-medium text-ink">{{ $cooperationRequest->page->title ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">Data zgłoszenia</dt>
                        <dd class="font-medium text-ink">{{ $cooperationRequest->created_at->locale('pl')->isoFormat('D MMMM YYYY, HH:mm') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">Status</dt>
                        <dd>
                            @if ($cooperationRequest->read_at)
                                <span class="inline-flex items-center gap-1 text-xs text-muted">
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    Przeczytane {{ $cooperationRequest->read_at->locale('pl')->diffForHumans() }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-brand">
                                    <i class="fa-solid fa-circle text-[8px]" aria-hidden="true"></i>
                                    Nowe
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <a href="mailto:{{ $cooperationRequest->email }}?subject=Re: Zgłoszenie współpracy"
               class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <i class="fa-solid fa-reply" aria-hidden="true"></i>
                Odpowiedz e-mailem
            </a>
        </div>
    </div>
@endsection
