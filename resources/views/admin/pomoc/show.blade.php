@extends('admin.layout')

@section('title', $ticket->subject)

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('admin.pomoc.index') }}" class="text-xs font-semibold text-muted hover:text-brand">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wszystkie zgłoszenia
                </a>
                <h1 class="mt-1 text-lg font-bold text-ink">{{ $ticket->subject }}</h1>
            </div>
            <span class="rounded-full px-2 py-0.5 text-xs font-bold
                {{ in_array($ticket->status, ['closed', 'resolved']) ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700' }}">
                {{ $ticket->statusLabel() }}
            </span>
        </div>

        @if (session('success'))
            <p class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('success') }}</p>
        @endif

        @unless ($ticket->submitted())
            <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                @if ($ticket->submit_error)
                    Nie udało się jeszcze wysłać do Helpdesku ({{ $ticket->submit_error }}) — spróbujemy ponownie automatycznie.
                @else
                    Zgłoszenie nie zostało jeszcze wysłane do Helpdesku — wyśle się automatycznie, gdy integracja będzie dostępna.
                @endif
            </p>
        @endunless

        <div class="space-y-4">
            @foreach ($ticket->messages as $message)
                <div class="rounded-lg border p-4 {{ $message->sender_type === 'agent' ? 'border-brand-light bg-brand-light/30' : 'border-gray-200 bg-white' }}">
                    <div class="mb-1 flex items-center justify-between text-xs text-muted">
                        <span class="font-bold {{ $message->sender_type === 'agent' ? 'text-brand' : 'text-ink' }}">
                            {{ $message->sender_type === 'agent' ? 'Obsługa Helpdesku' : ($ticket->submittedBy?->name ?? 'Ty') }}
                        </span>
                        <span>{{ $message->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    <p class="whitespace-pre-line text-sm text-ink">{{ $message->body }}</p>
                </div>
            @endforeach
        </div>

        @unless (in_array($ticket->status, ['closed', 'resolved']))
            <form method="POST" action="{{ route('admin.pomoc.reply', $ticket) }}" class="mt-6 space-y-3">
                @csrf
                <label for="message" class="block text-sm font-semibold text-ink">Odpowiedz</label>
                <textarea id="message" name="message" rows="4" required maxlength="10000"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        Wyślij
                    </button>
                </div>
            </form>
        @endunless
    </div>
@endsection
