@extends('admin.layout')

@section('title', 'Kopie zapasowe')

@section('content')
    @php
        use Illuminate\Support\Number;
        $values = $settings->all();
        $input = 'w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $btn = 'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    @endphp

    <div class="mb-6">
        <h1 class="text-lg font-bold text-ink">Kopie zapasowe</h1>
        <p class="text-sm text-muted">Bazy danych i pliki serwisu (zdjęcia, załączniki) w jednym archiwum ZIP.</p>
    </div>

    @if (session('status'))
        <div role="status" class="mb-4 rounded-md bg-brand-light p-3 text-sm text-ink">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-800">
            <i class="fa-solid fa-circle-xmark mr-1.5" aria-hidden="true"></i>
            @foreach ($errors->all() as $error) {{ $error }} @endforeach
        </div>
    @endif

    {{-- Stan --}}
    <section aria-labelledby="backup-status" class="mb-6 grid gap-4 sm:grid-cols-3">
        <h2 id="backup-status" class="sr-only">Stan kopii zapasowych</h2>
        <div class="rounded-md bg-white p-4">
            <p class="text-xs font-bold uppercase text-muted">Ostatnia kopia</p>
            @if ($backups)
                <p class="mt-1 font-bold text-ink">{{ $backups[0]['created_at']->format('d.m.Y H:i') }}</p>
                <p class="text-xs text-muted">{{ $backups[0]['kind_label'] }}, {{ Number::fileSize($backups[0]['size'], 1) }}</p>
            @else
                <p class="mt-1 font-bold text-red-700">Brak kopii</p>
            @endif
            @if ($lastResult && ! $lastResult['ok'])
                <p class="mt-2 text-xs font-bold text-red-700">
                    Ostatnia próba ({{ \Illuminate\Support\Carbon::parse($lastResult['at'])->format('d.m.Y H:i') }}) nieudana: {{ $lastResult['message'] }}
                </p>
            @endif
        </div>
        <div class="rounded-md bg-white p-4">
            <p class="text-xs font-bold uppercase text-muted">Harmonogram</p>
            <p class="mt-1 font-bold text-ink">
                {{ \App\Support\Backup\BackupSettings::SCHEDULES[$values['schedule']] ?? '—' }}@if ($values['schedule'] !== 'off'), {{ $values['time'] }}@endif
            </p>
            @if ($values['schedule'] !== 'off' && $scheduler['status'] !== 'ok')
                <p class="mt-1 text-xs font-bold text-red-700">Harmonogram nie działa: {{ $scheduler['detail'] }}</p>
            @else
                <p class="text-xs text-muted">Przechowywanych kopii: {{ $values['keep'] }}</p>
            @endif
        </div>
        <div class="rounded-md bg-white p-4">
            <p class="text-xs font-bold uppercase text-muted">Serwer zdalny</p>
            @if ($settings->remoteEnabled())
                <p class="mt-1 font-bold text-ink">{{ strtoupper($values['remote_type']) }} · {{ $values['remote_host'] }}</p>
                <form method="POST" action="{{ route('admin.kopie.test-remote') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-brand-dark underline hover:no-underline">Sprawdź połączenie</button>
                </form>
            @else
                <p class="mt-1 font-bold text-ink">Brak</p>
                <p class="text-xs text-muted">Kopie leżą tylko na tym serwerze.</p>
            @endif
            @if ($freeSpace)
                <p class="mt-1 text-xs text-muted">Wolne miejsce na dysku: {{ Number::fileSize($freeSpace, 1) }}</p>
            @endif
        </div>
    </section>

    {{-- Utwórz --}}
    <section aria-labelledby="backup-create" class="mb-6 rounded-md bg-white p-5">
        <h2 id="backup-create" class="mb-3 font-bold text-ink">Utwórz kopię teraz</h2>
        <form method="POST" action="{{ route('admin.kopie.store') }}" class="flex flex-wrap items-center gap-4"
              x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="include_files" value="1" @checked($values['include_files'])
                    class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                Z plikami (zdjęcia, załączniki)
            </label>
            @if ($settings->remoteEnabled())
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="upload" value="1" checked
                        class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                    Wyślij na serwer zdalny
                </label>
            @endif
            <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink" :disabled="busy" :aria-busy="busy">
                <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                <span x-text="busy ? 'Tworzenie kopii…' : 'Utwórz kopię'">Utwórz kopię</span>
            </button>
        </form>
        <p class="mt-3 text-xs text-muted">
            Do przywrócenia kopii na innym serwerze potrzebny jest ten sam <code>APP_KEY</code> z pliku <code>.env</code>
            (zaszyfrowane hasła i klucze w ustawieniach). Plik <code>.env</code> nie trafia do kopii — przechowuj go osobno, w bezpiecznym miejscu.
        </p>
    </section>

    {{-- Lista --}}
    <section aria-labelledby="backup-list" class="mb-6 rounded-md bg-white p-5">
        <h2 id="backup-list" class="mb-3 font-bold text-ink">Zapisane kopie</h2>

        @if (! $backups)
            <p class="text-sm text-muted">Nie ma jeszcze żadnej kopii.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Lista kopii zapasowych, od najnowszej</caption>
                    <thead class="border-b border-gray-200 text-xs uppercase text-muted">
                        <tr>
                            <th scope="col" class="py-2 pr-4">Data</th>
                            <th scope="col" class="py-2 pr-4">Rodzaj</th>
                            <th scope="col" class="py-2 pr-4">Rozmiar</th>
                            <th scope="col" class="py-2"><span class="sr-only">Akcje</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr class="border-b border-gray-100 align-top">
                                <td class="py-3 pr-4">
                                    <span class="font-bold text-ink">{{ $backup['created_at']->format('d.m.Y H:i:s') }}</span>
                                    <span class="block font-mono text-xs text-muted">{{ $backup['name'] }}</span>
                                </td>
                                <td class="py-3 pr-4">{{ $backup['kind_label'] }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap">{{ Number::fileSize($backup['size'], 1) }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap items-start justify-end gap-2">
                                        <a href="{{ route('admin.kopie.download', $backup['name']) }}"
                                           class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
                                            <i class="fa-solid fa-download" aria-hidden="true"></i> Pobierz
                                            <span class="sr-only">kopię z {{ $backup['created_at']->format('d.m.Y H:i') }}</span>
                                        </a>

                                        <details class="group">
                                            <summary class="{{ $btn }} cursor-pointer list-none bg-gray-100 text-ink hover:bg-brand-light">
                                                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Przywróć
                                                <span class="sr-only">kopię z {{ $backup['created_at']->format('d.m.Y H:i') }}</span>
                                            </summary>
                                            <form method="POST" action="{{ route('admin.kopie.restore', $backup['name']) }}"
                                                  class="mt-2 w-72 space-y-2 rounded-md bg-red-50 p-3 text-sm text-red-900"
                                                  x-data="{ busy: false }" @submit="busy = true">
                                                @csrf
                                                <p><strong>Uwaga:</strong> bieżąca treść serwisu zostanie zastąpiona stanem z kopii. Wcześniej automatycznie zapiszemy kopię obecnego stanu.</p>
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" name="restore_files" value="1" checked
                                                        class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                                    Przywróć także pliki
                                                </label>
                                                <label for="confirm-{{ $loop->index }}" class="block font-bold">Wpisz PRZYWRÓĆ, aby potwierdzić</label>
                                                <input type="text" id="confirm-{{ $loop->index }}" name="confirmation" required autocomplete="off"
                                                    pattern="PRZYWRÓĆ" class="{{ $input }}">
                                                <button type="submit" class="{{ $btn }} bg-red-700 text-white hover:bg-red-800" :disabled="busy">
                                                    <span x-text="busy ? 'Przywracanie…' : 'Przywróć kopię'">Przywróć kopię</span>
                                                </button>
                                            </form>
                                        </details>

                                        <form method="POST" action="{{ route('admin.kopie.destroy', $backup['name']) }}"
                                              onsubmit="return confirm('Usunąć kopię {{ $backup['name'] }}? Tej operacji nie można cofnąć.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="{{ $btn }} bg-gray-100 text-red-800 hover:bg-red-50">
                                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Usuń
                                                <span class="sr-only">kopię z {{ $backup['created_at']->format('d.m.Y H:i') }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.kopie.upload') }}" enctype="multipart/form-data"
              class="mt-5 border-t border-gray-100 pt-4">
            @csrf
            <label for="archive" class="mb-1 block text-sm font-bold">Wgraj kopię z pliku</label>
            <p id="archive-help" class="mb-2 text-xs text-muted">Archiwum ZIP utworzone przez weCMS, np. z innego serwera. Limit serwera: {{ $uploadLimit }} — większe kopie wgraj przez FTP do <code>storage/app/backups</code>.</p>
            <div class="flex flex-wrap items-center gap-3">
                <input type="file" id="archive" name="archive" accept=".zip,application/zip" required aria-describedby="archive-help"
                    class="block text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand-dark file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                <button type="submit" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
                    <i class="fa-solid fa-upload" aria-hidden="true"></i> Wgraj
                </button>
            </div>
        </form>
    </section>

    {{-- Ustawienia --}}
    <section aria-labelledby="backup-settings" class="rounded-md bg-white p-5">
        <h2 id="backup-settings" class="mb-3 font-bold text-ink">Harmonogram i serwer zdalny</h2>

        <form method="POST" action="{{ route('admin.kopie.settings') }}" class="space-y-5"
              x-data="{ remote: @js(old('remote_type', $values['remote_type'])) }">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="schedule" class="mb-1 block text-sm font-bold">Kopia automatyczna</label>
                    <select id="schedule" name="schedule" class="{{ $input }}">
                        @foreach (\App\Support\Backup\BackupSettings::SCHEDULES as $key => $label)
                            <option value="{{ $key }}" @selected(old('schedule', $values['schedule']) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="time" class="mb-1 block text-sm font-bold">Godzina</label>
                    <input type="time" id="time" name="time" value="{{ old('time', $values['time']) }}" required class="{{ $input }}">
                </div>
                <div>
                    <label for="keep" class="mb-1 block text-sm font-bold">Ile kopii trzymać na serwerze</label>
                    <input type="number" id="keep" name="keep" min="1" max="90" value="{{ old('keep', $values['keep']) }}" required class="{{ $input }}">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="include_files" value="1" @checked(old('include_files', $values['include_files']))
                    class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                Kopie automatyczne z plikami (zdjęcia, załączniki)
            </label>

            <p class="text-xs text-muted">
                Kopia automatyczna wymaga crona harmonogramu na serwerze:
                <code>* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run</code>.
                Stan harmonogramu: <strong class="{{ $scheduler['status'] === 'ok' ? 'text-ink' : 'text-red-700' }}">{{ $scheduler['detail'] }}</strong>.
            </p>

            <fieldset class="border-t border-gray-100 pt-5">
                <legend class="mb-2 text-sm font-bold">Serwer zdalny</legend>
                <p class="mb-3 text-xs text-muted">Kopia trzymana tylko na tym samym serwerze nie chroni przed awarią serwera. Każda nowa kopia zostanie dodatkowo wysłana tutaj.</p>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="remote_type" class="mb-1 block text-sm font-bold">Rodzaj</label>
                        <select id="remote_type" name="remote_type" x-model="remote" class="{{ $input }}">
                            @foreach (\App\Support\Backup\BackupSettings::REMOTE_TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-show="remote !== 'none'" x-cloak class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="remote_host" class="mb-1 block text-sm font-bold">Serwer</label>
                        <input type="text" id="remote_host" name="remote_host" value="{{ old('remote_host', $values['remote_host']) }}"
                            placeholder="backup.example.org" autocomplete="off" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label for="remote_port" class="mb-1 block text-sm font-bold">Port <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="number" id="remote_port" name="remote_port" min="1" max="65535" value="{{ old('remote_port', $values['remote_port']) }}"
                            :placeholder="remote === 'sftp' ? '22' : '21'" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="remote_username" class="mb-1 block text-sm font-bold">Użytkownik</label>
                        <input type="text" id="remote_username" name="remote_username" value="{{ old('remote_username', $values['remote_username']) }}"
                            autocomplete="off" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="remote_password" class="mb-1 block text-sm font-bold">Hasło</label>
                        <input type="password" id="remote_password" name="remote_password" autocomplete="new-password"
                            placeholder="{{ $settings->hasRemotePassword() ? '•••••••• (zapisane — zostaw puste)' : '' }}" class="{{ $input }}">
                        @if ($settings->hasRemotePassword())
                            <label class="mt-1 flex items-center gap-2 text-xs">
                                <input type="checkbox" name="remote_password_clear" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                Usuń zapisane hasło
                            </label>
                        @endif
                    </div>
                    <div>
                        <label for="remote_root" class="mb-1 block text-sm font-bold">Katalog</label>
                        <input type="text" id="remote_root" name="remote_root" value="{{ old('remote_root', $values['remote_root']) }}"
                            placeholder="/kopie/wecms" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label for="remote_keep" class="mb-1 block text-sm font-bold">Ile kopii trzymać zdalnie</label>
                        <input type="number" id="remote_keep" name="remote_keep" min="1" max="365" value="{{ old('remote_keep', $values['remote_keep']) }}" required class="{{ $input }}">
                    </div>
                    <label x-show="remote === 'ftp'" class="flex items-center gap-2 text-sm sm:col-span-3">
                        <input type="checkbox" name="remote_ssl" value="1" @checked(old('remote_ssl', $values['remote_ssl']))
                            class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        Szyfrowane połączenie (FTPS) — zalecane
                    </label>
                </div>
            </fieldset>

            <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">Zapisz ustawienia</button>
        </form>
    </section>
@endsection
