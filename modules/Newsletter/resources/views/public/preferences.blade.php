@extends('layouts.site')

@section('title', 'Preferencje newslettera — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="mb-2 text-2xl font-bold text-ink">Twoje preferencje</h1>
        <p class="mb-6 text-muted">Adres: <strong class="text-ink">{{ $subscriber->email }}</strong> · status: <strong class="text-ink">{{ $subscriber->statusLabel() }}</strong></p>

        @if (session('status'))
            <p class="mb-6 rounded border border-green-300 bg-green-50 px-4 py-3 font-bold text-green-900" role="status">{{ session('status') }}</p>
        @endif

        <form method="post" action="{{ route('newsletter.preferences.update', ['token' => $subscriber->token]) }}" class="space-y-6">
            @csrf @method('PUT')

            <div>
                <label for="name" class="mb-1 block text-sm font-bold text-ink">Imię</label>
                <input id="name" name="name" type="text" value="{{ old('name', $subscriber->name) }}" maxlength="100" autocomplete="given-name" class="w-full rounded border-gray-500 focus:border-brand focus:ring-brand">
            </div>

            <fieldset>
                <legend class="mb-2 text-sm font-bold text-ink">Tematy</legend>
                @error('topics')<p class="mb-2 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($topics as $key => $label)
                        <label class="flex min-h-[44px] items-center gap-3 rounded border-2 border-gray-300 px-3 py-2 has-[:checked]:border-brand-dark has-[:checked]:bg-brand/10">
                            <input type="checkbox" name="topics[]" value="{{ $key }}" class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand" @checked(in_array($key, old('topics', $subscriber->topics ?? []), true))> <span class="text-ink">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            @if ($lists->isNotEmpty())
            <fieldset>
                <legend class="mb-2 text-sm font-bold text-ink">Listy specjalne</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($lists as $list)
                        <label class="flex min-h-[44px] items-center gap-3 rounded border-2 border-gray-300 px-3 py-2 has-[:checked]:border-brand-dark has-[:checked]:bg-brand/10">
                            <input type="checkbox" name="lists[]" value="{{ $list->id }}" class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand" @checked($subscriber->lists->contains($list->id))> <span class="text-ink">{{ $list->name }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @endif

            <fieldset>
                <legend class="mb-2 text-sm font-bold text-ink">Kanały</legend>
                <label class="flex min-h-[44px] items-center gap-3"><input type="checkbox" checked disabled class="h-5 w-5 rounded border-gray-500 text-brand-dark"> <span class="text-ink">E-mail (podstawowy)</span></label>
                @if ($pushEnabled)
                <label class="flex min-h-[44px] items-center gap-3"><input type="checkbox" name="channels[]" value="webpush" class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand" @checked($subscriber->hasChannel('webpush')) data-nl-push-toggle> <span class="text-ink">Powiadomienia push w tej przeglądarce</span></label>
                <p class="mt-1 text-sm text-muted" data-nl-push-status></p>
                @endif
                @if (filled($subscriber->phone))
                <label class="flex min-h-[44px] items-center gap-3"><input type="checkbox" name="channels[]" value="sms" class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand" @checked($subscriber->hasChannel('sms'))> <span class="text-ink">SMS na numer {{ substr((string) $subscriber->phone, 0, 6) }}…</span></label>
                @endif
            </fieldset>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-md bg-brand-dark px-6 py-3 font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz preferencje</button>
                <a href="{{ route('newsletter.unsubscribe', ['token' => $subscriber->token]) }}" class="rounded-md border-2 border-gray-500 px-6 py-3 font-bold text-ink hover:bg-gray-100">Wypisz mnie całkowicie</a>
            </div>
        </form>

        <div class="mt-12 rounded-lg border border-gray-300 p-5">
            <h2 class="mb-2 text-lg font-bold text-ink">Twoje dane (RODO)</h2>
            <p class="mb-4 text-sm text-muted">Możesz pobrać wszystkie dane, jakie o Tobie przechowujemy w newsletterze, albo poprosić o ich usunięcie.</p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('newsletter.preferences.export', ['token' => $subscriber->token]) }}" class="rounded-md border-2 border-brand-dark px-5 py-2.5 font-bold text-brand-dark hover:bg-brand-dark hover:text-white">Pobierz moje dane (JSON)</a>
                <form method="post" action="{{ route('newsletter.preferences.destroy', ['token' => $subscriber->token]) }}" onsubmit="return confirm('Usunąć wszystkie Twoje dane z newslettera? Tej operacji nie można cofnąć.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-md border-2 border-red-700 px-5 py-2.5 font-bold text-red-800 hover:bg-red-700 hover:text-white">Usuń moje dane</button>
                </form>
            </div>
        </div>
    </section>
    @if ($pushEnabled)
    <script>
    (function(){
      var toggle = document.querySelector('[data-nl-push-toggle]'), status = document.querySelector('[data-nl-push-status]');
      if (!toggle || !('serviceWorker' in navigator) || !('PushManager' in window)) { if (status) status.textContent = 'Ta przeglądarka nie obsługuje powiadomień push.'; return; }
      toggle.addEventListener('change', function(){
        if (!toggle.checked) return;
        navigator.serviceWorker.ready.then(function(reg){
          return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: '{{ config('webpush.vapid.public_key') }}' });
        }).then(function(sub){
          return fetch('{{ route('newsletter.preferences.push', ['token' => $subscriber->token]) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(sub.toJSON()) });
        }).then(function(){ status.textContent = 'Powiadomienia push włączone w tej przeglądarce.'; })
          .catch(function(){ status.textContent = 'Nie udało się włączyć powiadomień — sprawdź uprawnienia przeglądarki.'; toggle.checked = false; });
      });
    })();
    </script>
    @endif
@endsection
