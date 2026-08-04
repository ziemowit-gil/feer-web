@extends('admin.layout')

@section('title', $report->exists ? 'Edytuj sprawozdania za ' . $report->year . ' rok' : 'Nowy rok sprawozdawczy')

@section('content')
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Popraw poniższe błędy:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $report->exists ? route('admin.sprawozdania.update', $report) : route('admin.sprawozdania.store') }}"
        enctype="multipart/form-data"
        x-data="{ status: {
            substantive: {{ Js::from(old('substantive_status', $report->substantive_status ?? 'not_yet')) }},
            financial: {{ Js::from(old('financial_status', $report->financial_status ?? 'not_yet')) }}
        } }"
        class="max-w-2xl space-y-6 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($report->exists) @method('PUT') @endif

        <div class="flex flex-wrap items-end gap-6">
            <div>
                <label for="year" class="mb-1 block text-sm font-bold">Rok sprawozdawczy</label>
                <input type="number" id="year" name="year" min="1990" max="{{ now()->year + 1 }}" value="{{ old('year', $report->year) }}" required
                    class="w-32 rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
            </div>
            <label class="flex items-center gap-2 pb-2">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $report->is_published ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
                <span class="text-sm font-bold">Widoczny na stronie</span>
            </label>
        </div>

        {{-- Dwa sprawozdania: merytoryczne i finansowe --}}
        @foreach (\App\Models\AnnualReport::TYPES as $type => $label)
            <fieldset class="rounded-lg border border-gray-200 p-4">
                <legend class="px-2 text-sm font-bold text-ink">{{ $label }}</legend>

                <div class="mb-3">
                    <label for="{{ $type }}_status" class="mb-1 block text-sm font-bold">Status</label>
                    <select id="{{ $type }}_status" name="{{ $type }}_status" x-model="status.{{ $type }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                        @foreach (\App\Models\AnnualReport::STATUSES as $value => $optionLabel)
                            <option value="{{ $value }}">{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Plik PDF (gdy status = opublikowane) --}}
                <div x-show="status.{{ $type }} === 'published'" x-cloak>
                    @if ($report->exists && $report->fileUrlFor($type))
                        <p class="mb-1 text-sm">
                            Obecny plik:
                            <a href="{{ $report->fileUrlFor($type) }}" target="_blank" rel="noopener" class="font-bold text-brand hover:text-brand-dark">otwórz PDF</a>
                        </p>
                        <label class="mb-2 flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remove_{{ $type }}" value="1" class="rounded border-gray-300 text-red-600 focus-visible:ring-2 focus-visible:ring-red-600">
                            Usuń obecny plik
                        </label>
                    @endif
                    <label for="{{ $type }}_file" class="mb-1 block text-sm font-bold">{{ $report->exists && $report->fileUrlFor($type) ? 'Zmień plik PDF' : 'Plik PDF' }}</label>
                    <input type="file" id="{{ $type }}_file" name="{{ $type }}_file" accept="application/pdf"
                        class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                    <p class="mt-1 text-xs text-muted">PDF do 10 MB.</p>
                </div>

                {{-- Powód własny (gdy status = własny) --}}
                <div x-show="status.{{ $type }} === 'custom'" x-cloak>
                    <label for="{{ $type }}_reason" class="mb-1 block text-sm font-bold">Powód (wyświetlany na stronie)</label>
                    <textarea id="{{ $type }}_reason" name="{{ $type }}_reason" rows="2" maxlength="500"
                        class="w-full rounded border-gray-300 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">{{ old($type.'_reason', $report->{$type.'_reason'}) }}</textarea>
                </div>

                {{-- Podgląd automatycznego komunikatu dla pozostałych statusów --}}
                <p class="text-xs text-muted" x-show="status.{{ $type }} === 'not_yet'" x-cloak>
                    Na stronie pojawi się: „Sprawozdanie za {rok} rok nie zostało jeszcze opublikowane."
                </p>
                <p class="text-xs text-muted" x-show="status.{{ $type }} === 'soon'" x-cloak>
                    Na stronie pojawi się: „Dokumenty zostaną niebawem uzupełnione."
                </p>
                <p class="text-xs text-muted" x-show="status.{{ $type }} === 'not_required'" x-cloak>
                    Na stronie pojawi się: „Organizacja nie ma obowiązku składania tego sprawozdania."
                </p>
            </fieldset>
        @endforeach

        {{-- Dodatkowe pliki --}}
        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-2 text-sm font-bold text-ink">Dodatkowe pliki</legend>

            @if ($report->exists && $report->additionalFiles()->isNotEmpty())
                <ul class="mb-3 space-y-1">
                    @foreach ($report->additionalFiles() as $media)
                        <li class="flex items-center justify-between gap-3 rounded border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-brand hover:text-brand-dark">
                                <i class="fa-solid {{ $report->fileIcon($media) }}" aria-hidden="true"></i>
                                {{ $media->name }} <span class="text-xs text-muted">({{ $media->human_readable_size }})</span>
                            </a>
                            <label class="flex items-center gap-1.5 text-xs text-muted">
                                <input type="checkbox" name="remove_files[]" value="{{ $media->id }}" class="rounded border-gray-300 text-red-600 focus-visible:ring-2 focus-visible:ring-red-600">
                                Usuń
                            </label>
                        </li>
                    @endforeach
                </ul>
            @endif

            <label for="additional_files" class="mb-1 block text-sm font-bold">Dodaj pliki</label>
            <input type="file" id="additional_files" name="additional_files[]" multiple
                accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.csv,.zip,.jpg,.jpeg,.png"
                class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            <p class="mt-1 text-xs text-muted">Możesz wybrać kilka plików naraz (PDF, Word, Excel, obraz lub ZIP; każdy do 10 MB).</p>
        </fieldset>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.sprawozdania.index') }}" class="rounded text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</a>
        </div>
    </form>
@endsection
