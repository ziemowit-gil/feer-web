@extends('layouts.site')

@section('title', 'Subskrybuj powiadomienia — ' . $siteSettings->site_name)
@section('meta_description', 'Zapisz się na tematyczne powiadomienia e-mail i bądź na bieżąco z tym, co Cię interesuje.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Subskrybuj powiadomienia', 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $feer = ($siteSettings->site_template ?? 'default') === 'feer';
        $inp = $feer ? 'w-full rounded-t-md rounded-b-none border-0 border-b-2 border-gray-500 bg-gray-100 text-base text-ink focus:border-brand focus:bg-white focus:outline-none focus:ring-0' : 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand';
        $btn = $feer ? 'rounded-md' : 'rounded';
        $linkCls = $feer ? 'text-brand-dark underline underline-offset-4 hover:text-ink' : 'text-brand hover:text-brand-dark';
    @endphp
    <section class="mx-auto max-w-xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Powiadomienia e-mail</h1>
        @if ($feer)<span class="mb-5 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
        <p class="mb-8 text-muted">Wybierz tematy, o&nbsp;których chcesz być informowany/a. Na Twój adres wyślemy wiadomość z&nbsp;linkiem potwierdzającym.</p>

        @if ($errors->any())
            <div role="alert" class="mb-6 {{ $feer ? 'rounded-md bg-red-50 text-red-900' : 'rounded-lg border border-red-200 bg-red-50 text-red-700' }} p-4 text-sm">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('subskrypcje.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-bold">Adres e-mail <span aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                    class="{{ $inp }}"
                    aria-describedby="email-hint">
                <p id="email-hint" class="mt-1 text-xs text-muted">Używamy go wyłącznie do wysyłki powiadomień.</p>
                @error('email') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-bold">Imię lub pseudonim <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="given-name"
                    class="{{ $inp }}">
                @error('name') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
            </div>

            <fieldset>
                <legend class="mb-3 text-sm font-bold">Tematy powiadomień <span aria-hidden="true">*</span></legend>
                @error('topics') <p class="mb-2 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                <div class="space-y-3">
                    @foreach ($topics as $key => $label)
                        <label class="flex cursor-pointer items-start gap-3 {{ $feer ? 'rounded-md bg-gray-50 hover:bg-gray-100' : 'rounded-lg border border-gray-200 hover:border-brand hover:bg-brand/5' }} p-3 transition
                            {{ in_array($key, old('topics', [])) ? ($feer ? 'bg-brand-light' : 'border-brand bg-brand/5') : '' }}">
                            <input type="checkbox" name="topics[]" value="{{ $key }}"
                                {{ in_array($key, old('topics', [])) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <button type="submit"
                class="{{ $btn }} bg-brand px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Zapisuję się
            </button>
        </form>
    </section>
@endsection
