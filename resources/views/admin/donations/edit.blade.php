@extends('admin.layout')

@section('title', 'Wpłata ' . $donation->amountLabel())

@php
    use App\Models\Donation;
    $field = 'w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark';
@endphp

@section('content')

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <div class="rounded-md bg-white p-6">
            <h2 class="mb-4 text-base font-bold">Dane wpłaty</h2>
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                <dt class="font-bold">Kwota</dt><dd>{{ $donation->amountLabel() }}</dd>
                <dt class="font-bold">Zbiórka</dt><dd>{{ $donation->campaign?->title ?? 'Cele statutowe' }}</dd>
                <dt class="font-bold">Rodzaj</dt><dd>{{ Donation::TYPES[$donation->type ?? 'one_time'] ?? '' }}</dd>
                <dt class="font-bold">Źródło</dt><dd>{{ Donation::SOURCES[$donation->source ?? 'online'] ?? '' }}@if ($donation->creator) ({{ $donation->creator->name }})@endif</dd>
                <dt class="font-bold">Utworzona</dt><dd>{{ $donation->created_at?->format('d.m.Y H:i') }}</dd>
                <dt class="font-bold">Zaksięgowana</dt><dd>{{ $donation->paid_at?->format('d.m.Y H:i') ?? '—' }}</dd>
                @if ($donation->refunded_at)<dt class="font-bold">Zwrot</dt><dd>{{ $donation->refunded_at->format('d.m.Y H:i') }}</dd>@endif
                <dt class="font-bold">Nr transakcji P24</dt><dd>{{ $donation->p24_order_id ?? '—' }}</dd>
                <dt class="font-bold">Darczyńca</dt><dd>{{ $donation->fullName() }}@if ($donation->company_name)<br>{{ $donation->company_name }}@endif @if ($donation->nip)<br>NIP {{ $donation->nip }}@endif</dd>
                <dt class="font-bold">Kontakt</dt><dd>{{ $donation->email }}@if ($donation->phone)<br>{{ $donation->phone }}@endif</dd>
                <dt class="font-bold">Na liście</dt><dd>{{ $donation->publicLabel() }}</dd>
                @if ($donation->dedication)<dt class="font-bold">Dedykacja</dt><dd>„{{ $donation->dedication }}” ({{ $donation->dedication_public ? 'publiczna' : 'niepubliczna' }})</dd>@endif
                <dt class="font-bold">Zgody</dt><dd>RODO{{ $donation->consent_newsletter ? ', newsletter' : '' }}</dd>
                <dt class="font-bold">Podziękowanie</dt><dd>{{ $donation->thanked_at ? 'wysłane '.$donation->thanked_at->format('d.m.Y H:i') : 'niewysłane' }}</dd>
                @if (! $donation->isManual())<dt class="font-bold">SZO</dt><dd>{{ $donation->szo_synced_at ? 'przekazana '.$donation->szo_synced_at->format('d.m.Y H:i') : ($donation->szo_error ? 'błąd: '.$donation->szo_error : '—') }}</dd>@endif
            </dl>

            @if ($donation->isPaid())
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('donation.receipt', $donation) }}" class="rounded bg-gray-100 px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Potwierdzenie PDF</a>
                    @if ($donation->email)
                        <form method="POST" action="{{ route('admin.darowizny.thanks', $donation) }}">
                            @csrf
                            <button type="submit" class="rounded bg-gray-100 px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light"><i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $donation->thanked_at ? 'Wyślij podziękowanie ponownie' : 'Wyślij podziękowanie' }}</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.darowizny.update', $donation) }}" class="space-y-5 rounded-md bg-white p-6">
            @csrf
            @method('PUT')
            <h2 class="text-base font-bold">Status i notatka</h2>
            <div>
                <label for="status" class="mb-1 block text-sm font-bold">Status</label>
                <select id="status" name="status" class="{{ $field }}" aria-describedby="status-help">
                    @foreach (Donation::STATUSES as $value => $text)
                        <option value="{{ $value }}" @selected(old('status', $donation->status) === $value)>{{ $text }}</option>
                    @endforeach
                </select>
                @error('status') <p id="status-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                <p id="status-help" class="mt-1 text-xs text-ink">„Zwrócona” odejmuje wpłatę od paska zbiórki. Zwrot pieniędzy w Przelewy24 wykonuje się w panelu P24.</p>
            </div>
            <label class="flex min-h-11 items-center gap-3 text-sm">
                <input type="checkbox" name="send_thanks" value="1" class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span>Przy zmianie na „Opłacona” wyślij podziękowanie</span>
            </label>
            <div>
                <label for="admin_note" class="mb-1 block text-sm font-bold">Notatka wewnętrzna</label>
                <textarea id="admin_note" name="admin_note" rows="4" maxlength="2000" class="{{ $field }}">{{ old('admin_note', $donation->admin_note) }}</textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="rounded bg-brand-dark px-6 py-2.5 text-sm font-bold text-white hover:bg-ink">Zapisz</button>
                <a href="{{ route('admin.darowizny.index') }}" class="rounded-md bg-gray-100 px-6 py-2.5 text-sm font-bold text-ink hover:bg-brand-light">Wróć do listy</a>
            </div>
        </form>
    </div>
@endsection
