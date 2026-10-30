{{-- Schemat kolorów panelu (User::ADMIN_SCHEMES): radia z opisem i próbką kolorów (próbka dekoracyjna — nazwa i opis wystarczą). --}}
@php $current = old('admin_scheme', auth()->user()->adminScheme()); @endphp
<form method="POST" action="{{ route('profile.scheme') }}" class="space-y-3">
    @csrf
    @method('PATCH')

    <fieldset>
        <legend class="text-sm font-bold text-ink">Schemat kolorów panelu</legend>
        <div class="mt-3 space-y-2">
            @foreach (\App\Models\User::ADMIN_SCHEMES as $value => [$label, $hint, $swatch])
                <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-gray-50 p-3 transition-colors hover:bg-brand-light has-[:checked]:bg-brand-light">
                    <input type="radio" name="admin_scheme" value="{{ $value }}" @checked($current === $value) required
                        aria-describedby="admin-scheme-hint-{{ $value }}"
                        class="mt-0.5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-ink">{{ $label }}{{ $value === \App\Models\User::DEFAULT_ADMIN_SCHEME ? ' (domyślny)' : '' }}</span>
                        <span id="admin-scheme-hint-{{ $value }}" class="block text-sm text-ink">{{ $hint }}</span>
                    </span>
                    <span class="flex shrink-0 overflow-hidden rounded border border-gray-400" aria-hidden="true">
                        @foreach ($swatch as $color)
                            <span class="block h-7 w-5" style="background: {{ $color }}"></span>
                        @endforeach
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>
    @error('admin_scheme') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror

    <p class="text-sm text-muted">Schemat zmienia tylko tła i linie panelu. Teksty i przyciski zostają kontrastowe. Dotyczy tylko Twojego konta.</p>

    <button type="submit"
        class="rounded-lg bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
        Zapisz schemat kolorów
    </button>
</form>
