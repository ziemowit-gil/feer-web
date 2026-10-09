@extends('admin.layout')
@section('title', $subscriber->exists ? 'Edycja subskrybenta' : 'Nowy subskrybent')
@section('content')
    @include('newsletter::admin.partials.flash')
    <form method="POST" action="{{ $subscriber->exists ? route('admin.newsletter.subskrybenci.update', $subscriber) : route('admin.newsletter.subskrybenci.store') }}" class="max-w-3xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf @if ($subscriber->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="email" class="mb-1 block text-sm font-bold">Adres e-mail</label><input id="email" name="email" type="email" required value="{{ old('email', $subscriber->email) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
            <div><label for="name" class="mb-1 block text-sm font-bold">Imię / nazwa</label><input id="name" name="name" value="{{ old('name', $subscriber->name) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
            <div><label for="phone" class="mb-1 block text-sm font-bold">Telefon (E.164)</label><input id="phone" name="phone" value="{{ old('phone', $subscriber->phone) }}" placeholder="+48…" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
            <div><label for="status" class="mb-1 block text-sm font-bold">Status</label><select id="status" name="status" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">@foreach (\App\Models\Subscriber::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $subscriber->status) === $k)>{{ $l }}</option>@endforeach</select>
                <p class="mt-1 text-xs text-muted">„Aktywny” bez double opt-in wymaga udokumentowanej zgody — wpisz jej źródło poniżej. „Oczekuje” wyśle e-mail potwierdzający.</p></div>
        </div>
        <div><label for="consent_note" class="mb-1 block text-sm font-bold">Źródło zgody (gdy ustawiasz „Aktywny” ręcznie)</label><input id="consent_note" name="consent_note" value="{{ old('consent_note') }}" placeholder="np. formularz papierowy z konferencji 12.09.2026" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
        <fieldset><legend class="mb-1 text-sm font-bold">Tematy</legend><div class="flex flex-wrap gap-3">@foreach (\App\Models\Subscriber::$availableTopics as $k => $l)<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="topics[]" value="{{ $k }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($k, old('topics', $subscriber->topics ?? []), true))> {{ $l }}</label>@endforeach</div></fieldset>
        <fieldset><legend class="mb-1 text-sm font-bold">Kanały</legend><div class="flex flex-wrap gap-3">@foreach (\App\Models\Subscriber::CHANNELS as $k => $l)<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="channels[]" value="{{ $k }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked($k === 'email' || in_array($k, old('channels', $subscriber->channels ?? []), true)) @disabled($k === 'email')> {{ $l }}</label>@endforeach</div></fieldset>
        <fieldset><legend class="mb-1 text-sm font-bold">Listy</legend><div class="flex flex-wrap gap-3">@forelse ($lists as $l)<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="lists[]" value="{{ $l->id }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($l->id, old('lists', $subscriber->exists ? $subscriber->lists->pluck('id')->all() : []), true))> {{ $l->name }}</label>@empty<span class="text-sm text-muted">Brak list.</span>@endforelse</div></fieldset>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="tags" class="mb-1 block text-sm font-bold">Tagi (po przecinku)</label><input id="tags" name="tags" value="{{ old('tags', implode(', ', $subscriber->tags ?? [])) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
            <div><label for="timezone" class="mb-1 block text-sm font-bold">Strefa czasowa</label><input id="timezone" name="timezone" value="{{ old('timezone', $subscriber->timezone ?? 'Europe/Warsaw') }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
        </div>
        <div class="flex gap-2 border-t border-gray-200 pt-4">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ $subscriber->exists ? route('admin.newsletter.subskrybenci.show', $subscriber) : route('admin.newsletter.subskrybenci.index') }}" class="rounded border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Anuluj</a>
            @if ($subscriber->exists)
            </form><form method="POST" action="{{ route('admin.newsletter.subskrybenci.destroy', $subscriber) }}" class="ml-auto" onsubmit="return confirm('Usunąć subskrybenta razem z historią?')">@csrf @method('DELETE')<button class="rounded border border-red-300 bg-white px-5 py-2 text-sm font-bold text-red-800 hover:bg-red-50">Usuń</button>
            @endif
        </div>
    </form>
@endsection
