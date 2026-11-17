@extends('admin.layout')

@section('title', 'Dodaj wpłatę ręcznie')

@php
    use App\Models\Donation;
    $field = 'w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark';
    $label = 'mb-1 block text-sm font-bold';
@endphp

@section('content')
    <form method="POST" action="{{ route('admin.darowizny.store') }}" class="max-w-3xl space-y-6">
        @csrf
        <div class="space-y-5 rounded-md bg-white p-6">
            <p class="text-sm text-ink">Np. przelew tradycyjny, wpłata gotówkowa albo z puszki. Wpłata od razu jest zaksięgowana i liczy się do paska zbiórki. Nie trafia do SZO (tam przelewy przychodzą z importu wyciągów).</p>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="amount" class="{{ $label }}">Kwota (zł) <span aria-hidden="true">*</span></label>
                    <input type="text" id="amount" name="amount" inputmode="decimal" value="{{ old('amount') }}" required class="{{ $field }}" @error('amount') aria-invalid="true" aria-describedby="amount-error" @enderror>
                    @error('amount') <p id="amount-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="paid_at" class="{{ $label }}">Data wpłaty <span aria-hidden="true">*</span></label>
                    <input type="date" id="paid_at" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $field }}" @error('paid_at') aria-invalid="true" aria-describedby="paid_at-error" @enderror>
                    @error('paid_at') <p id="paid_at-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="type" class="{{ $label }}">Rodzaj</label>
                    <select id="type" name="type" class="{{ $field }}">
                        @foreach (Donation::TYPES as $value => $text)
                            <option value="{{ $value }}" @selected(old('type', $donation->type) === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="campaign_id" class="{{ $label }}">Zbiórka</label>
                <select id="campaign_id" name="campaign_id" class="{{ $field }}">
                    <option value="">Ogólna darowizna (cele statutowe)</option>
                    @foreach ($campaigns as $c)
                        <option value="{{ $c->id }}" @selected((string) old('campaign_id', $donation->campaign_id) === (string) $c->id)>{{ $c->title }}</option>
                    @endforeach
                </select>
                @error('campaign_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ([['first_name', 'Imię', 'text'], ['last_name', 'Nazwisko', 'text'], ['company_name', 'Firma lub organizacja', 'text'], ['nip', 'NIP', 'text'], ['email', 'E-mail', 'email'], ['phone', 'Telefon', 'tel']] as [$name, $text, $inputType])
                    <div>
                        <label for="{{ $name }}" class="{{ $label }}">{{ $text }}</label>
                        <input type="{{ $inputType }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name) }}" class="{{ $field }}" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
                        @error($name) <p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-ink">Wymagane jest imię albo nazwa firmy.</p>

            <div>
                <label for="dedication" class="{{ $label }}">Dedykacja lub komentarz</label>
                <textarea id="dedication" name="dedication" rows="2" maxlength="300" class="{{ $field }}">{{ old('dedication') }}</textarea>
            </div>
            <div>
                <label for="admin_note" class="{{ $label }}">Notatka wewnętrzna (np. nr wyciągu)</label>
                <textarea id="admin_note" name="admin_note" rows="2" maxlength="2000" class="{{ $field }}">{{ old('admin_note') }}</textarea>
            </div>

            <div class="space-y-1">
                @foreach ([['is_anonymous', 'Na publicznej liście wpłat jako „Wpłata anonimowa”', true], ['dedication_public', 'Pokaż dedykację na liście wpłat', false], ['send_thanks', 'Wyślij e-mail z podziękowaniem (gdy podano e-mail)', false]] as [$name, $text, $default])
                    <label class="flex min-h-11 items-center gap-3 text-sm">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $default)) class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>{{ $text }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded bg-brand-dark px-6 py-2.5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Dodaj wpłatę</button>
            <a href="{{ route('admin.darowizny.index') }}" class="rounded-md bg-white px-6 py-2.5 text-sm font-bold text-ink hover:bg-brand-light">Anuluj</a>
        </div>
    </form>
@endsection
