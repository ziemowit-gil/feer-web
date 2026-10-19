{{-- Styl menu bocznego panelu (User::ADMIN_NAV_STYLES): wysuwane obok paska albo rozwijane w pasku. --}}
@php $current = old('admin_nav_style', auth()->user()->adminNavStyle()); @endphp
<form method="POST" action="{{ route('profile.nav-style') }}" class="space-y-3">
    @csrf
    @method('PATCH')

    <fieldset>
        <legend class="text-sm font-bold text-ink">Styl menu bocznego</legend>
        <div class="mt-3 space-y-2">
            @foreach (\App\Models\User::ADMIN_NAV_STYLES as $value => [$label, $hint])
                <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-gray-50 p-3 transition-colors hover:bg-brand-light has-[:checked]:bg-brand-light">
                    <input type="radio" name="admin_nav_style" value="{{ $value }}" @checked($current === $value) required
                        aria-describedby="admin-nav-style-hint-{{ $value }}"
                        class="mt-0.5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-ink">{{ $label }}{{ $value === \App\Models\User::DEFAULT_ADMIN_NAV_STYLE ? ' (domyślne)' : '' }}</span>
                        <span id="admin-nav-style-hint-{{ $value }}" class="block text-sm text-ink">{{ $hint }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>
    @error('admin_nav_style') <p class="text-sm text-red-700" role="alert">{{ $message }}</p> @enderror

    <p class="text-sm text-muted">Na telefonie menu zawsze się rozwija, a w zwężonym pasku zawsze wysuwa. Dotyczy tylko Twojego konta.</p>

    <button type="submit"
        class="rounded-lg bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
        Zapisz styl menu
    </button>
</form>
