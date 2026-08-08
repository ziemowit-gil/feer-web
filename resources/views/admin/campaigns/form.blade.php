@extends('admin.layout')

@section('title', $campaign->exists ? 'Edytuj kampanię' : 'Nowa kampania')

@section('content')
    <form method="POST" action="{{ $campaign->exists ? route('admin.kampanie.update', $campaign) : route('admin.kampanie.store') }}"
        enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($campaign->exists) @method('PUT') @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- ===== LEWA KOLUMNA (treść) ===== --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-base font-bold">Podstawowe</h2>

                    <div>
                        <label for="title" class="mb-1 block text-sm font-bold">Tytuł kampanii</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $campaign->title) }}" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="slug" class="mb-1 block text-sm font-bold">Slug (URL)</label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted">/kampanie/</span>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', $campaign->slug) }}"
                                placeholder="zostanie wygenerowany z tytułu"
                                class="flex-1 rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                        @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="excerpt" class="mb-1 block text-sm font-bold">Krótki opis</label>
                        <textarea id="excerpt" name="excerpt" rows="2"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('excerpt', $campaign->excerpt) }}</textarea>
                        @error('excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="content" class="mb-1 block text-sm font-bold">Treść</label>
                        <textarea id="content" name="content" rows="8"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('content', $campaign->content) }}</textarea>
                        @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-base font-bold">Zbiórka</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="goal_amount" class="mb-1 block text-sm font-bold">Cel (zł)</label>
                            <input type="number" id="goal_amount" name="goal_amount" min="0"
                                value="{{ old('goal_amount', $campaign->goal_amount) }}" required
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('goal_amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="collected_amount" class="mb-1 block text-sm font-bold">Zebrano dotychczas (zł)</label>
                            <input type="number" id="collected_amount" name="collected_amount" min="0"
                                value="{{ old('collected_amount', $campaign->collected_amount) }}" required
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('collected_amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="donation_url" class="mb-1 block text-sm font-bold">
                            Link do płatności <span class="font-normal text-muted">(opcjonalnie – zewnętrzna platforma)</span>
                        </label>
                        <input type="url" id="donation_url" name="donation_url"
                            value="{{ old('donation_url', $campaign->donation_url) }}"
                            placeholder="https://pomagam.pl/..."
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('donation_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="starts_at" class="mb-1 block text-sm font-bold">Data rozpoczęcia</label>
                            <input type="date" id="starts_at" name="starts_at"
                                value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d')) }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="ends_at" class="mb-1 block text-sm font-bold">Data zakończenia</label>
                            <input type="date" id="ends_at" name="ends_at"
                                value="{{ old('ends_at', $campaign->ends_at?->format('Y-m-d')) }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('ends_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-base font-bold">SEO</h2>
                    <div>
                        <label for="meta_title" class="mb-1 block text-sm font-bold">Meta tytuł</label>
                        <input type="text" id="meta_title" name="meta_title"
                            value="{{ old('meta_title', $campaign->meta_title) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label for="meta_description" class="mb-1 block text-sm font-bold">Meta opis</label>
                        <textarea id="meta_description" name="meta_description" rows="2"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('meta_description', $campaign->meta_description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ===== PRAWA KOLUMNA (opcje) ===== --}}
            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 text-base font-bold">Publikacja</h2>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $campaign->is_published) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Opublikowana</span>
                    </label>

                    <div class="mt-4">
                        <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                        <input type="number" id="order" name="order" min="0"
                            value="{{ old('order', $campaign->order) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>

                    @if ($campaign->exists && $campaign->goal_amount > 0)
                        <div class="mt-4 rounded-lg bg-gray-50 p-4">
                            <p class="mb-1 text-xs font-bold text-muted uppercase tracking-wide">Aktualny postęp</p>
                            <div class="mb-1 h-3 w-full overflow-hidden rounded-full bg-gray-200">
                                <div class="h-full rounded-full {{ $campaign->isGoalReached() ? 'bg-green-500' : 'bg-brand' }}"
                                    style="width: {{ $campaign->progressPercent() }}%"></div>
                            </div>
                            <p class="text-sm font-bold">
                                {{ number_format($campaign->collected_amount, 0, ',', ' ') }} zł
                                <span class="font-normal text-muted">/ {{ number_format($campaign->goal_amount, 0, ',', ' ') }} zł</span>
                                <span class="{{ $campaign->isGoalReached() ? 'text-green-700' : 'text-brand' }}">
                                    ({{ $campaign->progressPercent() }}%)
                                </span>
                            </p>
                        </div>
                    @endif
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 text-base font-bold">Zdjęcie</h2>

                    @if ($campaign->imageUrl)
                        <img src="{{ $campaign->imageUrl }}" alt="Zdjęcie kampanii" class="mb-3 w-full rounded object-cover" style="max-height:200px;">
                        <label class="flex items-center gap-2 text-sm text-red-600">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300">
                            Usuń zdjęcie
                        </label>
                    @endif

                    <div class="mt-3">
                        <label for="image" class="mb-1 block text-sm font-bold">{{ $campaign->imageUrl ? 'Zmień zdjęcie' : 'Dodaj zdjęcie' }}</label>
                        <input type="file" id="image" name="image" accept="image/*"
                            class="w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-gray-700">
                        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="rounded bg-brand px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                {{ $campaign->exists ? 'Zapisz zmiany' : 'Utwórz kampanię' }}
            </button>
            <a href="{{ route('admin.kampanie.index') }}" class="rounded border border-gray-300 bg-white px-6 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">
                Anuluj
            </a>
        </div>
    </form>
@endsection
