@extends('admin.layout')

@section('title', 'Edytuj szablon: ' . $template->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.mail-templates.index') }}"
           class="inline-flex items-center gap-1 text-sm text-muted hover:text-gray-900">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy szablonów
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.mail-templates.update', $template) }}">
                @csrf
                @method('PUT')

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h2 class="font-semibold">{{ $template->name }}</h2>
                        <p class="mt-0.5 text-xs text-muted">Klucz: <code class="rounded bg-gray-100 px-1">{{ $template->slug }}</code></p>
                    </div>

                    <div class="space-y-4 p-4">
                        <div>
                            <label for="subject" class="mb-1 block text-sm font-medium">Temat wiadomości</label>
                            <input type="text" id="subject" name="subject"
                                   value="{{ old('subject', $template->subject) }}"
                                   class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('subject') border-red-500 @enderror"
                                   required>
                            @error('subject')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="body" class="mb-1 block text-sm font-medium">Treść wiadomości (HTML)</label>
                            <textarea id="body" name="body" rows="16"
                                      class="w-full rounded border border-gray-300 px-3 py-2 font-mono text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('body') border-red-500 @enderror"
                                      required>{{ old('body', $template->body) }}</textarea>
                            @error('body')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-muted">Możesz używać znaczników HTML. Zmienne zastępuj w postaci <code class="rounded bg-gray-100 px-0.5">{{'{{'}}nazwa_zmiennej{{'}}'}}</code>.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
                        <a href="{{ route('admin.mail-templates.index') }}"
                           class="text-sm text-muted hover:text-gray-900">Anuluj</a>
                        <button type="submit"
                                class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                            Zapisz szablon
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if ($template->variables)
            <div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-3 text-sm font-semibold">Dostępne zmienne</h3>
                    <dl class="space-y-2">
                        @foreach ($template->variables as $key => $description)
                            <div>
                                <dt>
                                    <code class="rounded bg-blue-50 px-1.5 py-0.5 text-xs font-mono text-blue-700">{{'{{'}}{{ $key }}{{'}}'}}</code>
                                </dt>
                                <dd class="mt-0.5 text-xs text-muted">{{ $description }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if (session('success'))
                    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
