@extends('layouts.site')

@section('title', 'Subskrybuj powiadomienia — ' . $siteSettings->site_name)
@section('meta_description', 'Zapisz się na tematyczne powiadomienia e-mail i bądź na bieżąco z tym, co Cię interesuje.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Subskrybuj powiadomienia', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Powiadomienia e-mail</h1>
        <p class="mb-8 text-muted">Wybierz tematy, o&nbsp;których chcesz być informowany/a. Na Twój adres wyślemy wiadomość z&nbsp;linkiem potwierdzającym.</p>

        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
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
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"
                    aria-describedby="email-hint">
                <p id="email-hint" class="mt-1 text-xs text-muted">Używamy go wyłącznie do wysyłki powiadomień.</p>
                @error('email') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-bold">Imię lub pseudonim <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="given-name"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('name') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
            </div>

            <fieldset>
                <legend class="mb-3 text-sm font-bold">Tematy powiadomień <span aria-hidden="true">*</span></legend>
                @error('topics') <p class="mb-2 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                <div class="space-y-3">
                    @foreach ($topics as $key => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-brand hover:bg-brand/5
                            {{ in_array($key, old('topics', [])) ? 'border-brand bg-brand/5' : '' }}">
                            <input type="checkbox" name="topics[]" value="{{ $key }}"
                                {{ in_array($key, old('topics', [])) ? 'checked' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <button type="submit"
                class="rounded bg-brand px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Zapisuję się
            </button>
        </form>
    </section>
@endsection
