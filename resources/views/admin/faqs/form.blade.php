@extends('admin.layout')

@section('title', $faq->exists ? 'Edytuj pytanie' : 'Nowe pytanie')

@php $action = $faq->exists ? route('admin.faq.update', $faq) : route('admin.faq.store'); @endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($faq->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold">Popraw poniższe pola:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <div>
                <label for="question" class="mb-1 block text-sm font-bold">Pytanie <span aria-hidden="true" class="text-red-600">*</span></label>
                <input type="text" id="question" name="question" value="{{ old('question', $faq->question) }}" required maxlength="255" placeholder="np. Jak mogę zostać wolontariuszem?"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="answer" class="mb-1 block text-sm font-bold">Odpowiedź <span aria-hidden="true" class="text-red-600">*</span></label>
                <textarea id="answer" name="answer" rows="6" required maxlength="5000" placeholder="Treść odpowiedzi. Nowe akapity i linie zostaną zachowane."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('answer', $faq->answer) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="category" class="mb-1 block text-sm font-bold">Kategoria <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="category" name="category" value="{{ old('category', $faq->category) }}" maxlength="120" placeholder="np. Szkolenia, Wolontariat, Darowizny"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Pytania z tą samą kategorią grupują się w sekcję na stronie FAQ.</p>
                </div>
                <div>
                    <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                    <input type="number" id="order" name="order" value="{{ old('order', $faq->order ?? 0) }}" min="0"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $faq->is_published) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-sm font-bold">Opublikowane (widoczne na stronie)</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.faq.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
        </div>
    </form>
@endsection
