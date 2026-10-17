@extends('admin.layout')

@section('title', 'Baza MySQL')

{{--
    System → Baza MySQL: przeniesienie bazy głównej z SQLite do MySQL/MariaDB w trzech krokach.
    Kontrast: ink/muted na bieli i gray-50, przyciski biały na brand-dark (≥ 4,5:1); cele ≥ 44 px; błędy w role="alert".
--}}
@section('content')
    @php
        $input = 'mt-1 min-h-11 w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $label = 'block text-sm font-bold text-ink';
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded-lg px-4 text-sm font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $primary = $btn.' bg-brand-dark text-white hover:bg-ink';
        $secondary = $btn.' bg-gray-100 text-ink hover:bg-gray-200';
        $onMysql = in_array($driver, ['mysql', 'mariadb'], true);
    @endphp

    @php
        $dbRows = $onMysql ? rescue(fn () => (new \App\Support\HealthDashboard)->database()['rows'], [], false) : [];
        // Znaczki baz w kolorach obu systemów (biały tekst: SQLite #044a64 ≈ 10:1, MySQL #00618a ≈ 6,3:1) — bez kopiowania logotypów.
        $engines = [
            'sqlite' => ['name' => 'SQLite', 'bg' => '#044a64', 'stripe' => '#0f80cc', 'desc' => 'jeden plik na serwerze'],
            'mysql' => ['name' => 'MySQL', 'bg' => '#00618a', 'stripe' => '#e48e00', 'desc' => 'serwer bazy danych'],
        ];
        $active = $onMysql ? 'mysql' : 'sqlite';
    @endphp

    <div class="mb-6 max-w-3xl">
        <h1 class="text-lg font-bold text-ink">Baza MySQL</h1>
        <p class="text-sm text-ink">Przeniesienie bazy głównej z SQLite do MySQL lub MariaDB: tabele powstają z migracji, dane są kopiowane tabela po tabeli, a na końcu porównujemy liczbę wierszy. Baza bloga zostaje w osobnym pliku SQLite.</p>
    </div>

    {{-- Schemat przejścia SQLite → MySQL z oznaczeniem bieżącej bazy. --}}
    <section aria-labelledby="h-engines" class="mb-6 max-w-3xl rounded-md bg-white p-6">
        <h2 id="h-engines" class="sr-only">Bieżąca baza danych</h2>
        <div class="flex flex-wrap items-center gap-4">
            @foreach ($engines as $key => $e)
                @if (! $loop->first)
                    <span class="text-2xl text-muted" aria-hidden="true"><i class="fa-solid fa-arrow-right-long"></i></span>
                @endif
                <div class="flex min-w-[13rem] flex-1 items-center gap-3 rounded-md p-3 {{ $active === $key ? 'bg-brand-light' : 'bg-gray-50' }}">
                    <span class="relative inline-flex h-14 w-14 flex-none items-center justify-center overflow-hidden rounded-lg text-xl text-white" style="background: {{ $e['bg'] }}" aria-hidden="true">
                        <i class="fa-solid fa-database"></i>
                        <span class="absolute inset-x-0 bottom-0 h-1.5" style="background: {{ $e['stripe'] }}"></span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base font-bold text-ink">{{ $e['name'] }}</p>
                        <p class="text-sm text-ink">{{ $e['desc'] }}</p>
                        <p class="mt-0.5 text-sm font-bold {{ $active === $key ? 'text-brand-dark' : 'text-muted' }}">
                            @if ($active === $key)
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Serwis działa na tej bazie
                            @else
                                {{ $key === 'sqlite' ? 'Poprzednia baza (zapas)' : 'Cel konwersji' }}
                            @endif
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($dbRows)
            <dl class="mt-4 grid gap-x-6 gap-y-2 border-t border-gray-100 pt-4 text-sm sm:grid-cols-3">
                @foreach (array_intersect_key($dbRows, array_flip(['Baza', 'Rozmiar', 'Baza bloga (SQLite)'])) as $label => $value)
                    <div><dt class="text-muted">{{ $label }}</dt><dd class="font-bold text-ink">{{ $value }}</dd></div>
                @endforeach
            </dl>
        @endif
    </section>

    @if (session('status'))
        <div role="status" class="mb-4 max-w-3xl rounded-md bg-brand-light p-3 text-sm text-ink">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-4 max-w-3xl rounded-md bg-red-50 p-3 text-sm text-red-800">
            <i class="fa-solid fa-circle-xmark mr-1.5" aria-hidden="true"></i>
            @foreach ($errors->all() as $error) {{ $error }} @endforeach
        </div>
    @endif

    @if (! $mysqlAvailable)
        <p role="alert" class="mb-4 max-w-3xl rounded-md bg-red-50 p-3 text-sm text-red-800">Na serwerze brakuje rozszerzenia PHP <code>pdo_mysql</code> — bez niego połączenie z MySQL nie zadziała.</p>
    @endif

    @if ($onMysql)
        <section aria-labelledby="h-onmysql" class="max-w-3xl rounded-md bg-white p-6">
            <h2 id="h-onmysql" class="font-bold text-ink">Serwis działa na MySQL</h2>
            <p class="mt-1 text-sm text-ink">Konwersja nie jest już potrzebna. Kopie zapasowe (System → Kopie zapasowe) robią teraz zrzut MySQL.</p>
            @if ($envBackup && $oldSqlite)
                <form method="POST" action="{{ route('admin.baza-mysql.revert') }}" class="mt-4" onsubmit="return confirm('Wrócić do SQLite? Zmiany zapisane w MySQL po przełączeniu nie zostaną przeniesione.')">
                    @csrf
                    <button type="submit" class="{{ $secondary }}"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Wróć do SQLite</button>
                    <p class="mt-2 text-xs text-muted">Przywraca plik .env z kopii {{ basename($envBackup) }}.</p>
                </form>
            @endif
        </section>

        {{-- Poprzednia baza SQLite (zapas po przełączeniu) — usunięcie zwalnia miejsce i wyłącza „Wróć do SQLite”. --}}
        <section aria-labelledby="h-oldsqlite" class="mt-6 max-w-3xl rounded-md bg-white p-6">
            <h2 id="h-oldsqlite" class="font-bold text-ink">Poprzednia baza SQLite</h2>
            @if ($oldSqlite === [])
                <p class="mt-1 text-sm text-ink">Poprzedniej bazy już nie ma — serwis korzysta wyłącznie z MySQL.</p>
            @else
                <p class="mt-1 text-sm text-ink">Plik {{ basename($oldSqlite[0]) }} ({{ number_format(array_sum(array_map('filesize', $oldSqlite)) / 1048576, 1, ',', ' ') }} MB) został jako zapas po przełączeniu. Usunięcie zwolni miejsce, ale powrót do SQLite przestanie być możliwy.</p>
                <form method="POST" action="{{ route('admin.baza-mysql.delete-old') }}" class="mt-4 space-y-3">
                    @csrf
                    @method('DELETE')
                    <div class="flex min-h-11 items-center gap-2">
                        <input id="old-archive" name="archive" type="checkbox" value="1" checked class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" aria-describedby="old-archive-help">
                        <label for="old-archive" class="text-sm font-bold text-ink">Najpierw zapisz skompresowaną kopię</label>
                    </div>
                    <p id="old-archive-help" class="ml-7 text-xs text-muted">Do storage/app/sqlite-archive — na wszelki wypadek, zajmie mniej miejsca niż plik bazy.</p>
                    <div class="flex min-h-11 items-center gap-2">
                        <input id="old-confirm" name="confirm" type="checkbox" value="1" required class="h-5 w-5 rounded border-gray-500 text-red-700 focus:ring-red-700">
                        <label for="old-confirm" class="text-sm font-bold text-ink">Rozumiem, że „Wróć do SQLite” przestanie działać</label>
                    </div>
                    <div>
                        <label for="old-phrase" class="block text-sm font-bold text-ink">Aby potwierdzić, wpisz <span class="font-mono">USUŃ</span></label>
                        <input id="old-phrase" name="phrase" type="text" required autocomplete="off" class="{{ $input }} max-w-xs">
                    </div>
                    <button type="submit" class="{{ $btn }} bg-red-700 text-white hover:bg-red-800"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń poprzednią bazę</button>
                </form>
            @endif
        </section>
    @else
        <ol class="max-w-3xl space-y-6" role="list">
            {{-- Krok 1 --}}
            <li>
                <section aria-labelledby="h-step1" class="rounded-md bg-white p-6">
                    <h2 id="h-step1" class="font-bold text-ink">1. Połączenie z bazą MySQL</h2>
                    <p class="mt-1 text-sm text-muted">Utwórz w panelu hostingu pustą bazę i użytkownika z pełnymi uprawnieniami do niej. Dane połączenia przechowujemy zaszyfrowane przez 2 godziny — do zapisania w .env przy przełączeniu.</p>
                    <form method="POST" action="{{ route('admin.baza-mysql.check') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label for="db-host" class="{{ $label }}">Host</label>
                            <input id="db-host" name="host" type="text" required autocomplete="off" value="{{ old('host', $target['host'] ?? 'localhost') }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="db-port" class="{{ $label }}">Port</label>
                            <input id="db-port" name="port" type="number" required min="1" max="65535" inputmode="numeric" value="{{ old('port', $target['port'] ?? 3306) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="db-database" class="{{ $label }}">Nazwa bazy</label>
                            <input id="db-database" name="database" type="text" required autocomplete="off" value="{{ old('database', $target['database'] ?? '') }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="db-username" class="{{ $label }}">Użytkownik</label>
                            <input id="db-username" name="username" type="text" required autocomplete="off" value="{{ old('username', $target['username'] ?? '') }}" class="{{ $input }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="db-password" class="{{ $label }}">Hasło</label>
                            <input id="db-password" name="password" type="password" autocomplete="new-password" aria-describedby="db-password-help" class="{{ $input }}">
                            <p id="db-password-help" class="mt-1 text-xs text-muted">{{ $hasPassword ? 'Hasło jest zapamiętane — zostaw puste, aby go użyć.' : 'Hasło użytkownika bazy MySQL.' }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="{{ $primary }}"><i class="fa-solid fa-plug" aria-hidden="true"></i> Sprawdź połączenie</button>
                        </div>
                    </form>
                    @if ($check && $target)
                        <dl class="mt-4 grid gap-2 rounded-md bg-gray-50 p-4 text-sm text-ink sm:grid-cols-2">
                            <div><dt class="text-xs font-bold text-muted">Baza docelowa</dt><dd>{{ $target['database'] }} na {{ $target['host'] }} ({{ $check['target_driver'] }} {{ $check['target_version'] }})</dd></div>
                            <div><dt class="text-xs font-bold text-muted">Tabele w bazie docelowej</dt><dd>{{ $check['target_tables'] }}{{ $check['target_empty'] ? ' — pusta' : '' }}</dd></div>
                            <div><dt class="text-xs font-bold text-muted">Źródło (SQLite)</dt><dd>{{ $check['source_tables'] }} tabel, {{ number_format($check['source_rows'], 0, ',', ' ') }} wierszy</dd></div>
                        </dl>
                    @endif
                </section>
            </li>

            {{-- Krok 2 --}}
            <li>
                <section aria-labelledby="h-step2" class="rounded-md bg-white p-6">
                    <h2 id="h-step2" class="font-bold text-ink">2. Konwersja</h2>
                    <p class="mt-1 text-sm text-muted">Serwis działa dalej na SQLite. Zmiany wprowadzone w trakcie konwersji nie trafią do MySQL — najlepiej uruchom ją ponownie tuż przed przełączeniem, poza godzinami ruchu.</p>
                    <form method="POST" action="{{ route('admin.baza-mysql.convert') }}" class="mt-4">
                        @csrf
                        <div class="flex min-h-11 items-center gap-2">
                            <input id="db-fresh" name="fresh" type="checkbox" value="1" {{ ($check && ! $check['target_empty']) ? 'checked' : '' }} class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" aria-describedby="db-fresh-help">
                            <label for="db-fresh" class="text-sm font-bold text-ink">Zastąp zawartość bazy docelowej</label>
                        </div>
                        <p id="db-fresh-help" class="ml-7 text-xs text-muted">Usuwa wszystkie tabele w bazie MySQL i tworzy je od nowa (potrzebne przy powtórnej konwersji).</p>
                        <button type="submit" class="{{ $primary }} mt-4" {{ $target ? '' : 'disabled aria-disabled=true' }}><i class="fa-solid fa-right-left" aria-hidden="true"></i> Konwertuj do MySQL</button>
                        @unless ($target)<p class="mt-2 text-xs text-muted">Najpierw sprawdź połączenie (krok 1).</p>@endunless
                    </form>

                    @if ($report)
                        <h3 class="mt-6 text-sm font-bold text-ink">Raport z {{ $report['finished_at'] }} ({{ $report['seconds'] }} s) — {{ $report['ok'] ? 'wszystkie tabele zgodne' : 'są różnice' }}</h3>
                        <div class="mt-2 max-h-96 overflow-auto rounded-md bg-gray-50" tabindex="0" role="region" aria-label="Raport konwersji">
                            <table class="w-full text-left text-sm">
                                <caption class="sr-only">Liczba wierszy w SQLite i MySQL dla każdej tabeli</caption>
                                <thead class="sticky top-0 bg-gray-100 text-xs text-ink">
                                    <tr><th scope="col" class="px-3 py-2">Tabela</th><th scope="col" class="px-3 py-2 text-right">SQLite</th><th scope="col" class="px-3 py-2 text-right">MySQL</th><th scope="col" class="px-3 py-2">Wynik</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['tables'] as $row)
                                        <tr class="{{ $row['ok'] ? '' : 'bg-red-50' }}">
                                            <th scope="row" class="px-3 py-1.5 font-mono text-xs font-normal text-ink">{{ $row['table'] }}</th>
                                            <td class="px-3 py-1.5 text-right text-ink">{{ $row['source'] }}</td>
                                            <td class="px-3 py-1.5 text-right text-ink">{{ $row['target'] }}</td>
                                            <td class="px-3 py-1.5 {{ $row['ok'] ? 'text-ink' : 'font-bold text-red-800' }}">{{ $row['ok'] ? 'OK' : 'Różnica' }}@if ($row['note'])<span class="block text-xs font-normal text-muted">{{ $row['note'] }}</span>@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($report['skipped'])
                            <p class="mt-2 text-xs text-muted">Pominięte tabele (bez migracji): {{ implode(', ', $report['skipped']) }}.</p>
                        @endif
                    @endif
                </section>
            </li>

            {{-- Krok 3 --}}
            <li>
                <section aria-labelledby="h-step3" class="rounded-md bg-white p-6">
                    <h2 id="h-step3" class="font-bold text-ink">3. Przełączenie serwisu na MySQL</h2>
                    <p class="mt-1 text-sm text-muted">Zapisuje połączenie w pliku .env (z kopią poprzedniej wersji) i czyści cache konfiguracji. Plik SQLite zostaje nietknięty — w razie problemów wrócisz do niego jednym przyciskiem.</p>
                    <form method="POST" action="{{ route('admin.baza-mysql.switch') }}" class="mt-4" onsubmit="return confirm('Przełączyć serwis na MySQL?')">
                        @csrf
                        <div class="flex min-h-11 items-center gap-2">
                            <input id="db-confirm" name="confirm" type="checkbox" value="1" required class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                            <label for="db-confirm" class="text-sm font-bold text-ink">Konwersja została wykonana tuż przed przełączeniem</label>
                        </div>
                        <button type="submit" class="{{ $primary }} mt-4" {{ ($report['ok'] ?? false) && $target ? '' : 'disabled aria-disabled=true' }}><i class="fa-solid fa-database" aria-hidden="true"></i> Przełącz na MySQL</button>
                        @unless (($report['ok'] ?? false) && $target)<p class="mt-2 text-xs text-muted">Dostępne po udanej konwersji (krok 2).</p>@endunless
                    </form>
                    <p class="mt-4 text-xs text-muted">Z konsoli to samo: <code class="font-mono">php84 artisan db:konwertuj-mysql --host=… --database=… --username=… --zastap --przelacz</code>. Po przełączeniu uruchom <code class="font-mono">./deploy.sh</code>.</p>
                </section>
            </li>
        </ol>
    @endif
@endsection
