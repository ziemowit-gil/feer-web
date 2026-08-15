@extends('layouts.site')

@section('title', $form->title . ' — ' . $settings->site_name)
@section('meta_description', $form->description)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800" role="alert">
            <i class="fa-solid fa-circle-check mr-2" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    <h1 class="mb-2 text-2xl font-bold text-ink">{{ $form->title }}</h1>
    @if ($form->description)
        <p class="mb-6 text-muted">{{ $form->description }}</p>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">
            <p class="font-bold">Proszę poprawić błędy:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('formularz.store', $form->slug) }}"
        class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
        novalidate>
        @csrf

        @php $fields = $form->normalizedFields(); @endphp

        @forelse ($fields as $field)
            @php
                $id       = 'field-' . $field['key'];
                $name     = 'data[' . $field['key'] . ']';
                $label    = $field['label'];
                $required = $field['required'] ?? false;
                $type     = $field['type'] ?? 'text';
                $old      = old('data.' . $field['key']);
            @endphp

            <div>
                <label for="{{ $id }}"
                    class="mb-1 block text-sm font-semibold text-ink">
                    {{ $label }}
                    @if ($required)
                        <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">(wymagane)</span>
                    @endif
                </label>

                @if ($field['help_text'] ?? null)
                    <p id="{{ $id }}-help" class="mb-1 text-xs text-muted">{{ $field['help_text'] }}</p>
                @endif

                @if ($type === 'textarea')
                    <textarea id="{{ $id }}" name="{{ $name }}"
                        rows="4"
                        {{ $required ? 'required' : '' }}
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        {{ isset($field['help_text']) ? 'aria-describedby="' . $id . '-help"' : '' }}
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand @error('data.' . $field['key']) border-red-400 @enderror">{{ $old }}</textarea>

                @elseif ($type === 'select')
                    <select id="{{ $id }}" name="{{ $name }}"
                        {{ $required ? 'required' : '' }}
                        {{ isset($field['help_text']) ? 'aria-describedby="' . $id . '-help"' : '' }}
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand @error('data.' . $field['key']) border-red-400 @enderror">
                        <option value="">— wybierz —</option>
                        @foreach (array_map('trim', explode(',', $field['options'] ?? '')) as $opt)
                            @if (filled($opt))
                                <option value="{{ $opt }}" {{ $old === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endif
                        @endforeach
                    </select>

                @elseif ($type === 'radio')
                    <fieldset class="space-y-1.5" {{ isset($field['help_text']) ? 'aria-describedby="' . $id . '-help"' : '' }}>
                        <legend class="sr-only">{{ $label }}</legend>
                        @foreach (array_map('trim', explode(',', $field['options'] ?? '')) as $opt)
                            @if (filled($opt))
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="{{ $name }}" value="{{ $opt }}"
                                        {{ $required ? 'required' : '' }}
                                        {{ $old === $opt ? 'checked' : '' }}
                                        class="border-gray-300 text-brand focus:ring-brand">
                                    {{ $opt }}
                                </label>
                            @endif
                        @endforeach
                    </fieldset>

                @elseif ($type === 'checkbox')
                    <label class="flex items-start gap-2 text-sm"
                        {{ isset($field['help_text']) ? 'aria-describedby="' . $id . '-help"' : '' }}>
                        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1"
                            {{ $required ? 'required' : '' }}
                            {{ $old ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand @error('data.' . $field['key']) border-red-400 @enderror">
                        <span>{{ $label }}@if ($required)<span class="text-red-500 ml-0.5" aria-hidden="true">*</span>@endif</span>
                    </label>

                @else
                    <input id="{{ $id }}" name="{{ $name }}"
                        type="{{ $type }}"
                        value="{{ $old }}"
                        {{ $required ? 'required' : '' }}
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        {{ isset($field['help_text']) ? 'aria-describedby="' . $id . '-help"' : '' }}
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand @error('data.' . $field['key']) border-red-400 @enderror">
                @endif

                @error('data.' . $field['key'])
                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @empty
            <p class="text-sm text-muted">Ten formularz nie zawiera jeszcze żadnych pól.</p>
        @endforelse

        @if (count($fields) > 0)
            <div class="pt-2">
                <button type="submit"
                    class="w-full rounded-lg bg-brand px-5 py-3 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 sm:w-auto">
                    Wyślij zgłoszenie
                </button>
            </div>
        @endif
    </form>
</div>
@endsection
